<?php

function committee_volunteers_ensure_schema($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS committee_volunteers (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        committee VARCHAR(100) NOT NULL,
        kk_member_id VARCHAR(100) NOT NULL,
        event_id VARCHAR(60) NULL,
        role ENUM('member', 'lead') NOT NULL DEFAULT 'member',
        status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_by BIGINT UNSIGNED NULL,
        reviewed_at DATETIME NULL,
        KEY idx_committee_volunteers_committee_status (committee, status),
        KEY idx_committee_volunteers_member_date (kk_member_id, applied_at),
        KEY idx_committee_volunteers_event (event_id),
        KEY idx_committee_volunteers_reviewer (reviewed_by),
        CONSTRAINT fk_committee_volunteers_reviewer FOREIGN KEY (reviewed_by)
            REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function committee_volunteers_member_records($pdo, $legacyIds) {
    $wanted = [];
    foreach ($legacyIds as $legacyId) {
        $legacyId = trim((string)$legacyId);
        if ($legacyId !== '') $wanted[$legacyId] = true;
    }
    if (!$wanted) return [];

    $records = [];
    if (USE_MEMBERS_TABLE) {
        $ids = array_keys($wanted);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = $pdo->prepare("SELECT legacy_id, first_name, last_name, email, status
            FROM members WHERE legacy_id IN ($placeholders)");
        $query->execute($ids);
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $member) {
            $legacyId = (string)$member['legacy_id'];
            $records[$legacyId] = [
                'id' => $legacyId,
                'first_name' => (string)($member['first_name'] ?? ''),
                'last_name' => (string)($member['last_name'] ?? ''),
                'email' => (string)($member['email'] ?? ''),
                'status' => (string)($member['status'] ?? 'Active'),
            ];
        }
        return $records;
    }

    $query = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers'");
    $raw = $query->fetchColumn();
    if ($raw === false) throw new RuntimeException('KK member data is missing.');
    $members = json_decode((string)$raw, true);
    if (!is_array($members)) throw new RuntimeException('KK member data is invalid.');
    foreach ($members as $member) {
        if (!is_array($member)) continue;
        $legacyId = (string)($member['id'] ?? '');
        if ($legacyId === '' || !isset($wanted[$legacyId])) continue;
        $records[$legacyId] = [
            'id' => $legacyId,
            'first_name' => (string)($member['firstName'] ?? ''),
            'last_name' => (string)($member['lastName'] ?? ''),
            'email' => (string)($member['email'] ?? ''),
            'status' => (string)($member['status'] ?? 'Active'),
        ];
    }
    return $records;
}

function committee_volunteers_event_titles($pdo, $eventIds) {
    $wanted = [];
    foreach ($eventIds as $eventId) {
        $eventId = trim((string)$eventId);
        if ($eventId !== '') $wanted[$eventId] = true;
    }
    if (!$wanted) return [];

    $query = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skAnnouncements'");
    $raw = $query->fetchColumn();
    if ($raw === false) return [];
    $announcements = json_decode((string)$raw, true);
    if (!is_array($announcements)) throw new RuntimeException('Event data is invalid.');
    $titles = [];
    foreach ($announcements as $announcement) {
        if (!is_array($announcement)) continue;
        $eventId = (string)($announcement['id'] ?? '');
        if ($eventId !== '' && isset($wanted[$eventId])) {
            $titles[$eventId] = (string)($announcement['title'] ?? '');
        }
    }
    return $titles;
}

function committee_volunteers_approved_counts($pdo) {
    $counts = [];
    foreach ($pdo->query("SELECT committee, COUNT(*) AS volunteer_count
        FROM committee_volunteers WHERE status = 'approved' GROUP BY committee")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[(string)$row['committee']] = (int)$row['volunteer_count'];
    }
    return $counts;
}
