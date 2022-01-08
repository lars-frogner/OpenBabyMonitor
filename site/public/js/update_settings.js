async function updateSettings(settingType, settings) {
    var data = new URLSearchParams();
    data.append('setting_type', settingType);

    Object.entries(settings).forEach(entry => {
        const [name, value] = entry;
        data.append(name, value);
    });

    return await fetch('update_settings.php', {
        method: 'post',
        body: data
    })
        .then(response => response.text())
        .then(logoutIfSessionExpired)
        .catch(triggerErrorEvent);
}
