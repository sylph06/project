<?php
function participation_date($value) {
    $value = substr(trim((string)$value), 0, 10);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function participation_age_at_event($member, $eventDate, $fallbackAge = null) {
    $birthDate = is_array($member) ? participation_date($member['birthDate'] ?? '') : null;
    $eventDate = participation_date($eventDate);
    if ($birthDate && $eventDate) {
        $age = (int)$birthDate->diff($eventDate)->format('%r%y');
        if ($age >= 0 && $age <= 120) return $age;
    }
    if ($fallbackAge === null || $fallbackAge === '') return null;
    $age = filter_var($fallbackAge, FILTER_VALIDATE_INT);
    return $age !== false && $age >= 1 && $age <= 120 ? (int)$age : null;
}

function participation_age_group($age) {
    if ($age === null) return 'Unknown';
    if ($age >= 15 && $age <= 17) return '15-17';
    if ($age >= 18 && $age <= 20) return '18-20';
    if ($age >= 21 && $age <= 24) return '21-24';
    if ($age >= 25 && $age <= 30) return '25-30';
    return 'Outside ages 15-30';
}

function participation_signup_age($value) {
    if ($value === null || $value === '') return null;
    if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/D', $value))) return false;
    $age = (int)$value;
    if ($age < 0 || $age > 120) return false;
    return $age > 0 ? $age : null;
}

function participation_walkin_is_active($record) {
    return is_array($record) && empty($record['removedAt']);
}

function participation_event_interest_is_open($announcement, $now = null) {
    if (!is_array($announcement) || !empty($announcement['cancelled']) || !empty($announcement['interestClosed'])) {
        return false;
    }
    $eventDateText = (string)($announcement['date'] ?? '');
    $eventDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDateText)
        ? participation_date($eventDateText) : null;
    $startTime = (string)($announcement['startTime'] ?? $announcement['time'] ?? '');
    if (!$eventDate || ($startTime !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime))) {
        return false;
    }
    $eventStart = strtotime($eventDate->format('Y-m-d') . ' ' . ($startTime !== '' ? $startTime : '00:00') . ':00');
    return $eventStart !== false && $eventStart > ($now === null ? time() : (int)$now);
}

function participation_walkin_should_count($eventId, $memberId, $attendedSignupMemberEvents, $walkinMemberEvents) {
    if ($memberId === '') return true;
    $key = $eventId . ':' . $memberId;
    return !isset($attendedSignupMemberEvents[$key]) && !isset($walkinMemberEvents[$key]);
}
