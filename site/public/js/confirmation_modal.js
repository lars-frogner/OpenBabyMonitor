const MODAL_ID = 'confirmation_modal';
const MODAL_HEADER_ID = MODAL_ID + '_header';
const MODAL_BODY_ID = MODAL_ID + '_body';
const MODAL_CONFIRM_LINK_ID = MODAL_ID + '_confirm_link';
const MODAL_CONFIRM_BUTTON_ID = MODAL_ID + '_confirm_button';
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
    if (properties.hasOwnProperty('confirmElement') && properties['confirmElement'] == 'button') {
        confirmId = MODAL_CONFIRM_BUTTON_ID;
        $('#' + MODAL_CONFIRM_LINK_ID).hide();
    } else {
        confirmId = MODAL_CONFIRM_LINK_ID;
        $('#' + MODAL_CONFIRM_BUTTON_ID).hide();
    }
    $('#' + confirmId).show();
    if (properties.hasOwnProperty('confirm')) {
        $('#' + confirmId).html(properties['confirm']);
    }
    if (properties.hasOwnProperty('href')) {
        $('#' + confirmId).prop('href', properties['href']);
    }
    if (properties.hasOwnProperty('confirmName')) {
        $('#' + confirmId).prop('name', properties['confirmName']);
    }
    if (properties.hasOwnProperty('confirmType')) {
        $('#' + confirmId).prop('type', properties['confirmType']);
    }
    if (properties.hasOwnProperty('confirmForm')) {
        $('#' + confirmId).get()[0].form = properties['confirmForm'];
    }
    $('#' + confirmId).removeClass();
    if (properties.hasOwnProperty('confirmClass')) {
        $('#' + confirmId).addClass(properties['confirmClass']);
    } else {
        $('#' + confirmId).addClass('btn btn-primary');
    }
    if (properties.hasOwnProperty('confirmOnclick')) {
        $('#' + confirmId).click(properties['confirmOnclick']);
    }
    if (properties.hasOwnProperty('dismiss')) {
        $('#' + MODAL_DISMISS_ID).html(properties['dismiss']);
    }
    if (properties.hasOwnProperty('dismissClass')) {
        $('#' + MODAL_DISMISS_ID).addClass(properties['dismissClass']);
    }
}

function connectLinkToModal(link_id, modalProperties, bodySetters) {
    var link = $('#' + link_id);
    if (!link.length) {
        return;
    }
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
    link.data('modalProperties', modalProperties);
    link.data('bodySetters', bodySetters);
    link.data('showModal', showModal);

    link.click(function (event) {
        var modalProperties = link.data('modalProperties');
        var bodySetters = link.data('bodySetters');
        var showModal = link.data('showModal');
        if (showModal()) {
            modalProperties['body'] = [];
            bodySetters.forEach(setter => {
                if (setter.showText()) {
                    modalProperties['body'].push($('<p></p>').text(setter.text));
                }
            });
            setConfirmationModalProperties(modalProperties);
            event.preventDefault();
            $('#' + MODAL_ID).modal('show');
        }
    });
}
