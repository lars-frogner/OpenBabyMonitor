const SETTINGS_MODAL_WARNING_TEXT = 'Merk at du har endringer i innstillingene som ikke er lagret.';
const settingsFormHasChanged = () => { return DETECT_FORM_CHANGES && elementHasChangedSinceCapture(SETTINGS_FORM_ID); };
const MODAL_ADDITIONAL_SETTER = { text: SETTINGS_MODAL_WARNING_TEXT, showText: settingsFormHasChanged };
const LOGOUT_MODAL_WARNING_TEXT = 'Enheten står ikke i standby. Den vil fortsette i nåværende modus selv om du logger ut.';

$(function () {
    if (DETECT_FORM_CHANGES) {
        $('#' + SETTINGS_FORM_ID).submit(function () {
            captureElementState(SETTINGS_FORM_ID);
            return true;
        });
    }
    connectLinkToModal(MODES_NAV_LINK_ID, Object.assign({}, MODES_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(LISTEN_SETTINGS_NAV_LINK_ID, Object.assign({}, LISTEN_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(AUDIOSTREAM_SETTINGS_NAV_LINK_ID, Object.assign({}, AUDIOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(VIDEOSTREAM_SETTINGS_NAV_LINK_ID, Object.assign({}, VIDEOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(SERVER_SETTINGS_NAV_LINK_ID, Object.assign({}, SERVER_SETTINGS_MODAL_BASE_PROPERTIES, { showModal: settingsFormHasChanged }), MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(LOGOUT_NAV_LINK_ID, LOGOUT_MODAL_BASE_PROPERTIES, [{ text: LOGOUT_MODAL_WARNING_TEXT, showText: () => { return INITIAL_MODE != STANDBY_MODE; } }, MODAL_ADDITIONAL_SETTER]);
    connectLinkToModal(REBOOT_NAV_LINK_ID, REBOOT_MODAL_BASE_PROPERTIES, MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(SHUTDOWN_NAV_LINK_ID, SHUTDOWN_MODAL_BASE_PROPERTIES, MODAL_ADDITIONAL_SETTER);
    connectLinkToModal(AP_NAV_LINK_ID, AP_MODAL_BASE_PROPERTIES, [AP_MODAL_BASE_BODY_SETTER, MODAL_ADDITIONAL_SETTER]);
    connectLinkToModal(CLIENT_NAV_LINK_ID, CLIENT_MODAL_BASE_PROPERTIES, [CLIENT_MODAL_BASE_BODY_SETTER, MODAL_ADDITIONAL_SETTER]);
    setDisabledForNavbar(false);
});
