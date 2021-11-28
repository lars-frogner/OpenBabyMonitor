function logoutIfSessionExpired(responseText) {
    if (responseText == '-1') {
        logout();
    }
}

function logout() {
    window.location.replace('index.php');
}
