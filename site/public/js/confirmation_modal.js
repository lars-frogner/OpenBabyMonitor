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
    if (properties.hasOwnProperty('confirm_element') && properties['confirm_element'] == 'button') {
        confirm_id = MODAL_CONFIRM_BUTTON_ID;
        $('#' + MODAL_CONFIRM_LINK_ID).hide();
    } else {
        confirm_id = MODAL_CONFIRM_LINK_ID;
        $('#' + MODAL_CONFIRM_BUTTON_ID).hide();
    }
    $('#' + confirm_id).show();
    if (properties.hasOwnProperty('confirm')) {
        $('#' + confirm_id).html(properties['confirm']);
    }
    if (properties.hasOwnProperty('href')) {
        $('#' + confirm_id).prop('href', properties['href']);
    }
    if (properties.hasOwnProperty('confirm_name')) {
        $('#' + confirm_id).prop('name', properties['confirm_name']);
    }
    if (properties.hasOwnProperty('confirm_type')) {
        $('#' + confirm_id).prop('type', properties['confirm_type']);
    }
    if (properties.hasOwnProperty('confirm_form')) {
        $('#' + confirm_id).get()[0].form = properties['confirm_form'];
    }
    $('#' + confirm_id).removeClass();
    if (properties.hasOwnProperty('confirm_class')) {
        $('#' + confirm_id).addClass(properties['confirm_class']);
    } else {
        $('#' + confirm_id).addClass('btn btn-primary');
    }
    if (properties.hasOwnProperty('confirm_onclick')) {
        $('#' + confirm_id).click(properties['confirm_onclick']);
    }
    if (properties.hasOwnProperty('dismiss')) {
        $('#' + MODAL_DISMISS_ID).html(properties['dismiss']);
    }
    if (properties.hasOwnProperty('dismiss_class')) {
        $('#' + MODAL_DISMISS_ID).addClass(properties['dismiss_class']);
    }
}

function connectLinkToModal(link_id, modal_properties, body_setters) {
    var link = $('#' + link_id);
    if (!link.length) {
        return;
    }
    if (!body_setters || (Array.isArray(body_setters) && body_setters.length == 0)) {
        body_setters = [];
    }
    if (!Array.isArray(body_setters)) {
        body_setters = [body_setters];
    }
    if (modal_properties.hasOwnProperty('showModal')) {
        var showModal = modal_properties.showModal;
    } else {
        var showModal = () => { return true; };
    }
    link.data('modal_properties', modal_properties);
    link.data('body_setters', body_setters);
    link.data('showModal', showModal);

    link.click(function (event) {
        var modal_properties = link.data('modal_properties');
        var body_setters = link.data('body_setters');
        var showModal = link.data('showModal');
        if (showModal()) {
            modal_properties['body'] = [];
            body_setters.forEach(setter => {
                if (setter.showText()) {
                    modal_properties['body'].push($('<p></p>').text(setter.text));
                }
            });
            setConfirmationModalProperties(modal_properties);
            event.preventDefault();
            $('#' + MODAL_ID).modal('show');
        }
    });
}
