<?php
require_once('../config/config.php');
redirectIfLoggedOut('index.php');
?>
<!DOCTYPE html>
<html>
  <head>
    <?php
      require_once(TEMPLATES_PATH . '/head_common.php');
      require_once(TEMPLATES_PATH . '/video-js_css.php');
    ?>
  </head>

  <body>
    <?php require_once(TEMPLATES_PATH . '/navbar.php'); ?>

    <video-js id=example-video width=1080 height=720 class="vjs-default-skin" controls>
      <!-- <source src="hls/index.m3u8" type="application/x-mpegURL"> -->
      <source src="media/test.mp4" type="video/mp4">
    </video-js>

    <?php
      require_once(TEMPLATES_PATH . '/bootstrap_js.php');
      require_once(TEMPLATES_PATH . '/video-js_js.php');
    ?>

    <script>
      var player = videojs('example-video');
      player.play();
    </script>

  </body>
</html>
