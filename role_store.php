<?php

function sk_role_rows($pdo, $forUpdate = false) {
    if (USE_SK_ROLES_TABLE) {
        $sql = 'SELECT role_data FROM sk_roles' . ($forUpdate ? ' FOR UPDATE' : '');
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $sql = "SELECT data_value FROM app_data WHERE data_key = 'skRoles'" . ($forUpdate ? ' FOR UPDATE' : '');
        $raw = $pdo->query($sql)->fetchColumn();
        $rows = $raw === false ? [] : [$raw];
    }
    $roles = [];
    foreach ($rows as $raw) {
        if (USE_SK_ROLES_TABLE) {
            $role = json_decode((string)$raw, true);
            if (!is_array($role)) throw new RuntimeException('An SK role record is invalid.');
            $roles[] = $role;
            continue;
        }
        $decoded = json_decode((string)$raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new RuntimeException('The skRoles data is invalid.');
        }
        foreach ($decoded as $role) if (is_array($role)) $roles[] = $role;
    }
    return $roles;
}

function sk_role_write($pdo, $role, $updatedBy) {
    $payload = json_encode($role, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    if (USE_SK_ROLES_TABLE) {
        $date = function ($key) use ($role) {
            $value = $role[$key] ?? null;
            return $value === '' ? null : $value;
        };
        $pdo->prepare("INSERT INTO sk_roles
            (role_code, kk_id, position, status, basis, election_year, date_elected, term_start, term_end, role_data, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE kk_id=VALUES(kk_id), position=VALUES(position), status=VALUES(status),
                basis=VALUES(basis), election_year=VALUES(election_year), date_elected=VALUES(date_elected),
                term_start=VALUES(term_start), term_end=VALUES(term_end), role_data=VALUES(role_data),
                updated_by=VALUES(updated_by)")
            ->execute([
                (string)$role['id'], (string)$role['kkId'], (string)$role['position'], (string)$role['status'],
                $role['basis'] ?? null, isset($role['electionYear']) ? (string)$role['electionYear'] : null,
                $date('dateElected'), $date('termStart'), $date('termEnd'), $payload, $updatedBy,
            ]);
        return;
    }

    $roles = sk_role_rows($pdo, true);
    $replaced = false;
    foreach ($roles as $index => $existing) {
        if ((string)($existing['id'] ?? '') === (string)$role['id']) {
            $roles[$index] = $role;
            $replaced = true;
            break;
        }
    }
    if (!$replaced) $roles[] = $role;
    $save = $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by) VALUES ('skRoles', ?, ?)
        ON DUPLICATE KEY UPDATE data_value=VALUES(data_value), updated_by=VALUES(updated_by)");
    $save->execute([
        json_encode($roles, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
        $updatedBy,
    ]);
}

function sk_role_find($roles, $id) {
    foreach ($roles as $role) if ((string)($role['id'] ?? '') === (string)$id) return $role;
    return null;
}

function sk_role_canonicalize($value) {
    if (!is_array($value)) return $value;
    $isList = array_keys($value) === range(0, count($value) - 1);
    if (!$isList) ksort($value, SORT_STRING);
    foreach ($value as $key => $child) $value[$key] = sk_role_canonicalize($child);
    return $value;
}

function sk_role_equal($left, $right) {
    return json_encode(sk_role_canonicalize($left), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)
        === json_encode(sk_role_canonicalize($right), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
}

function sk_member_for_role_change($pdo, $legacyId, $lock = false) {
    if (USE_MEMBERS_TABLE) {
        $st = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ?' . ($lock ? ' FOR UPDATE' : ''));
        $st->execute([$legacyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $member = kk_member_row_to_object($row);
        return json_decode(json_encode($member, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), true);
    }
    $sql = "SELECT data_value FROM app_data WHERE data_key = 'kkMembers'" . ($lock ? ' FOR UPDATE' : '');
    $raw = $pdo->query($sql)->fetchColumn();
    $members = $raw === false ? [] : json_decode($raw, true);
    foreach (is_array($members) ? $members : [] as $member) {
        if (is_array($member) && (string)($member['id'] ?? '') === (string)$legacyId) return $member;
    }
    return null;
}

function sk_audit($pdo, $actor, $targetLegacyId, $action, $oldValue, $newValue) {
    $encode = function ($value) {
        return $value === null ? null : json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );
    };
    $pdo->prepare('INSERT INTO audit_log (actor_user_id, actor, target_legacy_id, action, old_value, new_value)
                   VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([
            $actor['id'] ?? null,
            (string)($actor['username'] ?? 'system'),
            $targetLegacyId,
            $action,
            $encode($oldValue),
            $encode($newValue),
        ]);
}

function sk_role_valid_date($value, $field, $required = false) {
    if ($value === null || $value === '') {
        if ($required) throw new RuntimeException($field . ' is required.');
        return null;
    }
    if (!is_string($value)) throw new RuntimeException($field . ' must use YYYY-MM-DD.');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) throw new RuntimeException($field . ' must use YYYY-MM-DD.');
    return $value;
}

function sk_role_validate_payload($input) {
    if (!is_array($input)) throw new RuntimeException('A role object is required.');
    $positions = ['SK Chairperson', 'SK Vice Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad'];
    $position = $input['position'] ?? '';
    $basis = $input['basis'] ?? '';
    $year = (string)($input['electionYear'] ?? '');
    if (!in_array($position, $positions, true)) throw new RuntimeException('Select a valid SK position.');
    if (!in_array($basis, ['elected', 'appointed'], true)) throw new RuntimeException('Basis must be elected or appointed.');
    if (!preg_match('/^\d{4}$/', $year) || (int)$year < 2000 || (int)$year > 2100) {
        throw new RuntimeException('Enter a valid election year.');
    }
    $elected = sk_role_valid_date($input['dateElected'] ?? null, 'Date elected', true);
    if (substr($elected, 0, 4) !== $year) throw new RuntimeException('Election year must match date elected.');
    $start = sk_role_valid_date($input['termStart'] ?? null, 'Term start');
    $end = sk_role_valid_date($input['termEnd'] ?? null, 'Term end');
    if ($start !== null && $end !== null && $end <= $start) throw new RuntimeException('Term end must be after term start.');
    if ($start !== null && $start < $elected) throw new RuntimeException('Term cannot start before date elected.');
    foreach (['description' => 500, 'responsibilities' => 600] as $field => $limit) {
        if (isset($input[$field]) && (!is_string($input[$field]) || mb_strlen($input[$field]) > $limit)) {
            throw new RuntimeException($field . ' is too long or invalid.');
        }
    }
    return [
        'position' => $position,
        'basis' => $basis,
        'electionYear' => $year,
        'dateElected' => $elected,
        'termStart' => $start,
        'termEnd' => $end,
        'description' => (string)($input['description'] ?? ''),
        'responsibilities' => (string)($input['responsibilities'] ?? ''),
    ];
}
