<?php
/* =========================================================
   api/committees.php — manage official committee names
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();
if ($action !== 'committee_save') out(['ok' => false, 'error' => 'Unknown action.'], 400);
if (!can_manage_sk_roles()) out(['ok' => false, 'error' => 'Only the SK Chairman or SK Secretary can manage committees.'], 403);

$name = trim((string)($in['name'] ?? ''));
$original = $in['original'] ?? null;
$expected = $in['expected'] ?? null;
if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !is_array($expected)
    || ($original !== null && !is_string($original))) {
    out(['ok' => false, 'error' => 'Enter a committee name between 2 and 100 characters.'], 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $defaults = json_encode(sk_default_committees(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
        VALUES ('skCommittees', ?, ?) ON DUPLICATE KEY UPDATE data_key = VALUES(data_key)")
        ->execute([$defaults, $user['username']]);
    $committees = sk_committee_names($pdo, true);
    if (!sk_role_equal($expected, $committees)) {
        throw new DomainException('The committee list changed in another session. Reload and try again.');
    }

    $targetIndex = null;
    if ($original !== null) {
        foreach ($committees as $index => $committee) {
            if ($committee === $original) {
                $targetIndex = $index;
                break;
            }
        }
        if ($targetIndex === null) throw new DomainException('That committee no longer exists.');
    } elseif (count($committees) >= 50) {
        throw new RuntimeException('A maximum of 50 committees is allowed.');
    }

    foreach ($committees as $index => $committee) {
        if ($index !== $targetIndex && mb_strtolower($committee) === mb_strtolower($name)) {
            throw new RuntimeException('A committee with that name already exists.');
        }
    }

    $roles = sk_role_rows($pdo, true);
    if ($targetIndex === null) {
        $committees[] = $name;
    } else {
        $oldName = $committees[$targetIndex];
        $committees[$targetIndex] = $name;
        if ($oldName !== $name) {
            foreach ($roles as $index => $role) {
                if (($role['committee'] ?? null) !== $oldName) continue;
                $oldRole = $role;
                $roles[$index]['committee'] = $name;
                if (!isset($roles[$index]['history']) || !is_array($roles[$index]['history'])) {
                    $roles[$index]['history'] = [];
                }
                $roles[$index]['history'][] = [
                    'at' => gmdate('Y-m-d\TH:i:s.000\Z'),
                    'action' => 'Committee renamed',
                    'by' => $user['username'],
                    'note' => $oldName . ' renamed to ' . $name . '.',
                ];
                sk_role_write($pdo, $roles[$index], $user['username']);
                sk_audit($pdo, $user, (string)($role['kkId'] ?? ''), 'committee_renamed', $oldRole, $roles[$index]);
            }
        }
    }

    $pdo->prepare("UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = 'skCommittees'")
        ->execute([json_encode($committees, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), $user['username']]);
    $pdo->commit();
    out(['ok' => true, 'committees' => $committees, 'roles' => array_values($roles)]);
} catch (DomainException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 409);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the committee.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SK committee update failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not save the committee.'], 500);
}
