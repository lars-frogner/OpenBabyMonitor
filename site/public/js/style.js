const BACKGROUND_COLOR = $(document.body).css('background-color');
const FOREGROUND_COLOR = $(document.body).css('color');

$(function () {
    manageColorSchemeCookie();

    if (getColorScheme() === 'dark') {
        $('<style>.text-bm, .btn-bm { color: ' + FOREGROUND_COLOR + '; filter: brightness(80%); } .btn-bm:hover { filter: brightness(100%); }</style>').appendTo('head');
    } else {
        $('<style>.text-bm { color: ' + FOREGROUND_COLOR + '; } .btn-bm { filter: brightness(160%); } .btn-bm:hover { filter: brightness(60%); }</style>').appendTo('head');
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
