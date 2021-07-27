<?php
require_once(TEMPLATES_PATH . '/utils.php');
$warning_text = 'Merk at du har endringer i innstillingene som ikke er lagret.';
echo render(TEMPLATES_PATH . '/navbar.php', array('logout_text' => $warning_text, 'reboot_text' => $warning_text, 'shutdown_text' => $warning_text, 'ap_text' => $warning_text, 'client_text' => $warning_text));
