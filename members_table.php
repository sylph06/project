<?php

function kk_member_column_map() {
    return [
        'id' => 'legacy_id',
        'memberNo' => 'member_no',
        'firstName' => 'first_name',
        'middleName' => 'middle_name',
        'lastName' => 'last_name',
        'suffix' => 'suffix',
        'birthDate' => 'birthdate',
        'gender' => 'gender',
        'genderOther' => 'gender_other',
        'civilStatus' => 'civil_status',
        'religion' => 'religion',
        'religionOther' => 'religion_other',
        'houseNo' => 'house_no',
        'street' => 'street',
        'purok' => 'purok',
        'mobile' => 'mobile',
        'email' => 'email',
        'facebook' => 'facebook',
        'classification' => 'classification',
        'classificationOther' => 'classification_other',
        'educationLevel' => 'education_level',
        'educationOther' => 'education_other',
        'educationStatus' => 'education_status',
        'educationStatusOther' => 'education_status_other',
        'school' => 'school',
        'schoolOther' => 'school_other',
        'employment' => 'employment',
        'employmentOther' => 'employment_other',
        'occupation' => 'occupation',
        'occupationOther' => 'occupation_other',
        'training' => 'training',
        'trainingOther' => 'training_other',
        'pwd' => 'pwd',
        'disabilityType' => 'disability_type',
        'disabilityOther' => 'disability_other',
        'skVoter' => 'sk_voter',
        'voterNo' => 'voter_no',
        'interests' => 'interests',
        'interestOther' => 'interest_other',
        'notes' => 'notes',
        'photo' => 'photo',
        'barangay' => 'barangay',
        'city' => 'city',
        'province' => 'province',
        'status' => 'status',
        'createdAt' => 'created_at',
        'history' => 'history',
        'archiveReason' => 'archive_reason',
        'archivedAt' => 'archived_at',
        'archivedBy' => 'archived_by',
        'archiveType' => 'archive_type',
    ];
}

function kk_members_is_authorized_operator() {
    $user = current_user();
    if ($user && in_array($user['role'] ?? '', MEMBER_DATA_ADMIN_ROLES, true)) return true;
    return PHP_SAPI === 'cli'
        || in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
}

function kk_members_is_list($value) {
    if (!is_array($value)) return false;
    $expected = 0;
    foreach ($value as $key => $_) {
        if ($key !== $expected++) return false;
    }
    return true;
}

function kk_member_datetime_to_sql($value, $field) {
    if ($value === null || $value === '') return [null, $value === '' ? '' : null];
    if (!is_string($value)) throw new RuntimeException($field . ' must be a timestamp string.');
    try {
        $date = new DateTimeImmutable($value);
    } catch (Exception $ex) {
        throw new RuntimeException($field . ' is not a valid timestamp.');
    }
    $utc = $date->setTimezone(new DateTimeZone('UTC'));
    $sqlValue = $utc->format('Y-m-d H:i:s.v');
    $roundTrip = $utc->format('Y-m-d\TH:i:s.v\Z');
    return [$sqlValue, $roundTrip === $value ? null : $value];
}

function kk_member_date_to_sql($value) {
    if ($value === null || $value === '') return [null, $value === '' ? '' : null];
    if (!is_string($value)) throw new RuntimeException('birthDate must be a date string.');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new RuntimeException('birthDate must use YYYY-MM-DD.');
    }
    return [$value, null];
}

