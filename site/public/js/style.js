const BACKGROUND_COLOR = $(document.body).css('background-color');
const FOREGROUND_COLOR = $(document.body).css('color');

$(function () {
    manageColorSchemeCookie();

    if (getColorScheme() === 'dark') {
        $('<style>.text-bm, .btn-bm { color: ' + FOREGROUND_COLOR + '; filter: brightness(80%); } .btn-bm:hover { filter: brightness(100%); }</style>').appendTo('head');
    } else {
        $('<style>.text-bm { color: ' + FOREGROUND_COLOR + '; } .btn-bm { filter: brightness(160%); } .btn-bm:hover { filter: brightness(60%); }</style>').appendTo('head');
    }

    updateDarkModeToggleIcon();
});

function getSystemColorScheme() {
    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
}

function getColorSchemeOverride() {
    return Cookies.get('color_scheme_override');
}

function getColorScheme() {
    const override = getColorSchemeOverride();
    return (override === 'dark' || override === 'light') ? override : getSystemColorScheme();
}

function toggleDarkMode() {
    const current = getColorScheme();
    const next = (current === 'dark') ? 'light' : 'dark';
    Cookies.set('color_scheme_override', next, { expires: 365 });
    Cookies.set('color_scheme', next);
    location.reload();
}

function updateDarkModeToggleIcon() {
    const icon = document.getElementById('dark_mode_toggle_icon');
    if (!icon) return;
    const isDark = getColorScheme() === 'dark';
    icon.setAttribute('href', 'media/bootstrap-icons.svg#' + (isDark ? 'sun-fill' : 'moon-fill'));
}

function updateColorSchemeCookie() {
    Cookies.set('color_scheme', getColorScheme());
}

function manageColorSchemeCookie() {
    const colorScheme = Cookies.get('color_scheme');

    if ((typeof colorScheme === 'undefined') || (getColorScheme() != colorScheme)) {
        updateColorSchemeCookie();
    }

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addListener(function () {
            if (!getColorSchemeOverride()) {
                updateColorSchemeCookie();
            }
        });
    }
}
