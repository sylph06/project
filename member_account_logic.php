<?php

function kk_member_temporary_password($surname) {
    $surname = trim(mb_strtoupper(trim((string)$surname), 'UTF-8'));
    if ($surname === '') throw new RuntimeException('A member surname is required for a temporary password.');
    return $surname;
}
