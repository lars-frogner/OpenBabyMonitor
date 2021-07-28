function captureElementState(id) {
    $('#' + id).data('serialize', $('#' + id).serialize());
}

function elementHasChangedSinceCapture(id) {
    return $('#' + id).serialize() != $('#' + id).data('serialize');
}
