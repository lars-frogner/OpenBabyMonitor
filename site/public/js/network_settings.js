const MAIN_CONTAINER_ID = 'main_container';
const SETTINGS_FORM_CONTAINER_CLASS = 'network-settings-form-container';
const SWITCHING_INFO_ID = 'switching_network_info';
const NETWORK_STATUS_MSG_CLASS = 'network-status-msg';
const NETWORK_PASSWORD_INPUT_ID = 'network_password_input';
const REMEMBER_CHECK_ID = 'remember_check';
const CONNECT_SUBMIT_BUTTON_ID = 'connect_submit_button';
const CONNECT_BUTTON_ID = 'connect_button';
const DISCONNECT_SUBMIT_BUTTON_ID = 'disconnect_submit_button';
const DISCONNECT_BUTTON_ID = 'disconnect_button';
const FORGET_SUBMIT_BUTTON_ID = 'forget_submit_button';
const FORGET_BUTTON_ID = 'forget_button';
const AP_CHANNEL_RANGE_ID = 'ap_channel_range';
const AP_CHANNEL_RANGE_OUTPUT_ID = 'ap_channel_range_output';
const CHANGE_AP_CHANNEL_SUBMIT_BUTTON_ID = 'change_ap_channel_submit_button';
const CHANGE_AP_CHANNEL_BUTTON_ID = 'change_ap_channel_button';
const AP_SSID_INPUT_ID = 'ap_ssid_input';
const AP_SSID_REQUIRES_PASSWORD_MSG_ID = 'ap_ssid_requires_password_msg';
const AP_PASSWORD_INPUT_ID = 'ap_password_input';
const CHANGE_AP_SSID_PASSWORD_SUBMIT_BUTTON_ID = 'change_ap_ssid_password_submit_button';
const CHANGE_AP_SSID_PASSWORD_BUTTON_ID = 'change_ap_ssid_password_button';
const SITE_PASSWORD_INPUT_ID = 'site_password_input';
const CHANGE_SITE_PASSWORD_SUBMIT_BUTTON_ID = 'change_site_password_submit_button';
const CHANGE_SITE_PASSWORD_BUTTON_ID = 'change_site_password_button';
const COUNTRY_CODE_SELECT_ID = 'country_code_select';
const CHANGE_COUNTRY_CODE_SUBMIT_BUTTON_ID = 'change_country_code_submit_button';
const CHANGE_COUNTRY_CODE_BUTTON_ID = 'change_country_code_button';

const DISABLED_BUTTON_CLASS = 'btn btn-secondary';
const NETWORK_BUTTON_CLASSES = {};
NETWORK_BUTTON_CLASSES[CONNECT_BUTTON_ID] = 'btn btn-success';
NETWORK_BUTTON_CLASSES[DISCONNECT_BUTTON_ID] = 'btn btn-danger';
NETWORK_BUTTON_CLASSES[FORGET_BUTTON_ID] = 'btn btn-warning';

const ICON_UNSELECTED_BACKGROUND_COLOR = 'DimGray';
const ICON_SELECTED_BACKGROUND_COLOR = 'ForestGreen';
const ICON_CONNECTED_UNSELECTED_BACKGROUND_COLOR = 'DodgerBlue';
const ICON_CONNECTED_SELECTED_BACKGROUND_COLOR = 'Crimson';

