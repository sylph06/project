<?php
/* =========================================================
   api/registration.php — retired public KK registration endpoint
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

if (($_GET['action'] ?? '') !== 'register') {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

out([
    'ok' => false,
    'error' => 'KK youth registration is handled in person. Please visit the SK Office at the Barangay Hall in Barangay Sikat, Alfonso, Cavite.',
], 410);
