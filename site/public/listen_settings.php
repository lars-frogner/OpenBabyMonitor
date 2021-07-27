<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
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
    <?php require_once(TEMPLATES_PATH . '/navbar.php'); ?>
  </header>

  <main>
    <div class="container">
      <h1>Listen settings</h1>
      <form>
        <button type="submit" class="btn btn-primary">Submit</button>
      </form>
  </main>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
?>

</html>