$(function () {
    generateNetworkSelectStyle();
    setupAvailableNetworksSelect();
    setupKnownNetworksSelect();

    connectModalToLink(CONNECT_BUTTON_ID, { header: LANG['sure_want_to_connect'], confirm: LANG['connect'], dismiss: LANG['cancel'], confirmClass: NETWORK_BUTTON_CLASSES[CONNECT_BUTTON_ID], confirmOnclick: () => { performPreSwitchingPostActions(); enabled($('#' + CONNECT_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_new_network'], showText: () => { return true; } });
    connectModalToLink(DISCONNECT_BUTTON_ID, { header: LANG['sure_want_to_disconnect'], confirm: LANG['disconnect'], dismiss: LANG['cancel'], confirmClass: NETWORK_BUTTON_CLASSES[DISCONNECT_BUTTON_ID], confirmOnclick: () => { performPreSwitchingPostActions(); enabled($('#' + DISCONNECT_SUBMIT_BUTTON_ID))[0].click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_access_point'], showText: () => { return true; } });
    connectModalToLink(FORGET_BUTTON_ID, { header: LANG['sure_want_to_forget'], confirm: LANG['forget'], dismiss: LANG['cancel'], confirmClass: NETWORK_BUTTON_CLASSES[FORGET_BUTTON_ID], confirmOnclick: () => { enabled($('#' + FORGET_SUBMIT_BUTTON_ID)).click(); } }, null);
    connectModalToLink(CHANGE_AP_CHANNEL_BUTTON_ID, { header: LANG['sure_want_to_change_ap_channel'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_AP_CHANNEL_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['must_reboot_for_changes'], showText: () => { return ACCESS_POINT_ACTIVE; } });
    connectModalToLink(CHANGE_AP_SSID_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_ap_ssid_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_AP_SSID_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['must_reboot_for_changes'], showText: () => { return ACCESS_POINT_ACTIVE; } });
    connectModalToLink(CHANGE_SITE_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_site_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_SITE_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
    connectModalToLink(CHANGE_COUNTRY_CODE_BUTTON_ID, { header: LANG['sure_want_to_change_country_code'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_COUNTRY_CODE_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['must_reboot_for_changes'], showText: () => { return true; } });
    $('#' + MAIN_CONTAINER_ID).show();
});

function generateNetworkSelectStyle() {
    $('<style>.network-option { color: ' + FOREGROUND_COLOR + '; background-color: ' + BACKGROUND_COLOR + '; }</style>').appendTo('head');
    if (getColorScheme() == 'dark') {
        $('<style>.network-option:hover { filter: brightness(130%); } .network-option-selected { filter: brightness(130%); } .network-icon { color: ' + FOREGROUND_COLOR + '; } .network-icon-selected { filter: brightness(70%); } </style>').appendTo('head');
    } else {
        $('<style>.network-option:hover { filter: brightness(90%); } .network-option-selected { filter: brightness(90%); } .network-icon { color: ' + BACKGROUND_COLOR + '; } .network-icon-selected { filter: brightness(110%); } </style>').appendTo('head');
    }
}

function setupAvailableNetworksSelect() {
    const radios = getAvailableNetworksRadioButtons();

    radios.each(function () {
        const clickedRadio = this;
        const clickedSSID = clickedRadio.value;
        const clickedId = NETWORK_INFO[clickedSSID].id;
        setIconToUnselected(clickedSSID);
        const option = getAvailableNetworksSelectOption(clickedId);
        option.click(function () {
            const selectedRadios = getSelectedAvailableNetworksRadioButton().get();
            selectedRadios.forEach(selectedRadio => {
                unselectAvailableNetworksOption(selectedRadio);
            });
            selectAvailableNetworksOptionAndNetwork(clickedRadio);
        });
    });
}

function setupKnownNetworksSelect() {
    const radios = getKnownNetworksRadioButtons();
    radios.each(function () {
        const clickedRadio = this;
        const clickedSSID = clickedRadio.value;
        const clickedId = NETWORK_INFO[clickedSSID].id;
        const option = getKnownNetworksSelectOption(clickedId);
        option.click(function () {
            const selectedRadios = getSelectedKnownNetworksRadioButton().get();
            selectedRadios.forEach(selectedRadio => {
                unselectKnownNetworksOption(selectedRadio);
            });
            selectKnownNetworksOptionAndNetwork(clickedRadio);
        });
    });
}

function updateSelectedAvailableNetworksOptions(clickedId) {
    const selectedRadios = getSelectedAvailableNetworksRadioButton().get();
    selectedRadios.forEach(selectedRadio => {
        unselectAvailableNetworksOption(selectedRadio);
    });
    if (clickedId) {
        const clickedRadio = getAvailableNetworksRadioButton(clickedId).get()[0];
        selectAvailableNetworksOption(clickedRadio);
    }
}

function updateSelectedKnownNetworksOptions(clickedId) {
    const selectedRadios = getSelectedKnownNetworksRadioButton().get();
    selectedRadios.forEach(selectedRadio => {
        unselectKnownNetworksOption(selectedRadio);
    });
    if (clickedId) {
        const clickedRadio = getKnownNetworksRadioButton(clickedId).get()[0];
        selectKnownNetworksOption(clickedRadio);
    }
}

function getAvailableNetworksRadioButtons() {
    return $('[id^=available_radio_]');
}

function getSelectedAvailableNetworksRadioButton() {
    return $('[id^=available_radio_]:checked');
}

function getAvailableNetworksSelectOptions() {
    return $('[id^=available_option_]');
}

function getAvailableNetworksRadioButton(id) {
    return $('#available_radio_' + id);
}

function getAvailableNetworksSelectOption(id) {
    return $('#available_option_' + id);
}

function getAvailableNetworksIcon(id) {
    return $('#available_icon_' + id);
}

function getKnownNetworksRadioButtons() {
    return $('[id^=known_radio_]');
}

function getSelectedKnownNetworksRadioButton() {
    return $('[id^=known_radio_]:checked');
}

function getKnownNetworksSelectOptions() {
    return $('[id^=known_option_]');
}

function getKnownNetworksRadioButton(id) {
    return $('#known_radio_' + id);
}

function getKnownNetworksSelectOption(id) {
    return $('#known_option_' + id);
}

function selectAvailableNetworksOptionAndNetwork(radio) {
    const networkMeta = NETWORK_INFO[radio.value];
    selectAvailableNetworksOption(radio);
    if (networkMeta.isKnown) {
        updateSelectedKnownNetworksOptions(networkMeta.id);
    } else {
        updateSelectedKnownNetworksOptions(null);
    }
    selectNetwork(networkMeta);
}

function selectKnownNetworksOptionAndNetwork(radio) {
    const networkMeta = NETWORK_INFO[radio.value];
    selectKnownNetworksOption(radio);
    if (networkMeta.isAvailable) {
        updateSelectedAvailableNetworksOptions(networkMeta.id);
    } else {
        updateSelectedAvailableNetworksOptions(null);
    }
    selectNetwork(networkMeta);
}

function selectAvailableNetworksOption(radio) {
    const option = getAvailableNetworksSelectOption(NETWORK_INFO[radio.value].id);
    option.addClass('network-option-selected');
    setIconToSelected(radio.value);
    setChecked(radio, true);
}


function selectKnownNetworksOption(radio) {
    const option = getKnownNetworksSelectOption(NETWORK_INFO[radio.value].id);
    option.addClass('network-option-selected');
    setChecked(radio, true);
}

function unselectAvailableNetworksOption(radio) {
    const option = getAvailableNetworksSelectOption(NETWORK_INFO[radio.value].id);
    option.removeClass('network-option-selected');
    setIconToUnselected(radio.value);
    setChecked(radio, false);
}

function unselectKnownNetworksOption(radio) {
    const option = getKnownNetworksSelectOption(NETWORK_INFO[radio.value].id);
    option.removeClass('network-option-selected');
    setChecked(radio, false);
}

function setIconToSelected(ssid) {
    const networkMeta = NETWORK_INFO[ssid];
    const icon = getAvailableNetworksIcon(networkMeta.id);
    icon.addClass('network-icon-selected');
    icon.css({ 'background-color': networkMeta.isConnected ? ICON_CONNECTED_SELECTED_BACKGROUND_COLOR : ICON_SELECTED_BACKGROUND_COLOR });
}

function setIconToUnselected(ssid) {
    const networkMeta = NETWORK_INFO[ssid];
    const icon = getAvailableNetworksIcon(networkMeta.id);
    icon.removeClass('network-icon-selected');
    icon.css({ 'background-color': networkMeta.isConnected ? ICON_CONNECTED_UNSELECTED_BACKGROUND_COLOR : ICON_UNSELECTED_BACKGROUND_COLOR });
}


function setChecked(radio, checked) {
    radio.checked = checked;
}

function enabled(button) {
    button.prop('disabled', false);
    return button;
}

function disableNetworkButton(button) {
    button.prop('disabled', true);
    button.removeClass();
    button.addClass(DISABLED_BUTTON_CLASS);
}

function enableNetworkButton(button) {
    button.removeClass();
    button.addClass(NETWORK_BUTTON_CLASSES[button.prop('id')]);
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

function selectNetwork(networkMeta) {
    if (networkMeta.isConnected) {
        disableNetworkButton($('#' + CONNECT_BUTTON_ID));
        disablePasswordInput();
        $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
        enableNetworkButton($('#' + DISCONNECT_BUTTON_ID));
    } else {
        disableNetworkButton($('#' + DISCONNECT_BUTTON_ID));
        if (networkMeta.isAvailable && networkMeta.requiresPassword && !networkMeta.isKnown) {
            disableNetworkButton($('#' + CONNECT_BUTTON_ID));
            enablePasswordInput(true);
        } else {
            disablePasswordInput();
            enableNetworkButton($('#' + CONNECT_BUTTON_ID));
        }
        $('#' + REMEMBER_CHECK_ID).prop('disabled', false);
    }
    if (networkMeta.isKnown) {
        disablePasswordInput();
        $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
        enableNetworkButton($('#' + FORGET_BUTTON_ID));
    } else {
        disableNetworkButton($('#' + FORGET_BUTTON_ID));
    }
}

function performPreSwitchingPostActions() {
    setDisabledForNavbar(true);
    $('.' + SETTINGS_FORM_CONTAINER_CLASS).hide();
    $('.' + NETWORK_STATUS_MSG_CLASS).hide();
    $('#' + SWITCHING_INFO_ID).show();
    $('#' + MODAL_ID).modal('hide');
}

function networkSSIDIsValid(ssid) {
    return ssid.length > 0 && ssid.length <= 32;
}

function networkPasswordIsValid(password) {
    return password.length >= 8 && password.length < 64;
}

function handleAPSSIDOrPasswordInput(ssid, password) {
    const SSIDDifferent = ssid != AP_SSID;
    const SSIDdValid = networkSSIDIsValid(ssid);
    const passwordValid = networkPasswordIsValid(password);
    $('#' + CHANGE_AP_SSID_PASSWORD_BUTTON_ID).prop('disabled', !passwordValid || !SSIDdValid);
    if (passwordValid || !SSIDDifferent) {
        $('#' + AP_SSID_REQUIRES_PASSWORD_MSG_ID).hide();
    } else {
        $('#' + AP_SSID_REQUIRES_PASSWORD_MSG_ID).show();
    }
}

$('#' + NETWORK_PASSWORD_INPUT_ID).on('input', function () {
    if (networkPasswordIsValid(this.value)) {
        enableButton($('#' + CONNECT_BUTTON_ID));
    } else {
        disableButton($('#' + CONNECT_BUTTON_ID));
    }
});

$('#' + AP_CHANNEL_RANGE_ID).on('input', function () {
    $('#' + AP_CHANNEL_RANGE_OUTPUT_ID).prop('value', this.value);
    $('#' + CHANGE_AP_CHANNEL_BUTTON_ID).prop('disabled', this.value == AP_CHANNEL);
});

$('#' + AP_SSID_INPUT_ID).on('input', function () {
    handleAPSSIDOrPasswordInput(this.value, $('#' + AP_PASSWORD_INPUT_ID).val());
});

$('#' + AP_PASSWORD_INPUT_ID).on('input', function () {
    handleAPSSIDOrPasswordInput($('#' + AP_SSID_INPUT_ID).val(), this.value);
});

$('#' + SITE_PASSWORD_INPUT_ID).on('input', function () {
    $('#' + CHANGE_SITE_PASSWORD_BUTTON_ID).prop('disabled', this.value.length < 4);
});

$('#' + COUNTRY_CODE_SELECT_ID).on('change', function () {
    $('#' + CHANGE_COUNTRY_CODE_BUTTON_ID).prop('disabled', this.value == COUNTRY_CODE);
});
