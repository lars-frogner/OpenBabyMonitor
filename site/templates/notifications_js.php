<script>
  const SIGNED_IN = <?php echo (isset($_GET['signin']) && $_GET['signin'] == '1') ? 'true' : 'false'; ?>;
  var SETTING_ASK_SECURE_REDIRECT = <?php echo readValuesFromTable($_DATABASE, 'system_settings', 'ask_secure_redirect', true); ?>;
  var SETTING_SHOW_UNSUPPORTED_MESSAGE = <?php echo readValuesFromTable($_DATABASE, 'system_settings', 'show_unsupported_message', true); ?>;
  var SETTING_ASK_NOTIFICATION_PERMISSION = <?php echo readValuesFromTable($_DATABASE, 'system_settings', 'ask_notification_permission', true); ?>;
</script>

<script src="js/update_settings.js"></script>
<script src="js/notifications.js"></script>
