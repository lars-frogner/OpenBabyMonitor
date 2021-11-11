const PHPSYSINFO_IFRAME_ID = 'phpsysinfo';

$(function () {
    $('#' + PHPSYSINFO_IFRAME_ID).on('load', function () {
        $('#' + PHPSYSINFO_IFRAME_ID).show();
        setDisabledForNavbar(false);
    });
});
