const MAX_TIME_DEVIATION = 60 * 1e3; // Milliseconds

function sendClientTimestampToServer() {
    if (Math.abs(SERVER_TIMESTAMP - CLIENT_TIMESTAMP) < MAX_TIME_DEVIATION) {
        return;
    }

    var data = new URLSearchParams();
    data.append('current_timestamp', Math.round(Date.now() * 1e-3));

    fetch('sync_time.php', {
        method: 'post',
        body: data
    })
        .then(response => response.text())
        .then(logoutIfSessionExpired)
        .catch(error => {
            console.log(error)
        });

}

sendClientTimestampToServer();
