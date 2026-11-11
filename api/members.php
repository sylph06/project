<?php
/* =========================================================
   api/members.php — KK member add, update, delete
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();

if (!in_array($action, ['member_add', 'member_update', 'member_delete', 'member_restore', 'member_register', 'member_data_save'], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

if (!can_write_app_data()) out(['ok' => false, 'error' => 'Your account role cannot change KK member records.'], 403);
$pdo = db();

if ($action === 'member_data_save') {
    if (USE_MEMBERS_TABLE) {
        out(['ok' => false, 'error' => 'Use the dedicated member actions to save member-table data.'], 400);
    }
    $members = $in['members'] ?? null;
    $requests = $in['requests'] ?? null;
    $expectedMembers = $in['expectedMembers'] ?? null;
    $expectedRequests = $in['expectedRequests'] ?? null;
    if (!is_array($members) || ($members !== [] && array_keys($members) !== range(0, count($members) - 1))
        || !is_array($requests) || ($requests !== [] && array_keys($requests) !== range(0, count($requests) - 1))
        || ($expectedMembers !== null && !is_string($expectedMembers))
        || ($expectedRequests !== null && !is_string($expectedRequests))) {
        out(['ok' => false, 'error' => 'Member or request data is invalid. Reload the page and try again.'], 400);
    }

    $memberIds = [];
    foreach ($members as $member) {
        $id = is_array($member) ? ($member['id'] ?? null) : null;
        if ((!is_string($id) && !is_int($id)) || (string)$id === '' || isset($memberIds[(string)$id])) {
            out(['ok' => false, 'error' => 'Every KK member must have a unique valid id.'], 400);
        }
        $memberIds[(string)$id] = true;
    }
    $requestIds = [];
    foreach ($requests as $request) {
        $id = is_array($request) ? ($request['id'] ?? null) : null;
        if ((!is_string($id) && !is_int($id)) || (string)$id === '' || isset($requestIds[(string)$id])) {
            out(['ok' => false, 'error' => 'Every request must have a unique valid id.'], 400);
        }
        $requestIds[(string)$id] = true;
    }

    $pdo->beginTransaction();
    try {
        $memberLock = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
        $memberRaw = $memberLock->fetchColumn();
        $memberRaw = $memberRaw === false ? null : (string)$memberRaw;
        $requestLock = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkEditRequests' FOR UPDATE");
        $requestRaw = $requestLock->fetchColumn();
        $requestRaw = $requestRaw === false ? null : (string)$requestRaw;
        if ($memberRaw !== $expectedMembers || $requestRaw !== $expectedRequests) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Member or request data changed in another session. Reload and try again.'], 409);
        }

        $oldMembers = $memberRaw === null ? [] : json_decode($memberRaw, true);
        $oldRequests = $requestRaw === null ? [] : json_decode($requestRaw, true);
        if (!is_array($oldMembers) || ($oldMembers !== [] && array_keys($oldMembers) !== range(0, count($oldMembers) - 1))
            || !is_array($oldRequests) || ($oldRequests !== [] && array_keys($oldRequests) !== range(0, count($oldRequests) - 1))) {
            throw new RuntimeException('Saved member or request data is invalid.');
        }
        $oldMemberIds = [];
        $oldMembersById = [];
        foreach ($oldMembers as $member) {
            if (!is_array($member) || !isset($member['id'])) {
                throw new RuntimeException('Saved KK member data contains an invalid record.');
            }
            $id = (string)$member['id'];
            $oldMemberIds[$id] = true;
            $oldMembersById[$id] = $member;
        }
        if (array_diff_key($oldMemberIds, $memberIds) || array_diff_key($memberIds, $oldMemberIds)) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Use the dedicated registration or restoration actions to add KK members.'], 403);
        }
        if (!in_array($user['role'] ?? '', MEMBER_DATA_ADMIN_ROLES, true)) {
            $newMembersById = [];
            foreach ($members as $member) $newMembersById[(string)$member['id']] = $member;
            $newRequestsByMember = [];
            $memberChangesAllowed = true;
            $allowedRequestFields = [
                'firstName', 'middleName', 'lastName', 'suffix', 'birthDate', 'gender', 'genderOther',
                'civilStatus', 'religion', 'religionOther', 'houseNo', 'street', 'purok', 'mobile',
                'email', 'facebook', 'classification', 'classificationOther', 'educationLevel',
                'educationOther', 'educationStatus', 'educationStatusOther', 'school', 'schoolOther',
                'employment', 'employmentOther', 'salaryIncome', 'occupation', 'occupationOther',
                'training', 'trainingOther', 'pwd', 'disabilityType', 'disabilityOther', 'skVoter',
                'voterNo', 'interests', 'interestOther', 'notes', 'photo',
            ];
            for ($index = count($oldRequests); $index < count($requests); $index++) {
                $request = $requests[$index];
                if (is_array($request)) {
                    $memberId = (string)($request['memberId'] ?? '');
                    $newRequestsByMember[$memberId] = ($newRequestsByMember[$memberId] ?? 0) + 1;
                    $changes = $request['changes'] ?? null;
                    $previous = $request['previous'] ?? null;
                    $validChanges = is_array($changes) && $changes !== [] && is_array($previous)
                        && !array_diff_key($changes, array_fill_keys($allowedRequestFields, true))
                        && !array_diff_key($previous, array_fill_keys($allowedRequestFields, true))
                        && !array_diff_key($changes, $previous) && !array_diff_key($previous, $changes);
                    if ($validChanges) {
                        foreach ($changes as $field => $value) {
                            if ($field === 'interests') {
                                if (!is_array($value) || ($value !== [] && array_keys($value) !== range(0, count($value) - 1))
                                    || array_filter($value, 'is_string') !== $value) {
                                    $validChanges = false;
                                    break;
                                }
                            } elseif ($field === 'disabilityType') {
                                try {
                                    if (kk_member_normalize_disability_types($value) !== $value) {
                                        $validChanges = false;
                                        break;
                                    }
                                } catch (RuntimeException $exception) {
                                    $validChanges = false;
                                    break;
                                }
                            } elseif (!is_string($value)) {
                                $validChanges = false;
                                break;
                            }
                        }
                    }
                    if (!$validChanges || !is_string($request['requestedAt'] ?? null) || $request['requestedAt'] === ''
                        || !is_string($request['requestedBy'] ?? null) || $request['requestedBy'] === '') {
                        $memberChangesAllowed = false;
                    }
                }
            }
            foreach ($oldMembers as $oldMember) {
                $memberId = (string)$oldMember['id'];
                $newMember = $newMembersById[$memberId];
                $expectedMember = $oldMember;
                if (empty($expectedMember['gender']) && !empty($expectedMember['sex'])) {
                    $expectedMember['gender'] = $expectedMember['sex'];
                }
                if (($expectedMember['status'] ?? '') !== 'Archived') $expectedMember['status'] = 'Active';
                $expectedMember['disabilityType'] = kk_member_normalize_disability_types($expectedMember['disabilityType'] ?? []);
                if (is_string($expectedMember['interests'] ?? null)) {
                    $expectedMember['interests'] = $expectedMember['interests'] === ''
                        ? [] : array_map('trim', explode(',', $expectedMember['interests']));
                } elseif (!is_array($expectedMember['interests'] ?? null)) {
                    $expectedMember['interests'] = [];
                }
                if (!is_array($expectedMember['history'] ?? null)) $expectedMember['history'] = [];
                if (empty($expectedMember['createdAt'])) {
                    if (!is_string($newMember['createdAt'] ?? null) || $newMember['createdAt'] === '') {
                        $memberChangesAllowed = false;
                        break;
                    }
                    $expectedMember['createdAt'] = $newMember['createdAt'];
                }

                $archiveEvent = null;
                if (($newMember['status'] ?? '') === 'Archived' && ($expectedMember['status'] ?? '') !== 'Archived') {
                    if (!is_string($newMember['archiveReason'] ?? null) || $newMember['archiveReason'] === ''
                        || !is_string($newMember['archivedAt'] ?? null) || $newMember['archivedAt'] === ''
                        || !is_string($newMember['archivedBy'] ?? null) || $newMember['archivedBy'] === ''
                        || !in_array($newMember['archiveType'] ?? '', ['manual', 'auto'], true)
                        || !empty($newRequestsByMember[$memberId])) {
                        $memberChangesAllowed = false;
                        break;
                    }
                    foreach (['status', 'archiveReason', 'archivedAt', 'archivedBy', 'archiveType'] as $field) {
                        $expectedMember[$field] = $newMember[$field];
                    }
                    $archiveEvent = $newMember['archiveType'] === 'auto' ? 'Auto-archived' : 'Archived';
                }

                $history = $newMember['history'] ?? null;
                $requestEvents = $newRequestsByMember[$memberId] ?? 0;
                $historyEvents = $requestEvents + ($archiveEvent !== null ? 1 : 0);
                $oldHistory = $expectedMember['history'];
                if (!is_array($history) || count($history) !== count($oldHistory) + $historyEvents) {
                    $memberChangesAllowed = false;
                    break;
                }
                foreach ($oldHistory as $index => $event) {
                    if (!array_key_exists($index, $history) || !sk_role_equal($event, $history[$index])) {
                        $memberChangesAllowed = false;
                        break 2;
                    }
                }
                for ($index = count($oldHistory); $index < count($history); $index++) {
                    $event = $history[$index];
                    $expectedAction = $requestEvents > 0 ? 'Edit requested' : $archiveEvent;
                    if (!is_array($event) || ($event['action'] ?? '') !== $expectedAction
                        || !is_string($event['at'] ?? null) || !is_string($event['by'] ?? null)
                        || !is_string($event['note'] ?? null)) {
                        $memberChangesAllowed = false;
                        break 2;
                    }
                    if ($requestEvents > 0) $requestEvents--;
                    else $expectedAction = null;
                }
                if ($requestEvents !== 0 || ($archiveEvent !== null
                    && (($history[count($history) - 1]['action'] ?? '') !== $archiveEvent
                        || ($history[count($history) - 1]['note'] ?? '') !== $newMember['archiveReason']))) {
                    $memberChangesAllowed = false;
                    break;
                }
                $expectedMember['history'] = $history;
                if (!sk_role_equal($expectedMember, $newMember)) {
                    $memberChangesAllowed = false;
                    break;
                }
            }
            if (!$memberChangesAllowed) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Member profiles can only be changed through approved requests or archiving.'], 403);
            }
            $canUpdateRequests = count($requests) >= count($oldRequests);
            foreach ($oldRequests as $index => $oldRequest) {
                if (!$canUpdateRequests || !is_array($oldRequest) || !isset($requests[$index]) || !is_array($requests[$index])) {
                    $canUpdateRequests = false;
                    break;
                }
                $newRequest = $requests[$index];
                if (sk_role_equal($oldRequest, $newRequest)) continue;

                $memberId = (string)($oldRequest['memberId'] ?? '');
                $archivedRejection = ($oldRequest['status'] ?? '') === 'Edit Request'
                    && ($newRequest['status'] ?? '') === 'Rejected'
                    && ($newRequest['reviewedBy'] ?? '') === 'System'
                    && ($newRequest['remarks'] ?? '') === 'Profile was archived.'
                    && is_string($newRequest['reviewedAt'] ?? null) && $newRequest['reviewedAt'] !== ''
                    && isset($oldMembersById[$memberId], $newMembersById[$memberId])
                    && ($oldMembersById[$memberId]['status'] ?? '') !== 'Archived'
                    && ($newMembersById[$memberId]['status'] ?? '') === 'Archived';
                if ($archivedRejection) {
                    $expectedRequest = $oldRequest;
                    foreach (['status', 'reviewedAt', 'reviewedBy', 'remarks'] as $field) {
                        $expectedRequest[$field] = $newRequest[$field];
                    }
                    $archivedRejection = sk_role_equal($expectedRequest, $newRequest);
                }
                if (!$archivedRejection) {
                    $canUpdateRequests = false;
                    break;
                }
            }
            for ($index = count($oldRequests); $canUpdateRequests && $index < count($requests); $index++) {
                $newRequest = $requests[$index];
                $memberId = is_array($newRequest) ? (string)($newRequest['memberId'] ?? '') : '';
                if (!is_array($newRequest) || ($newRequest['status'] ?? '') !== 'Edit Request'
                    || !isset($oldMemberIds[$memberId]) || ($newMembersById[$memberId]['status'] ?? '') === 'Archived'
                    || !is_array($newRequest['changes'] ?? null) || !$newRequest['changes']
                    || !is_array($newRequest['previous'] ?? null)) {
                    $canUpdateRequests = false;
                }
            }
            if (!$canUpdateRequests) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Only the Chairman or Secretary can review existing member requests.'], 403);
            }
        }

        $save = $pdo->prepare('INSERT INTO app_data (data_key, data_value, updated_by) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)');
        $memberJson = json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        $requestJson = json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        $save->execute(['kkMembers', $memberJson, $user['username']]);
        $save->execute(['kkEditRequests', $requestJson, $user['username']]);
        $pdo->commit();
        out(['ok' => true, 'members' => $memberJson, 'requests' => $requestJson]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('KK member data update failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not save KK member data.'], 500);
    }
}

if ($action === 'member_register') {
    $member = $in['member'] ?? null;
    $registrationId = $in['registrationId'] ?? null;
    if (!is_array($member) || empty($member['id']) || empty($member['memberNo'])
        || ($member['status'] ?? '') !== 'Active') {
        out(['ok' => false, 'error' => 'A complete active member profile is required.'], 400);
    }
    try {
        $member['disabilityType'] = kk_member_normalize_disability_types($member['disabilityType'] ?? []);
    } catch (RuntimeException $exception) {
        out(['ok' => false, 'error' => $exception->getMessage()], 400);
    }
    if (($member['pwd'] ?? '') === 'Yes' && !$member['disabilityType']) {
        out(['ok' => false, 'error' => 'Select at least one disability type.'], 400);
    }
    if (($member['pwd'] ?? '') === 'Yes' && in_array('Other', $member['disabilityType'], true)
        && trim((string)($member['disabilityOther'] ?? '')) === '') {
        out(['ok' => false, 'error' => 'Please specify the other disability type.'], 400);
    }
    if (($member['pwd'] ?? '') !== 'Yes') {
        $member['disabilityType'] = [];
        $member['disabilityOther'] = '';
    } elseif (!in_array('Other', $member['disabilityType'], true)) {
        $member['disabilityOther'] = '';
    }
    $birthDate = $member['birthDate'] ?? null;
    if (!is_string($birthDate)) out(['ok' => false, 'error' => 'A valid date of birth is required.'], 400);
    $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    $today = new DateTimeImmutable('today');
    if (!$birth || $birth->format('Y-m-d') !== $birthDate || $birth > $today) {
        out(['ok' => false, 'error' => 'Enter a valid date of birth that is not in the future.'], 400);
    }
    $age = $birth->diff($today)->y;
    if ($age < 15 || $age > 30) {
        out(['ok' => false, 'error' => 'Only youth aged 15 to 30 can be registered.'], 400);
    }
    $guardianConsent = ($member['guardianConsent'] ?? false) === true;
    if ($age < 18 && !$guardianConsent) {
        out(['ok' => false, 'error' => 'Guardian consent is required before creating an account for a minor.'], 400);
    }
    if ($age >= 18) $guardianConsent = false;
    $member['guardianConsent'] = $guardianConsent;
    $member['guardianConsentAt'] = $guardianConsent ? gmdate('Y-m-d\TH:i:s.000\Z') : null;
    $member['guardianConsentVersion'] = $guardianConsent ? 'guardian-consent-v1' : null;
    $consentAt = $guardianConsent ? gmdate('Y-m-d H:i:s') : null;

    $username = trim((string)$member['memberNo']);
    if (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {
        out(['ok' => false, 'error' => 'The member number cannot be used as an account username.'], 400);
    }

    $pdo->beginTransaction();
    try {
        if ($registrationId !== null) {
            if (!is_string($registrationId) || $registrationId === '') {
                throw new RuntimeException('The pending registration reference is invalid.');
            }
            $pendingLock = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'kkPendingRegistrations' FOR UPDATE");
            $pendingLock->execute();
            $pendingRaw = $pendingLock->fetchColumn();
            $pending = $pendingRaw === false ? null : json_decode((string)$pendingRaw, true);
            if (!is_array($pending)) throw new RuntimeException('Pending registration data is missing or invalid.');
            $pendingIndex = null;
            foreach ($pending as $index => $request) {
                if (is_array($request) && (string)($request['id'] ?? '') === $registrationId) {
                    $pendingIndex = $index;
                    break;
                }
            }
            if ($pendingIndex === null || ($pending[$pendingIndex]['status'] ?? '') !== 'Pending') {
                throw new DomainException('This registration has already been reviewed.');
            }
            $request = $pending[$pendingIndex];
            if (($request['birthDate'] ?? '') !== $birthDate
                || strtolower((string)($request['firstName'] ?? '')) !== strtolower((string)($member['firstName'] ?? ''))
                || strtolower((string)($request['lastName'] ?? '')) !== strtolower((string)($member['lastName'] ?? ''))) {
                throw new DomainException('The pending registration changed. Reload and review it again.');
            }
            $pending[$pendingIndex]['status'] = 'Approved';
            $pending[$pendingIndex]['reviewedAt'] = gmdate('Y-m-d\TH:i:s.000\Z');
            $pending[$pendingIndex]['reviewedBy'] = $user['username'];
            $pending[$pendingIndex]['memberId'] = (string)$member['id'];
            $pending[$pendingIndex]['memberNo'] = (string)$member['memberNo'];
        }

        $accountCheck = $pdo->prepare('SELECT id FROM users WHERE legacy_id = ? OR username = ? FOR UPDATE');
        $accountCheck->execute([(string)$member['id'], $username]);
        if ($accountCheck->fetchColumn() !== false) {
            throw new DomainException('An account already exists for this member number or profile.');
        }

        if (USE_MEMBERS_TABLE) {
            $existing = $pdo->prepare('SELECT 1 FROM members WHERE legacy_id = ? FOR UPDATE');
            $existing->execute([(string)$member['id']]);
            if ($existing->fetchColumn()) throw new DomainException('This member profile already exists.');
            $duplicate = $pdo->prepare('SELECT 1 FROM members
                WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND birthdate = ?
                LIMIT 1 FOR UPDATE');
            $duplicate->execute([$member['firstName'] ?? '', $member['lastName'] ?? '', $birthDate]);
            if ($duplicate->fetchColumn()) {
                throw new DomainException('This youth is already registered (same name and date of birth).');
            }
            kk_member_upsert($pdo, $member, $user['username']);
        } else {
            $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
                VALUES ('kkMembers', '[]', ?) ON DUPLICATE KEY UPDATE data_key = VALUES(data_key)")
                ->execute([$user['username']]);
            $memberLock = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
            $memberLock->execute();
            $members = json_decode((string)$memberLock->fetchColumn(), true);
            if (!is_array($members)) throw new RuntimeException('KK member data is missing or invalid.');
            foreach ($members as $existingMember) {
                if (!is_array($existingMember)) continue;
                if ((string)($existingMember['id'] ?? '') === (string)$member['id']) {
                    throw new DomainException('This member profile already exists.');
                }
                if ((string)($existingMember['memberNo'] ?? '') === (string)$member['memberNo']) {
                    throw new DomainException('That member number is already assigned. Reload the page and try again.');
                }
                if (strtolower((string)($existingMember['firstName'] ?? '')) === strtolower((string)($member['firstName'] ?? ''))
                    && strtolower((string)($existingMember['lastName'] ?? '')) === strtolower((string)($member['lastName'] ?? ''))
                    && (string)($existingMember['birthDate'] ?? '') === $birthDate) {
                    throw new DomainException('This youth is already registered (same name and date of birth).');
                }
            }
            $members[] = $member;
            $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'kkMembers'")
                ->execute([json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username']]);
        }

        if ($registrationId !== null) {
            $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'kkPendingRegistrations'")
                ->execute([json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username']]);
        }

        $temporaryPassword = bin2hex(random_bytes(12));
        $fullName = trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '', $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return (string)$part !== ''; })));
        $pdo->prepare('INSERT INTO users
            (username, password, full_name, role, is_active, legacy_id, must_change_password,
             guardian_consent, consent_at, consent_version, created_by)
            VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?)')
            ->execute([
                $username, password_hash($temporaryPassword, PASSWORD_DEFAULT), $fullName, 'KK Member',
                (string)$member['id'], $guardianConsent ? 1 : 0, $consentAt,
                $member['guardianConsentVersion'], $user['id'],
            ]);
        sk_audit($pdo, $user, (string)$member['id'], 'member_registered', null, [
            'member_no' => $member['memberNo'], 'account_username' => $username,
            'guardian_consent' => $guardianConsent,
        ]);
        $pdo->commit();
        out([
            'ok' => true,
            'member' => $member,
            'registration' => $registrationId !== null ? $pending[$pendingIndex] : null,
            'username' => $username,
            'temporaryPassword' => $temporaryPassword,
        ]);
    } catch (DomainException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 409);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $ex->getMessage()], 400);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('KK member/account registration failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not register the KK member and create their account.'], 500);
    }
}

if ($action === 'member_restore') {
    $legacyId = $in['id'] ?? '';
    $expected = $in['expected'] ?? null;
    $birthDate = $in['birthDate'] ?? null;
    $reason = trim((string)($in['reason'] ?? ''));
    if (!is_string($legacyId) || $legacyId === '' || !is_array($expected)
        || !is_string($birthDate) || $reason === '' || mb_strlen($reason) > 500) {
        out(['ok' => false, 'error' => 'A member, valid birth date, original record, and restoration reason are required.'], 400);
    }
    $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    $today = new DateTimeImmutable('today');
    if (!$birth || $birth->format('Y-m-d') !== $birthDate || $birth > $today) {
        out(['ok' => false, 'error' => 'Enter a valid birth date that is not in the future.'], 400);
    }
    $age = $birth->diff($today)->y;
    if ($age < 15 || $age > 30) {
        out(['ok' => false, 'error' => 'A KK member can be restored only when the verified birth date shows an age of 15 to 30.'], 400);
    }

    $pdo->beginTransaction();
    try {
        $now = gmdate('Y-m-d\TH:i:s.000\Z');
        if (USE_MEMBERS_TABLE) {
            $find = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ? FOR UPDATE');
            $find->execute([$legacyId]);
            $row = $find->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'The archived member no longer exists.'], 404);
            }
            $memberObject = kk_member_row_to_object($row);
            if (!kk_members_json_equal(kk_members_request_to_object($expected), $memberObject)) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member changed in another session. Reload and try again.'], 409);
            }
            if (($row['status'] ?? '') !== 'Archived') {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Only archived members can be restored.'], 409);
            }
            $history = json_decode((string)($row['history'] ?? '[]'), true);
            if (!is_array($history)) $history = [];
            $oldBirthDate = $row['birthdate'] === null ? '' : (string)$row['birthdate'];
            if ($birthDate !== $oldBirthDate) {
                $history[] = ['at' => $now, 'action' => 'Birth date corrected for age eligibility', 'by' => $user['username'], 'note' => $reason];
            }
            $history[] = ['at' => $now, 'action' => 'Restored', 'by' => $user['username'], 'note' => $reason];
            $save = $pdo->prepare("UPDATE members SET birthdate = ?, status = 'Active', archive_reason = '',
                archived_at = NULL, archived_by = '', archive_type = '',
                history = ?, updated_by = ? WHERE legacy_id = ?");
            $save->execute([
                $birthDate,
                json_encode($history, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                $user['username'],
                $legacyId,
            ]);
            $member = json_decode(json_encode($memberObject), true);
            $member['birthDate'] = $birthDate;
            $member['status'] = 'Active';
            $member['archiveReason'] = '';
            $member['archivedAt'] = null;
            $member['archivedBy'] = '';
            $member['archiveType'] = '';
            $member['history'] = $history;
        } else {
            $lock = $pdo->prepare("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE");
            $lock->execute();
            $raw = $lock->fetchColumn();
            $members = $raw === false ? null : json_decode((string)$raw, true);
            if (!is_array($members)) throw new RuntimeException('KK member data is missing or invalid.');
            $memberIndex = null;
            foreach ($members as $index => $candidate) {
                if (is_array($candidate) && (string)($candidate['id'] ?? '') === $legacyId) {
                    $memberIndex = $index;
                    break;
                }
            }
            if ($memberIndex === null) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'The archived member no longer exists.'], 404);
            }
            $member = $members[$memberIndex];
            if (!kk_members_json_equal(kk_members_request_to_object($expected), kk_members_request_to_object($member))) {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'This member changed in another session. Reload and try again.'], 409);
            }
            if (($member['status'] ?? '') !== 'Archived') {
                $pdo->rollBack();
                out(['ok' => false, 'error' => 'Only archived members can be restored.'], 409);
            }
            if (!isset($members[$memberIndex]['history']) || !is_array($members[$memberIndex]['history'])) {
                $members[$memberIndex]['history'] = [];
            }
            if ((string)($member['birthDate'] ?? '') !== $birthDate) {
                $members[$memberIndex]['history'][] = ['at' => $now, 'action' => 'Birth date corrected for age eligibility', 'by' => $user['username'], 'note' => $reason];
            }
            $members[$memberIndex]['birthDate'] = $birthDate;
            $members[$memberIndex]['status'] = 'Active';
            $members[$memberIndex]['archiveReason'] = '';
            $members[$memberIndex]['archivedAt'] = null;
            $members[$memberIndex]['archivedBy'] = '';
            $members[$memberIndex]['archiveType'] = '';
            $members[$memberIndex]['history'][] = ['at' => $now, 'action' => 'Restored', 'by' => $user['username'], 'note' => $reason];
            $member = $members[$memberIndex];
            $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?')
                ->execute([json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username'], 'kkMembers']);
        }
        sk_audit($pdo, $user, $legacyId, 'member_restored', $expected, $member);
        $pdo->commit();
        out(['ok' => true, 'member' => $member]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('KK member restore failed: ' . $ex->getMessage());
        out(['ok' => false, 'error' => 'Could not restore the KK member.'], 500);
    }
}

if (!USE_MEMBERS_TABLE) out(['ok' => false, 'error' => 'Member-table actions are unavailable in this storage configuration.'], 400);
$pdo->beginTransaction();
try {
    if ($action === 'member_add') {
        $member = $in['member'] ?? null;
        if (!is_array($member) || empty($member['id'])) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'A member record with an id is required.'], 400);
        }
        $exists = $pdo->prepare('SELECT 1 FROM members WHERE legacy_id = ? FOR UPDATE');
        $exists->execute([(string)$member['id']]);
        if ($exists->fetchColumn()) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'That member already exists.'], 409);
        }
        $result = kk_member_upsert($pdo, $member, $user['username']);
        $pdo->commit();
        out(['ok' => true, 'result' => $result]);
    }

    $legacyId = $in['id'] ?? '';
    if (!is_string($legacyId) || $legacyId === '') {
        $pdo->rollBack();
        out(['ok' => false, 'error' => 'A member id is required.'], 400);
    }
    $find = $pdo->prepare('SELECT * FROM members WHERE legacy_id = ? FOR UPDATE');
    $find->execute([$legacyId]);
    $row = $find->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $pdo->rollBack();
        out(['ok' => false, 'error' => 'The member no longer exists.'], 404);
    }

    if ($action === 'member_update') {
        $member = $in['member'] ?? null;
        $expected = $in['expected'] ?? null;
        if (!is_array($member) || !is_array($expected) || (string)($member['id'] ?? '') !== $legacyId) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'A member record and its original values are required.'], 400);
        }
        $expected = kk_members_request_to_object($expected);
        if (!kk_members_json_equal($expected, kk_member_row_to_object($row))) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'This member changed in another session. Reload and try again.'], 409);
        }
        $result = kk_member_upsert($pdo, $member, $user['username']);
        $pdo->commit();
        out(['ok' => true, 'result' => $result]);
    }

    /* member_delete */
    $expected = $in['expected'] ?? null;
    if (!is_array($expected)) {
        $pdo->rollBack();
        out(['ok' => false, 'error' => 'The original member values are required to delete this record.'], 400);
    }
    $expected = kk_members_request_to_object($expected);
    if (!kk_members_json_equal($expected, kk_member_row_to_object($row))) {
        $pdo->rollBack();
        out(['ok' => false, 'error' => 'This member changed in another session. Reload and try again.'], 409);
    }

    foreach (sk_role_rows($pdo, true) as $role) {
        if (is_array($role) && (string)($role['kkId'] ?? '') === $legacyId) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Unassign this member\'s SK role before deleting the member.'], 409);
        }
    }
    $requests = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkEditRequests' FOR UPDATE")->fetchColumn();
    $requests = $requests === false ? [] : json_decode($requests, true);
    foreach (is_array($requests) ? $requests : [] as $request) {
        if (is_array($request) && (string)($request['memberId'] ?? '') === $legacyId) {
            $pdo->rollBack();
            out(['ok' => false, 'error' => 'Resolve this member\'s edit requests before deleting the member.'], 409);
        }
    }
    $pdo->prepare('DELETE FROM members WHERE legacy_id = ?')->execute([$legacyId]);
    $pdo->commit();
    out(['ok' => true]);
} catch (DomainException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 409);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('KK member table write failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the KK member record.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('KK member table save failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the KK member record.'], 500);
}
