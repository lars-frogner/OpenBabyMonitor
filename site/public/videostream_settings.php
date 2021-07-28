<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

$table_name = 'videostream_settings';
$setting_names = getColumnNames($_CONFIG['database']['tables'][$table_name]['types']);
if (isset($_POST['submit'])) {
  unset($_POST['submit']);
  $_POST['capture_audio'] = isset($_POST['capture_audio']);
  $_POST['vertical_resolution'] = intval($_POST['vertical_resolution']);
  updateValuesInTable($_DATABASE, $table_name, createColumnValueMap($setting_names, $_POST, false));
  extract($_POST);
} else {
  $values = readValuesFromTable($_DATABASE, $table_name, $setting_names);
  extract($values);
}
?>

<!DOCTYPE html>
<html>

<head>
  <?php
  require_once(TEMPLATES_PATH . '/head_common.php');
  ?>
</head>

<body>
  <header>
    <?php require_once(TEMPLATES_PATH . '/navbar_settings.php'); ?>
  </header>

  <main>
    <div class="container">
      <h1>Video settings</h1>
      <form id="videostream_settings_form" action="" method="post">
        <div class="mb-3">
          <label class="form-label" for="resolution_select">Resolution</label>
          <select name="vertical_resolution" class="form-select" id="resolution_select">
            <?php
            foreach ($_CONFIG['vertical_resolution']['values'] as $name => $value) {
              $selected = ($value == $vertical_resolution) ? ' selected' : '';
              echo "<option value=\"$value\"$selected>$name</option>\n";
            }
            ?>
          </select>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" name="capture_audio" class="form-check-input" id="audio_check" <?php echo $capture_audio ? ' checked' : ''; ?>>
          <label class="form-check-label" for="audio_check">Capture audio</label>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
      </form>
  </main>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
require_once(TEMPLATES_PATH . '/jquery_js.php');
?>

<script src="js/jquery_utils.js"></script>
<script src="js/navbar_settings.js"></script>

<script>
  handleModalTexts('videostream_settings_form');
</script>

</html>
