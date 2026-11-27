<?php
/* =========================================================
   api/committee-preferences.php — SK officials submit/manage their committee preferences
   ========================================================= */
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$user = require_officer();
if (!in_array($action, ['submit_preferences', 'get_preferences', 'get_all_preferences'], true)) {
    out(['ok' => false, 'error' => 'Unknown action.'], 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Current SK roles (active only) for validation
    $roles = sk_role_rows($pdo, true);
    $today = date('Y-m-d');
    $activeRoles = [];
    foreach ($roles as $role) {
        if (($role['status'] ?? '') !== 'Active') continue;
        if (!empty($role['termStart']) && $role['termStart'] > $today) continue;
        if (!empty($role['termEnd']) && $role['termEnd'] < $today) continue;
        $activeRoles[$role['id']] = $role;
    }

    // Current user's role
    $myRoleId = null;
    $myRole = null;
    foreach ($activeRoles as $id => $role) {
        if ((string)($role['kkId'] ?? '') === (string)$user['legacy_id']) {
            $myRoleId = $id;
            $myRole = $role;
            break;
        }
    }

    // Load existing preferences
    $prefRaw = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'skCommitteePreferences'")->fetchColumn();
    $allPreferences = $prefRaw === false ? [] : json_decode((string)$prefRaw, true);
    if (!is_array($allPreferences)) $allPreferences = [];

    if ($action === 'submit_preferences') {
        // Only SK officials with active roles can submit preferences
        if (!$myRole) {
            throw new RuntimeException('Only active SK officials can submit committee preferences.');
        }
        // Chairperson and Secretary don't submit preferences (they assign)
        if (in_array($myRole['position'] ?? '', ['SK Chairperson', 'SK Chairman', 'SK Secretary'], true)) {
            throw new RuntimeException('Committee preferences are submitted by Kagawads and other officials. Chairperson/Secretary manage assignments.');
        }

        $preferences = $in['preferences'] ?? null;
        if (!is_array($preferences)) throw new RuntimeException('Preferences must be an array of committee names in order of priority.');

        // Validate committees exist
        $committees = sk_committee_names($pdo);
        foreach ($preferences as $pref) {
            if (!is_string($pref) || !in_array($pref, $committees, true)) {
                throw new RuntimeException('Invalid committee: ' . $pref);
            }
        }

        // Check for duplicates
        if (count($preferences) !== count(array_unique($preferences))) {
            throw new RuntimeException('Duplicate committee in preferences.');
        }

        // Max 3 preferences
        if (count($preferences) > 3) {
            throw new RuntimeException('Maximum 3 committee preferences allowed.');
        }

        $oldPrefs = $allPreferences[$myRoleId] ?? [];
        $allPreferences[$myRoleId] = $preferences;

        $pdo->prepare("INSERT INTO app_data (data_key, data_value, updated_by)
            VALUES ('skCommitteePreferences', ?, ?)
            ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_by = VALUES(updated_by)")
            ->execute([json_encode($allPreferences, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $user['username']]);

        sk_audit($pdo, $user, (string)$user['legacy_id'], 'committee_preferences_submitted', $oldPrefs, $preferences);
        $pdo->commit();
        out(['ok' => true, 'preferences' => $preferences]);
    }

    if ($action === 'get_preferences') {
        // Return current user's preferences
        out(['ok' => true, 'preferences' => $allPreferences[$myRoleId] ?? []]);
    }

    if ($action === 'get_all_preferences') {
        // Chairperson/Secretary only - see all preferences with member info
        if (!in_array($user['role'] ?? '', ['SK Chairperson', 'SK Chairman', 'SK Secretary'], true)) {
            out(['ok' => false, 'error' => 'Only Chairperson/Secretary can view all preferences.'], 403);
        }

        // Build response with member details
        $committees = sk_committee_names($pdo);
        $memberNames = [];
        foreach ($activeRoles as $id => $role) {
            $member = sk_member_for_role_change($pdo, (string)$role['kkId']);
            if ($member) {
                $memberNames[$id] = $member['full_name'] ?? $member['first_name'] ?? $role['kkId'];
            }
        }

        $result = [];
        foreach ($activeRoles as $id => $role) {
            // Skip Chairperson/Secretary - they don't submit preferences
            if (in_array($role['position'] ?? '', ['SK Chairperson', 'SK Chairman', 'SK Secretary'], true)) continue;

            $prefs = $allPreferences[$id] ?? [];
            $result[] = [
                'role_id' => $id,
                'member_id' => $role['kkId'],
                'member_name' => $memberNames[$id] ?? $role['kkId'],
                'position' => $role['position'],
                'preferences' => $prefs,
            ];
        }
        out(['ok' => true, 'preferences' => $result, 'committees' => $committees]);
    }

} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['ok' => false, 'error' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Committee preferences failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not process preferences.'], 500);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Committee preferences failed: ' . $ex->getMessage());
    out(['ok' => false, 'error' => 'Could not process preferences.'], 500);
}