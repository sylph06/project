<?php
require_once __DIR__ . '/config.php';
if (!current_user()) $_SESSION['public'] = true;
header('Location: index.php');
exit;