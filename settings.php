<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backup_restore.php';
require_once __DIR__ . '/google_auth.php';
$settingsUser = current_user();
$googleAuthenticatedAt = (int)($_SESSION['google_authenticated_at'] ?? 0);
$googlePasswordRecoveryMode = $settingsUser !== null
    && ($settingsUser['role'] ?? '') === 'KK Member'
    && (int)($_SESSION['google_authenticated_user_id'] ?? 0) === (int)$settingsUser['id']
    && $googleAuthenticatedAt >= time() - 600
    && $googleAuthenticatedAt <= time() + 60;
if (!$googlePasswordRecoveryMode) require_viewer();
$passwordResetNotice = $_SESSION['password_reset_notice'] ?? null;
unset($_SESSION['password_reset_notice']);
$canManageBackups = can_manage_sk_roles();
$canManageSKPermissions = can_manage_sk_roles();
$memberGoogleLink = null;
if (($settingsUser['role'] ?? '') === 'KK Member' && google_auth_account_is_member_linked($settingsUser)) {
    $memberId = (string)$settingsUser['legacy_id'];
    $member = sk_member_for_role_change(db(), $memberId);
    $memberGoogleLink = [
        'email' => trim((string)($member['email'] ?? '')),
        'linkedEmail' => null,
        'canBind' => false,
        'issue' => null,
    ];
    if (!google_oauth_configured()) {
        $memberGoogleLink['issue'] = 'Google sign-in is not configured. Contact the SK administrator.';
    } elseif (!google_oauth_schema_ready()) {
        $memberGoogleLink['issue'] = 'Google account binding is not ready yet. Contact the SK administrator.';
    } else {
        $linkQuery = db()->prepare('SELECT google_email FROM google_account_links WHERE user_id = ?');
        $linkQuery->execute([(int)$settingsUser['id']]);
        $linkedEmail = $linkQuery->fetchColumn();
        if ($linkedEmail !== false) {
            $memberGoogleLink['linkedEmail'] = (string)$linkedEmail;
        } elseif (!$member || ($member['status'] ?? 'Active') !== 'Active') {
            $memberGoogleLink['issue'] = 'Only active KK member profiles can bind a Google account.';
        } else {
            $memberGoogleLink['canBind'] = true;
        }
    }
}
if ($canManageBackups && empty($_SESSION['backup_restore_csrf'])) {
    $_SESSION['backup_restore_csrf'] = bin2hex(random_bytes(32));
}
$latestSafetyBackup = $canManageBackups ? kk_backup_latest_safety(db()) : null;
?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Settings</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">

    <style>
        :root {
            --kk-primary:var(--bs-primary);
            --black:var(--bs-body-color);
            --cream:var(--bs-tertiary-bg);
            --line:var(--bs-border-color);
            --muted:var(--bs-secondary-color);
        }

        * { box-sizing:border-box; }

        body {
            margin:0;
            font-family:Arial,sans-serif;
            background:var(--cream);
            color:var(--bs-body-color);
        }

        .top-strip {
            height:7px;
            background:var(--bs-primary);
        }

        .header {
            height:70px;
            background:white;
            border-bottom:1px solid var(--line);
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:0 30px;
        }

        .header-title {
            font-weight:bold;
            font-size:20px;
        }

        .header small {
            color:var(--muted);
            font-size:14px;
        }

        .user-area {
            display:flex;
            align-items:center;
            gap:10px;
        }

        .avatar {
            width:38px;
            height:38px;
            background:var(--bs-primary);
            border-radius:50%;
            display:flex;
            justify-content:center;
            align-items:center;
            font-weight:bold;
        }

        .content {
            padding: clamp(16px, 3vw, 32px);
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero {
            background: linear-gradient(135deg, var(--bs-primary) 0%, #15459b 100%);
            border-radius: 18px;
            padding: 24px clamp(20px, 3vw, 32px);
            margin-bottom: 25px;
            color: white;
            box-shadow: 0 10px 24px rgba(21, 69, 155, .15);
        }

        .hero h1 {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            color: white;
        }

        .hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
            margin: 0;
            max-width: 650px;
        }

        .card-box {
            background: var(--ui-surface, white);
            border: 1px solid var(--line);
            border-radius: 16px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: var(--ui-shadow, 0 4px 20px rgba(0,0,0,.04));
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
            font-weight: 700;
            background: var(--ui-surface, white);
            color: var(--bs-body-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
            flex: 1;
        }

        .stat {
            background:white;
            border:1px solid var(--line);
            border-radius:13px;
            padding:20px;
        }

        .stat i {
            display:flex;
            width:42px;
            height:42px;
            background:var(--bs-primary);
            border-radius:9px;
            align-items:center;
            justify-content:center;
            font-size:23px;
            margin-bottom:12px;
        }

        .number {
            font-size:29px;
            font-weight:800;
        }

        .label {
            color:var(--muted);
            font-size:14px;
        }

        .table {
            font-size:14px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table th {
            background:var(--bs-primary-text-emphasis);
            color:white;
            font-size:13px;
            padding:13px;
            white-space: nowrap;
        }

        .table td {
            padding:13px;
            vertical-align:middle;
        }

        .progress {
            height:8px;
            background:var(--bs-border-color);
        }

        .progress-bar {
            background:var(--bs-primary);
        }

        .footer-note {
            text-align:center;
            color:var(--muted);
            font-size:13px;
            padding:25px;
        }

        .stat i {
            width:42px;
            height:42px;
            font-size:20px;
        }

        .number {
            font-size: clamp(1.75rem, 2.5vw, 2rem);
            font-weight:800;
        }

        .label {
            color:var(--muted);
            font-size:0.9rem;
        }

        /* Mobile/tablet responsive */
        @media(max-width:991px) {
            .content { padding:16px; }
            .hero { padding: 18px 20px; }
            .hero h1 { font-size: 1.5rem; }
            .card-header { padding: 14px 16px; font-size: 1rem; }
            .card-body { padding: 16px; }
            .stat { padding: 16px; }
            .stat i { width: 38px; height: 38px; font-size: 18px; }
        }

        @media(max-width:576px) {
            .content { padding: 12px; }
            .header { padding: 0 16px; height: 64px; }
            .header-title { font-size: 1rem; }
            .hero h1 { font-size: 1.35rem; }
            .card-header { font-size: 0.95rem; }
            .card-body { padding: 14px; }
            .stat { padding: 14px; }
            .stat i { width: 36px; height: 36px; font-size: 17px; }
            .number { font-size: 1.5rem; }
        }
    </style>
<?php include __DIR__ . '/init.php'; ?>
</head>

<body>

<div class="top-strip"></div>

<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=18"></script>

<main class="main page-content">

<header class="header">

    <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
        <div class="header-title">Settings</div>
        <small>KK Youth Profiling System</small>
    </div></div>

    <div class="header-account-area">
        <div class="header-avatar"><?= e(uinit()) ?></div>
        <div class="d-none d-md-block header-identity">
            <div class="header-identity-name"><?= e(uname()) ?></div>
            <div class="header-identity-role"><?= e(strtoupper(urole())) ?></div>
        </div>

        <a href="<?= is_public() ? 'login.php' : 'logout.php' ?>" class="btn btn-sm header-logout">
            <i class="bi bi-box-arrow-right me-1"></i>
            <?= is_public() ? 'Official Login' : 'Logout' ?>
        </a>

    </div>

</header>

<div class="content">

    <section class="hero hero-primary">
        <h1>Settings</h1>
        <p><?= is_public() ? 'Choose how the system looks for you. You are viewing as a public viewer.' : 'Your account and display settings.' ?></p>
    </section>

    <div id="msg"></div>
    <?php if (is_string($passwordResetNotice) && $passwordResetNotice !== ''): ?>
        <div class="alert alert-warning" role="status"><?= e($passwordResetNotice) ?></div>
    <?php endif; ?>

    <div class="row g-3">

        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-person-circle me-2"></i>Account</div>
                <div class="card-body">
                    <?php if (current_user()): $u = current_user(); ?>
                        <div class="mb-1"><span class="label">Name</span><div class="fw-bold"><?= e($u['name']) ?></div></div>
                        <div class="mb-1"><span class="label">Position</span><div class="fw-bold"><?= e($u['role']) ?></div></div>
                        <div class="mb-3"><span class="label">Username</span><div class="fw-bold"><?= e($u['username']) ?></div></div>
                    <?php else: ?>
                        <p class="mb-2">You are using the system as a <b>Public Viewer</b> (view only). To register as KK youth and create an account, visit the SK Office at the Barangay Hall in Barangay Sikat, Alfonso, Cavite.</p>
                        <a href="registration.php" class="btn btn-primary btn-sm me-1"><i class="bi bi-person-plus-fill me-1"></i>KK Youth Registration</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-type me-2"></i>Display</div>
                <div class="card-body">
                    <label class="form-label fw-bold" for="textSize">Text size</label>
                    <select id="textSize" class="form-select mb-2">
                        <option value="small">Small</option>
                        <option value="normal">Normal</option>
                        <option value="large">Large</option>
                    </select>
                    <div class="label">Saved on this device only. The page reloads to apply it.</div>
                </div>
            </div>
        </div>

        <?php if (current_user()): ?>
        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-key-fill me-2"></i>Change Password</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label" for="curPass">Current password</label>
                        <input type="password" id="curPass" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="newPass">New password <small class="text-muted">(8-100 characters)</small></label>
                        <input type="password" id="newPass" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="newPass2">Confirm new password</label>
                        <input type="password" id="newPass2" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="button" class="btn btn-primary" id="savePassBtn"><i class="bi bi-check-circle me-1"></i>Save password</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($googlePasswordRecoveryMode): ?>
        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Set Password After Google Sign-In</div>
                <div class="card-body">
                    <p class="small text-muted">You recently verified your bound Google account. Set or replace your password without entering the forgotten password.</p>
                    <label class="form-label" for="googleNewPass">New password (8-100 characters)</label>
                    <input type="password" id="googleNewPass" class="form-control mb-2" autocomplete="new-password" minlength="8" maxlength="100">
                    <label class="form-label" for="googleNewPass2">Confirm new password</label>
                    <input type="password" id="googleNewPass2" class="form-control mb-3" autocomplete="new-password" minlength="8" maxlength="100">
                    <button type="button" class="btn btn-primary" id="saveGooglePassBtn">Set password</button>
                    <div id="googlePasswordStatus" class="small mt-3" aria-live="polite"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($memberGoogleLink !== null): ?>
        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-google me-2"></i>Google Account</div>
                <div class="card-body">
                    <?php if ($memberGoogleLink['linkedEmail'] !== null): ?>
                        <p class="mb-1">Your KK account is bound to:</p>
                        <div class="fw-bold"><?= e($memberGoogleLink['linkedEmail']) ?></div>
                        <p class="small text-muted mt-2 mb-0">Use this Google account when signing in.</p>
                    <?php elseif ($memberGoogleLink['canBind']): ?>
                        <p class="mb-2">Bind a verified Google account to use it for sign-in.</p>
                        <div class="small mb-3">
                            Email on your KK profile:
                            <strong><?= $memberGoogleLink['email'] !== '' ? e($memberGoogleLink['email']) : 'Not set' ?></strong>
                        </div>
                        <button type="button" class="btn btn-primary" id="bindGoogleBtn">
                            <i class="bi bi-link-45deg me-1"></i>Bind Google Account
                        </button>
                        <div id="googleBindStatus" class="small mt-3" aria-live="polite"></div>
                        <div id="googleEmailMismatch" class="alert alert-warning mt-3 mb-0 d-none" role="alert">
                            <p id="googleEmailMismatchSummary" class="small mb-2"></p>
                            <label class="form-label fw-semibold" for="googleBindPassword">Current password</label>
                            <input type="password" id="googleBindPassword" class="form-control mb-2" autocomplete="current-password">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-warning btn-sm" id="useGoogleEmailBtn">Use this Google email instead</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelGoogleEmailChangeBtn">Cancel</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0" role="status"><?= e($memberGoogleLink['issue']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canManageSKPermissions): ?>
        <div class="col-12">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-shield-check me-2"></i>SK Officials &amp; Delegated Permissions</div>
                <div class="card-body">
                    <p class="small text-muted">Grant or revoke specific tasks for active officials. Permissions never include role management, permission management, deletions, or changes to the SK Chairperson/Secretary accounts.</p>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="permissionOfficial">Official</label>
                            <select class="form-select" id="permissionOfficial"><option value="">Loading officials...</option></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="permissionPreset">Preset</label>
                            <select class="form-select" id="permissionPreset">
                                <option value="">Choose a preset</option>
                                <option value="attendance">Attendance helper</option>
                                <option value="records">Records helper</option>
                                <option value="account">Account helper</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="permissionExpiresAt">Expiry date</label>
                            <input class="form-control" id="permissionExpiresAt" type="date">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="button" class="btn btn-primary" id="grantPermissionsBtn">Grant selected</button>
                        </div>
                    </div>
                    <div class="row g-2 mt-2" id="permissionOptions">
                        <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" value="record_attendance"><span class="form-check-label">Record attendance</span></label></div>
                        <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" value="edit_members"><span class="form-check-label">Edit member records</span></label></div>
                        <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" value="manage_projects"><span class="form-check-label">Manage projects</span></label></div>
                        <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" value="unbind_google"><span class="form-check-label">Unbind Google accounts</span></label></div>
                        <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" value="reset_member_password"><span class="form-check-label">Reset KK member passwords</span></label></div>
                        <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" value="view_audit_log"><span class="form-check-label">View audit log</span></label></div>
                    </div>
                    <div id="permissionStatus" class="small mt-3" aria-live="polite"></div>
                    <h2 class="h6 fw-bold mt-4">Permission grants</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Official</th><th>Permission</th><th>Granted</th><th>Expires</th><th>Status</th><th></th></tr></thead>
                            <tbody id="permissionGrants"><tr><td colspan="6" class="text-muted">Loading grants...</td></tr></tbody>
                        </table>
                    </div>
                    <div class="border-top mt-4 pt-3">
                        <h2 class="h6 fw-bold">Emergency official recovery code</h2>
                        <p class="small text-muted">Generate once, then store the code securely offline. It cannot be displayed or replaced after generation and can reset one SK Chairperson/Secretary account if both officials are locked out.</p>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="generateRecoveryCodeBtn">Generate one-time code</button>
                        <div id="recoveryCodeStatus" class="small mt-2" aria-live="polite"></div>
                        <div id="recoveryCodeResult" class="alert alert-warning mt-2 d-none" role="status"></div>
                    </div>
                    <div class="border-top mt-4 pt-3">
                        <h2 class="h6 fw-bold">Official cross-reset</h2>
                        <p class="small text-muted">The SK Chairperson can reset the Secretary, and the Secretary can reset the Chairperson. Self-reset and other official-account changes are not allowed here.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <select class="form-select" id="officialResetTarget" style="max-width:420px">
                                <option value="">Loading counterpart account...</option>
                            </select>
                            <button type="button" class="btn btn-outline-danger" id="resetOfficialCounterpartBtn">Reset selected official</button>
                        </div>
                        <div id="officialResetStatus" class="small mt-2" aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canManageBackups): ?>
        <div class="col-12">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-shield-lock-fill me-2"></i>Backup &amp; Restore</div>
                <div class="card-body">
                    <p class="mb-3">Download a backup of operational KK and SK records. Passwords, user accounts, and server configuration are not included.</p>
                    <a class="btn btn-outline-primary mb-3" href="backup.php?action=download">
                        <i class="bi bi-download me-1"></i>Download backup
                    </a>

                    <div class="alert alert-warning small" role="note">
                        Restoring replaces the current operational records with the contents of the selected backup.
                        A safety backup of the current records is saved automatically before restore. User accounts and passwords are not changed.
                    </div>

                    <form id="backupRestoreForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['backup_restore_csrf']) ?>">
                        <label class="form-label fw-bold" for="backupFile">Backup file</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input class="form-control flex-grow-1" style="max-width:520px" type="file" id="backupFile" name="backup_file" accept=".json,application/json" required>
                            <button type="submit" class="btn btn-danger" id="restoreBackupBtn">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Restore backup
                            </button>
                        </div>
                    </form>

                    <div id="backupRestoreMessage" class="mt-3" aria-live="polite"></div>
                    <div id="safetyBackupLink" class="small mt-3">
                        <?php if ($latestSafetyBackup): ?>
                            Latest pre-restore safety backup:
                            <a href="backup.php?action=download-safety&amp;id=<?= (int)$latestSafetyBackup['id'] ?>">
                                Download from <?= e($latestSafetyBackup['created_at']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="footer-note">
        KK Youth Profiling System • SK Barangay Sikat, Alfonso, Cavite • FY 2026–2027
    </div>

</div>

</main>

<script>
function note(type, text) {
    var d = document.getElementById("msg");
    d.innerHTML = '<div class="alert alert-' + type + ' small">' + text.replace(/[<>&]/g, "") + '</div>';
    window.scrollTo({ top: 0, behavior: "smooth" });
}

/* text size (saved in this browser; sidebar.js applies it on every page) */
var sel = document.getElementById("textSize");
try { sel.value = localStorage.getItem("kkTextSize") || "normal"; } catch (e) {}
sel.addEventListener("change", function () {
    try { localStorage.setItem("kkTextSize", sel.value); } catch (e) {}
    location.reload();
});

/* change password (officers only) */
var btn = document.getElementById("savePassBtn");
if (btn) btn.addEventListener("click", function () {
    var cur = document.getElementById("curPass").value;
    var n1 = document.getElementById("newPass").value;
    var n2 = document.getElementById("newPass2").value;
    if (!cur || !n1) { note("danger", "Please fill in your current and new password."); return; }
    if (n1.length < 8 || n1.length > 100) { note("danger", "The new password must be 8 to 100 characters."); return; }
    if (n1 !== n2) { note("danger", "The new passwords do not match."); return; }
    fetch("api.php?action=password", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> },
        body: JSON.stringify({ current: cur, "new": n1 })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) {
            note("success", "Your password was changed.");
            ["curPass", "newPass", "newPass2"].forEach(function (id) { document.getElementById(id).value = ""; });
        } else note("danger", res.error || "Could not change the password.");
    })
    .catch(function () { note("danger", "Could not reach the server."); });
});

