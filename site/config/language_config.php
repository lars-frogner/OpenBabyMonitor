<?php
require_once(__DIR__ . '/path_config.php');
include_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/config.php');
include_once(__DIR__ . '/network_config.php');
require_once(SRC_DIR . '/io.php');
require_once(SRC_DIR . '/database.php');
require_once(SRC_DIR . '/language.php');

define('VALID_LANGUAGES', $_CONFIG['language']['current']['values']);

if (isset($_GET['lang'])) {
  $new_language = $_GET['lang'];
  if (!in_array($new_language, VALID_LANGUAGES)) {
    bm_error("Invalid language code: $new_language");
  }
  $old_language = readCurrentLanguage($_DATABASE);
  if ($new_language != $old_language) {
    updateCurrentLanguage($_DATABASE, $new_language);
  }
  define('LANGUAGE', $new_language);
} else {
  define('LANGUAGE', readCurrentLanguage($_DATABASE));
}

function getLanguageFilePath($name) {
  return LANGUAGE_DIR . '/' . LANGUAGE . "/$name.json";
}

$language_common_filepath = getLanguageFilePath('common');
$lang_common = readJSON($language_common_filepath);

$language_filepath = getLanguageFilePath(pathinfo(URI_WITHOUT_SEARCH, PATHINFO_FILENAME));
if (file_exists($language_filepath)) {
  define('LANG', array_merge($lang_common, readJSON($language_filepath)));
} else {
  define('LANG', $lang_common);
}
define('LANG_JSON', json_encode(LANG));
