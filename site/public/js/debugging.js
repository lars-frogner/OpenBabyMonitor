$(function () {
    setDisabledForNavbar(false);
});

function setDebug(debug) {
    var data = new URLSearchParams();
    data.append('BM_DEBUG', debug ? '1' : '0');

    fetch('set_env_vars.php', {
        method: 'post',
        body: data
    }).catch(triggerErrorEvent);
}
