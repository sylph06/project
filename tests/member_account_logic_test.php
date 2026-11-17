<?php
require_once __DIR__ . '/../member_account_logic.php';

$failures = [];
$assert = function ($condition, $message) use (&$failures) {
    if (!$condition) $failures[] = $message;
};

$password = kk_member_temporary_password(' de la Cruz ');
$assert($password === 'DE LA CRUZ', 'Temporary passwords must be exactly the trimmed uppercase surname.');
$assert(kk_member_temporary_password(str_repeat('ñ', 100)) === mb_strtoupper(str_repeat('ñ', 100), 'UTF-8'), 'Long surnames must not be changed or truncated.');

try {
    kk_member_temporary_password('   ');
    $assert(false, 'A missing surname must be rejected instead of receiving a shared fallback password.');
} catch (RuntimeException $exception) {
    $assert(true, '');
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Member account logic checks passed: 3\n");