var saveGooglePassBtn = document.getElementById("saveGooglePassBtn");
if (saveGooglePassBtn) saveGooglePassBtn.addEventListener("click", function () {
    var first = document.getElementById("googleNewPass").value;
    var second = document.getElementById("googleNewPass2").value;
    var status = document.getElementById("googlePasswordStatus");
    if (first.length < 8 || first.length > 100 || first !== second) {
        status.className = "small mt-3 text-danger";
        status.textContent = "Enter the same password twice; it must be 8 to 100 characters.";
        return;
    }
    saveGooglePassBtn.disabled = true;
    fetch("api.php?action=password_google_set", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        },
        body: JSON.stringify({ "new": first })
    }).then(function (response) {
        return response.json().then(function (result) {
            if (!response.ok || !result.ok) throw new Error(result.error || "Could not set your password.");
            return result;
        });
    }).then(function () {
        status.className = "small mt-3 text-success";
        status.textContent = "Password saved. Reloading Settings...";
        window.setTimeout(function () { window.location.reload(); }, 900);
    }).catch(function (error) {
        status.className = "small mt-3 text-danger";
        status.textContent = error.message || "Could not set your password.";
        saveGooglePassBtn.disabled = false;
    });
});

var bindGoogleBtn = document.getElementById("bindGoogleBtn");
if (bindGoogleBtn) {
    var googleBindStatus = document.getElementById("googleBindStatus");
    var googleEmailMismatch = document.getElementById("googleEmailMismatch");
    var googleBindPassword = document.getElementById("googleBindPassword");
    var useGoogleEmailBtn = document.getElementById("useGoogleEmailBtn");
    var googleBindPopup = null;
    var googleBindHandled = false;
    var googleBindCloseCheck = null;
    var pendingGoogleBinding = null;
    var googleProfileEmail = <?= json_encode($memberGoogleLink['email'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var googleMemberId = <?= json_encode((string)$settingsUser['legacy_id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function submitGoogleBinding(ticket, replaceProfileEmail, currentPassword) {
        googleBindStatus.textContent = "Binding your Google account...";
        fetch("api.php?action=account_self_link_google", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            },
            body: JSON.stringify({
                googleRegistrationTicket: ticket,
                replaceProfileEmail: replaceProfileEmail,
                currentPassword: currentPassword || ""
            })
        })
        .then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data.ok) throw new Error(data.error || "Could not bind the Google account.");
                return data;
            });
        })
        .then(function (data) {
            googleEmailMismatch.classList.add("d-none");
            googleBindStatus.textContent = data.profileEmailUpdated
                ? "Your profile email was updated and Google was bound successfully. Reloading Settings..."
                : "Google account bound successfully. Reloading Settings...";
            window.setTimeout(function () { window.location.reload(); }, 1200);
        })
        .catch(function (error) {
            googleBindStatus.textContent = error.message || "Could not bind the Google account.";
            bindGoogleBtn.disabled = !googleEmailMismatch.classList.contains("d-none");
            useGoogleEmailBtn.disabled = false;
        });
    }

    bindGoogleBtn.addEventListener("click", function () {
        googleBindPopup = window.open("", "kkMemberGoogleBinding", "popup,width=520,height=640");
        if (!googleBindPopup) {
            googleBindStatus.textContent = "Allow pop-ups for this site, then try again.";
            return;
        }
        googleBindHandled = false;
        bindGoogleBtn.disabled = true;
        googleBindStatus.textContent = "Continue in the Google sign-in window...";
        var form = document.createElement("form");
        form.method = "POST";
        form.action = "google-registration-start.php";
        form.target = "kkMemberGoogleBinding";
        [
            ["csrf", <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>],
            ["purpose", "member_self_link"]
        ].forEach(function (pair) {
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = pair[0];
            input.value = pair[1];
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
        form.remove();
        googleBindCloseCheck = window.setInterval(function () {
            if (!googleBindPopup || googleBindPopup.closed) {
                window.clearInterval(googleBindCloseCheck);
                if (!googleBindHandled) {
                    googleBindStatus.textContent = "The Google window was closed before verification completed.";
                    bindGoogleBtn.disabled = false;
                }
            }
        }, 700);
    });

    window.addEventListener("message", function (event) {
        if (event.origin !== window.location.origin || event.source !== googleBindPopup) return;
        var result = event.data;
        if (!result || result.type !== "kk-google-registration") return;
        googleBindHandled = true;
        if (googleBindCloseCheck) window.clearInterval(googleBindCloseCheck);
        if (!result.ok) {
            googleBindStatus.textContent = result.message || "Google account verification failed.";
            bindGoogleBtn.disabled = false;
            return;
        }
        if (result.purpose !== "member_self_link" || !result.ticket || result.memberId !== googleMemberId
            || typeof result.email !== "string" || !result.email) {
            googleBindStatus.textContent = "The Google verification response was invalid. Please try again.";
            bindGoogleBtn.disabled = false;
            return;
        }
        pendingGoogleBinding = { ticket: result.ticket, email: result.email };
        var savedEmail = String(googleProfileEmail || "").trim();
        if (savedEmail && savedEmail.toLowerCase() !== result.email.trim().toLowerCase()) {
            document.getElementById("googleEmailMismatchSummary").textContent =
                "This Google account (" + result.email + ") does not match your profile email (" + savedEmail +
                "). Re-enter your current password to replace the profile email and bind this account.";
            googleEmailMismatch.classList.remove("d-none");
            googleBindPassword.value = "";
            googleBindStatus.textContent = "";
            bindGoogleBtn.disabled = true;
            googleBindPassword.focus();
            return;
        }
        submitGoogleBinding(result.ticket, false, "");
    });

    useGoogleEmailBtn.addEventListener("click", function () {
        if (!pendingGoogleBinding) return;
        if (!googleBindPassword.value) {
            googleBindPassword.focus();
            googleBindStatus.textContent = "Enter your current password to change the profile email.";
            return;
        }
        bindGoogleBtn.disabled = true;
        useGoogleEmailBtn.disabled = true;
        submitGoogleBinding(pendingGoogleBinding.ticket, true, googleBindPassword.value);
    });

    document.getElementById("cancelGoogleEmailChangeBtn").addEventListener("click", function () {
        pendingGoogleBinding = null;
        googleBindPassword.value = "";
        googleEmailMismatch.classList.add("d-none");
        googleBindStatus.textContent = "No changes were made. You can try again with a different Google account.";
        bindGoogleBtn.disabled = false;
    });
}
</script>

<?php if ($canManageSKPermissions): ?>
<script>
(function () {
    var csrf = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var officialSelect = document.getElementById("permissionOfficial");
    var expiryInput = document.getElementById("permissionExpiresAt");
    var status = document.getElementById("permissionStatus");
    var grantsBody = document.getElementById("permissionGrants");
    var recoveryCodeButton = document.getElementById("generateRecoveryCodeBtn");
    var recoveryCodeStatus = document.getElementById("recoveryCodeStatus");
    var recoveryCodeResult = document.getElementById("recoveryCodeResult");
    var officialResetTarget = document.getElementById("officialResetTarget");
    var officialResetButton = document.getElementById("resetOfficialCounterpartBtn");
    var officialResetStatus = document.getElementById("officialResetStatus");
    var currentOfficialRole = <?= json_encode((string)($settingsUser['role'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var officials = [];
    var permissionLabels = {
        unbind_google: "Unbind Google accounts",
        reset_member_password: "Reset KK member passwords",
        record_attendance: "Record attendance",
        edit_members: "Edit member records",
        manage_projects: "Manage projects",
        view_audit_log: "View audit log"
    };
    var presets = {
        attendance: ["record_attendance"],
        records: ["edit_members"],
        account: ["unbind_google", "reset_member_password"]
    };

    function request(action, payload) {
        return fetch("api.php?action=" + encodeURIComponent(action), {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
            body: JSON.stringify(payload || {})
        }).then(function (response) {
            return response.json().then(function (result) {
                if (!response.ok || !result.ok) throw new Error(result.error || "Permission request failed.");
                return result;
            });
        });
    }

    function loadPermissions() {
        request("permission_list").then(function (result) {
            officials = result.officials || [];
            officialSelect.replaceChildren();
            var prompt = document.createElement("option");
            prompt.value = "";
            prompt.textContent = officials.length ? "Choose an official" : "No eligible officials";
            officialSelect.appendChild(prompt);
            officials.forEach(function (official) {
                var option = document.createElement("option");
                option.value = official.memberId;
                option.textContent = official.name + " — " + official.role;
                officialSelect.appendChild(option);
            });
            renderGrants(result.grants || []);
            recoveryCodeButton.disabled = !!result.emergencyCodeGenerated;
            if (result.emergencyCodeGenerated) {
                recoveryCodeStatus.textContent = "A code has already been generated. It is intentionally not retrievable.";
            }
            officialResetTarget.replaceChildren();
            var counterpartRole = currentOfficialRole === "SK Secretary"
                ? ["SK Chairman", "SK Chairperson"] : ["SK Secretary"];
            var counterparts = (result.protectedOfficials || []).filter(function (official) {
                return counterpartRole.indexOf(official.role) !== -1;
            });
            var resetPrompt = document.createElement("option");
            resetPrompt.value = "";
            resetPrompt.textContent = counterparts.length ? "Choose the counterpart official" : "No active counterpart account";
            officialResetTarget.appendChild(resetPrompt);
            counterparts.forEach(function (official) {
                var option = document.createElement("option");
                option.value = String(official.id);
                option.textContent = official.name + " — " + official.role;
                officialResetTarget.appendChild(option);
            });
            officialResetButton.disabled = !counterparts.length;
            status.textContent = "";
        }).catch(function (error) {
            status.textContent = error.message || "Could not load permissions.";
            status.className = "small mt-3 text-danger";
        });
    }

    function renderGrants(grants) {
        grantsBody.replaceChildren();
        if (!grants.length) {
            var empty = document.createElement("tr");
            var cell = document.createElement("td");
            cell.colSpan = 6;
            cell.className = "text-muted";
            cell.textContent = "No permission grants have been recorded.";
            empty.appendChild(cell);
            grantsBody.appendChild(empty);
            return;
        }
        grants.forEach(function (grant) {
            var official = officials.find(function (item) { return item.memberId === String(grant.member_id); });
            var row = document.createElement("tr");
            [
                official ? official.name + " — " + official.role : String(grant.member_id),
                permissionLabels[grant.permission] || grant.permission,
                grant.granted_at || "",
                grant.expires_at || "No expiry",
                grant.revoked_at ? "Revoked" :
                    (grant.expires_at && new Date(String(grant.expires_at).replace(" ", "T")) <= new Date() ? "Expired" : "Active")
            ].forEach(function (value) {
                var cell = document.createElement("td");
                cell.textContent = value;
                row.appendChild(cell);
            });
            var actionCell = document.createElement("td");
            if (!grant.revoked_at && (!grant.expires_at || new Date(String(grant.expires_at).replace(" ", "T")) > new Date())) {
                var revoke = document.createElement("button");
                revoke.type = "button";
                revoke.className = "btn btn-sm btn-outline-danger";
                revoke.textContent = "Revoke";
                revoke.setAttribute("data-grant-id", String(grant.id));
                actionCell.appendChild(revoke);
            }
            row.appendChild(actionCell);
            grantsBody.appendChild(row);
        });
    }

    officialSelect.addEventListener("change", function () {
        var selected = officials.find(function (official) { return official.memberId === officialSelect.value; });
        expiryInput.value = selected ? selected.defaultExpiresAt : "";
    });

    document.getElementById("permissionPreset").addEventListener("change", function () {
        var selected = presets[this.value] || [];
        document.querySelectorAll("#permissionOptions input[type=checkbox]").forEach(function (checkbox) {
            checkbox.checked = selected.indexOf(checkbox.value) !== -1;
        });
    });

    document.getElementById("grantPermissionsBtn").addEventListener("click", function () {
        var selectedPermissions = Array.prototype.slice.call(
            document.querySelectorAll("#permissionOptions input[type=checkbox]:checked")
        ).map(function (checkbox) { return checkbox.value; });
        if (!officialSelect.value || !selectedPermissions.length) {
            status.className = "small mt-3 text-danger";
            status.textContent = "Choose an official and at least one permission.";
            return;
        }
        request("permission_grant", {
            memberId: officialSelect.value,
            permissions: selectedPermissions,
            expiresAt: expiryInput.value
        }).then(function () {
            status.className = "small mt-3 text-success";
            status.textContent = "Permission grant saved.";
            loadPermissions();
        }).catch(function (error) {
            status.className = "small mt-3 text-danger";
            status.textContent = error.message || "Could not grant permissions.";
        });
    });

    grantsBody.addEventListener("click", function (event) {
        var button = event.target.closest("[data-grant-id]");
        if (!button) return;
        button.disabled = true;
        request("permission_revoke", { grantId: Number(button.getAttribute("data-grant-id")) })
            .then(function () {
                status.className = "small mt-3 text-success";
                status.textContent = "Permission revoked.";
                loadPermissions();
            }).catch(function (error) {
                button.disabled = false;
                status.className = "small mt-3 text-danger";
                status.textContent = error.message || "Could not revoke permission.";
            });
    });

    recoveryCodeButton.addEventListener("click", function () {
        if (!window.confirm("Generate the single emergency recovery code? It will be shown only once. Store it securely offline.")) return;
        recoveryCodeButton.disabled = true;
        request("official_recovery_code_generate").then(function (result) {
            recoveryCodeResult.textContent = "Store this code securely now. It cannot be shown again: " + result.recoveryCode;
            recoveryCodeResult.classList.remove("d-none");
            recoveryCodeStatus.textContent = "Code generated and audited.";
        }).catch(function (error) {
            recoveryCodeButton.disabled = false;
            recoveryCodeStatus.textContent = error.message || "Could not generate the recovery code.";
            recoveryCodeStatus.className = "small mt-2 text-danger";
        });
    });

    officialResetButton.addEventListener("click", function () {
        if (!officialResetTarget.value || !window.confirm("Reset this counterpart official's password? The temporary password will be shown once.")) return;
        officialResetButton.disabled = true;
        request("account_reset", { accountId: Number(officialResetTarget.value) }).then(function (result) {
            officialResetStatus.className = "small mt-2 text-success";
            officialResetStatus.textContent = "Temporary password (show only once): " + result.temporaryPassword
                + ". Share it securely; the official must change it at next sign-in.";
        }).catch(function (error) {
            officialResetStatus.className = "small mt-2 text-danger";
            officialResetStatus.textContent = error.message || "Could not reset the official password.";
            officialResetButton.disabled = false;
        });
    });

    loadPermissions();
})();
</script>
<?php endif; ?>

<?php if ($canManageBackups): ?>
<script>
document.getElementById('backupRestoreForm').addEventListener('submit', function (event) {
    event.preventDefault();
    var file = document.getElementById('backupFile').files[0];
    if (!file) return;
    if (!window.confirm('Restore this backup? Current operational records will be replaced. A safety backup will be created first.')) return;

    var button = document.getElementById('restoreBackupBtn');
    var message = document.getElementById('backupRestoreMessage');
    button.disabled = true;
    message.textContent = 'Restoring backup...';

    fetch('backup.php?action=restore', { method: 'POST', body: new FormData(this) })
        .then(function (response) {
            return response.json().then(function (result) {
                if (!response.ok || !result.ok) throw new Error(result.error || 'Could not restore the backup.');
                return result;
            });
        })
        .then(function (result) {
            message.className = 'alert alert-success mt-3';
            message.textContent = result.message;
            var linkBox = document.getElementById('safetyBackupLink');
            var link = document.createElement('a');
            link.href = 'backup.php?action=download-safety&id=' + encodeURIComponent(result.safetyBackupId);
            link.textContent = 'Download the pre-restore safety backup';
            linkBox.replaceChildren(document.createTextNode('Safety backup created: '), link);
        })
        .catch(function (error) {
            message.className = 'alert alert-danger mt-3';
            message.textContent = error.message || 'Restore failed. No changes were applied.';
        })
        .finally(function () {
            button.disabled = false;
        });
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>