function triggerErrorEvent(error) {
    console.error(error);
    window.dispatchEvent(new ErrorEvent('error', {
        error: error,
        message: error.message,
        filename: error.fileName,
        lineno: error.lineNumber,
        colno: error.columnNumber
    }));
}

function displayJSErrorEvent(event) {
    const colnoText = event.colno ? `, column ${event.colno}` : '';
    const linenoText = event.lineno ? ` on line ${event.lineno}${colnoText}` : '';
    const filenameText = event.filename ? ` in ${event.filename}` : '';
    displayErrorMessage(`
        <b>JavaScript error${linenoText}${filenameText}:</b><br>
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
