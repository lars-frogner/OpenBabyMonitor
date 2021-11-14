const MODAL_ID = 'confirmation_modal';
const MODAL_ICON_ID = MODAL_ID + '_icon';
const MODAL_HEADER_ID = MODAL_ID + '_header';
const MODAL_BODY_ID = MODAL_ID + '_body';
const MODAL_CONFIRM_CHECKBOX_ID = MODAL_ID + '_checkbox';
const MODAL_CONFIRM_CHECKBOX_LABEL_ID = MODAL_ID + '_checkbox_label';
const MODAL_CONFIRM_LINK_ID = MODAL_ID + '_confirm_link';
const MODAL_CONFIRM_BUTTON_ID = MODAL_ID + '_confirm_button';
const MODAL_DISMISS_ID = MODAL_ID + '_dismiss';

function setConfirmationModalProperties(properties) {
    if (properties.hasOwnProperty('icon')) {
        $('#' + MODAL_ICON_ID).attr('href', 'media/bootstrap-icons.svg#' + properties['icon']);
    } else {
        $('#' + MODAL_ICON_ID).attr('href', 'media/bootstrap-icons.svg#question-circle');
    }
    if (properties.hasOwnProperty('header')) {
        $('#' + MODAL_HEADER_ID).html(properties['header']);
        $('#' + MODAL_HEADER_ID).show()
    } else if (!(properties.hasOwnProperty('noHeaderHiding') && properties['noHeaderHiding'])) {
        $('#' + MODAL_HEADER_ID).hide()
    } else {
        $('#' + MODAL_HEADER_ID).show();
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
    } else if (!(properties.hasOwnProperty('noBodyHiding') && properties['noBodyHiding'])) {
        $('#' + MODAL_BODY_ID).hide();
    } else {
        $('#' + MODAL_BODY_ID).show();
    }
    var useCheckbox = false;
    if (properties.hasOwnProperty('checkboxLabel')) {
        useCheckbox = true;
        $('#' + MODAL_CONFIRM_CHECKBOX_LABEL_ID).html(properties['checkboxLabel']);
    } else {
        $('#' + MODAL_CONFIRM_CHECKBOX_LABEL_ID).html('');
    }
    if (properties.hasOwnProperty('checkboxChecked')) {
        useCheckbox = true;
        $('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked', properties['checkboxChecked']);
    }
    if (properties.hasOwnProperty('checkboxOnclick')) {
        useCheckbox = true;
        $('#' + MODAL_CONFIRM_CHECKBOX_ID).click(properties['checkboxOnclick']);
    }
    if (useCheckbox) {
        $('#' + MODAL_CONFIRM_CHECKBOX_ID).show();
    } else {
        $('#' + MODAL_CONFIRM_CHECKBOX_ID).hide();
    }
    var useConfirm = false;
    if (properties.hasOwnProperty('confirmElement') && properties['confirmElement'] == 'button') {
        useConfirm = true;
        confirmId = MODAL_CONFIRM_BUTTON_ID;
        $('#' + MODAL_CONFIRM_LINK_ID).hide();
    } else {
        confirmId = MODAL_CONFIRM_LINK_ID;
        $('#' + MODAL_CONFIRM_BUTTON_ID).hide();
    }
    if (properties.hasOwnProperty('confirm')) {
        useConfirm = true;
        $('#' + confirmId).html(properties['confirm']);
    }
    if (properties.hasOwnProperty('href')) {
        useConfirm = true;
        $('#' + confirmId).prop('href', properties['href']);
    }
    if (properties.hasOwnProperty('confirmName')) {
        useConfirm = true;
        $('#' + confirmId).prop('name', properties['confirmName']);
    }
    if (properties.hasOwnProperty('confirmType')) {
        useConfirm = true;
        $('#' + confirmId).prop('type', properties['confirmType']);
    }
    if (properties.hasOwnProperty('confirmForm')) {
        useConfirm = true;
        $('#' + confirmId).get()[0].form = properties['confirmForm'];
    }
    $('#' + confirmId).removeClass();
    if (properties.hasOwnProperty('confirmClass')) {
        useConfirm = true;
        $('#' + confirmId).addClass(properties['confirmClass']);
    } else {
        $('#' + confirmId).addClass('btn btn-primary');
    }
    if (properties.hasOwnProperty('confirmOnclick')) {
        useConfirm = true;
        $('#' + confirmId).click(properties['confirmOnclick']);
    }
    if (useConfirm) {
        $('#' + confirmId).show();
    } else {
        $('#' + confirmId).hide();
    }
    var useDismiss = false;
    if (properties.hasOwnProperty('dismiss')) {
        useDismiss = true;
        $('#' + MODAL_DISMISS_ID).html(properties['dismiss']);
    }
    if (properties.hasOwnProperty('dismissClass')) {
        useDismiss = true;
        $('#' + MODAL_DISMISS_ID).addClass(properties['dismissClass']);
    }
    if (properties.hasOwnProperty('dismissOnclick')) {
        useDismiss = true;
        $('#' + MODAL_ID).on('hide.bs.modal', function () {
            properties['dismissOnclick']();
            $('#' + MODAL_ID).off('hide.bs.modal');
        });
    } else {
        $('#' + MODAL_ID).off('hide.bs.modal');
    }
    if (useDismiss) {
        $('#' + MODAL_DISMISS_ID).show();
    } else {
        $('#' + MODAL_DISMISS_ID).hide();
    }
}

function revealModal() {
    $('#' + MODAL_ID).modal('show');
}

function hideModal() {
    $('#' + MODAL_ID).modal('hide');
}

function hideModalWithoutDismissCallback() {
    $('#' + MODAL_ID).off('hide.bs.modal');
    hideModal();
}

function setModalHeaderHTML(html) {
    $('#' + MODAL_HEADER_ID).html(html);
}

function setModalBodyHTML(html) {
    $('#' + MODAL_BODY_ID).html(html);
}

function connectModalToObject(object, modalProperties, bodySetters) {
    if (!bodySetters || (Array.isArray(bodySetters) && bodySetters.length == 0)) {
        bodySetters = [];
    }
    if (!Array.isArray(bodySetters)) {
        bodySetters = [bodySetters];
    }
    if (modalProperties.hasOwnProperty('showModal')) {
        var showModal = modalProperties.showModal;
    } else {
        var showModal = () => { return true; };
    }
    object._modalProperties = modalProperties;
    object._bodySetters = bodySetters;
    object._showModal = showModal;

    object.triggerModal = function (event) {
        var modalProperties = object._modalProperties;
        var bodySetters = object._bodySetters;
        var showModal = object._showModal;
        if (showModal()) {
            if (bodySetters.length > 0) {
                modalProperties['body'] = [];
            }
            bodySetters.forEach(setter => {
                if (setter.showText && setter.showText()) {
                    modalProperties['body'].push($('<p></p>').text(setter.text));
                }
            });
            setConfirmationModalProperties(modalProperties);
            if (event) {
                event.preventDefault();
            }
            revealModal();
        }
    };
}

function connectModalToLink(link_id, modalProperties, bodySetters) {
    var link = $('#' + link_id);
    if (!link.length) {
        return;
    }
    connectModalToObject(link, modalProperties, bodySetters)
    link.click(link.triggerModal);
}
