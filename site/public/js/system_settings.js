const NOTIFICATION_BUTTON_ID = 'notification_button';
const NOTIFICATION_MESSAGE_ID = 'notification_message';
const CHANGE_USER_PASSWORD_INPUT_ID = 'device_password_input';
const CHANGE_USER_PASSWORD_BUTTON_ID = 'change_device_password_button';
const CHANGE_USER_PASSWORD_SUBMIT_BUTTON_ID = 'change_device_password_submit_button';

$(function () {
    connectModalToLink(CHANGE_USER_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_device_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_USER_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
    updateBrowserNotificationIndicator();
})

function updateBrowserNotificationIndicator() {
    const message = $('#' + NOTIFICATION_MESSAGE_ID);
    const button = $('#' + NOTIFICATION_BUTTON_ID);
    if (!USES_SECURE_PROTOCOL) {
        message.html(LANG['unavailable_insecure']);
        button.html(LANG['switch_to_encrypted']);
        button.click(function (event) { event.preventDefault(); window.location.replace(SECURE_URL); });
        button.show();
    } else if (!browserNotificationsSupported()) {
        message.html(LANG['unsupported_by_browser']);
        button.hide();
    } else if (browserNotificationsBlocked()) {
        message.html(LANG['blocked']);
        button.hide();
    } else if (browserNotificationsAllowed()) {
        message.html(LANG['allowed']);
        button.hide();
    } else if (browserNotificationsNotAllowed()) {
        message.html(LANG['not_allowed']);
        button.html(LANG['grant_permission']);
        button.click(function (event) { event.preventDefault(); askBrowserNotificationPermission(updateBrowserNotificationIndicator); });
        button.show();
    }
}

function enabled(button) {
    button.prop('disabled', false);
    return button;
}

$('#' + CHANGE_USER_PASSWORD_INPUT_ID).on('input', function () {
    $('#' + CHANGE_USER_PASSWORD_BUTTON_ID).prop('disabled', this.value.length < 1);
});
