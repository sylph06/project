<?php
/* "Continue as Public Viewer": view-only session, no password needed */
require_once __DIR__ . '/config.php';
if (!current_user()) $_SESSION['public'] = true;
header('Location: announcements.php');
exit;