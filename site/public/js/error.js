function triggerErrorEvent(error) {
    window.dispatchEvent(new ErrorEvent('error', {
        error: error,
        message: error.message,
        filename: error.fileName,
        lineno: error.lineNumber,
        colno: error.columnNumber
    }));
}

function displayJSErrorEvent(event) {
    displayErrorMessage(`
        <b>JavaScript error on line ${event.lineno}, column ${event.colno} in ${event.filename}:</b><br>
        ${event.message}<br><br>`);
}

function displayErrorMessage(messageText) {
    const message = document.getElementById('error_message');
    message.innerHTML += messageText;
    if (message.style.display == 'none') {
        message.style.display = 'block';
    }
}

if (DEBUG) {
    window.addEventListener('error', displayJSErrorEvent);
}
