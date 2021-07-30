function requestModeRestart() {
    fetch('restart_mode.php').catch(error => {
        console.log(error)
    });
}

$(function () {
    if (SETTINGS_EDITED && INITIAL_MODE == SITE_MODE) {
        requestModeRestart();
    }
})
