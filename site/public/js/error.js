function displayError(event) {
    document.getElementById('error_message').innerHTML += `
        <b>Javascript error on line ${event.lineno}, column ${event.colno} in ${event.filename}:</b><br>
        ${event.message}<br><br>`;
}

window.addEventListener('error', displayError);
