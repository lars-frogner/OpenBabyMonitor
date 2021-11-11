const BACKGROUND_COLOR = $(document.body).css('background-color');
const FOREGROUND_COLOR = $(document.body).css('color');

$(function () {
    manageColorSchemeCookie();

    if (getColorScheme() === 'dark') {
        $('<style>.text-bm, .btn-bm { color: ' + FOREGROUND_COLOR + '; filter: brightness(80%); } .btn-bm:hover { color: ' + FOREGROUND_COLOR + '; filter: brightness(100%); }</style>').appendTo('head');
    } else {
        $('<style>.text-bm, .btn-bm { color: ' + FOREGROUND_COLOR + '; filter: brightness(120%); } .btn-bm:hover { color: ' + FOREGROUND_COLOR + '; filter: brightness(200%); }</style>').appendTo('head');
    }
});

function getColorScheme() {
    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
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
        window.matchMedia('(prefers-color-scheme: dark)').addListener(updateColorSchemeCookie);
    }
}
