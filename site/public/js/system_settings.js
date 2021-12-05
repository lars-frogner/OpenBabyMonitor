const NOTIFICATION_BUTTON_ID = 'notification_button';
const NOTIFICATION_MESSAGE_ID = 'notification_message';
const SERVER_TIMEZONE_SELECT_ID = 'server_timezone_select';
const CHANGE_TIMEZONE_BUTTON_ID = 'change_timezone_button';
const CHANGE_TIMEZONE_SUBMIT_BUTTON_ID = 'change_timezone_submit_button';
const HOSTNAME_INPUT_ID = 'hostname_input';
const HOSTNAME_INPUT_INVALID_MSG_ID = 'hostname_input_invalid_msg';
const CHANGE_HOSTNAME_BUTTON_ID = 'change_hostname_button';
const CHANGE_HOSTNAME_SUBMIT_BUTTON_ID = 'change_hostname_submit_button';
const DEVICE_PASSWORD_INPUT_ID = 'device_password_input';
const CHANGE_DEVICE_PASSWORD_BUTTON_ID = 'change_device_password_button';
const CHANGE_DEVICE_PASSWORD_SUBMIT_BUTTON_ID = 'change_device_password_submit_button';

$(function () {
    connectModalToLink(CHANGE_TIMEZONE_BUTTON_ID, { header: LANG['sure_want_to_change_timezone'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_TIMEZONE_SUBMIT_BUTTON_ID)).click(); } }, { text: LANG['must_reboot_for_changes'], showText: () => { return true; } });
    connectModalToLink(CHANGE_HOSTNAME_BUTTON_ID, { header: LANG['sure_want_to_change_hostname'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_HOSTNAME_SUBMIT_BUTTON_ID)).click(); } }, [{ text: LANG['will_change_url'], showText: () => { return true; } }, { text: LANG['will_reboot'], showText: () => { return true; } }]);
    connectModalToLink(CHANGE_DEVICE_PASSWORD_BUTTON_ID, { header: LANG['sure_want_to_change_device_password'], confirm: LANG['change'], dismiss: LANG['cancel'], confirmClass: 'btn btn-primary', confirmOnclick: () => { enabled($('#' + CHANGE_DEVICE_PASSWORD_SUBMIT_BUTTON_ID)).click(); } }, null);
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

function validateServerTimezone(timezone) {
    $('#' + CHANGE_TIMEZONE_BUTTON_ID).prop('disabled', timezone == CURRENT_TIMEZONE);
}

function validateHostname(hostname) {
    const valid_length = hostname.length > 0 && hostname.length < 64;
    const valid_characters = /^[a-z][a-z0-9\-]*$/.test(hostname) || hostname.length == 0;

    const error_msg = $('#' + HOSTNAME_INPUT_INVALID_MSG_ID);
    if (valid_characters) {
        error_msg.hide();
    } else {
        error_msg.show();
    }
    $('#' + CHANGE_HOSTNAME_BUTTON_ID).prop('disabled', !valid_length || !valid_characters || hostname == SERVER_HOSTNAME);
}

function validateDevicePassword(password) {
    $('#' + CHANGE_DEVICE_PASSWORD_BUTTON_ID).prop('disabled', password.length < 1);
}

$('#' + SERVER_TIMEZONE_SELECT_ID).on('change', function () {
    validateServerTimezone(this.value);
});

$('#' + HOSTNAME_INPUT_ID).on('input', function () {
    validateHostname(this.value);
});

$('#' + DEVICE_PASSWORD_INPUT_ID).on('input', function () {
    validateDevicePassword(this.value);
});
