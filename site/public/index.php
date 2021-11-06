<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedIn('main.php');
?>

<!DOCTYPE html>
<html>

<head>
  <?php require_once(TEMPLATES_DIR . '/head_common.php'); ?>
</head>

<body>
  <main id="main_container" class="vh-100" style="display: none;">
    <div class="container h-100">
      <div class="row h-100 justify-content-center align-items-center">
        <aside class="col-sm-5">
          <div class="card">
            <article class="card-body">
              <h3 class="card-title text-center"><?php echo LANG['please_sign_in']; ?></h3>
              <?php
              if (isset($_POST['submit'])) {
                tryLogin($_DATABASE, $_POST['password'], 'main.php', '<hr><p class="text-center text-danger">Feil passord</p>');
              }
              ?>
              <form action="" method="post">
                <div class="my-3 form-group">
                  <label class="visually-hidden" for="password"><?php echo LANG['password']; ?></label>
                  <input type="password" name="password" class="form-control" id="password" placeholder="<?php echo LANG['password']; ?>" required>
                </div>
                <div class="form-group text-center">
                  <button type="submit" name="submit" class="btn btn-primary"><?php echo LANG['sign_in']; ?></button>
                </div>
              </form>
            </article>
          </div>
        </aside>
      </div>
    </div>
  </main>
</body>

<?php
require_once(TEMPLATES_DIR . '/jquery_js.php');
?>

<script src="js/index.js"></script>

</html>
