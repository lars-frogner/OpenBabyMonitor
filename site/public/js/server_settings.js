const MAIN_CONTAINER_ID = 'main_container';
const SETTINGS_FORM_CONTAINER_ID = 'server_settings_form_container';
const SWITCHING_INFO_ID = 'switching_network_info';
const AVAILABLE_NETWORKS_SELECT_ID = 'available_networks';
const KNOWN_NETWORKS_SELECT_ID = 'known_networks';
const PASSWORD_INPUT_ID = 'password_input';
const REMEMBER_CHECK_ID = 'remember_check';
const CONNECT_SUBMIT_BUTTON_ID = 'connect_submit_button';
const CONNECT_BUTTON_ID = 'connect_button';
const DISCONNECT_SUBMIT_BUTTON_ID = 'disconnect_submit_button';
const DISCONNECT_BUTTON_ID = 'disconnect_button';
const FORGET_SUBMIT_BUTTON_ID = 'forget_submit_button';
const FORGET_BUTTON_ID = 'forget_button';

const DISABLED_BUTTON_CLASS = 'btn btn-secondary';
const BUTTON_CLASSES = {};
BUTTON_CLASSES[CONNECT_BUTTON_ID] = 'btn btn-primary';
BUTTON_CLASSES[DISCONNECT_BUTTON_ID] = 'btn btn-warning';
BUTTON_CLASSES[FORGET_BUTTON_ID] = 'btn btn-danger';

$(function () {
    connectModalToLink(CONNECT_BUTTON_ID, { header: LANG['sure_want_to_connect'], confirm: LANG['connect'], dismiss: LANG['cancel'], confirmOnclick: () => { performPrePostActions(); $('#' + CONNECT_SUBMIT_BUTTON_ID).click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_new_network'], showText: () => { return true; } });
    connectModalToLink(DISCONNECT_BUTTON_ID, { header: LANG['sure_want_to_disconnect'], confirm: LANG['disconnect'], dismiss: LANG['cancel'], confirmClass: 'btn btn-warning', confirmOnclick: () => { $('#' + DISCONNECT_SUBMIT_BUTTON_ID)[0].click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_access_point'], showText: () => { return true; } });
    connectModalToLink(FORGET_BUTTON_ID, { header: LANG['sure_want_to_forget'], confirm: LANG['forget'], dismiss: LANG['cancel'], confirmClass: 'btn btn-danger', confirmOnclick: () => { performPrePostActions(); $('#' + FORGET_SUBMIT_BUTTON_ID).click(); } }, null);
    $('#' + MAIN_CONTAINER_ID).show();
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
    input.focus();
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