function kk_member_to_columns($member) {
    if (is_array($member)) $member = (object)$member;
    if (!($member instanceof stdClass)) throw new RuntimeException('member must be a JSON object.');
    $map = kk_member_column_map();
    $properties = get_object_vars($member);
    $values = [];
    $missing = [];
    $special = [];
    $unexpected = [];

    foreach ($map as $jsonField => $column) {
        if (!array_key_exists($jsonField, $properties)) {
            $missing[] = $jsonField;
            $values[$column] = $jsonField === 'photo' ? '' : ($jsonField === 'status' ? 'Active' : null);
            continue;
        }
        $value = $properties[$jsonField];
        if ($jsonField === 'birthDate') {
            list($values[$column], $preserved) = kk_member_date_to_sql($value);
            if ($preserved !== null) $special[$jsonField] = $preserved;
        } elseif ($jsonField === 'createdAt' || $jsonField === 'archivedAt') {
            list($values[$column], $preserved) = kk_member_datetime_to_sql($value, $jsonField);
            if ($preserved !== null) $special[$jsonField] = $preserved;
        } elseif ($jsonField === 'interests' || $jsonField === 'history') {
            if ($value === null) {
                $values[$column] = 'null';
            } elseif (!is_array($value)) {
                throw new RuntimeException($jsonField . ' must be an array or null.');
            } else {
                $values[$column] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
            }
        } elseif ($jsonField === 'photo') {
            if (!is_string($value)) throw new RuntimeException('photo must be a string.');
            if ($value !== '') throw new RuntimeException('photo conversion is deferred to the final photo-handling step.');
            $values[$column] = '';
        } else {
            if ($value !== null && !is_string($value)) {
                throw new RuntimeException($jsonField . ' must be a string or null.');
            }
            $values[$column] = $value;
        }
    }

    if (!isset($properties['id']) || !is_string($properties['id']) || $properties['id'] === '') {
        throw new RuntimeException('id is missing or invalid.');
    }
    if (!isset($properties['memberNo']) || !is_string($properties['memberNo']) || $properties['memberNo'] === '') {
        throw new RuntimeException('memberNo is missing or invalid.');
    }

    foreach ($properties as $key => $value) {
        if (!array_key_exists($key, $map)) $unexpected[$key] = $value;
    }

    $values['extra_json'] = json_encode(
        ['missing' => $missing, 'special' => (object)$special, 'unexpected' => (object)$unexpected],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
    );
    return $values;
}

function kk_member_row_to_object($row) {
    $map = kk_member_column_map();
    $metadata = json_decode($row['extra_json'] ?: '{}');
    if (!($metadata instanceof stdClass)) throw new RuntimeException('extra_json metadata is invalid.');
    $missing = isset($metadata->missing) && is_array($metadata->missing) ? $metadata->missing : [];
    $special = isset($metadata->special) && $metadata->special instanceof stdClass ? $metadata->special : new stdClass();
    $object = new stdClass();

    foreach ($map as $jsonField => $column) {
        $archiveMetadata = ['archiveReason', 'archivedAt', 'archivedBy', 'archiveType'];
        if (in_array($jsonField, $missing, true)
            && !(in_array($jsonField, $archiveMetadata, true) && $row[$column] !== null)) continue;
        if (property_exists($special, $jsonField)) {
            $object->$jsonField = $special->$jsonField;
        } elseif ($jsonField === 'birthDate') {
            $object->$jsonField = $row[$column] === null ? null : (string)$row[$column];
        } elseif ($jsonField === 'createdAt' || $jsonField === 'archivedAt') {
            if ($row[$column] === null) {
                $object->$jsonField = null;
            } else {
                $date = DateTimeImmutable::createFromFormat(
                    '!Y-m-d H:i:s.v',
                    (string)$row[$column],
                    new DateTimeZone('UTC')
                );
                if (!$date) throw new RuntimeException($jsonField . ' could not be reconstructed.');
                $object->$jsonField = $date->format('Y-m-d\TH:i:s.v\Z');
            }
        } elseif ($jsonField === 'interests' || $jsonField === 'history') {
            $object->$jsonField = $row[$column] === null ? null : json_decode($row[$column]);
            if ($row[$column] !== null && json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException($jsonField . ' JSON is invalid in members.');
            }
        } else {
            $object->$jsonField = $row[$column];
        }
    }

    if (isset($metadata->unexpected) && $metadata->unexpected instanceof stdClass) {
        foreach (get_object_vars($metadata->unexpected) as $key => $value) {
            if (!property_exists($object, $key)) $object->$key = $value;
        }
    }
    return $object;
}

