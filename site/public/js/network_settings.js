const MAIN_CONTAINER_ID = 'main_container';
const SETTINGS_FORM_CONTAINER_ID = 'network_settings_form_container';
const SWITCHING_INFO_ID = 'switching_network_info';
const AVAILABLE_NETWORKS_SELECT_ID = 'available_networks';
const KNOWN_NETWORKS_SELECT_ID = 'known_networks';
const NETWORK_PASSWORD_INPUT_ID = 'network_password_input';
const REMEMBER_CHECK_ID = 'remember_check';
const CONNECT_SUBMIT_BUTTON_ID = 'connect_submit_button';
const CONNECT_BUTTON_ID = 'connect_button';
const DISCONNECT_SUBMIT_BUTTON_ID = 'disconnect_submit_button';
const DISCONNECT_BUTTON_ID = 'disconnect_button';
const FORGET_SUBMIT_BUTTON_ID = 'forget_submit_button';
const FORGET_BUTTON_ID = 'forget_button';
const SITE_PASSWORD_INPUT_ID = 'site_password_input';
const CHANGE_SITE_PASSWORD_SUBMIT_BUTTON_ID = 'change_site_password_submit_button';
const CHANGE_SITE_PASSWORD_BUTTON_ID = 'change_site_password_button';
const AP_PASSWORD_INPUT_ID = 'ap_password_input';
const CHANGE_AP_PASSWORD_SUBMIT_BUTTON_ID = 'change_ap_password_submit_button';
const CHANGE_AP_PASSWORD_BUTTON_ID = 'change_ap_password_button';

const DISABLED_BUTTON_CLASS = 'btn btn-secondary';
const BUTTON_CLASSES = {};
BUTTON_CLASSES[CONNECT_BUTTON_ID] = 'btn btn-primary';
BUTTON_CLASSES[DISCONNECT_BUTTON_ID] = 'btn btn-warning';
BUTTON_CLASSES[FORGET_BUTTON_ID] = 'btn btn-danger';
BUTTON_CLASSES[CHANGE_SITE_PASSWORD_BUTTON_ID] = 'btn btn-primary';
BUTTON_CLASSES[CHANGE_AP_PASSWORD_BUTTON_ID] = 'btn btn-primary';

$(function () {
    connectModalToLink(CONNECT_BUTTON_ID, { header: LANG['sure_want_to_connect'], confirm: LANG['connect'], dismiss: LANG['cancel'], confirmOnclick: () => { performPrePostActions(); enabled($('#' + CONNECT_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_new_network'], showText: () => { return true; } });
    connectModalToLink(DISCONNECT_BUTTON_ID, { header: LANG['sure_want_to_disconnect'], confirm: LANG['disconnect'], dismiss: LANG['cancel'], confirmClass: 'btn btn-warning', confirmOnclick: () => { enabled($('#' + DISCONNECT_SUBMIT_BUTTON_ID))[0].click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_access_point'], showText: () => { return true; } });
    connectModalToLink(FORGET_BUTTON_ID, { header: LANG['sure_want_to_forget'], confirm: LANG['forget'], dismiss: LANG['cancel'], confirmClass: 'btn btn-danger', confirmOnclick: () => { performPrePostActions(); enabled($('#' + FORGET_SUBMIT_BUTTON_ID)).click(); } }, null);
    connectModalToLink(CHANGE_SITE_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_site_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_SITE_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
    connectModalToLink(CHANGE_AP_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_ap_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_AP_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
    $('#' + MAIN_CONTAINER_ID).show();
});

function enabled(button) {
    button.prop('disabled', false);
    return button;
}

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
    var input = $('#' + NETWORK_PASSWORD_INPUT_ID);
    input.prop('value', '');
    input.prop('disabled', true);
}

function enablePasswordInput(focus) {
    var input = $('#' + NETWORK_PASSWORD_INPUT_ID);
    input.prop('disabled', false);
    if (focus) {
        input.focus();
    }
}

function selectNoNetwork() {
    disablePasswordInput();
    $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
    disableButton($('#' + CONNECT_BUTTON_ID));
    disableButton($('#' + DISCONNECT_BUTTON_ID));
    disableButton($('#' + FORGET_BUTTON_ID));
}

function selectAvailableNetwork(networkMeta) {
    disableButton($('#' + DISCONNECT_BUTTON_ID));
    disableButton($('#' + FORGET_BUTTON_ID));
    if (networkMeta.requiresPassword && !networkMeta.isKnown) {
        enablePasswordInput(true);
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

$('#' + NETWORK_PASSWORD_INPUT_ID).on('input', function () {
    if (this.value.length < 8 || this.value.length > 63) {
        disableButton($('#' + CONNECT_BUTTON_ID));
    } else {
        enableButton($('#' + CONNECT_BUTTON_ID));
    }
});

$('#' + SITE_PASSWORD_INPUT_ID).on('input', function () {
    if (this.value.length < 4) {
        disableButton($('#' + CHANGE_SITE_PASSWORD_BUTTON_ID));
    } else {
        enableButton($('#' + CHANGE_SITE_PASSWORD_BUTTON_ID));
    }
});

$('#' + AP_PASSWORD_INPUT_ID).on('input', function () {
    if (this.value.length < 8 || this.value.length > 63) {
        disableButton($('#' + CHANGE_AP_PASSWORD_BUTTON_ID));
    } else {
        enableButton($('#' + CHANGE_AP_PASSWORD_BUTTON_ID));
    }
});
