#!/bin/bash
#
# update_server.sh — Atualiza uma instalação existente do OpenBabyMonitor
# preservando todas as configurações do usuário.
#
# O que é PRESERVADO:
#   - Banco de dados MySQL inteiro (configurações de notificação, áudio, vídeo,
#     sistema, redes wifi conhecidas, senha do site, histórico de eventos,
#     inscrições de push). O banco NUNCA é recriado/apagado.
#   - Senhas do banco em config/config.json (reinjetadas após o git pull).
#   - config/setup_config.env (hostname, fuso horário, país, canal, etc.).
#   - Chaves VAPID (config/vapid_*.key), certificados SSL, pasta env/, id do
#     microfone — todos ignorados pelo git e nunca tocados.
#
# O que é ATUALIZADO:
#   - Código (git pull do branch atual).
#   - Dependências Python (requirements.txt).
#   - Bibliotecas front-end novas (ex.: Chart.js).
#   - Documentação renderizada (readme.html).
#   - Esquema do banco via migração NÃO-destrutiva (novas tabelas apenas).
#   - Permissões dos arquivos.
#
# Antes de qualquer alteração é criado um backup completo (dump do banco +
# arquivos de configuração + chaves) em backups/.
#
# Uso:
#   ./update_server.sh                # atualiza o branch atual
#   ./update_server.sh --branch main  # atualiza um branch específico
#   ./update_server.sh --reboot       # reinicia ao final (recomendado)
#   ./update_server.sh --no-backup    # pula a etapa de backup (não recomendado)

set -e

BM_DIR=$(dirname $(readlink -f $0))
source $BM_DIR/config/setup_config.env

# ---------------------------------------------------------------------------
# Opções
# ---------------------------------------------------------------------------
DO_BACKUP=true
DO_REBOOT=false
UPDATE_BRANCH=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --no-backup) DO_BACKUP=false; shift ;;
        --reboot) DO_REBOOT=true; shift ;;
        --branch) UPDATE_BRANCH="$2"; shift 2 ;;
        -h|--help)
            grep '^#' "$0" | sed 's/^# \{0,1\}//'
            exit 0 ;;
        *) echo "Opção desconhecida: $1" >&2; exit 1 ;;
    esac
done

# ---------------------------------------------------------------------------
# Verificações iniciais
# ---------------------------------------------------------------------------
if [[ "$(whoami)" != "$BM_USER" ]]; then
    echo "Erro: este script deve ser executado pelo usuário $BM_USER" >&2
    exit 1
fi

if ! git -C "$BM_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Erro: $BM_DIR não é um repositório git; não é possível atualizar." >&2
    exit 1
fi

cd "$BM_DIR"

BM_WEB_GROUP=www-data
BM_READ_PERMISSIONS=750
BM_WRITE_PERMISSIONS=770
BM_LINKED_SITE_DIR=$BM_DIR/site/public
BM_CONFIG_FILE=$BM_DIR/config/config.json
BM_SETUP_CONFIG_FILE=$BM_DIR/config/setup_config.env
BM_VAPID_PRIVATE_KEY=$BM_DIR/config/vapid_private.key
BM_ENV_FILE=$BM_DIR/env/envvars

log() { echo -e "\n\033[1;32m==>\033[0m \033[1m$1\033[0m"; }
warn() { echo -e "\033[1;33m[aviso]\033[0m $1" >&2; }

# Executa um comando de rede com até 4 tentativas (backoff 2s,4s,8s,16s)
retry_net() {
    local n=0 delay=2
    until "$@"; do
        n=$((n + 1))
        if [[ $n -ge 5 ]]; then
            return 1
        fi
        warn "Falha de rede; nova tentativa em ${delay}s..."
        sleep $delay
        delay=$((delay * 2))
    done
    return 0
}

# Lê um campo de config.json com o python
read_config() {
    python3 -c "import json,sys; print(json.load(open('$BM_CONFIG_FILE'))$1)" 2>/dev/null
}

# ---------------------------------------------------------------------------
# 1. Backup
# ---------------------------------------------------------------------------
if [[ "$DO_BACKUP" = true ]]; then
    log "Criando backup das configurações e do banco de dados"
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_DIR=$BM_DIR/backups/bm_backup_$TIMESTAMP
    mkdir -p "$BACKUP_DIR"

    # Arquivos de configuração e chaves (o que existir)
    for f in "$BM_CONFIG_FILE" "$BM_SETUP_CONFIG_FILE" \
             "$BM_DIR/config/vapid_private.key" "$BM_DIR/config/vapid_public.key"; do
        [[ -f "$f" ]] && cp -a "$f" "$BACKUP_DIR/"
    done

    # Dump do banco de dados
    DB_NAME=$(read_config "['database']['name']")
    DB_ROOT_USER=$(read_config "['database']['root_account']['user']")
    DB_ROOT_PW=$(read_config "['database']['root_account']['password']")
    if [[ -n "$DB_NAME" ]]; then
        DUMP_FILE="$BACKUP_DIR/${DB_NAME}.sql"
        if sudo mysqldump --user=root --databases "$DB_NAME" > "$DUMP_FILE" 2>/dev/null; then
            echo "  Banco '$DB_NAME' exportado (via socket root)."
        elif mysqldump -u "$DB_ROOT_USER" -p"$DB_ROOT_PW" --databases "$DB_NAME" > "$DUMP_FILE" 2>/dev/null; then
            echo "  Banco '$DB_NAME' exportado (via senha root)."
        else
            rm -f "$DUMP_FILE"
            warn "Não foi possível exportar o banco de dados. Continuando mesmo assim."
        fi
    fi

    # Compacta o backup
    tar -czf "$BACKUP_DIR.tar.gz" -C "$(dirname "$BACKUP_DIR")" "$(basename "$BACKUP_DIR")" 2>/dev/null && \
        rm -rf "$BACKUP_DIR" && BACKUP_DIR="$BACKUP_DIR.tar.gz"
    echo "  Backup salvo em: $BACKUP_DIR"
