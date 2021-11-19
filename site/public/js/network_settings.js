const MAIN_CONTAINER_ID = 'main_container';
const SETTINGS_FORM_CONTAINER_ID = 'network_settings_form_container';
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
const SITE_PASSWORD_INPUT_ID = 'site_password_input';
const CHANGE_SITE_PASSWORD_SUBMIT_BUTTON_ID = 'change_site_password_submit_button';
const CHANGE_SITE_PASSWORD_BUTTON_ID = 'change_site_password_button';
const AP_PASSWORD_INPUT_ID = 'ap_password_input';
const CHANGE_AP_PASSWORD_SUBMIT_BUTTON_ID = 'change_ap_password_submit_button';
const CHANGE_AP_PASSWORD_BUTTON_ID = 'change_ap_password_button';

const DISABLED_BUTTON_CLASS = 'btn btn-secondary';
const BUTTON_CLASSES = {};
BUTTON_CLASSES[CONNECT_BUTTON_ID] = 'btn btn-success';
BUTTON_CLASSES[DISCONNECT_BUTTON_ID] = 'btn btn-danger';
BUTTON_CLASSES[FORGET_BUTTON_ID] = 'btn btn-warning';
BUTTON_CLASSES[CHANGE_SITE_PASSWORD_BUTTON_ID] = 'btn btn-primary';
BUTTON_CLASSES[CHANGE_AP_PASSWORD_BUTTON_ID] = 'btn btn-primary';

const ICON_UNSELECTED_BACKGROUND_COLOR = 'DimGray';
const ICON_SELECTED_BACKGROUND_COLOR = 'ForestGreen';
const ICON_CONNECTED_UNSELECTED_BACKGROUND_COLOR = 'DodgerBlue';
const ICON_CONNECTED_SELECTED_BACKGROUND_COLOR = 'Crimson';

$(function () {
    generateNetworkSelectStyle();
    setupAvailableNetworksSelect();
    setupKnownNetworksSelect();

    connectModalToLink(CONNECT_BUTTON_ID, { header: LANG['sure_want_to_connect'], confirm: LANG['connect'], dismiss: LANG['cancel'], confirmClass: BUTTON_CLASSES[CONNECT_BUTTON_ID], confirmOnclick: () => { performPreSwitchingPostActions(); enabled($('#' + CONNECT_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_new_network'], showText: () => { return true; } });
    connectModalToLink(DISCONNECT_BUTTON_ID, { header: LANG['sure_want_to_disconnect'], confirm: LANG['disconnect'], dismiss: LANG['cancel'], confirmClass: BUTTON_CLASSES[DISCONNECT_BUTTON_ID], confirmOnclick: () => { performPreSwitchingPostActions(); enabled($('#' + DISCONNECT_SUBMIT_BUTTON_ID))[0].click(); } }, { text: LANG['will_be_disconnected'] + ' ' + LANG['until_access_point'], showText: () => { return true; } });
    connectModalToLink(FORGET_BUTTON_ID, { header: LANG['sure_want_to_forget'], confirm: LANG['forget'], dismiss: LANG['cancel'], confirmClass: BUTTON_CLASSES[FORGET_BUTTON_ID], confirmOnclick: () => { enabled($('#' + FORGET_SUBMIT_BUTTON_ID)).click(); } }, null);
    connectModalToLink(CHANGE_SITE_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_site_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_SITE_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
    connectModalToLink(CHANGE_AP_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_ap_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_AP_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
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

function selectNetwork(networkMeta) {
    if (networkMeta.isConnected) {
        disableButton($('#' + CONNECT_BUTTON_ID));
        disablePasswordInput();
        $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
        enableButton($('#' + DISCONNECT_BUTTON_ID));
    } else {
        disableButton($('#' + DISCONNECT_BUTTON_ID));
        if (networkMeta.isAvailable && networkMeta.requiresPassword) {
            disableButton($('#' + CONNECT_BUTTON_ID));
            enablePasswordInput(true);
        } else {
            disablePasswordInput();
            enableButton($('#' + CONNECT_BUTTON_ID));
        }
        $('#' + REMEMBER_CHECK_ID).prop('disabled', false);
    }
    if (networkMeta.isKnown) {
        disablePasswordInput();
        $('#' + REMEMBER_CHECK_ID).prop('disabled', true);
        enableButton($('#' + FORGET_BUTTON_ID));
    } else {
        disableButton($('#' + FORGET_BUTTON_ID));
    }
}

function performPreSwitchingPostActions() {
    setDisabledForNavbar(true);
    $('#' + SETTINGS_FORM_CONTAINER_ID).find('*').hide();
    $('.' + NETWORK_STATUS_MSG_CLASS).hide();
    $('#' + SWITCHING_INFO_ID).show();
    $('#' + MODAL_ID).modal('hide');
}

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
