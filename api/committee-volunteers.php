<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../committee_volunteer_store.php';

$action = (string)($_GET['action'] ?? '');
$pdo = db();

if ($action === 'submit') {
    $user = committee_volunteer_require_member();
    committee_volunteer_ensure_schema($pdo);

    $committee = trim((string)($in['committee'] ?? ''));
    $eventId = $in['event_id'] ?? ($in['eventId'] ?? null);
    if ($committee === '' || mb_strlen($committee) > 100) {
        out(['ok' => false, 'error' => 'Choose a committee.'], 400);
    }
    if ($eventId !== null) {
        if (!is_string($eventId) && !is_int($eventId)) {
            out(['ok' => false, 'error' => 'Choose a valid event.'], 400);
        }
        $eventId = trim((string)$eventId);
        if ($eventId === '' || !preg_match('/^[A-Za-z0-9_-]{1,60}$/', $eventId)) {
            out(['ok' => false, 'error' => 'Choose a valid event.'], 400);
        }
    }

    $memberId = (string)$user['legacy_id'];
    $pdo->beginTransaction();
    try {
        if (!in_array($committee, sk_committee_names($pdo, true), true)) {
            throw new RuntimeException('Choose a committee from the current list.');
        }

        $members = committee_volunteers_member_records($pdo, [$memberId]);
        $member = $members[$memberId] ?? null;
        if (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            throw new RuntimeException('Only active KK member accounts can apply to volunteer.');
        }

        if ($eventId !== null) {
            $eventQuery = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skAnnouncements' FOR UPDATE");
            $rawEvents = $eventQuery->fetchColumn();
            $events = $rawEvents === false ? [] : json_decode((string)$rawEvents, true);
            if (!is_array($events)) throw new RuntimeException('Event data is invalid.');
            $event = null;
            foreach ($events as $candidate) {
                if (is_array($candidate) && (string)($candidate['id'] ?? '') === $eventId) {
                    $event = $candidate;
                    break;
                }
            }
            if (!$event || ($event['publishState'] ?? '') !== 'Published' || !empty($event['cancelled'])
                || (!empty($event['publishDate']) && $event['publishDate'] > date('Y-m-d'))
                || (!empty($event['date']) && $event['date'] < date('Y-m-d'))) {
                throw new RuntimeException('Choose a published, active event.');
            }
        }

        $duplicateQuery = $pdo->prepare("SELECT id FROM committee_volunteers
            WHERE committee = ? AND kk_member_id = ? AND event_id <=> ?
              AND status IN ('pending', 'approved') LIMIT 1 FOR UPDATE");
        $duplicateQuery->execute([$committee, $memberId, $eventId]);
        if ($duplicateQuery->fetchColumn() !== false) {
            throw new DomainException('You already have a pending or approved application for this committee and event.');
        }

        $insert = $pdo->prepare("INSERT INTO committee_volunteers
            (committee, kk_member_id, event_id, role, status)
            VALUES (?, ?, ?, 'member', 'pending')");
        $insert->execute([$committee, $memberId, $eventId]);
        $volunteerId = (int)$pdo->lastInsertId();
        sk_audit($pdo, $user, $memberId, 'committee_volunteer_submitted', null, [
            'volunteer_id' => $volunteerId,
            'committee' => $committee,
            'event_id' => $eventId,
        ]);
        $pdo->commit();
        out(['ok' => true, 'id' => $volunteerId]);
    } catch (DomainException $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $error->getMessage()], 409);
    } catch (RuntimeException $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $error->getMessage()], 400);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Committee volunteer submission failed: ' . $error->getMessage());
        out(['ok' => false, 'error' => 'Could not submit the volunteer application.'], 500);
    }
}

