const MODAL_ID = 'confirmation_modal';
const MODAL_HEADER_ID = MODAL_ID + '_header';
const MODAL_BODY_ID = MODAL_ID + '_body';
const MODAL_CONFIRM_ID = MODAL_ID + '_confirm';
const MODAL_DISMISS_ID = MODAL_ID + '_dismiss';

function setConfirmationModalProperties(properties) {
    if (properties.hasOwnProperty('header')) {
        $('#' + MODAL_HEADER_ID).html(properties['header']);
    }
    if (properties.hasOwnProperty('body')) {
        var body = $('#' + MODAL_BODY_ID);
        var content = properties['body'];
        if (!content || (Array.isArray(content) && content.length == 0)) {
            body.hide()
        } else {
            body.empty();
            body.show();
            body.append(content);
        }
    } else {
        body.hide();
    }
    if (properties.hasOwnProperty('confirm')) {
        $('#' + MODAL_CONFIRM_ID).html(properties['confirm']);
    }
    if (properties.hasOwnProperty('href')) {
        $('#' + MODAL_CONFIRM_ID).prop('href', properties['href']);
    }
    if (properties.hasOwnProperty('dismiss')) {
        $('#' + MODAL_DISMISS_ID).html(properties['dismiss']);
    }
}
