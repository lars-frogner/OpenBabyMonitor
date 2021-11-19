const NAVBAR_ID = 'navbar';
const TITLE_NAV_LINK_ID = 'title_nav_link';
const MODES_NAV_LINK_ID = 'modes_nav_link';
const LISTEN_SETTINGS_NAV_LINK_ID = 'listen_settings_nav_link';
const AUDIOSTREAM_SETTINGS_NAV_LINK_ID = 'audiostream_settings_nav_link';
const VIDEOSTREAM_SETTINGS_NAV_LINK_ID = 'videostream_settings_nav_link';
const NETWORK_SETTINGS_NAV_LINK_ID = 'network_settings_nav_link';
const LOGOUT_NAV_LINK_ID = 'logout_nav_link';
const REBOOT_NAV_LINK_ID = 'reboot_nav_link';
const SHUTDOWN_NAV_LINK_ID = 'shutdown_nav_link';
const AP_MODE_SWITCH_ID = 'ap_mode_switch';

const MODES_MODAL_BASE_PROPERTIES = { href: 'main.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const LISTEN_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'listen_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const AUDIOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'audiostream_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const VIDEOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'videostream_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const NETWORK_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'network_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const LOGOUT_MODAL_BASE_PROPERTIES = { href: 'logout.php', header: LANG['nav_want_to_sign_out'], confirm: LANG['nav_sign_out'], dismiss: LANG['nav_cancel'] };
const REBOOT_MODAL_BASE_PROPERTIES = { href: 'reboot.php', header: LANG['nav_want_to_reboot'], confirm: LANG['nav_reboot'], dismiss: LANG['nav_cancel'] };
const SHUTDOWN_MODAL_BASE_PROPERTIES = { href: 'shutdown.php', header: LANG['nav_want_to_shutdown'], confirm: LANG['nav_shutdown'], dismiss: LANG['nav_cancel'] };
const AP_MODAL_BASE_PROPERTIES = { href: 'activate_ap_mode.php', header: LANG['nav_want_to_create_ap'], confirm: LANG['nav_create'], dismiss: LANG['nav_cancel'], dismissOnclick: toggleApModeSwitchWithoutEvent };
const CLIENT_MODAL_BASE_PROPERTIES = { href: 'activate_client_mode.php', header: LANG['nav_want_to_connect_to_network'], confirm: LANG['nav_connect'], dismiss: LANG['nav_cancel'], dismissOnclick: toggleApModeSwitchWithoutEvent };
const CLIENT_MODAL_NO_NETWORK_BASE_PROPERTIES = { icon: 'exclamation-circle', href: 'network_settings.php', header: LANG['nav_client_no_network'], confirm: LANG['nav_go_to_network'], dismiss: LANG['nav_cancel'], dismissOnclick: toggleApModeSwitchWithoutEvent };
const CLIENT_MODAL_NO_NETWORK_AT_NETWORK_BASE_PROPERTIES = { icon: 'exclamation-circle', header: LANG['nav_client_no_network'], dismiss: LANG['nav_cancel'], dismissOnclick: toggleApModeSwitchWithoutEvent };

const AP_MODAL_BASE_BODY_SETTER = { text: LANG['nav_you_are_in_client_mode'], showText: () => { return true; } };
const CLIENT_MODAL_BASE_BODY_SETTER = { text: LANG['nav_you_are_in_ap_mode'], showText: () => { return true; } };
const CLIENT_MODAL_NO_NETWORK_BASE_BODY_SETTER = { text: LANG['nav_cannot_deactivate_ap'] + ' ' + LANG['nav_please_go_to_network'], showText: () => { return true; } };
const CLIENT_MODAL_NO_NETWORK_AT_NETWORK_BASE_BODY_SETTER = { text: LANG['nav_cannot_deactivate_ap'], showText: () => { return true; } };

var _AP_MODE_MODAL_TRIGGER = {};
var _CLIENT_MODE_NO_NETWORK_MODAL_TRIGGER = {};
var _CLIENT_MODE_MODAL_TRIGGER = {};

$(function () {
    $('.nav-link').on('click', '.disabled', function (event) {
        event.preventDefault();
        return false;
    })
    $('#' + AP_MODE_SWITCH_ID).change(function () {
        if (this.checked) {
            _AP_MODE_MODAL_TRIGGER.triggerModal();
        } else {
            if (ANY_KNOWN_NETWORKS) {
                _CLIENT_MODE_MODAL_TRIGGER.triggerModal();
            } else {
                _CLIENT_MODE_NO_NETWORK_MODAL_TRIGGER.triggerModal();
            }
        }
    });
    $('#' + NAVBAR_ID).show();
});

function setDisabledForNavbar(isDisabled) {
    if (isDisabled) {
        $('.nav-link').addClass('disabled');
    } else {
        $('.nav-link').removeClass('disabled');
    }
}

function toggleApModeSwitchWithoutEvent() {
    const sw = $('#' + AP_MODE_SWITCH_ID);
    sw.prop('checked', !sw.prop('checked'));
}