fi

# ---------------------------------------------------------------------------
# 2. Preserva os segredos do usuário antes de mexer no git
# ---------------------------------------------------------------------------
log "Preservando configurações específicas do usuário"

# Cópia temporária dos arquivos versionados que o usuário editou
PRESERVE_DIR=$(mktemp -d)
cp -a "$BM_CONFIG_FILE" "$PRESERVE_DIR/config.json"
cp -a "$BM_SETUP_CONFIG_FILE" "$PRESERVE_DIR/setup_config.env"

# ---------------------------------------------------------------------------
# 3. Atualiza o código via git
# ---------------------------------------------------------------------------
if [[ -z "$UPDATE_BRANCH" ]]; then
    UPDATE_BRANCH=$(git rev-parse --abbrev-ref HEAD)
fi
log "Atualizando o código (branch: $UPDATE_BRANCH)"

# Guarda quaisquer alterações locais (inclui config.json/setup_config.env)
STASHED=false
if ! git diff --quiet || ! git diff --cached --quiet; then
    git stash push -u -m "update_server.sh $TIMESTAMP" >/dev/null 2>&1 && STASHED=true
    [[ "$STASHED" = true ]] && echo "  Alterações locais guardadas no git stash (rede de segurança extra)."
fi

retry_net git fetch origin "$UPDATE_BRANCH" || { warn "Falha ao buscar do origin."; }

if git merge --ff-only "origin/$UPDATE_BRANCH" >/dev/null 2>&1; then
    echo "  Código atualizado para origin/$UPDATE_BRANCH."
else
    warn "Não foi possível fazer fast-forward (o branch local divergiu do remoto)."
    warn "Resolva manualmente com 'git pull' e rode este script de novo."
    # Restaura o estado guardado para não deixar a instalação quebrada
    [[ "$STASHED" = true ]] && git stash pop >/dev/null 2>&1 || true
    rm -rf "$PRESERVE_DIR"
    exit 1
fi

# ---------------------------------------------------------------------------
# 4. Reinjeta os segredos do usuário no código atualizado
# ---------------------------------------------------------------------------
log "Restaurando configurações do usuário no código atualizado"

# setup_config.env: as variáveis são do usuário; restauramos por completo.
cp -a "$PRESERVE_DIR/setup_config.env" "$BM_SETUP_CONFIG_FILE"
echo "  setup_config.env restaurado."

# config.json: mantemos a NOVA estrutura (pode ter configurações novas) mas
# reinjetamos as senhas do banco que pertencem ao usuário.
python3 - "$PRESERVE_DIR/config.json" "$BM_CONFIG_FILE" <<'PYEOF'
import json, sys
old_path, new_path = sys.argv[1], sys.argv[2]
with open(old_path) as f: old = json.load(f)
with open(new_path) as f: new = json.load(f)

# Caminhos de segredos a preservar do config.json antigo
secret_paths = [
    ['database', 'root_account', 'password'],
    ['database', 'root_account', 'user'],
    ['database', 'account', 'password'],
    ['database', 'account', 'user'],
    ['database', 'name'],
]

def get(d, path):
    for k in path:
        if not isinstance(d, dict) or k not in d:
            return (False, None)
        d = d[k]
    return (True, d)

def setp(d, path, val):
    for k in path[:-1]:
        d = d.setdefault(k, {})
    d[path[-1]] = val

changed = 0
for p in secret_paths:
    ok, val = get(old, p)
    if ok:
        setp(new, p, val)
        changed += 1

with open(new_path, 'w') as f:
    json.dump(new, f, indent=2)
    f.write('\n')
print(f"  config.json: {changed} valor(es) do usuario reinjetado(s), estrutura nova preservada.")
PYEOF

rm -rf "$PRESERVE_DIR"

# ---------------------------------------------------------------------------
# 5. Dependências Python
# ---------------------------------------------------------------------------
log "Atualizando dependências Python"
pip3 install --no-cache-dir -r "$BM_DIR/requirements.txt" || \
    warn "Falha ao instalar dependências Python (veja a saída acima)."

