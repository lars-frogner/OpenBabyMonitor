<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Baby monitor">
<meta name="author" content="Lars Frogner">
<meta name="color-scheme" content="light dark">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<title>Babymonitor</title>

<meta name="theme-color" content="#111111" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#eeeeee" media="(prefers-color-scheme: dark)">

<link rel="manifest" href="manifest.json">

<?php require_once(TEMPLATES_DIR . '/error.php'); ?>

<?php require_once(TEMPLATES_DIR . '/bootstrap_css.php'); ?>
<?php require_once(TEMPLATES_DIR . '/flag-icons_css.php'); ?>

<?php require_once(TEMPLATES_DIR . '/language.php'); ?>

<script src="js/session.js"></script>
<script>
  const LOCATION = '<?php echo LOCATION; ?>';
</script>

<script>
// Register Service Worker for PWA + Push support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('sw.js').catch(function() {});
    });
}
</script>
