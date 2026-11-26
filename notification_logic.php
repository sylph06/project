<?php

function sidebar_notification_parse_timestamp($value) {
    if (!is_string($value) || trim($value) === '') return null;
    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $error) {
        return null;
    }
}

function sidebar_notification_is_newer($value, $lastSeenAt) {
    $timestamp = sidebar_notification_parse_timestamp($value);
    if ($timestamp === null) return false;
    $lastSeen = sidebar_notification_parse_timestamp($lastSeenAt);
    if ($lastSeen === null) return true;
    return $timestamp > $lastSeen;
}

function sidebar_notification_latest_value(array $values) {
    $latest = null;
    foreach ($values as $value) {
        $timestamp = sidebar_notification_parse_timestamp($value);
        if ($timestamp !== null && ($latest === null || $timestamp > $latest)) $latest = $timestamp;
    }
    return $latest ? $latest->format('Y-m-d H:i:s.u') : null;
}

function sidebar_notification_json_data($pdo, array $keys) {
    if (!$keys) return [];
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $statement = $pdo->prepare("SELECT data_key, data_value FROM app_data WHERE data_key IN ($placeholders)");
    $statement->execute($keys);
    $data = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $decoded = json_decode((string)$row['data_value'], true);
        $data[$row['data_key']] = is_array($decoded) ? $decoded : [];
    }
    return $data;
}

function sidebar_notification_mark_seen($pdo, $userId, $pageKey) {
    $statement = $pdo->prepare("INSERT INTO user_sidebar_last_seen (user_id, page_key, last_seen_at)
        VALUES (?, ?, NOW(3))
        ON DUPLICATE KEY UPDATE last_seen_at = VALUES(last_seen_at)");
    $statement->execute([(int)$userId, $pageKey]);
}

function sidebar_notification_table_exists($pdo, $tableName) {
    $statement = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $statement->execute([$tableName]);
    return (int)$statement->fetchColumn() === 1;
}

function sidebar_notification_compute($pdo, array $user, $currentPage) {
    $pageKeys = [
        'announcements.php' => 'announcements',
        'request.php' => 'review_requests',
    ];
    $currentPage = strtolower(basename((string)$currentPage));
    if (!sidebar_notification_table_exists($pdo, 'user_sidebar_last_seen')) {
        error_log('Sidebar notification tracking is not configured; apply the sidebar notifications migration.');
        return ['announcements' => false, 'interested' => false, 'review_requests' => false];
    }

    if (isset($pageKeys[$currentPage])) {
        sidebar_notification_mark_seen($pdo, (int)$user['id'], $pageKeys[$currentPage]);
    }

    $statement = $pdo->prepare('SELECT page_key, last_seen_at FROM user_sidebar_last_seen WHERE user_id = ?');
    $statement->execute([(int)$user['id']]);
    $lastSeen = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $lastSeen[(string)$row['page_key']] = (string)$row['last_seen_at'];
    }

    $data = sidebar_notification_json_data($pdo, [
        'skAnnouncements', 'skEventInterests', 'kkEditRequests', 'kkPendingRegistrations',
    ]);
    $announcementsSeen = $lastSeen['announcements'] ?? null;
    $newAnnouncement = false;
    foreach (($data['skAnnouncements'] ?? []) as $announcement) {
        if (!is_array($announcement)
            || ($announcement['publishState'] ?? '') !== 'Published'
            || !empty($announcement['cancelled'])
            || (!empty($announcement['publishDate']) && $announcement['publishDate'] > date('Y-m-d'))) continue;

        if (sidebar_notification_is_newer(
            sidebar_notification_latest_value([
                $announcement['createdAt'] ?? null,
                $announcement['updatedAt'] ?? null,
            ]),
            $announcementsSeen
        )) {
            $newAnnouncement = true;
            break;
        }
        foreach (($announcement['rescheduleHistory'] ?? []) as $change) {
            if (is_array($change) && sidebar_notification_is_newer($change['changedAt'] ?? null, $announcementsSeen)) {
                $newAnnouncement = true;
                break 2;
            }
        }
    }

    $isOfficial = (string)($user['role'] ?? '') !== 'KK Member';
    $newInterested = false;
    if ($isOfficial) {
        foreach (($data['skEventInterests'] ?? []) as $interest) {
            if (is_array($interest) && ($interest['response'] ?? '') === 'Interested'
                && sidebar_notification_is_newer($interest['respondedAt'] ?? null, $announcementsSeen)) {
                $newInterested = true;
                break;
            }
        }
    }

    $newReviewRequest = false;
    if ($isOfficial) {
        foreach ([
            ['items' => $data['kkEditRequests'] ?? [], 'status' => 'Edit Request', 'dateKey' => 'requestedAt'],
            ['items' => $data['kkPendingRegistrations'] ?? [], 'status' => 'Pending', 'dateKey' => 'submittedAt'],
        ] as $group) {
            foreach ($group['items'] as $request) {
                if (is_array($request) && ($request['status'] ?? '') === $group['status']
                    && sidebar_notification_is_newer($request[$group['dateKey']] ?? null, $lastSeen['review_requests'] ?? null)) {
                    $newReviewRequest = true;
                    break 2;
                }
            }
        }
    }

    return [
        'announcements' => $newAnnouncement,
        'interested' => $newInterested,
        'review_requests' => $newReviewRequest,
    ];
}
