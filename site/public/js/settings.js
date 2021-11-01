const MAIN_CONTAINER_ID = 'main_container';

$(function () {
    if (SETTINGS_EDITED && INITIAL_MODE == SITE_MODE) {
        requestModeRestart();
    }
    $('#' + MAIN_CONTAINER_ID).show();
});

function requestModeRestart() {
    fetch('restart_mode.php').then(response => response.text())
        .then(logoutIfSessionExpired).catch(error => {
            console.error('Mode restart failed:', error)
        });
}
