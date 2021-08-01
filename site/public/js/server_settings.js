function selectNoNetwork() {
    $('#' + PASSWORD_INPUT_ID).prop('disabled', true);
    $('#' + CONNECT_BUTTON_ID).prop('disabled', true);
    $('#' + DISCONNECT_BUTTON_ID).prop('disabled', true);
    $('#' + FORGET_BUTTON_ID).prop('disabled', true);
}

function selectAvailableNetwork() {
    $('#' + PASSWORD_INPUT_ID).prop('disabled', false);
    $('#' + CONNECT_BUTTON_ID).prop('disabled', false);
    $('#' + DISCONNECT_BUTTON_ID).prop('disabled', true);
    $('#' + FORGET_BUTTON_ID).prop('disabled', true);
}

function selectConnectedNetwork() {
    $('#' + PASSWORD_INPUT_ID).prop('disabled', true);
    $('#' + CONNECT_BUTTON_ID).prop('disabled', true);
    $('#' + DISCONNECT_BUTTON_ID).prop('disabled', false);
    $('#' + FORGET_BUTTON_ID).prop('disabled', true);
}

function selectKnownNetwork() {
    $('#' + PASSWORD_INPUT_ID).prop('disabled', true);
    $('#' + CONNECT_BUTTON_ID).prop('disabled', true);
    $('#' + DISCONNECT_BUTTON_ID).prop('disabled', true);
    $('#' + FORGET_BUTTON_ID).prop('disabled', false);
}

$('#' + AVAILABLE_NETWORKS_SELECT_ID).change(function () {
    console.log('av ' + this.value);
    $('#' + KNOWN_NETWORKS_SELECT_ID).prop('value', '');
    switch (this.value) {
        case '1':
            selectConnectedNetwork();
            break;
        default:
            selectAvailableNetwork()
            break;
    }
});

$('#' + KNOWN_NETWORKS_SELECT_ID).change(function () {
    console.log('kn ' + this.value);
    $('#' + AVAILABLE_NETWORKS_SELECT_ID).prop('value', '');
    selectKnownNetwork();
});

$(function () {
    connectLinkToModal(CONNECT_BUTTON_ID, { header: 'Er du sikker på at du vil koble til nettverket?', confirm: 'Koble til', dismiss: 'Avbryt', confirm_onclick: () => { $('#' + CONNECT_SUBMIT_BUTTON_ID).click(); } }, { text: 'Den nåværende tilkoblingen vil bli avbrutt. Du vil logges ut og miste forbindelsen med enheten til du kobler deg til det nye nettverket.', showText: () => { return true; } });
    connectLinkToModal(DISCONNECT_BUTTON_ID, { header: 'Er du sikker på at du vil koble fra nettverket?', confirm: 'Koble fra', dismiss: 'Avbryt', confirm_class: 'btn btn-warning', confirm_onclick: () => { $('#' + DISCONNECT_SUBMIT_BUTTON_ID).click(); } }, { text: 'Den nåværende tilkoblingen vil bli avbrutt. Du vil logges ut og miste forbindelsen med enheten til du kobler deg til enhetens tilgangspunkt.', showText: () => { return true; } });
    connectLinkToModal(FORGET_BUTTON_ID, { header: 'Er du sikker på at du vil glemme nettverket?', confirm: 'Glem', dismiss: 'Avbryt', confirm_class: 'btn btn-danger', confirm_onclick: () => { $('#' + FORGET_SUBMIT_BUTTON_ID).click(); } }, null);
    selectNoNetwork();
});
