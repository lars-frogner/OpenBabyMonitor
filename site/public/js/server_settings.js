const DISABLED_BUTTON_CLASS = 'btn btn-secondary';
const BUTTON_CLASSES = {};
BUTTON_CLASSES[CONNECT_BUTTON_ID] = 'btn btn-primary';
BUTTON_CLASSES[DISCONNECT_BUTTON_ID] = 'btn btn-warning';
BUTTON_CLASSES[FORGET_BUTTON_ID] = 'btn btn-danger';

$(function () {
    connectModalToLink(CONNECT_BUTTON_ID, { header: 'Er du sikker på at du vil koble til nettverket?', confirm: 'Koble til', dismiss: 'Avbryt', confirmOnclick: () => { performPrePostActions(); $('#' + CONNECT_SUBMIT_BUTTON_ID).click(); } }, { text: 'Den nåværende tilkoblingen vil bli avbrutt. Du vil logges ut og miste forbindelsen med enheten til du kobler deg til det nye nettverket.', showText: () => { return true; } });
    connectModalToLink(DISCONNECT_BUTTON_ID, { header: 'Er du sikker på at du vil koble fra nettverket?', confirm: 'Koble fra', dismiss: 'Avbryt', confirmClass: 'btn btn-warning', confirmOnclick: () => { $('#' + DISCONNECT_SUBMIT_BUTTON_ID)[0].click(); } }, { text: 'Den nåværende tilkoblingen vil bli avbrutt. Du vil logges ut og miste forbindelsen med enheten til du kobler deg til enhetens tilgangspunkt.', showText: () => { return true; } });
    connectModalToLink(FORGET_BUTTON_ID, { header: 'Er du sikker på at du vil glemme nettverket?', confirm: 'Glem', dismiss: 'Avbryt', confirmClass: 'btn btn-danger', confirmOnclick: () => { performPrePostActions(); $('#' + FORGET_SUBMIT_BUTTON_ID).click(); } }, null);
});

function disableButton(button) {
    button.prop('disabled', true);
    button.removeClass();
    button.addClass(DISABLED_BUTTON_CLASS);
}

function enableButton(button) {
    button.removeClass();
    button.addClass(BUTTON_CLASSES[button.prop('id')]);
    button.prop('disabled', false);
}

function disablePasswordInput() {
    var input = $('#' + PASSWORD_INPUT_ID);
    input.prop('value', '');
    input.prop('disabled', true);
}

function enablePasswordInput() {
    var input = $('#' + PASSWORD_INPUT_ID);
    input.prop('disabled', false);
}

function selectNoNetwork() {
    disablePasswordInput();
    $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
    disableButton($('#' + CONNECT_BUTTON_ID));
    disableButton($('#' + DISCONNECT_BUTTON_ID));
    disableButton($('#' + FORGET_BUTTON_ID));
}

function selectAvailableNetwork(networkMeta) {
    enablePasswordInput();
    disableButton($('#' + DISCONNECT_BUTTON_ID));
    disableButton($('#' + FORGET_BUTTON_ID));
    if (networkMeta.requiresPassword && !networkMeta.isKnown) {
        enablePasswordInput();
        disableButton($('#' + CONNECT_BUTTON_ID));
    } else {
        disablePasswordInput();
        enableButton($('#' + CONNECT_BUTTON_ID));
    }
    if (networkMeta.isKnown) {
        $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
    } else {
        $('#' + REMEMBER_CHECK_ID).prop('disabled', false);
    }
}

function selectConnectedNetwork() {
    disablePasswordInput();
    $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
    disableButton($('#' + CONNECT_BUTTON_ID));
    enableButton($('#' + DISCONNECT_BUTTON_ID));
    disableButton($('#' + FORGET_BUTTON_ID));
}

function selectKnownNetwork() {
    disablePasswordInput();
    $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
    disableButton($('#' + CONNECT_BUTTON_ID));
    disableButton($('#' + DISCONNECT_BUTTON_ID));
    enableButton($('#' + FORGET_BUTTON_ID));
}

function performPrePostActions() {
    setDisabledForNavbar(true);
    $('#' + SETTINGS_FORM_CONTAINER_ID).find('*').hide();
    $('#' + SWITCHING_INFO_ID).show();
    $('#' + MODAL_ID).modal('hide');
}

$('#' + AVAILABLE_NETWORKS_SELECT_ID).change(function () {
    $('#' + KNOWN_NETWORKS_SELECT_ID).prop('value', '');
    const networkMeta = $('#' + this.value).data('networkMeta');
    if (networkMeta.isConnected) {
        selectConnectedNetwork();
    } else {
        selectAvailableNetwork(networkMeta);
    }
});

$('#' + KNOWN_NETWORKS_SELECT_ID).change(function () {
    $('#' + AVAILABLE_NETWORKS_SELECT_ID).prop('value', '');
    selectKnownNetwork();
});

$('#' + PASSWORD_INPUT_ID).on('input', function () {
    if (this.value.length < 8 || this.value.length > 63) {
        disableButton($('#' + CONNECT_BUTTON_ID));
    } else {
        enableButton($('#' + CONNECT_BUTTON_ID));
    }
});
