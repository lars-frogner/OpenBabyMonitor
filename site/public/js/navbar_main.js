const LOGOUT_MODAL_WARNING_TEXT = 'Enheten står ikke i standby. Den vil fortsette i nåværende modus selv om du logger ut.';

$(function () {
    connectLinkToModal(LOGOUT_NAV_LINK_ID, LOGOUT_MODAL_BASE_PROPERTIES, { text: LOGOUT_MODAL_WARNING_TEXT, showText: () => { return getCurrentMode() != STANDBY_MODE; } });
    connectLinkToModal(REBOOT_NAV_LINK_ID, REBOOT_MODAL_BASE_PROPERTIES, null);
    connectLinkToModal(SHUTDOWN_NAV_LINK_ID, SHUTDOWN_MODAL_BASE_PROPERTIES, null);
    connectLinkToModal(AP_NAV_LINK_ID, AP_MODAL_BASE_PROPERTIES, AP_MODAL_BASE_BODY_SETTER);
    connectLinkToModal(CLIENT_NAV_LINK_ID, CLIENT_MODAL_BASE_PROPERTIES, CLIENT_MODAL_BASE_BODY_SETTER);
});
