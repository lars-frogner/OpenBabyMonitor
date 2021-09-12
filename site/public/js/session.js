function logoutIfSessionExpired(responseText) {
    if (responseText == '-1') {
        window.location.replace('index.php');
    }
}
