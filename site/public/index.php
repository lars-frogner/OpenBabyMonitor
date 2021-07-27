<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedIn('main.php');
?>

<!DOCTYPE html>
<html>

<head>
  <?php require_once(TEMPLATES_PATH . '/head_common.php'); ?>
  <link href="css/signin.css" rel="stylesheet">
</head>

<body class="text-center">
  <main class="form-signin">
    <?php
    if (isset($_POST['submit'])) {
      tryLogin($_DATABASE, $_POST['password'], 'main.php', '<div class="alert alert-danger">Feil passord</div>');
    }
    ?>
    <form action="" method="post">
      <h1 class="h3 mb-3 fw-normal">Vennligst logg inn</h1>
      <label class="visually-hidden" for="password">Passord</label>
      <input type="password" name="password" class="form-control" id="password" placeholder="Passord" required>
      <button type="submit" name="submit" class="w-100 btn btn-lg btn-primary">Logg inn</button>
    </form>
  </main>
</body>

</html>
