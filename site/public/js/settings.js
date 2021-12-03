const MAIN_CONTAINER_ID = 'main_container';
const RESET_BUTTON_ID = 'reset_button';
const RESET_SUBMIT_BUTTON_ID = 'reset_submit_button';

$(function () {
    if (SETTINGS_EDITED && INITIAL_MODE == SITE_MODE) {
        requestModeRestart();
    }
    $('#' + MAIN_CONTAINER_ID).show();

    connectModalToLink(RESET_BUTTON_ID, { header: LANG['sure_want_to_reset'], confirm: LANG['reset'], dismiss: LANG['cancel'], confirmClass: 'btn btn-warning', confirmOnclick: () => { enabled($('#' + RESET_SUBMIT_BUTTON_ID)).click(); } }, null);
});

function requestModeRestart() {
    fetch('restart_mode.php').then(response => response.text())
        .then(logoutIfSessionExpired).catch(error => {
            console.error('Mode restart failed:', error)
        });
}

function enabled(button) {
    button.prop('disabled', false);
    return button;
}
