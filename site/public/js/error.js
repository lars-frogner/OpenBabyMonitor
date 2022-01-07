function displayError(event) {
    const message = document.getElementById('error_message');
    message.innerHTML += `
        <b>Javascript error on line ${event.lineno}, column ${event.colno} in ${event.filename}:</b><br>
        ${event.message}<br><br>`;
    message.style.display = 'block';
}

window.addEventListener('error', displayError);
