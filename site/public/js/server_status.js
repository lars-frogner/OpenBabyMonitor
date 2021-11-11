const PHPSYSINFO_IFRAME_ID = 'phpsysinfo';
const SERVER_STATUS_BUSY_SPINNER_ID = 'server_status_busy_spinner';

$(function () {
    $('#' + SERVER_STATUS_BUSY_SPINNER_ID).show();

    $('#' + PHPSYSINFO_IFRAME_ID).on('load', function () {
        $('#' + SERVER_STATUS_BUSY_SPINNER_ID).hide();
        $('#' + PHPSYSINFO_IFRAME_ID).show();
        setDisabledForNavbar(false);
    });
});
