const settingsFormHasChanged = () => { return DETECT_FORM_CHANGES && elementHasChangedSinceCapture(SETTINGS_FORM_ID); };
const MODAL_ADDITIONAL_SETTER = { text: LANG['nav_unsaved_settings'], showText: settingsFormHasChanged };

$(function () {
    if (DETECT_FORM_CHANGES) {
        $('#' + SETTINGS_FORM_ID).submit(function () {
            captureElementState(SETTINGS_FORM_ID);
            return true;
        });
    }
    connectModalToLink(TITLE_NAV_LINK_ID, Object.assign({}, MODES_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectModalToLink(MODES_NAV_LINK_ID, Object.assign({}, MODES_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectModalToLink(LISTEN_SETTINGS_NAV_LINK_ID, Object.assign({}, LISTEN_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectModalToLink(AUDIOSTREAM_SETTINGS_NAV_LINK_ID, Object.assign({}, AUDIOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    if (USES_CAMERA) {
        connectModalToLink(VIDEOSTREAM_SETTINGS_NAV_LINK_ID, Object.assign({}, VIDEOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    }
    connectModalToLink(NETWORK_SETTINGS_NAV_LINK_ID, Object.assign({}, NETWORK_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectModalToLink(LOGOUT_NAV_LINK_ID, LOGOUT_MODAL_BASE_PROPERTIES, [{ text: LANG['nav_device_not_in_standby'], showText: () => { return INITIAL_MODE != STANDBY_MODE; } }, MODAL_ADDITIONAL_SETTER]);
    connectModalToLink(REBOOT_NAV_LINK_ID, REBOOT_MODAL_BASE_PROPERTIES, MODAL_ADDITIONAL_SETTER);
    connectModalToLink(SHUTDOWN_NAV_LINK_ID, SHUTDOWN_MODAL_BASE_PROPERTIES, MODAL_ADDITIONAL_SETTER);
    connectModalToObject(_AP_MODE_MODAL_TRIGGER, AP_MODAL_BASE_PROPERTIES, [AP_MODAL_BASE_BODY_SETTER, MODAL_ADDITIONAL_SETTER]);
    connectModalToObject(_CLIENT_MODE_MODAL_TRIGGER, CLIENT_MODAL_BASE_PROPERTIES, [CLIENT_MODAL_BASE_BODY_SETTER, MODAL_ADDITIONAL_SETTER]);
    setDisabledForNavbar(false);
});
