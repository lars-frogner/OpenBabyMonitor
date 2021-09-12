$(function () {
    if (SETTINGS_EDITED && INITIAL_MODE == SITE_MODE) {
        requestModeRestart();
    }
});

function requestModeRestart() {
    fetch('restart_mode.php').then(response => response.text())
        .then(logoutIfSessionExpired).catch(error => {
            console.log(error)
        });
}
