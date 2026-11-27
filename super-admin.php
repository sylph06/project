<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/members_table.php';
require_once __DIR__ . '/role_store.php';

$superAdminUser = current_user();
if (!$superAdminUser) {
    header('Location: login.php');
    exit;
}
if (!empty($superAdminUser['must_change_password'])) {
    header('Location: change-password.php');
    exit;
}
if (!is_super_admin()) {
    header('Location: index.php');
    exit;
}

$pageError = '';
$accounts = [];
$roles = [];
$dataRows = [];
$maintenanceEnabled = false;
$maintenanceInvalid = false;
$selectedDataKey = isset($_GET['data_key']) && is_string($_GET['data_key'])
    ? substr($_GET['data_key'], 0, 100) : '';
$selectedDataRow = null;
$accountQuery = isset($_GET['account_query']) && is_string($_GET['account_query'])
    ? trim(substr($_GET['account_query'], 0, 254)) : '';
$flash = $_SESSION['super_admin_flash'] ?? null;
unset($_SESSION['super_admin_flash']);
$accountRoles = array_values(array_unique(array_merge(['KK Member'], APP_WRITE_ROLES)));
$positions = ['SK Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad', 'SK Vice Chairperson'];

try {
    $pdo = db();
    if ($accountQuery === '') {
        $accounts = $pdo->query('SELECT id, username, full_name, email, role, is_active, legacy_id
            FROM users ORDER BY full_name, username')->fetchAll(PDO::FETCH_ASSOC);
    } elseif (ctype_digit($accountQuery)) {
        $statement = $pdo->prepare('SELECT id, username, full_name, email, role, is_active, legacy_id
            FROM users WHERE id = ? OR LOWER(email) = LOWER(?) ORDER BY full_name, username');
        $statement->execute([(int)$accountQuery, $accountQuery]);
        $accounts = $statement->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $statement = $pdo->prepare('SELECT id, username, full_name, email, role, is_active, legacy_id
            FROM users WHERE LOWER(email) = LOWER(?) ORDER BY full_name, username');
        $statement->execute([$accountQuery]);
        $accounts = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    $roles = sk_role_rows($pdo);
    $dataRows = $pdo->query('SELECT data_key, data_value FROM app_data ORDER BY data_key')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dataRows as $dataRow) {
        if ($dataRow['data_key'] === 'maintenance_mode') {
            $maintenanceState = json_decode((string)$dataRow['data_value'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($maintenanceState)
                && is_bool($maintenanceState['enabled'] ?? null)) {
                $maintenanceEnabled = $maintenanceState['enabled'];
            } else {
                $maintenanceInvalid = true;
            }
        }
        if ($dataRow['data_key'] !== 'maintenance_mode') {
            if ($selectedDataKey === '' && json_decode((string)$dataRow['data_value']) !== null) {
                $selectedDataKey = (string)$dataRow['data_key'];
            }
            if ((string)$dataRow['data_key'] === $selectedDataKey) $selectedDataRow = $dataRow;
        }
    }
    if ($selectedDataRow === null && $selectedDataKey !== '') {
        $selectedDataKey = '';
    }
} catch (Throwable $error) {
    error_log('Admin panel data could not be loaded: ' . $error->getMessage());
    $pageError = 'Some Admin data could not be loaded. Check the application error log.';
}

$members = [];
if ($pageError === '') {
    try {
        if (USE_MEMBERS_TABLE) {
            $members = kk_members_array_from_table($pdo);
        } else {
            $memberRaw = $pdo->query("SELECT data_value FROM app_data WHERE data_key = 'kkMembers'")->fetchColumn();
            $decodedMembers = $memberRaw === false ? [] : json_decode((string)$memberRaw, true);
            if (!is_array($decodedMembers)) throw new RuntimeException('KK member data is invalid.');
            $members = $decodedMembers;
        }
    } catch (Throwable $error) {
        error_log('Admin member data could not be loaded: ' . $error->getMessage());
        $pageError = 'KK member records could not be loaded, so official-position overrides are unavailable.';
    }
}
if ($pageError !== '') http_response_code(500);

$membersById = [];
foreach ($members as $member) {
    if (is_array($member) && isset($member['id'])) $membersById[(string)$member['id']] = $member;
}
$memberName = function ($member) {
    if (!is_array($member)) return 'Unknown KK member';
    $name = trim(implode(' ', array_filter([
        $member['firstName'] ?? '',
        $member['middleName'] ?? '',
        $member['lastName'] ?? '',
        $member['suffix'] ?? '',
    ], function ($part) { return trim((string)$part) !== ''; })));
    return $name !== '' ? $name : (string)($member['name'] ?? 'Unnamed KK member');
};

$selectedDataJsonValid = false;
$selectedDataHash = '';
if ($selectedDataRow !== null) {
    json_decode((string)$selectedDataRow['data_value']);
    $selectedDataJsonValid = json_last_error() === JSON_ERROR_NONE;
    $selectedDataHash = hash('sha256', (string)$selectedDataRow['data_value']);
}
?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--ui-paper); color: var(--mem-ink); }
        .top-strip { height: 7px; background: var(--mem-brand); }
        .header { min-height: 70px; background: var(--ui-surface); border-bottom: 1px solid var(--ui-line); display: flex; align-items: center; justify-content: space-between; padding: 0 26px; gap: 14px; }
        .header-title { font-size: 20px; font-weight: 800; }
        .content { max-width: 1680px; margin: 0 auto; padding: 24px; }
        .intro, .admin-card { background: var(--ui-surface); border: 1px solid var(--ui-line); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); }
        .intro { padding: 20px; margin-bottom: 16px; }
        .intro h1 { margin: 0 0 5px; font-size: 24px; font-weight: 900; }
        .intro p { color: var(--ui-muted); margin: 0; }
        .admin-card { margin-bottom: 16px; overflow: hidden; }
        .admin-card-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid var(--ui-line); }
        .admin-card-header h2 { margin: 0; font-size: 17px; font-weight: 800; }
        .admin-card-body { padding: 18px; }
        .table-responsive { max-height: 560px; overflow: auto; }
        .table { margin: 0; }
        .table th { white-space: nowrap; background: var(--mem-brand-soft); color: var(--ui-muted); font-size: 12px; }
        .table td, .table th { padding: 11px; vertical-align: middle; }
        .json-editor { min-height: 360px; font-family: Consolas, "Courier New", monospace; font-size: 13px; white-space: pre; }
        .json-preview { max-height: 360px; min-height: 100px; overflow: auto; padding: 14px; background: var(--ui-paper); border: 1px solid var(--ui-line); border-radius: var(--ui-radius-sm); font: 13px/1.5 Consolas, "Courier New", monospace; white-space: pre; }
        .json-token-key { color: #1558c7; font-weight: 700; }
        .json-token-string { color: #26734d; }
        .json-token-number { color: #8a4ab3; }
        .json-token-literal { color: #c04c28; font-weight: 700; }
        .role-form { min-width: 820px; }
        .small-note { color: var(--ui-muted); font-size: 13px; }
        .footer-note { padding: 20px; text-align: center; color: var(--ui-muted); font-size: 13px; }
        @media (max-width: 767px) {
            .content { padding: 14px; }
            .header { padding: 0 16px; }
            .admin-card-header { align-items: flex-start; flex-direction: column; }
            .admin-card-body { padding: 14px; }
            .json-editor { min-height: 280px; }
        }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
</head>
<body>
<div class="top-strip"></div>
<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=20"></script>
<main class="main page-content">
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <div><div class="header-title">Admin</div><small>KK Youth Profiling System</small></div>
        </div>
        <div class="header-account-area">
            <span class="header-avatar"><?= e(uinit()) ?></span>
            <div class="d-none d-md-block header-identity">
                <div class="header-identity-name"><?= e(uname()) ?></div>
                <div class="header-identity-role">ADMIN</div>
            </div>
            <a href="logout.php" class="btn btn-sm header-logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </header>

    <div class="content">
        <section class="intro">
            <h1>System maintenance tools</h1>
            <p>Changes here bypass normal role-management restrictions and are recorded in the audit log.</p>
        </section>

        <?php if (is_array($flash)): ?>
            <div class="alert alert-<?= e($flash['type'] ?? 'info') ?>" role="status"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>
        <?php if ($pageError !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= e($pageError) ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2><i class="bi bi-person-gear me-2"></i>Login-role overrides</h2>
                <span class="small-note">Only unlinked login accounts can have their base role changed here.</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Account</th><th>Email</th><th>Current role</th><th>Status</th><th>Override</th></tr></thead>
                    <tbody>
                    <tr><td colspan="5">
                        <form method="get" action="super-admin.php" class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold" for="account_query">Search account by exact ID or email</label>
                                <input class="form-control form-control-sm" id="account_query" name="account_query" value="<?= e($accountQuery) ?>" maxlength="254" placeholder="User ID or email">
                            </div>
                            <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary" type="submit">Search</button></div>
                            <?php if ($accountQuery !== ''): ?><div class="col-md-2 d-grid"><a class="btn btn-sm btn-outline-secondary" href="super-admin.php">Clear</a></div><?php endif; ?>
                        </form>
                    </td></tr>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><strong><?= e($account['full_name'] ?: $account['username']) ?></strong><div class="small text-muted"><?= e($account['username']) ?> · ID <?= (int)$account['id'] ?></div></td>
                            <td><?= e($account['email'] ?: 'Not provisioned') ?></td>
                            <td><?= e($account['role']) ?><?= $account['legacy_id'] !== null ? '<div class="small text-muted">Effective role comes from SK position</div>' : '' ?></td>
                            <td><?= (int)$account['is_active'] === 1 ? 'Active' : 'Disabled' ?></td>
                            <td>
                                <?php if (strtolower((string)($account['email'] ?? '')) === SUPER_ADMIN_EMAIL): ?>
                                    <span class="small-note">Reserved Admin role</span>
                                <?php elseif ($account['legacy_id'] === null): ?>
                                    <form method="post" action="admin-actions.php" class="d-flex flex-wrap gap-2 align-items-center">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="override_role">
                                        <input type="hidden" name="account_id" value="<?= (int)$account['id'] ?>">
                                        <select class="form-select form-select-sm" name="role" aria-label="New role for <?= e($account['username']) ?>" required>
                                            <?php foreach ($accountRoles as $accountRole): ?>
                                                <option value="<?= e($accountRole) ?>"<?= $account['role'] === $accountRole ? ' selected' : '' ?>><?= e($accountRole) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save role</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small-note">Use the official-position override below.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$accounts): ?><tr><td colspan="5" class="text-center text-muted py-4">No matching login accounts were found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2><i class="bi bi-person-badge me-2"></i>Official-position overrides</h2>
                <span class="small-note">Overrides require an active KK member and one active role per member; seat limits are bypassed.</span>
            </div>
            <div class="admin-card-body border-bottom">
                <h3 class="h6 fw-bold">Create official assignment override</h3>
                <p class="small-note">Use when a normal seat limit prevents an authorized assignment. This does not bypass active-member or one-current-role checks.</p>
                <form method="post" action="admin-actions.php" class="row g-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create_position_override">
                    <div class="col-lg-3">
                        <label class="form-label small" for="new_member_id">Active KK member</label>
                        <select class="form-select form-select-sm" id="new_member_id" name="member_id" required>
                            <option value="">Choose a member</option>
                            <?php foreach ($members as $member): if (!is_array($member) || ($member['status'] ?? 'Active') !== 'Active' || !isset($member['id'])) continue; ?>
                                <option value="<?= e($member['id']) ?>"><?= e($memberName($member)) ?> · <?= e($member['id']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small" for="new_position">Position</label>
                        <select class="form-select form-select-sm" id="new_position" name="position" required>
                            <?php foreach ($positions as $position): ?><option value="<?= e($position) ?>"><?= e($position) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small" for="new_basis">Basis</label>
                        <select class="form-select form-select-sm" id="new_basis" name="basis" required>
                            <option value="">Choose basis</option><option value="elected">Elected</option><option value="appointed">Appointed</option>
                        </select>
                    </div>
                    <div class="col-lg-1">
                        <label class="form-label small" for="new_election_year">Election/appointment year</label>
                        <input class="form-control form-control-sm" id="new_election_year" name="election_year" inputmode="numeric" maxlength="4" required>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small" for="new_term_start">Term start</label>
                        <input class="form-control form-control-sm" type="date" id="new_term_start" name="term_start">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small" for="new_term_end">Term end</label>
                        <input class="form-control form-control-sm" type="date" id="new_term_end" name="term_end">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small" for="new_date_elected">Election/appointment date</label>
                        <input class="form-control form-control-sm" type="date" id="new_date_elected" name="date_elected" required>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-sm btn-primary w-100" type="submit"<?= count(array_filter($members, function ($member) { return is_array($member) && ($member['status'] ?? 'Active') === 'Active'; })) === 0 ? ' disabled' : '' ?>>Create override</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>KK member</th><th>Position</th><th>Status</th><th>Basis</th><th>Election year</th><th>Term</th><th>Save override</th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <?php
                        $member = $membersById[(string)($role['kkId'] ?? '')] ?? null;
                        $roleHash = hash('sha256', json_encode($role, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
                        ?>
                        <tr>
                            <td><strong><?= e($memberName($member)) ?></strong><div class="small text-muted">Member ID <?= e($role['kkId'] ?? '') ?> · Role ID <?= e($role['id'] ?? '') ?></div></td>
                            <td colspan="6">
                                <form method="post" action="admin-actions.php" class="role-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="override_position">
                                    <input type="hidden" name="role_id" value="<?= e($role['id'] ?? '') ?>">
                                    <input type="hidden" name="expected_hash" value="<?= e($roleHash) ?>">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label small" for="position-<?= e($role['id'] ?? '') ?>">Position</label>
                                            <select class="form-select form-select-sm" id="position-<?= e($role['id'] ?? '') ?>" name="position" required>
                                                <?php foreach ($positions as $position): ?>
                                                    <option value="<?= e($position) ?>"<?= ($role['position'] ?? '') === $position ? ' selected' : '' ?>><?= e($position) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small" for="status-<?= e($role['id'] ?? '') ?>">Status</label>
                                            <select class="form-select form-select-sm" id="status-<?= e($role['id'] ?? '') ?>" name="status">
                                                <?php foreach (['Active', 'Inactive', 'Archived'] as $status): ?>
                                                    <option value="<?= e($status) ?>"<?= ($role['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small" for="basis-<?= e($role['id'] ?? '') ?>">Basis</label>
                                            <select class="form-select form-select-sm" id="basis-<?= e($role['id'] ?? '') ?>" name="basis" required>
                                                <option value="" disabled<?= empty($role['basis']) ? ' selected' : '' ?>>Choose basis</option>
                                                <?php foreach (['elected', 'appointed'] as $basis): ?>
                                                    <option value="<?= e($basis) ?>"<?= ($role['basis'] ?? '') === $basis ? ' selected' : '' ?>><?= ucfirst($basis) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small" for="year-<?= e($role['id'] ?? '') ?>">Year</label>
                                            <input class="form-control form-control-sm" id="year-<?= e($role['id'] ?? '') ?>" name="election_year" inputmode="numeric" maxlength="4" value="<?= e($role['electionYear'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small" for="start-<?= e($role['id'] ?? '') ?>">Term start</label>
                                            <input class="form-control form-control-sm" type="date" id="start-<?= e($role['id'] ?? '') ?>" name="term_start" value="<?= e($role['termStart'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small" for="end-<?= e($role['id'] ?? '') ?>">Term end</label>
                                            <input class="form-control form-control-sm" type="date" id="end-<?= e($role['id'] ?? '') ?>" name="term_end" value="<?= e($role['termEnd'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small" for="elected-<?= e($role['id'] ?? '') ?>">Election/appointment date</label>
                                            <input class="form-control form-control-sm" type="date" id="elected-<?= e($role['id'] ?? '') ?>" name="date_elected" value="<?= e($role['dateElected'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-sm btn-outline-primary w-100" type="submit">Save override</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$roles): ?><tr><td colspan="7" class="text-center text-muted py-4">No SK role assignments were found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2><i class="bi bi-braces me-2"></i>App data JSON inspector/editor</h2>
                <span class="small-note">Edits affect live data. JSON syntax is checked; application-specific rules are not.</span>
            </div>
            <div class="admin-card-body">
                <form method="get" action="super-admin.php" class="row g-2 align-items-end mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" for="data_key">App data key</label>
                        <select class="form-select" id="data_key" name="data_key" required>
                            <option value="">Choose a record</option>
                            <?php foreach ($dataRows as $dataRow): if ($dataRow['data_key'] === 'maintenance_mode') continue; ?>
                                <option value="<?= e($dataRow['data_key']) ?>"<?= $selectedDataKey === $dataRow['data_key'] ? ' selected' : '' ?>><?= e($dataRow['data_key']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-grid"><button class="btn btn-outline-primary" type="submit">Inspect record</button></div>
                </form>
                <?php if ($selectedDataRow !== null): ?>
                    <?php if (!$selectedDataJsonValid): ?><div class="alert alert-warning" role="alert">This stored value is not valid JSON. Correct it before saving.</div><?php endif; ?>
                    <form method="post" action="admin-actions.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="save_app_data">
                        <input type="hidden" name="data_key" value="<?= e($selectedDataKey) ?>">
                        <input type="hidden" name="expected_hash" value="<?= e($selectedDataHash) ?>">
                        <label class="form-label fw-semibold" for="json_value"><?= e($selectedDataKey) ?> JSON value</label>
                        <textarea class="form-control json-editor mb-3" id="json_value" name="json_value" maxlength="2097152" spellcheck="false" required><?= e($selectedDataRow['data_value']) ?></textarea>
                        <div class="form-label fw-semibold">Syntax-highlighted preview</div>
                        <pre class="json-preview mb-3" id="json_preview" aria-live="polite"></pre>
                        <label class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_live_data" value="yes" required>
                            <span class="form-check-label">I understand this changes live application data.</span>
                        </label>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Validate and save JSON</button>
                        <span class="small-note ms-2">Maximum 2 MB. Saving requires valid JSON and checks for concurrent edits.</span>
                    </form>
                <?php else: ?>
                    <p class="text-muted mb-0">Select a stored record to inspect or edit its JSON value.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2><i class="bi bi-cone-striped me-2"></i>Site-wide maintenance mode</h2>
                <span class="badge text-bg-<?= $maintenanceEnabled ? 'warning' : 'success' ?>"><?= $maintenanceInvalid ? 'Invalid state' : ($maintenanceEnabled ? 'Enabled' : 'Disabled') ?></span>
            </div>
            <div class="admin-card-body">
                <p class="small-note">When enabled, PHP pages and API requests return 503 for everyone except the Admin. Login and password-change routes remain available. Static files are served directly by the web server and are not affected.</p>
                <?php if ($maintenanceInvalid): ?><div class="alert alert-warning" role="alert">The stored maintenance value is invalid. Saving either option below will repair it.</div><?php endif; ?>
                <form method="post" action="admin-actions.php" class="d-flex flex-wrap gap-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_maintenance">
                    <div>
                        <label class="form-label fw-semibold" for="maintenance_enabled">Mode</label>
                        <select class="form-select" id="maintenance_enabled" name="enabled">
                            <option value="0"<?= !$maintenanceEnabled ? ' selected' : '' ?>>Disabled</option>
                            <option value="1"<?= $maintenanceEnabled ? ' selected' : '' ?>>Enabled</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Save maintenance mode</button>
                </form>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <h2><i class="bi bi-journal-text me-2"></i>Audit history</h2>
                <a class="btn btn-outline-primary btn-sm" href="audit-log.php"><i class="bi bi-box-arrow-up-right me-1"></i>Open full audit log</a>
            </div>
            <div class="admin-card-body">
                <p class="mb-0">The full paginated viewer includes system activity, device details, and change records.</p>
            </div>
        </section>

        <div class="footer-note">KK Youth Profiling System · Admin maintenance tools</div>
    </div>
</main>
<script>
(function () {
    var input = document.getElementById('json_value');
    var preview = document.getElementById('json_preview');
    if (!input || !preview) return;

    function escapeHtml(value) {
        return value.replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function highlight() {
        var source = input.value;
        var tokenPattern = /"(?:\\.|[^"\\])*"|\b(?:true|false|null)\b|-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?/g;
        var result = '';
        var cursor = 0;
        var match;
        while ((match = tokenPattern.exec(source)) !== null) {
            result += escapeHtml(source.slice(cursor, match.index));
            var token = match[0];
            var className = 'json-token-string';
            if (token.charAt(0) === '"') {
                className = /^\s*:/.test(source.slice(match.index + token.length)) ? 'json-token-key' : 'json-token-string';
            } else if (token === 'true' || token === 'false' || token === 'null') {
                className = 'json-token-literal';
            } else {
                className = 'json-token-number';
            }
            result += '<span class="' + className + '">' + escapeHtml(token) + '</span>';
            cursor = match.index + token.length;
        }
        preview.innerHTML = result + escapeHtml(source.slice(cursor));
    }

    input.addEventListener('input', highlight);
    highlight();
}());
</script>
</body>
</html>