function kk_members_from_table($pdo) {
    $members = [];
    foreach ($pdo->query('SELECT * FROM members ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $members[] = kk_member_row_to_object($row);
    }
    return $members;
}

function kk_members_array_from_table($pdo) {
    $members = [];
    foreach (kk_members_from_table($pdo) as $member) {
        $members[] = json_decode(json_encode($member, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), true);
    }
    return $members;
}

function kk_member_upsert($pdo, $member, $updatedBy) {
    $values = kk_member_to_columns($member);
    $columnMap = kk_member_column_map();
    $dbColumns = array_values(array_unique(array_merge(array_values($columnMap), ['extra_json'])));

    $find = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ? FOR UPDATE');
    $find->execute([$values['legacy_id']]);
    $existing = $find->fetch(PDO::FETCH_ASSOC);

    $numberOwner = $pdo->prepare('SELECT legacy_id FROM members WHERE member_no = ? AND legacy_id <> ? FOR UPDATE');
    $numberOwner->execute([$values['member_no'], $values['legacy_id']]);
    if ($numberOwner->fetchColumn() !== false) {
        throw new DomainException('That member number is already assigned to another member.');
    }

    if ($existing) {
        $columns = array_values(array_diff($dbColumns, ['legacy_id']));
        $sql = 'UPDATE members SET ' . implode(' = ?, ', $columns) . ' = ?, updated_by = ? WHERE legacy_id = ?';
        $params = [];
        foreach ($columns as $column) $params[] = $values[$column] ?? null;
        $params[] = $updatedBy;
        $params[] = $values['legacy_id'];
        $pdo->prepare($sql)->execute($params);
        return 'updated';
    }

    $columns = array_merge($dbColumns, ['updated_by']);
    $sql = 'INSERT INTO members (' . implode(', ', $columns) . ') VALUES (' .
        implode(', ', array_fill(0, count($columns), '?')) . ')';
    $params = [];
    foreach ($columns as $column) $params[] = $column === 'updated_by' ? $updatedBy : ($values[$column] ?? null);
    $pdo->prepare($sql)->execute($params);
    return 'inserted';
}

function kk_members_canonicalize($value) {
    if ($value instanceof stdClass) {
        $properties = get_object_vars($value);
        ksort($properties, SORT_STRING);
        $canonical = new stdClass();
        foreach ($properties as $key => $child) $canonical->$key = kk_members_canonicalize($child);
        return $canonical;
    }
    if (is_array($value)) {
        foreach ($value as $index => $child) $value[$index] = kk_members_canonicalize($child);
    }
    return $value;
}

function kk_members_json_equal($left, $right) {
    return json_encode(kk_members_canonicalize($left), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)
        === json_encode(kk_members_canonicalize($right), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
}

function kk_members_request_to_object($value) {
    if (!is_array($value)) return $value;
    if (kk_members_is_list($value)) {
        return array_map('kk_members_request_to_object', $value);
    }
    $object = new stdClass();
    foreach ($value as $key => $child) $object->$key = kk_members_request_to_object($child);
    return $object;
}

function kk_members_compare($original, $rebuilt) {
    $originalById = [];
    $rebuiltById = [];
    $originalIndexes = [];
    $rebuiltIndexes = [];
    $differences = [];

    foreach ($original as $index => $member) {
        if (!($member instanceof stdClass) || !isset($member->id) || !is_string($member->id)) {
            $differences[] = ['index' => $index, 'field' => 'id'];
            continue;
        }
        $originalById[$member->id] = $member;
        $originalIndexes[$member->id] = $index;
    }
    foreach ($rebuilt as $index => $member) {
        if (!($member instanceof stdClass) || !isset($member->id) || !is_string($member->id)) {
            $differences[] = ['index' => $index, 'field' => 'id'];
            continue;
        }
        $rebuiltById[$member->id] = $member;
        $rebuiltIndexes[$member->id] = $index;
    }

    foreach ($originalById as $legacyId => $originalMember) {
        if (!isset($rebuiltById[$legacyId])) {
            $differences[] = ['index' => $originalIndexes[$legacyId], 'field' => 'member'];
            continue;
        }
        $rebuiltMember = $rebuiltById[$legacyId];
        $originalFields = get_object_vars($originalMember);
        $rebuiltFields = get_object_vars($rebuiltMember);
        $fields = array_unique(array_merge(array_keys($originalFields), array_keys($rebuiltFields)));
        foreach ($fields as $field) {
            if (!array_key_exists($field, $originalFields) || !array_key_exists($field, $rebuiltFields)
                || !kk_members_json_equal($originalFields[$field], $rebuiltFields[$field])) {
                $differences[] = ['index' => $originalIndexes[$legacyId], 'field' => $field];
            }
        }
    }
    foreach ($rebuiltById as $legacyId => $_) {
        if (!isset($originalById[$legacyId])) {
            $differences[] = ['index' => $rebuiltIndexes[$legacyId], 'field' => 'extra_member'];
        }
    }

    return ['count' => count($differences), 'details' => $differences];
}
