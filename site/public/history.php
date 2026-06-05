<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

require_once(TEMPLATES_DIR . '/main.php');
?>

<!DOCTYPE html>
<html>

<head>
  <?php require_once(TEMPLATES_DIR . '/head_common.php'); ?>
  <style>
    .event-badge-cry    { background-color: #dc3545; color: #fff; }
    .event-badge-babble { background-color: #198754; color: #fff; }
    .event-badge-sound  { background-color: #fd7e14; color: #fff; }
    .event-badge-other  { background-color: #6c757d; color: #fff; }
    .stat-card { min-width: 8rem; }
    #history_chart_container { position: relative; height: 280px; }
    #dist_chart_container    { position: relative; height: 220px; }
  </style>
</head>

<body>
  <div class="d-flex flex-column" style="min-height: 100vh;">
    <header>
      <?php
      require_once(TEMPLATES_DIR . '/navbar.php');
      require_once(TEMPLATES_DIR . '/confirmation_modal.php');
      ?>
    </header>

    <main class="flex-grow-1 py-4">
      <div class="container">

        <div class="d-flex align-items-center justify-content-between mb-4">
          <h4 class="mb-0 text-bm"><?php echo LANG['history_title']; ?></h4>
          <div class="d-flex align-items-center gap-2">
            <label class="text-bm mb-0 me-1"><?php echo LANG['history_filter_days']; ?></label>
            <select id="days_filter" class="form-select form-select-sm" style="width:auto;" onchange="loadHistory()">
              <option value="1">1 <?php echo LANG['history_days']; ?></option>
              <option value="3">3 <?php echo LANG['history_days']; ?></option>
              <option value="7" selected>7 <?php echo LANG['history_days']; ?></option>
              <option value="30">30 <?php echo LANG['history_days']; ?></option>
            </select>
            <button id="clear_btn" class="btn btn-sm btn-outline-danger" onclick="confirmClearHistory()">
              <?php echo LANG['history_clear']; ?>
            </button>
          </div>
        </div>

        <!-- Summary cards -->
        <div class="row g-3 mb-4" id="stats_row">
          <div class="col-auto">
            <div class="card stat-card text-center p-3">
              <div class="fw-bold fs-2 text-danger" id="stat_cry">—</div>
              <div class="small text-bm"><?php echo LANG['history_crying']; ?></div>
            </div>
          </div>
          <div class="col-auto">
            <div class="card stat-card text-center p-3">
              <div class="fw-bold fs-2 text-success" id="stat_babble">—</div>
              <div class="small text-bm"><?php echo LANG['history_babbling']; ?></div>
            </div>
          </div>
          <div class="col-auto">
            <div class="card stat-card text-center p-3">
              <div class="fw-bold fs-2 text-warning" id="stat_sound">—</div>
              <div class="small text-bm"><?php echo LANG['history_sound']; ?></div>
            </div>
          </div>
          <div class="col-auto">
            <div class="card stat-card text-center p-3">
              <div class="fw-bold fs-2 text-bm" id="stat_total">—</div>
              <div class="small text-bm"><?php echo LANG['history_events_today']; ?></div>
            </div>
          </div>
        </div>

        <!-- Charts row -->
        <div class="row g-4 mb-4">
          <div class="col-md-8">
            <div class="card p-3">
              <div class="fw-bold text-bm mb-2"><?php echo LANG['history_timeline']; ?></div>
              <div id="history_chart_container">
                <canvas id="history_chart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card p-3">
              <div class="fw-bold text-bm mb-2"><?php echo LANG['history_distribution']; ?></div>
              <div id="dist_chart_container">
                <canvas id="dist_chart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Event list -->
        <div class="card">
          <div class="card-body p-0">
            <div id="event_list">
              <div class="text-center py-5 text-bm">
                <span class="spinner-border spinner-border-sm me-2"></span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <?php
  require_once(TEMPLATES_DIR . '/bootstrap_js.php');
  require_once(TEMPLATES_DIR . '/jquery_js.php');
  require_once(TEMPLATES_DIR . '/js-cookie_js.php');
  ?>

  <!-- Chart.js — bundled locally so it works without internet (AP mode) -->
  <script src="js/chart.umd.min.js" onerror="console.warn('Chart.js not available');"></script>

  <script>
    const LANG_NO_EVENTS    = <?php echo json_encode(LANG['history_no_events']); ?>;
    const LANG_CRYING       = <?php echo json_encode(LANG['history_crying']); ?>;
    const LANG_BABBLING     = <?php echo json_encode(LANG['history_babbling']); ?>;
    const LANG_SOUND        = <?php echo json_encode(LANG['history_sound']); ?>;
    const LANG_SURE_CLEAR   = <?php echo json_encode(LANG['history_sure_clear']); ?>;
    const LANG_CANCEL       = <?php echo json_encode(LANG['cancel']); ?>;
    const LANG_CLEAR        = <?php echo json_encode(LANG['history_clear']); ?>;

    const CHARTJS_AVAILABLE = (typeof Chart !== 'undefined');
    let _historyChart = null;
    let _distChart    = null;

    function getColorSchemeNow() {
        const override = (typeof Cookies !== 'undefined') ? Cookies.get('color_scheme_override') : null;
        if (override === 'dark' || override === 'light') return override;
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }

    const isDark   = getColorSchemeNow() === 'dark';
    const gridCol  = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';
    const textCol  = isDark ? '#e0e0e0' : '#333';
    const cardCol  = isDark ? '#2a2a2a' : '#fff';

    Chart.defaults.color = textCol;

    function loadHistory() {
        const days = document.getElementById('days_filter').value;
        fetch('api/history_data.php?days=' + days)
            .then(r => r.json())
            .then(data => {
                renderStats(data.stats);
                renderTimeline(data.timeline, days);
                renderDistribution(data.distribution);
                renderList(data.events);
            })
            .catch(() => {
                document.getElementById('event_list').innerHTML =
                    '<div class="text-center py-4 text-danger">Error loading data</div>';
            });
    }

    function renderStats(stats) {
        document.getElementById('stat_cry').textContent    = stats.cry    ?? 0;
        document.getElementById('stat_babble').textContent = stats.babble ?? 0;
        document.getElementById('stat_sound').textContent  = stats.sound  ?? 0;
        document.getElementById('stat_total').textContent  = stats.total  ?? 0;
    }

    function renderTimeline(timeline, days) {
        if (!CHARTJS_AVAILABLE) { document.getElementById('history_chart_container').innerHTML = '<div class="text-muted small p-2">Chart.js not installed yet — run setup_server.sh</div>'; return; }
        const labels = timeline.map(d => d.label);
        const cryData    = timeline.map(d => d.cry    ?? 0);
        const babbleData = timeline.map(d => d.babble ?? 0);
        const soundData  = timeline.map(d => d.sound  ?? 0);

        if (_historyChart) _historyChart.destroy();
        const ctx = document.getElementById('history_chart').getContext('2d');
        _historyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: LANG_CRYING,  data: cryData,    backgroundColor: 'rgba(220,53,69,0.75)',  borderRadius: 3 },
                    { label: LANG_BABBLING,data: babbleData, backgroundColor: 'rgba(25,135,84,0.75)',  borderRadius: 3 },
                    { label: LANG_SOUND,   data: soundData,  backgroundColor: 'rgba(253,126,20,0.75)', borderRadius: 3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                scales: {
                    x: { stacked: true, grid: { color: gridCol }, ticks: { maxRotation: 45 } },
                    y: { stacked: true, grid: { color: gridCol }, ticks: { stepSize: 1, precision: 0 } }
                }
            }
        });
    }

    function renderDistribution(dist) {
        if (!CHARTJS_AVAILABLE) { document.getElementById('dist_chart_container').innerHTML = ''; return; }
        if (_distChart) _distChart.destroy();
        const ctx = document.getElementById('dist_chart').getContext('2d');
        _distChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [LANG_CRYING, LANG_BABBLING, LANG_SOUND],
                datasets: [{
                    data: [dist.cry ?? 0, dist.babble ?? 0, dist.sound ?? 0],
                    backgroundColor: ['rgba(220,53,69,0.8)', 'rgba(25,135,84,0.8)', 'rgba(253,126,20,0.8)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
            }
        });
    }

    function badgeClass(type) {
        if (type === 'cry' || type === 'bad_and_good' || type === 'bad_or_good') return 'event-badge-cry';
        if (type === 'babble') return 'event-badge-babble';
        if (type === 'sound')  return 'event-badge-sound';
        return 'event-badge-other';
    }

    function badgeLabel(type) {
        if (type === 'cry' || type === 'bad_and_good' || type === 'bad_or_good') return LANG_CRYING;
        if (type === 'babble') return LANG_BABBLING;
        if (type === 'sound')  return LANG_SOUND;
        return type;
    }

    function formatDuration(seconds) {
        if (!seconds || seconds < 1) return '';
        if (seconds < 60) return seconds + 's';
        return Math.round(seconds / 60) + 'm ' + (seconds % 60) + 's';
    }

    function renderList(events) {
        const el = document.getElementById('event_list');
        if (!events || events.length === 0) {
            el.innerHTML = '<div class="text-center py-5 text-bm">' + LANG_NO_EVENTS + '</div>';
            return;
        }
        let html = '<ul class="list-group list-group-flush">';
        events.forEach(function(ev) {
            const badge  = badgeClass(ev.type);
            const label  = badgeLabel(ev.type);
            const dur    = formatDuration(ev.duration);
            const conf   = ev.confidence ? (parseFloat(ev.confidence) * 100).toFixed(0) + '%' : '';
            html += '<li class="list-group-item d-flex align-items-center py-2 px-3">'
                  + '<span class="badge rounded-pill ' + badge + ' me-3" style="min-width:5rem;">' + label + '</span>'
                  + '<span class="text-bm flex-grow-1">' + ev.started_at + '</span>'
                  + (dur  ? '<span class="text-muted small me-3">' + dur  + '</span>' : '')
                  + (conf ? '<span class="text-muted small">'       + conf + '</span>' : '')
                  + '</li>';
        });
        html += '</ul>';
        el.innerHTML = html;
    }

    function confirmClearHistory() {
        if (confirm(LANG_SURE_CLEAR)) {
            fetch('api/history_data.php', { method: 'DELETE' })
                .then(() => loadHistory());
        }
    }

    $(function() { loadHistory(); });
  </script>

  <script src="js/style.js"></script>
  <script src="js/navbar.js"></script>
</body>

</html>
