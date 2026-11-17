<?php

function kk_id_token_from_scan($value) {
    if (!is_string($value)) return null;
    $value = trim($value);
    if (strpos($value, 'KKID:') === 0) $value = substr($value, 5);
    return preg_match('/^[a-f0-9]{64}$/i', $value) === 1 ? strtolower($value) : null;
}

function kk_id_print_method_is_valid($method) {
    return is_string($method) && in_array($method, ['automatic', 'manual'], true);
}

function kk_id_member_name($member) {
    if (!is_array($member)) return '';
    return trim(implode(' ', array_filter([
        $member['firstName'] ?? '',
        $member['middleName'] ?? '',
        $member['lastName'] ?? '',
        $member['suffix'] ?? '',
    ], function ($part) { return is_string($part) && trim($part) !== ''; })));
}

function kk_id_tracking_records($memberId, $announcements, $registrations, $walkins) {
    $events = [];
    foreach ($announcements as $event) {
        if (!is_array($event) || !isset($event['id'])) continue;
        $events[(string)$event['id']] = $event;
    }

    $records = [];
    $attendedSignups = [];
    foreach ($registrations as $registration) {
        if (!is_array($registration) || (string)($registration['memberId'] ?? '') !== (string)$memberId) continue;
        $eventId = (string)($registration['eventId'] ?? '');
        $event = $events[$eventId] ?? [];
        $status = $registration['attendanceStatus'] ?? 'Not recorded';
        if (!in_array($status, ['Attended', 'Did not attend', 'Not recorded'], true)) $status = 'Not recorded';
        if ($status === 'Attended') $attendedSignups[$eventId] = true;
        $records[] = [
            'eventId' => $eventId,
            'title' => (string)($event['title'] ?? $registration['eventTitle'] ?? 'Activity'),
            'date' => (string)($event['date'] ?? ''),
            'status' => $status,
            'source' => 'Sign-up',
        ];
    }

    $seenWalkins = [];
    foreach ($walkins as $walkin) {
        if (!is_array($walkin) || (string)($walkin['memberId'] ?? '') !== (string)$memberId) continue;
        $eventId = (string)($walkin['eventId'] ?? '');
        if ($eventId === '' || isset($attendedSignups[$eventId]) || isset($seenWalkins[$eventId])) continue;
        $event = $events[$eventId] ?? [];
        $records[] = [
            'eventId' => $eventId,
            'title' => (string)($event['title'] ?? $walkin['eventTitle'] ?? 'Activity'),
            'date' => (string)($event['date'] ?? ''),
            'status' => 'Attended',
            'source' => 'Walk-in',
        ];
        $seenWalkins[$eventId] = true;
    }

    usort($records, function ($left, $right) {
        return strcmp($right['date'], $left['date']) ?: strcmp($left['title'], $right['title']);
    });
    return $records;
}
