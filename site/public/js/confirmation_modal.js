const MODAL_ID = 'confirmation_modal';
const MODAL_ICON_ID = MODAL_ID + '_icon';
const MODAL_ICON_SRC_ID = MODAL_ICON_ID + '_src';
const MODAL_HEADER_ID = MODAL_ID + '_header';
const MODAL_BODY_ID = MODAL_ID + '_body';
const MODAL_CONFIRM_CHECKBOX_ID = MODAL_ID + '_checkbox';
const MODAL_CONFIRM_CHECKBOX_LABEL_ID = MODAL_ID + '_checkbox_label';
const MODAL_CONFIRM_LINK_ID = MODAL_ID + '_confirm_link';
const MODAL_CONFIRM_BUTTON_ID = MODAL_ID + '_confirm_button';
const MODAL_DISMISS_ID = MODAL_ID + '_dismiss';

var _MODAL_QUEUE;

$(function () {
    _MODAL_QUEUE = new ModalQueue();
});

class ModalQueue {
    constructor() {
        this.queue = [];
        this.currentTriggerObject = null;
    }

    get length() {
        return this.queue.length;
    }

    insert(triggerObject, revealFunc) {
        // Avoid repeats of same modal
        if (modalIsRevealed() && triggerObject === this.currentTriggerObject) {
            return;
        } else if (this.length > 0 && triggerObject === this.queue[this.length - 1].triggerObject) {
            this.queue.pop();
        }
        // Enqueue modal
        this.queue.push({ triggerObject: triggerObject, reveal: revealFunc });
    }

    revealNext() {
        if (this.length > 0) {
            const modal = this.queue.shift();
            this.currentTriggerObject = modal.triggerObject;
            modal.reveal();
        }
    }
}

function setConfirmationModalProperties(properties) {
    if (properties.hasOwnProperty('icon')) {
        $('#' + MODAL_ICON_SRC_ID).attr('href', 'media/bootstrap-icons.svg#' + properties['icon']);
    } else {
        $('#' + MODAL_ICON_SRC_ID).attr('href', 'media/bootstrap-icons.svg#question-circle');
    }
    if (properties.hasOwnProperty('icon_size')) {
        $('#' + MODAL_ICON_ID).css({ width: properties['icon_size'], height: properties['icon_size'] });
    } else {
        $('#' + MODAL_ICON_ID).css({ 'min-width': '2.5rem', 'min-height': '2.5rem', width: '2.5rem', height: '2.5rem' });
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
    if (properties.hasOwnProperty('href')) {
        useConfirm = true;
        confirmId = MODAL_CONFIRM_LINK_ID;
        $('#' + MODAL_CONFIRM_BUTTON_ID).hide();
        $('#' + confirmId).prop('href', properties['href']);
    } else {
        confirmId = MODAL_CONFIRM_BUTTON_ID;
        $('#' + MODAL_CONFIRM_LINK_ID).hide();
    }
    if (properties.hasOwnProperty('confirm')) {
        useConfirm = true;
        $('#' + confirmId).html(properties['confirm']);
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
        $('#' + confirmId).click(function () {
            properties['confirmOnclick']();
            revealNextModal();
        });
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
        $('#' + MODAL_ID).off('hide.bs.modal');
        $('#' + MODAL_ID).on('hide.bs.modal', function (event) {
            properties['dismissOnclick']();
            $('#' + MODAL_ID).off('hide.bs.modal');
            $('#' + MODAL_ID).on('hide.bs.modal', switchToNextModal);
            switchToNextModal(event);
        });
    } else {
        $('#' + MODAL_ID).off('hide.bs.modal');
        $('#' + MODAL_ID).on('hide.bs.modal', switchToNextModal);
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
    $('#' + MODAL_ID).on('hide.bs.modal', switchToNextModal);
    hideModal();
}

function modalIsRevealed() {
    return $('#' + MODAL_ID).is(':visible');
}

function revealNextModal() {
    _MODAL_QUEUE.revealNext();
}

function enqueueModal(triggerObject, revealFunc) {
    _MODAL_QUEUE.insert(triggerObject, revealFunc);
}

function switchToNextModal(hideEvent) {
    if (_MODAL_QUEUE.length > 0) {
        hideEvent.preventDefault();
        revealNextModal();
    }
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

    object.triggerModal = function (extraAction) {
        var showModal = object._showModal;
        if (showModal()) {
            enqueueModal(object, function () {
                var modalProperties = object._modalProperties;
                var bodySetters = object._bodySetters;

                if (bodySetters.length > 0) {
                    modalProperties['body'] = [];
                }
                bodySetters.forEach(setter => {
                    if (setter.showText && setter.showText()) {
                        modalProperties['body'].push($('<p></p>').html(setter.text));
                    }
                });
                if (extraAction) {
                    extraAction();
                }
                setConfirmationModalProperties(modalProperties);
                revealModal();
            });
            if (!modalIsRevealed()) {
                revealNextModal();
            }
        }
    };
}

function connectModalToLink(link_id, modalProperties, bodySetters) {
    var link = $('#' + link_id);
    if (!link.length) {
        return;
    }
    connectModalToObject(link, modalProperties, bodySetters)
    link.click(function (event) {
        link.triggerModal(function () {
            event.preventDefault();

        });
    });
}