# ---------------------------------------------------------------------------
# 6. Chaves VAPID (gera se ainda não existirem)
# ---------------------------------------------------------------------------
if [[ ! -f "$BM_VAPID_PRIVATE_KEY" ]] && [[ -f "$BM_DIR/control/generate_vapid_keys.py" ]]; then
    log "Gerando chaves VAPID para notificações Web Push"
    BM_DIR="$BM_DIR" python3 "$BM_DIR/control/generate_vapid_keys.py" || \
        warn "Falha ao gerar chaves VAPID."
fi

# ---------------------------------------------------------------------------
# 7. Bibliotecas front-end novas
# ---------------------------------------------------------------------------
# Chart.js é necessário para o painel de histórico; baixa se estiver faltando.
CHARTJS_FILE=$BM_LINKED_SITE_DIR/js/chart.umd.min.js
if [[ ! -f "$CHARTJS_FILE" ]]; then
    log "Baixando Chart.js (necessário para o painel de histórico)"
    CHARTJS_VERSION=4.4.0
    retry_net wget -q -O "$CHARTJS_FILE" \
        "https://cdn.jsdelivr.net/npm/chart.js@${CHARTJS_VERSION}/dist/chart.umd.min.js" || \
        warn "Não foi possível baixar o Chart.js (sem internet?). O painel de histórico não funcionará até baixá-lo."
fi

# ---------------------------------------------------------------------------
# 8. Renderiza a documentação
# ---------------------------------------------------------------------------
if command -v pandoc >/dev/null 2>&1 && [[ -d "$BM_LINKED_SITE_DIR/docs" ]]; then
    log "Atualizando documentação renderizada"
    pandoc -f gfm -t html -o "$BM_LINKED_SITE_DIR/docs/readme.html" "$BM_DIR/README.md" || \
        warn "Falha ao renderizar o README."
fi

# ---------------------------------------------------------------------------
# 9. Migração NÃO-destrutiva do banco de dados
# ---------------------------------------------------------------------------
log "Aplicando migrações do banco de dados (não-destrutivo)"
if [[ -f "$BM_ENV_FILE" ]]; then
    sudo php "$BM_DIR/site/config/init/migrate_database.php" || \
        warn "A migração do banco retornou um erro; verifique a saída acima."
else
    warn "env/envvars não encontrado — pulando a migração. Rode o setup completo se esta for uma instalação nova."
fi

# ---------------------------------------------------------------------------
# 10. Permissões
# ---------------------------------------------------------------------------
log "Corrigindo permissões dos arquivos"
sudo chmod -R $BM_READ_PERMISSIONS "$BM_DIR"
sudo chown -R $BM_USER:$BM_WEB_GROUP "$BM_DIR"

# Pastas onde o grupo web precisa de permissão de escrita
for d in "$BM_DIR/site/servercontrol/.hook" "$BM_DIR/control/.lock" \
         "$BM_DIR/control/.comm" "$BM_DIR/control/.mic" "$BM_DIR/control/.cam"; do
    [[ -d "$d" ]] && sudo chmod $BM_WRITE_PERMISSIONS "$d"
done

# Arquivos de sinal das services
for MODE in standby listen audiostream videostream; do
    SIG="$BM_DIR/control/.comm/signal.$MODE"
    if [[ -e "$SIG" ]]; then
        sudo chmod $BM_WRITE_PERMISSIONS "$SIG"
        sudo chown $BM_USER:$BM_WEB_GROUP "$SIG"
    fi
done

# Configuração do phpsysinfo (gravável pelo grupo web)
PHPSYSINFO_INI=$BM_LINKED_SITE_DIR/library/phpsysinfo/phpsysinfo.ini
if [[ -f "$PHPSYSINFO_INI" ]]; then
    sudo chmod $BM_WRITE_PERMISSIONS "$PHPSYSINFO_INI" "$(dirname "$PHPSYSINFO_INI")"
fi

# Protege a chave privada VAPID (o chmod recursivo acima a deixou exposta)
[[ -f "$BM_VAPID_PRIVATE_KEY" ]] && chmod 600 "$BM_VAPID_PRIVATE_KEY"

# ---------------------------------------------------------------------------
# 11. Reinicia serviços
# ---------------------------------------------------------------------------
log "Reiniciando serviços"
sudo systemctl daemon-reload || true
sudo systemctl restart apache2 || warn "Falha ao reiniciar o Apache."

log "Atualização concluída com sucesso!"
if [[ "$DO_BACKUP" = true ]]; then
    echo "Backup disponível em: $BACKUP_DIR"
fi

if [[ "$DO_REBOOT" = true ]]; then
    log "Reiniciando o sistema em 5 segundos (Ctrl+C para cancelar)..."
    sleep 5
    sudo reboot
else
    echo -e "\nRecomendado: reinicie o dispositivo para garantir que todos os serviços"
    echo "rodem com a versão nova:  sudo reboot"
fi
