<script>
  var SETTING_ASK_SECURE_REDIRECT = <?php echo readValuesFromTable($_DATABASE, 'system_settings', 'ask_secure_redirect', true); ?>;
  var SETTING_SHOW_UNSUPPORTED_MESSAGE = <?php echo readValuesFromTable($_DATABASE, 'system_settings', 'show_unsupported_message', true); ?>;
  var SETTING_ASK_NOTIFICATION_PERMISSION = <?php echo readValuesFromTable($_DATABASE, 'system_settings', 'ask_notification_permission', true); ?>;
</script>

<script src="js/notifications.js"></script>
