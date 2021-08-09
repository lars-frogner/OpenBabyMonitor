const MODES_NAV_LINK_ID = 'modes_nav_link';
const LISTEN_SETTINGS_NAV_LINK_ID = 'listen_settings_nav_link';
const AUDIOSTREAM_SETTINGS_NAV_LINK_ID = 'audiostream_settings_nav_link';
const VIDEOSTREAM_SETTINGS_NAV_LINK_ID = 'videostream_settings_nav_link';
const SERVER_SETTINGS_NAV_LINK_ID = 'server_settings_nav_link';
const LOGOUT_NAV_LINK_ID = 'logout_nav_link';
const REBOOT_NAV_LINK_ID = 'reboot_nav_link';
const SHUTDOWN_NAV_LINK_ID = 'shutdown_nav_link';
const AP_NAV_LINK_ID = 'ap_mode_nav_link';
const CLIENT_NAV_LINK_ID = 'client_mode_nav_link';

const MODES_MODAL_BASE_PROPERTIES = { href: 'main.php', header: 'Er du sikker på at du vil forlate siden?', confirm: 'Fortsett', dismiss: 'Avbryt' };
const LISTEN_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'listen_settings.php', header: 'Er du sikker på at du vil forlate siden?', confirm: 'Fortsett', dismiss: 'Avbryt' };
const AUDIOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'audiostream_settings.php', header: 'Er du sikker på at du vil forlate siden?', confirm: 'Fortsett', dismiss: 'Avbryt' };
const VIDEOSTREAM_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'videostream_settings.php', header: 'Er du sikker på at du vil forlate siden?', confirm: 'Fortsett', dismiss: 'Avbryt' };
const SERVER_SETTINGS_MODAL_BASE_PROPERTIES = { href: 'server_settings.php', header: 'Er du sikker på at du vil forlate siden?', confirm: 'Fortsett', dismiss: 'Avbryt' };
const LOGOUT_MODAL_BASE_PROPERTIES = { href: 'logout.php', header: 'Er du sikker på at du vil logge ut?', confirm: 'Logg ut', dismiss: 'Avbryt' };
const REBOOT_MODAL_BASE_PROPERTIES = { href: 'reboot.php', header: 'Er du sikker på at du vil restarte enheten?', confirm: 'Restart', dismiss: 'Avbryt' };
const SHUTDOWN_MODAL_BASE_PROPERTIES = { href: 'shutdown.php', header: 'Er du sikker på at du vil slå av enheten?', confirm: 'Slå av', dismiss: 'Avbryt' };
const AP_MODAL_BASE_PROPERTIES = { href: 'activate_ap_mode.php', header: 'Er du sikker på at du vil opprette et trådløst tilgangspunkt på enheten?', confirm: 'Opprett', dismiss: 'Avbryt' };
const CLIENT_MODAL_BASE_PROPERTIES = { href: 'activate_client_mode.php', header: 'Er du sikker på at du vil koble enheten til et eksternt trådløst nettverk?', confirm: 'Koble til', dismiss: 'Avbryt' };

const AP_MODAL_BASE_BODY_SETTER = { text: 'Du er nå koblet til via et eksternt trådløst nettverk. Enheten vil koble seg av nettverket og opprette sitt eget tilgangspunkt. Du vil logges ut og midlertidig miste forbindelsen med enheten. Enheten vil ikke lenger ha tilgang til internett.', showText: () => { return true; } };
const CLIENT_MODAL_BASE_BODY_SETTER = { text: 'Du er nå koblet til via enhetens tilgangspunkt, som vil deaktiveres. Du vil logges ut og midlertidig miste forbindelsen med enheten.', showText: () => { return true; } };

$(function () {
    $('.nav-link').on('click', '.disabled', function (event) {
        event.preventDefault();
        return false;
    })
});

function setDisabledForNavbar(is_disabled) {
    if (is_disabled) {
        $('.nav-link').addClass('disabled');
    } else {
        $('.nav-link').removeClass('disabled');
    }
}
