<?php

$__user = current_user();
$__official = $__user !== null && ($__user['role'] ?? '') !== 'KK Member';
$__public = is_limited_viewer();

$__publicKeys = ['skAnnouncements'];

if (USE_MEMBERS_TABLE) require_once __DIR__ . '/members_table.php';

/* AUTOMATIC ARCHIVE: a KK member who is 31 or older is archived by the server.
   Runs on every page load (officers and public viewers), so nobody has to click anything. */
(function () {
    if (USE_MEMBERS_TABLE) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $eligible = $pdo->query("SELECT legacy_id FROM members
                WHERE status <> 'Archived' AND birthdate IS NOT NULL
                  AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) > 30
                FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            if (!$eligible) {
                $pdo->commit();
                return;
            }

            $now = gmdate('Y-m-d\TH:i:s.000\Z');
            $sqlNow = gmdate('Y-m-d H:i:s');
            $reason = 'Automatically archived – exceeded youth age eligibility.';
            $archive = $pdo->prepare("UPDATE members
                SET status = 'Archived', archive_reason = ?, archived_at = ?, archived_by = 'System',
                    archive_type = 'auto',
                    history = JSON_ARRAY_APPEND(
                        CASE WHEN JSON_VALID(history) AND JSON_TYPE(history) = 'ARRAY'
                             THEN history ELSE JSON_ARRAY() END,
                        '$',
                        JSON_OBJECT('at', ?, 'action', 'Auto-archived', 'by', 'System', 'note', ?)
                    ),
                    updated_by = 'system'
                WHERE status <> 'Archived' AND birthdate IS NOT NULL
                  AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) > 30");
            $archive->execute([$reason, $sqlNow, $now, $reason]);

            $changed = array_fill_keys(array_map('strval', $eligible), true);
            $requestRow = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkEditRequests' FOR UPDATE")->fetchColumn();
            $requests = $requestRow === false ? null : json_decode($requestRow, true);
            if (is_array($requests)) {
                $dirty = false;
                foreach ($requests as $index => $request) {
                    if (is_array($request) && ($request['status'] ?? '') === 'Edit Request'
                        && isset($changed[(string)($request['memberId'] ?? '')])) {
                        $requests[$index]['status'] = 'Rejected';
                        $requests[$index]['reviewedAt'] = $now;
                        $requests[$index]['reviewedBy'] = 'System';
                        $requests[$index]['remarks'] = 'Profile was archived.';
                        $dirty = true;
                    }
                }
                if ($dirty) {
                    $saveRequests = $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?');
                    $saveRequests->execute([
                        json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                        'system',
                        'kkEditRequests',
                    ]);
                }
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $ex;
        }
        return;
    }

    $pdo = null;
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $memberRow = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers' FOR UPDATE")->fetchColumn();
        if ($memberRow === false) {
            $pdo->commit();
            return;
        }
        $members = json_decode((string)$memberRow, true);
        if (!is_array($members)) throw new RuntimeException('KK member data is invalid.');
        $today = new DateTime('today');
        $now = gmdate('Y-m-d\TH:i:s.000\Z');
        $reason = 'Automatically archived – exceeded youth age eligibility.';
        $changed = [];
        foreach ($members as $i => $m) {
            if (!is_array($m) || ($m['status'] ?? '') === 'Archived' || empty($m['birthDate'])) continue;
            try { $age = (new DateTime($m['birthDate']))->diff($today)->y; } catch (Exception $ex) { continue; }
            if ($age <= 30) continue;
            $m['status'] = 'Archived';
            $m['archiveReason'] = $reason;
            $m['archivedAt'] = $now;
            $m['archivedBy'] = 'System';
            $m['archiveType'] = 'auto';
            if (!isset($m['history']) || !is_array($m['history'])) $m['history'] = [];
            $m['history'][] = ['at' => $now, 'action' => 'Auto-archived', 'by' => 'System', 'note' => $reason];
            $members[$i] = $m;
            if (!empty($m['id'])) $changed[(string)$m['id']] = true;
        }
        if (!$changed) {
            $pdo->commit();
            return;
        }

        $save = $pdo->prepare('UPDATE app_data SET data_value = ?, updated_by = ? WHERE data_key = ?');
        $save->execute([
            json_encode($members, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
            'system',
            'kkMembers',
        ]);
        $requestRow = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkEditRequests' FOR UPDATE")->fetchColumn();
        if ($requestRow !== false) {
            $requests = json_decode((string)$requestRow, true);
            if (!is_array($requests)) throw new RuntimeException('KK edit request data is invalid.');
            $dirty = false;
            foreach ($requests as $index => $request) {
                if (is_array($request) && ($request['status'] ?? '') === 'Edit Request'
                    && isset($changed[(string)($request['memberId'] ?? '')])) {
                    $requests[$index]['status'] = 'Rejected';
                    $requests[$index]['reviewedAt'] = $now;
                    $requests[$index]['reviewedBy'] = 'System';
                    $requests[$index]['remarks'] = 'Profile was archived.';
                    $dirty = true;
                }
            }
            if ($dirty) {
                $save->execute([
                    json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                    'system',
                    'kkEditRequests',
                ]);
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        error_log('Automatic KK member archival failed: ' . $ex->getMessage());
    }
})();

$__data = [];
if ($__official || $__public) {
    foreach (db()->query('SELECT data_key, data_value FROM app_data') as $__row) {
        if (USE_MEMBERS_TABLE && $__row['data_key'] === 'kkMembers') continue;
        if (USE_SK_ROLES_TABLE && $__row['data_key'] === 'skRoles') continue;
        if ($__official || in_array($__row['data_key'], $__publicKeys, true)) {
            $__data[$__row['data_key']] = $__row['data_value'];
        }
    }
    if (USE_MEMBERS_TABLE && !$__public) {
        $__data['kkMembers'] = json_encode(
            kk_members_array_from_table(db()),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
    if (USE_SK_ROLES_TABLE && !$__public) {
        $__roles = db()->query('SELECT role_data FROM sk_roles ORDER BY role_code')->fetchAll(PDO::FETCH_COLUMN);
        $__data['skRoles'] = json_encode(
            array_map(function ($role) { return json_decode($role, true); }, $__roles),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
if ($__public) {
    $__decode = function ($key) use (&$__data) {
        $v = isset($__data[$key]) ? json_decode($__data[$key], true) : [];
        return is_array($v) ? $v : [];
    };
    if (isset($__data['skAnnouncements'])) {
        $__keep = [];
        foreach ($__decode('skAnnouncements') as $__a) {
            if (($__a['publishState'] ?? '') === 'Published' && (empty($__a['publishDate']) || $__a['publishDate'] <= date('Y-m-d'))) {
                $__keep[] = $__a;
            }
        }
        $__data['skAnnouncements'] = json_encode($__keep);
    }
}
if (isset($__data['skRoles']) && !can_view_background_history()) {
    $__roles = json_decode($__data['skRoles'], true);
    if (is_array($__roles)) {
        foreach ($__roles as $__i => $__r) {
            if (is_array($__r)) unset($__roles[$__i]['history']);
        }
        $__data['skRoles'] = json_encode(array_values($__roles));
    }
}
$__flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
?>
<?php if (!$__public): ?>
<link rel="stylesheet" href="vendor/adminlte/css/adminlte.min.css">
<script src="vendor/adminlte/js/adminlte.min.js" defer></script>
<?php endif; ?>
<script>
document.documentElement.classList.add('sidebar-expand-lg');
window.__DB = <?= json_encode((object)$__data, $__flags) ?>;
window.SK_CSRF_TOKEN = <?= json_encode(csrf_token(), $__flags) ?>;
window.SK_PUBLIC = <?= $__public ? 'true' : 'false' ?>;
window.SK_OFFICER = <?= json_encode($__official ? uname() . ' (' . urole() . ')' : '', $__flags) ?>;
window.SK_CAN_MANAGE_ROLES = <?= can_manage_sk_roles() ? 'true' : 'false' ?>;
window.USE_MEMBERS_TABLE = <?= USE_MEMBERS_TABLE ? 'true' : 'false' ?>;
window.USE_SK_ROLES_TABLE = <?= USE_SK_ROLES_TABLE ? 'true' : 'false' ?>;
</script>
<script src="storage.js?v=<?= filemtime(__DIR__ . '/storage.js') ?>"></script>