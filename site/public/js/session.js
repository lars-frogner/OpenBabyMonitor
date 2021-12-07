const SESSION_REFRESH_INTERVAL = 600000;
var _SESSION_REFRESH_INTERVAL_HANDLE;

function logoutIfSessionExpired(responseText) {
    if (responseText == '-1') {
        logout();
    }
}

function logout() {
    window.location.replace('index.php');
}

function preventSessionTimeout() {
    if (!_SESSION_REFRESH_INTERVAL_HANDLE) {
        _SESSION_REFRESH_INTERVAL_HANDLE = setInterval(() => {
            fetch('refresh_session.php');
        }, SESSION_REFRESH_INTERVAL);
    }
}

function allowSessionTimeout() {
    if (_SESSION_REFRESH_INTERVAL_HANDLE) {
        clearInterval(_SESSION_REFRESH_INTERVAL_HANDLE);
        _SESSION_REFRESH_INTERVAL_HANDLE = null;
    }
}

function setAllowSessionTimeout(allow) {
    if (allow) {
        allowSessionTimeout();
    } else {
        preventSessionTimeout();
    }
}
