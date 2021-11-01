const NAVBAR_ID = 'navbar';
const TITLE_NAV_LINK_ID = 'title_nav_link';
const MODES_NAV_LINK_ID = 'modes_nav_link';
const LISTEN_SETTINGS_NAV_LINK_ID = 'listen_settings_nav_link';
const AUDIOSTREAM_SETTINGS_NAV_LINK_ID = 'audiostream_settings_nav_link';
const VIDEOSTREAM_SETTINGS_NAV_LINK_ID = 'videostream_settings_nav_link';
const SERVER_SETTINGS_NAV_LINK_ID = 'server_settings_nav_link';
const LOGOUT_NAV_LINK_ID = 'logout_nav_link';
const REBOOT_NAV_LINK_ID = 'reboot_nav_link';
const SHUTDOWN_NAV_LINK_ID = 'shutdown_nav_link';
const AP_MODE_SWITCH_ID = 'ap_mode_switch';

const MODES_MODAL_BASE_PROPERTIES = { href: 'main.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const LISTEN_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'listen_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const AUDIOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'audiostream_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const VIDEOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'videostream_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const SERVER_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'server_settings.php', header: LANG['nav_want_to_leave_site'], confirm: LANG['nav_continue'], dismiss: LANG['nav_cancel'] };
const LOGOUT_MODAL_BASE_PROPERTIES = { href: 'logout.php', header: LANG['nav_want_to_sign_out'], confirm: LANG['nav_sign_out'], dismiss: LANG['nav_cancel'] };
const REBOOT_MODAL_BASE_PROPERTIES = { href: 'reboot.php', header: LANG['nav_want_to_reboot'], confirm: LANG['nav_reboot'], dismiss: LANG['nav_cancel'] };
const SHUTDOWN_MODAL_BASE_PROPERTIES = { href: 'shutdown.php', header: LANG['nav_want_to_shutdown'], confirm: LANG['nav_shutdown'], dismiss: LANG['nav_cancel'] };
const AP_MODAL_BASE_PROPERTIES = { href: 'activate_ap_mode.php', header: LANG['nav_want_to_create_ap'], confirm: LANG['nav_create'], dismiss: LANG['nav_cancel'], dismissOnclick: toggleApModeSwitchWithoutEvent };
const CLIENT_MODAL_BASE_PROPERTIES = { href: 'activate_client_mode.php', header: LANG['nav_want_to_connect_to_network'], confirm: LANG['nav_connect'], dismiss: LANG['nav_cancel'], dismissOnclick: toggleApModeSwitchWithoutEvent };

const AP_MODAL_BASE_BODY_SETTER = { text: LANG['nav_you_are_in_client_mode'], showText: () => { return true; } };
const CLIENT_MODAL_BASE_BODY_SETTER = { text: LANG['nav_you_are_in_ap_mode'], showText: () => { return true; } };

var _AP_MODE_MODAL_TRIGGER = {};
var _CLIENT_MODE_MODAL_TRIGGER = {};

const BACKGROUND_COLOR = $(document.body).css('background-color');
const FOREGROUND_COLOR = $(document.body).css('color');

$(function () {
    const uses_dark_mode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (uses_dark_mode) {
        $('<style>.text-bm, .btn-bm { color: ' + FOREGROUND_COLOR + '; filter: brightness(80%); } .btn-bm:hover { color: ' + FOREGROUND_COLOR + '; filter: brightness(100%); }</style>').appendTo('head');
    } else {
        $('<style>.text-bm, .btn-bm { color: ' + FOREGROUND_COLOR + '; filter: brightness(120%); } .btn-bm:hover { color: ' + FOREGROUND_COLOR + '; filter: brightness(200%); }</style>').appendTo('head');
    }
    $('#' + NAVBAR_ID).addClass(uses_dark_mode ? 'navbar-dark' : 'navbar-light');
    $('.nav-link').on('click', '.disabled', function (event) {
        event.preventDefault();
        return false;
    })
    $('#' + AP_MODE_SWITCH_ID).change(function () {
        if (this.checked) {
            _AP_MODE_MODAL_TRIGGER.triggerModal();
        } else {
            _CLIENT_MODE_MODAL_TRIGGER.triggerModal();
        }
    });
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