if ($action === 'list') {
    $user = require_officer();
    if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the Chairperson or Secretary can review volunteers.'], 403);
    committee_volunteer_ensure_schema($pdo);

    $committee = trim((string)($_GET['committee'] ?? ''));
    if ($committee === '' || !in_array($committee, sk_committee_names($pdo), true)) {
        out(['ok' => false, 'error' => 'Choose a valid committee.'], 400);
    }
    try {
        $query = $pdo->prepare('SELECT id, committee, kk_member_id, event_id, role, status, applied_at, reviewed_by, reviewed_at
            FROM committee_volunteers WHERE committee = ? ORDER BY applied_at DESC, id DESC');
        $query->execute([$committee]);
        $volunteers = $query->fetchAll(PDO::FETCH_ASSOC);
        $memberIds = array_column($volunteers, 'kk_member_id');
        $members = committee_volunteers_member_records($pdo, $memberIds);
        $eventIds = array_filter(array_column($volunteers, 'event_id'), function ($eventId) {
            return $eventId !== null && $eventId !== '';
        });
        $eventTitles = committee_volunteers_event_titles($pdo, $eventIds);
        foreach ($volunteers as &$volunteer) {
            $member = $members[(string)$volunteer['kk_member_id']] ?? [];
            $volunteer['first_name'] = $member['first_name'] ?? '';
            $volunteer['last_name'] = $member['last_name'] ?? '';
            $volunteer['email'] = $member['email'] ?? '';
            $volunteer['event_title'] = $eventTitles[(string)($volunteer['event_id'] ?? '')] ?? '';
        }
        unset($volunteer);
        out(['ok' => true, 'volunteers' => $volunteers]);
    } catch (Throwable $error) {
        error_log('Committee volunteer list failed: ' . $error->getMessage());
        out(['ok' => false, 'error' => 'Could not load committee volunteers.'], 500);
    }
}

if ($action === 'review') {
    $user = require_officer();
    if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the Chairperson or Secretary can review volunteers.'], 403);
    committee_volunteer_ensure_schema($pdo);

    $id = filter_var($in['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $status = $in['status'] ?? '';
    if ($id === false || !is_string($status) || !in_array($status, ['approved', 'declined'], true)) {
        out(['ok' => false, 'error' => 'Choose a valid volunteer and review status.'], 400);
    }

    $pdo->beginTransaction();
    try {
        $find = $pdo->prepare('SELECT kk_member_id, status FROM committee_volunteers WHERE id = ? FOR UPDATE');
        $find->execute([$id]);
        $volunteer = $find->fetch(PDO::FETCH_ASSOC);
        if (!$volunteer) throw new DomainException('That volunteer application no longer exists.');
        if ($volunteer['status'] !== 'pending') {
            throw new DomainException('That application has already been reviewed.');
        }

        $update = $pdo->prepare("UPDATE committee_volunteers
            SET status = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ? AND status = 'pending'");
        $update->execute([$status, (int)$user['id'], $id]);
        if ($update->rowCount() !== 1) {
            throw new DomainException('That application changed before it could be reviewed. Reload and try again.');
        }
        sk_audit($pdo, $user, (string)$volunteer['kk_member_id'], 'committee_volunteer_' . $status,
            ['status' => 'pending'], ['status' => $status, 'volunteer_id' => $id]);
        $pdo->commit();
        out(['ok' => true]);
    } catch (DomainException $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        out(['ok' => false, 'error' => $error->getMessage()], 409);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Committee volunteer review failed: ' . $error->getMessage());
        out(['ok' => false, 'error' => 'Could not review the volunteer application.'], 500);
    }
}

if ($action === 'my_history') {
    $user = committee_volunteer_require_member();
    committee_volunteer_ensure_schema($pdo);
    try {
        $query = $pdo->prepare('SELECT id, committee, event_id, role, status, applied_at, reviewed_at
            FROM committee_volunteers WHERE kk_member_id = ? ORDER BY applied_at DESC, id DESC');
        $query->execute([(string)$user['legacy_id']]);
        $history = $query->fetchAll(PDO::FETCH_ASSOC);
        $eventIds = array_filter(array_column($history, 'event_id'), function ($eventId) {
            return $eventId !== null && $eventId !== '';
        });
        $eventTitles = committee_volunteers_event_titles($pdo, $eventIds);
        foreach ($history as &$record) {
            $record['event_title'] = $eventTitles[(string)($record['event_id'] ?? '')] ?? '';
        }
        unset($record);
        out(['ok' => true, 'history' => $history]);
    } catch (Throwable $error) {
        error_log('Committee volunteer history failed: ' . $error->getMessage());
        out(['ok' => false, 'error' => 'Could not load volunteer history.'], 500);
    }
}

out(['ok' => false, 'error' => 'Unknown action.'], 400);

function committee_volunteer_require_member() {
    $user = current_user();
    if (!$user) out(['ok' => false, 'error' => 'Please log in to manage volunteer applications.'], 401);
    if (!empty($user['must_change_password'])) {
        out(['ok' => false, 'error' => 'Change your temporary password before applying.'], 403);
    }
    if ($user['legacy_id'] === null || (string)$user['legacy_id'] === '') {
        out(['ok' => false, 'error' => 'Link an active KK member profile to apply to volunteer.'], 403);
    }
    return $user;
}

function committee_volunteer_ensure_schema($pdo) {
    try {
        committee_volunteers_ensure_schema($pdo);
    } catch (Throwable $error) {
        error_log('Committee volunteer schema could not be prepared: ' . $error->getMessage());
        out(['ok' => false, 'error' => 'Volunteer applications are not available right now.'], 500);
    }
}
