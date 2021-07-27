
function actOnModalTexts(action) {
    [LOGOUT_MODAL_ID, REBOOT_MODAL_ID, SHUTDOWN_MODAL_ID].forEach(modal_id => {
        action(document.getElementById(modal_id + MODAL_BODY_ID_TAIL));
    });
    [AP_MODAL_ID, CLIENT_MODAL_ID].forEach(modal_id => {
        var element = document.getElementById(modal_id + MODAL_BODY_TEXT_ID_TAIL);
        if (element) {
            action(element);
        }
    });
}

function showModalTexts() {
    actOnModalTexts(showElement);
}

function hideModalTexts() {
    actOnModalTexts(hideElement);
}

function updateModalTextsBasedOnFormChange(form_id) {
    if (elementHasChangedSinceCapture(form_id)) {
        showModalTexts();
    } else {
        hideModalTexts()
    }
}

function registerModalTextUpdate(form_id) {
    [LOGOUT_NAV_LINK_ID, REBOOT_NAV_LINK_ID, SHUTDOWN_NAV_LINK_ID, AP_NAV_LINK_ID, CLIENT_NAV_LINK_ID].forEach(nav_link_id => {
        $('#' + nav_link_id).click(function () {
            updateModalTextsBasedOnFormChange(form_id);
        });
    });
}

function handleModalTexts(form_id) {
    captureElementState(form_id);
    updateModalTextsBasedOnFormChange(form_id);
    $('#' + form_id).submit(function () {
        captureElementState(form_id);
        return true;
    });
    registerModalTextUpdate(form_id)
}
