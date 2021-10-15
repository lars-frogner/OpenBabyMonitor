<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedIn('main.php');
?>

<!DOCTYPE html>
<html>

<head>
  <?php require_once(TEMPLATES_PATH . '/head_common.php'); ?>
</head>

<body>
  <main class="vh-100">
    <div class="container h-100">
      <div class="row h-100 justify-content-center align-items-center">
        <aside class="col-sm-6">
          <div class="card">
            <article class="card-body">
              <h3 class="card-title text-center">Vennligst logg inn</h3>
              <?php
              if (isset($_POST['submit'])) {
                tryLogin($_DATABASE, $_POST['password'], 'main.php', '<hr><p class="text-center text-danger">Feil passord</p>');
              }
              ?>
              <form action="" method="post">
                <div class="my-3 form-group">
                  <label class="visually-hidden" for="password">Passord</label>
                  <input type="password" name="password" class="form-control" id="password" placeholder="Passord" required>
                </div>
                <div class="form-group text-center">
                  <button type="submit" name="submit" class="btn btn-primary">Logg inn</button>
                </div>
              </form>
            </article>
          </div>
        </aside>
      </div>
    </div>
  </main>
</body>

</html>
