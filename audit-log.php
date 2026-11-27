<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/role_store.php';
if (!is_super_admin()) {
    require_official();
    if (!can_view_background_history()) {
        http_response_code(403);
        exit('You do not have permission to view the audit log.');
    }
}
$auditViewer = current_user();
if (is_array($GLOBALS['SK_ACTIVE_DELEGATION'] ?? null)) {
    $auditTarget = isset($_GET['detail']) && is_scalar($_GET['detail'])
        ? 'audit:' . substr((string)$_GET['detail'], 0, 20) : null;
    sk_audit(db(), $auditViewer, $auditTarget, 'audit_log_viewed', null, [
        'view' => array_key_exists('detail', $_GET) ? 'entry_detail' : 'audit_list',
    ]);
}

if (array_key_exists('detail', $_GET)) {
    header('Content-Type: application/json; charset=utf-8');
    $detailId = $_GET['detail'];
    if (!is_string($detailId) || !ctype_digit($detailId) || (int)$detailId < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid audit entry ID is required.']);
        exit;
    }
    try {
        $statement = db()->prepare('SELECT id, actor_user_id, actor, target_legacy_id, action, old_value, new_value,
                user_agent, device_type, browser, ip_address, location, created_at
            FROM audit_log WHERE id = ? LIMIT 1');
        $statement->execute([(int)$detailId]);
        $detail = $statement->fetch();
        if (!$detail) {
            http_response_code(404);
            echo json_encode(['error' => 'That audit entry was not found.']);
            exit;
        }
        echo json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $error) {
        error_log('Audit log detail could not load: ' . $error->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'The audit entry details could not be loaded.']);
    }
    exit;
}

$pageSize = 50;
$pageValue = $_GET['page'] ?? null;
$page = is_scalar($pageValue) && ctype_digit((string)$pageValue) ? max(1, (int)$pageValue) : 1;
$auditCount = 0;
$auditRows = [];
$auditError = '';
$filters = ['actor' => '', 'action' => '', 'from' => '', 'to' => ''];
$filterError = '';

foreach (array_keys($filters) as $filterName) {
    if (!array_key_exists($filterName, $_GET)) continue;
    if (!is_string($_GET[$filterName])) {
        $filterError = 'Audit filters must be single text values.';
        break;
    }
    $filters[$filterName] = trim(substr($_GET[$filterName], 0, $filterName === 'actor' ? 120 : 50));
}
foreach (['from', 'to'] as $dateFilter) {
    if ($filterError !== '' || $filters[$dateFilter] === '') continue;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$dateFilter]);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$date || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))
        || $date->format('Y-m-d') !== $filters[$dateFilter]) {
        $filterError = 'Audit date filters must use YYYY-MM-DD.';
        break;
    }
}
if ($filterError === '' && $filters['from'] !== '' && $filters['to'] !== ''
    && $filters['from'] > $filters['to']) {
    $filterError = 'The audit date range is invalid.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">
    <style>
        :root { --line:var(--ui-line); --muted:var(--ui-muted); }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--ui-paper); color:var(--mem-ink); }
        .top-strip { height:7px; background:var(--mem-brand); }
        .header { min-height:70px; background:var(--ui-surface); border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 26px; gap:14px; }
        .header-title { font-size:20px; font-weight:800; }
        .content { padding:24px; max-width:1600px; margin:0 auto; }
        .intro, .audit-card { background:var(--ui-surface); border:1px solid var(--line); border-radius:var(--ui-radius); box-shadow:var(--ui-shadow); }
        .intro { padding:20px; margin-bottom:16px; }
        .intro h1 { font-size:24px; font-weight:900; margin:0 0 5px; }
        .intro p { color:var(--muted); margin:0; }
        .audit-card { overflow:hidden; }
        .audit-heading { padding:16px 18px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .audit-heading h2 { font-size:17px; font-weight:800; margin:0; }
        .table-responsive { max-height:calc(100vh - 350px); min-height:220px; overflow:auto; }
        .table { margin:0; font-size:14px; }
        .table th { white-space:nowrap; background:var(--mem-brand-soft); color:var(--mem-muted); font-size:12px; text-transform:uppercase; }
        .table td, .table th { padding:12px; vertical-align:top; }
        .audit-action { font-weight:700; white-space:nowrap; }
        .audit-target { overflow-wrap:anywhere; }
        .audit-device { min-width:150px; }
        .audit-detail-dialog { max-width:1120px; }
        .audit-detail-modal { border:1px solid var(--ui-line); border-radius:var(--ui-radius); overflow:hidden; box-shadow:var(--ui-shadow); }
        .audit-detail-header { gap:16px; padding:20px 24px; background:var(--mem-brand); color:#fff; border-bottom:1px solid var(--mem-brand); }
        .audit-detail-heading { display:flex; align-items:center; gap:15px; min-width:0; }
        .audit-detail-icon { display:grid; place-items:center; width:46px; height:46px; flex:none; border:1px solid rgba(255,255,255,.28); border-radius:var(--ui-radius-sm); color:#fff; background:rgba(255,255,255,.14); font-size:21px; }
        .audit-detail-copy { min-width:0; }
        .audit-detail-kicker { color:rgba(255,255,255,.78); font-size:10px; font-weight:800; letter-spacing:.1em; }
        .audit-detail-title { margin:2px 0 3px; color:#fff; font-size:19px; font-weight:750; }
        .audit-detail-summary { color:rgba(255,255,255,.86); font-size:13px; overflow-wrap:anywhere; }
        .audit-detail-body { padding:22px 24px 24px; background:var(--ui-paper); }
        .audit-action-summary { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:15px 18px; margin-bottom:18px; background:var(--mem-brand-soft); border:1px solid var(--mem-line); border-left:4px solid var(--mem-brand); border-radius:var(--ui-radius-sm); }
        .audit-summary-label { display:block; margin-bottom:4px; color:var(--ui-muted); font-size:10px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
        .audit-summary-action { font-size:17px; font-weight:750; overflow-wrap:anywhere; }
        .audit-record-badge { color:var(--mem-brand-text-emphasis)!important; background:var(--mem-brand-soft); border-color:var(--mem-line)!important; }
        .audit-section { height:100%; padding:17px; background:var(--ui-surface); border:1px solid var(--ui-line); border-radius:var(--ui-radius); }
        .audit-section-heading { display:flex; align-items:center; gap:9px; margin:0 0 15px; font-size:14px; font-weight:800; }
        .audit-section-heading i { color:var(--ui-brand); font-size:16px; }
        .audit-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 12px; }
        .audit-info-item { min-width:0; }
        .audit-info-label { display:block; margin-bottom:4px; color:var(--ui-muted); font-size:10px; font-weight:750; letter-spacing:.06em; text-transform:uppercase; }
        .audit-info-value { color:var(--mem-ink); font-size:13px; font-weight:600; overflow-wrap:anywhere; }
        .audit-ua { margin-top:15px; padding-top:12px; border-top:1px solid var(--ui-line); }
        .audit-ua summary { color:var(--ui-muted); cursor:pointer; font-size:12px; font-weight:700; }
        .audit-ua-value { margin:8px 0 0; color:var(--ui-muted); font-size:11px; overflow-wrap:anywhere; }
        .audit-changes { margin-top:18px; }
        .audit-change-card { height:100%; padding:15px; background:var(--ui-surface); border:1px solid var(--ui-line); border-radius:var(--ui-radius); }
        .audit-change-table-wrap { padding:0; overflow:hidden; }
        .audit-change-table-wrap .table-responsive { max-height:none; min-height:0; }
        .audit-change-table { min-width:600px; margin:0; font-size:13px; }
        .audit-change-table th { padding:11px 14px; color:var(--ui-muted); font-size:10px; font-weight:800; letter-spacing:.06em; white-space:nowrap; }
        .audit-change-table td { min-width:150px; padding:11px 14px; vertical-align:top; white-space:pre-wrap; overflow-wrap:anywhere; }
        .audit-change-table .audit-change-label { min-width:170px; background:var(--ui-surface); color:var(--mem-ink); font-weight:700; }
        .audit-changes-empty { padding:14px 16px; color:var(--ui-muted); font-size:13px; }
        .audit-detail-note { margin:14px 2px 0; color:var(--ui-muted); font-size:11px; }
        .audit-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; }
        .footer-note { text-align:center; color:var(--muted); font-size:13px; padding:20px; }
        @media(max-width:767px) {
            .content { padding:14px; }
            .header { padding:0 16px; }
            .audit-heading { align-items:flex-start; flex-direction:column; }
            .table-responsive { max-height:none; }
            .audit-pagination { align-items:flex-start; flex-direction:column; }
            .audit-detail-header { padding:18px; }
            .audit-detail-body { padding:16px; }
            .audit-action-summary { align-items:flex-start; flex-direction:column; }
            .audit-info-grid { grid-template-columns:1fr 1fr; }
        }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
    <?php
    $where = [];
    $filterParameters = [];
    if ($filters['actor'] !== '') {
        $where[] = 'LOWER(actor) LIKE LOWER(:actor)';
        $filterParameters[':actor'] = '%' . $filters['actor'] . '%';
    }
    if ($filters['action'] !== '') {
        $where[] = 'LOWER(action) LIKE LOWER(:action)';
        $filterParameters[':action'] = '%' . $filters['action'] . '%';
    }
    if ($filters['from'] !== '') {
        $where[] = 'created_at >= :date_from';
        $filterParameters[':date_from'] = $filters['from'] . ' 00:00:00';
    }
    if ($filters['to'] !== '') {
        $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $filters['to'])->modify('+1 day')->format('Y-m-d');
        $where[] = 'created_at < :date_to';
        $filterParameters[':date_to'] = $endDate . ' 00:00:00';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $pageCount = 1;
    try {
        if ($filterError !== '') {
            http_response_code(400);
            $auditError = $filterError;
        } else {
            $countStatement = db()->prepare('SELECT COUNT(*) FROM audit_log' . $whereSql);
            $countStatement->execute($filterParameters);
            $auditCount = (int)$countStatement->fetchColumn();
        }
        $pageCount = max(1, (int)ceil($auditCount / $pageSize));
        $page = min($page, $pageCount);
        $offset = ($page - 1) * $pageSize;
        if ($filterError === '') {
            $statement = db()->prepare('SELECT id, actor, target_legacy_id, action, device_type, browser, created_at
            FROM audit_log' . $whereSql . '
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset');
            foreach ($filterParameters as $name => $value) $statement->bindValue($name, $value, PDO::PARAM_STR);
            $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
            $statement->execute();
            $auditRows = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $error) {
        error_log('Audit log page could not load: ' . $error->getMessage());
        http_response_code(500);
        $auditError = 'The audit log could not be loaded. Confirm that the audit-log table has been initialized.';
        $pageCount = 1;
    }
    $firstRecord = $auditRows ? (($page - 1) * $pageSize + 1) : 0;
    $lastRecord = $auditRows ? min($page * $pageSize, $auditCount) : 0;
    ?>
</head>
<body>
<div class="top-strip"></div>
<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=20"></script>
<main class="main page-content">
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <div>
                <div class="header-title">Audit Log</div>
                <small>KK Youth Profiling System</small>
            </div>
        </div>
        <div class="header-account-area">
            <span class="header-avatar"><?= e(uinit()) ?></span>
            <div class="d-none d-md-block header-identity">
                <div class="header-identity-name"><?= e(uname()) ?></div>
                <div class="header-identity-role"><?= e(strtoupper(urole())) ?></div>
            </div>
            <a href="logout.php" class="btn btn-sm header-logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </header>
    <div class="content">
        <section class="intro">
            <h1><i class="bi bi-journal-text me-2"></i>Audit Log</h1>
            <p>Review recent actions and their recorded details, including device, browser, IP address, and approximate location when available. Newest entries appear first.</p>
        </section>

        <form class="audit-card p-3 mb-3" method="get" action="audit-log.php" aria-label="Filter audit log">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="filterActor">Performed by</label>
                    <input class="form-control form-control-sm" id="filterActor" name="actor" value="<?= e($filters['actor']) ?>" maxlength="120">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="filterAction">Action contains</label>
                    <input class="form-control form-control-sm" id="filterAction" name="action" value="<?= e($filters['action']) ?>" maxlength="50">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold" for="filterFrom">From</label>
                    <input class="form-control form-control-sm" type="date" id="filterFrom" name="from" value="<?= e($filters['from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold" for="filterTo">To</label>
                    <input class="form-control form-control-sm" type="date" id="filterTo" name="to" value="<?= e($filters['to']) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary btn-sm" href="audit-log.php">Clear</a>
                </div>
            </div>
        </form>

        <section class="audit-card" aria-labelledby="auditHeading">
            <div class="audit-heading">
                <h2 id="auditHeading">Recorded activity</h2>
                <?php if ($auditError === ''): ?>
                    <span class="small text-muted">Showing <?= (int)$firstRecord ?>–<?= (int)$lastRecord ?> of <?= (int)$auditCount ?> entries</span>
                <?php endif; ?>
            </div>
            <?php if ($auditError !== ''): ?>
                <div class="alert alert-danger m-3 mb-0"><?= e($auditError) ?></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Date &amp; time</th>
                                <th scope="col">Performed by</th>
                                <th scope="col">Action</th>
                                <th scope="col">Target</th>
                                <th scope="col">Device / browser</th>
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$auditRows): ?>
                                <tr><td colspan="6" class="text-center text-muted py-5">No audit entries have been recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($auditRows as $row): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e($row['created_at']) ?></td>
                                        <td><?= e($row['actor']) ?></td>
                                        <td class="audit-action"><?= e(ucwords(str_replace('_', ' ', $row['action']))) ?></td>
                                        <td class="audit-target"><?= e($row['target_legacy_id'] ?? '—') ?></td>
                                        <td class="audit-device">
                                            <div><?= e($row['device_type'] ?? 'Not recorded') ?></div>
                                            <small class="text-muted"><?= e($row['browser'] ?? 'Not recorded') ?></small>
                                        </td>
                                        <td><button class="btn btn-outline-primary btn-sm js-audit-detail" type="button" data-audit-id="<?= (int)$row['id'] ?>"><i class="bi bi-box-arrow-up-right me-1"></i>View details</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="audit-pagination" aria-label="Audit log pages">
                    <span class="small text-muted">Page <?= (int)$page ?> of <?= (int)$pageCount ?></span>
                    <div class="d-flex gap-2">
                        <?php if ($page > 1): ?>
                            <?php $previousQuery = http_build_query(array_merge($filters, ['page' => $page - 1])); ?>
                            <a class="btn btn-outline-secondary btn-sm" href="audit-log.php?<?= e($previousQuery) ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $pageCount): ?>
                            <?php $nextQuery = http_build_query(array_merge($filters, ['page' => $page + 1])); ?>
                            <a class="btn btn-outline-secondary btn-sm" href="audit-log.php?<?= e($nextQuery) ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </section>
        <div class="footer-note">KK Youth Profiling System &bull; SK Barangay Sikat, Alfonso, Cavite</div>
    </div>
</main>
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-labelledby="auditDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable audit-detail-dialog">
        <div class="modal-content audit-detail-modal">
            <div class="modal-header audit-detail-header">
                <div class="audit-detail-heading">
                    <div class="audit-detail-icon"><i class="bi bi-journal-check"></i></div>
                    <div class="audit-detail-copy">
                        <div class="audit-detail-kicker">SYSTEM ACTIVITY</div>
                        <h2 class="audit-detail-title" id="auditDetailTitle">Audit entry details</h2>
                        <div class="audit-detail-summary" id="auditDetailSummary">Loading activity details…</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body audit-detail-body">
                <section class="audit-action-summary" aria-label="Selected action">
                    <div>
                        <span class="audit-summary-label">What happened</span>
                        <div class="audit-summary-action" id="auditAction">Loading…</div>
                    </div>
                    <span class="badge rounded-pill border text-secondary audit-record-badge">Audit record</span>
                </section>
                <div class="row g-3">
                    <div class="col-lg-5">
                        <section class="audit-section">
                            <h3 class="audit-section-heading"><i class="bi bi-person-lines-fill"></i>Activity details</h3>
                            <div class="audit-info-grid">
                                <div class="audit-info-item"><span class="audit-info-label">Performed by</span><div class="audit-info-value" id="auditActor">Loading…</div></div>
                                <div class="audit-info-item"><span class="audit-info-label">Account ID</span><div class="audit-info-value" id="auditActorId">Loading…</div></div>
                                <div class="audit-info-item"><span class="audit-info-label">Target</span><div class="audit-info-value" id="auditTarget">Loading…</div></div>
                                <div class="audit-info-item"><span class="audit-info-label">Date &amp; time</span><div class="audit-info-value" id="auditDate">Loading…</div></div>
                            </div>
                        </section>
                    </div>
                    <div class="col-lg-7">
                        <section class="audit-section">
                            <h3 class="audit-section-heading"><i class="bi bi-pc-display"></i>Device &amp; network</h3>
                            <div class="audit-info-grid">
                                <div class="audit-info-item"><span class="audit-info-label">Device</span><div class="audit-info-value" id="auditDevice">Loading…</div></div>
                                <div class="audit-info-item"><span class="audit-info-label">Browser</span><div class="audit-info-value" id="auditBrowser">Loading…</div></div>
                                <div class="audit-info-item"><span class="audit-info-label">IP address</span><div class="audit-info-value" id="auditIp">Loading…</div></div>
                                <div class="audit-info-item"><span class="audit-info-label">Approximate location</span><div class="audit-info-value" id="auditLocation">Loading…</div></div>
                            </div>
                            <details class="audit-ua">
                                <summary>Show reported User-Agent</summary>
                                <div class="audit-ua-value" id="auditUserAgent">Loading…</div>
                            </details>
                        </section>
                    </div>
                </div>
                <section class="audit-changes" aria-labelledby="auditChangesHeading">
                    <h3 class="audit-section-heading" id="auditChangesHeading"><i class="bi bi-arrow-left-right"></i>What changed?</h3>
                    <div class="audit-change-card audit-change-table-wrap">
                        <div class="table-responsive">
                            <table class="table align-middle audit-change-table" id="auditChangesTable">
                                <thead>
                                    <tr><th scope="col">Information</th><th scope="col">Before</th><th scope="col">After</th></tr>
                                </thead>
                                <tbody id="auditChanges"></tbody>
                            </table>
                        </div>
                        <div class="audit-changes-empty d-none" id="auditChangesEmpty" role="status"></div>
                    </div>
                    <p class="audit-detail-note mb-0">Location is approximate IP-based information, not precise GPS. User-Agent details are reported by the browser and can be spoofed.</p>
                </section>
                <div class="alert alert-danger mt-3 mb-0 d-none" id="auditDetailError" role="alert"></div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('auditDetailModal'));
    const setText = (id, value) => {
        document.getElementById(id).textContent =
            value === null || value === undefined || value === '' ? 'Not recorded' : String(value);
    };
    const auditActionNames = {
        page_view: 'Viewed a page',
        logout: 'Logged out',
        backup_downloaded: 'Downloaded a system backup',
        backup_restored: 'Restored a system backup',
        google_account_linked: 'Linked a Google account',
        google_login: 'Signed in with Google',
        google_account_link_requested: 'Requested a Google account link',
        google_account_link_approved: 'Approved a Google account link',
        google_account_link_rejected: 'Rejected a Google account link',
        google_profile_email_updated: 'Updated a KK profile email during Google binding',
        google_profile_email_cleared: 'Cleared a KK profile email',
        google_account_unbound: 'Unbound a Google account',
        google_account_email_updated: 'Updated the linked Google email',
        account_linked: 'Linked a member account',
        account_created: 'Created a member login account',
        account_username_retrieved: 'Retrieved a member username',
        account_password_reset: 'Reset a member password',
        account_deactivated: 'Deactivated an account',
        role_assigned: 'Assigned an SK role',
        role_updated: 'Updated an SK role',
        role_ended: 'Ended an SK role',
        role_archived: 'Archived an SK role',
        transparency_budget_updated: 'Updated the annual budget',
        transparency_expenses_updated: 'Updated expense records',
        member_profile_photo_changed: 'Changed a member profile photo',
        official_profile_updated: 'Updated an official profile',
        official_file_viewed: 'Viewed a protected file',
        expense_receipt_uploaded: 'Uploaded or replaced an expense receipt',
        meeting_attendance_marked: 'Recorded meeting attendance',
        meeting_attendance_corrected: 'Corrected meeting attendance',
        meeting_attendance_deleted: 'Deleted meeting attendance',
        meeting_minutes_approved: 'Approved meeting minutes',
        meeting_minutes_reopened: 'Reopened meeting minutes',
        member_registered: 'Registered a KK member',
        member_restored: 'Restored a KK member record',
        committee_created: 'Added a committee',
        committee_renamed: 'Renamed a committee',
        committee_assignments_updated: 'Updated committee memberships and leadership',
        committee_preference_updated: 'Updated committee preferences',
        committee_chair_assignment_updated: 'Updated committee chair assignments',
        committee_vice_assignment_updated: 'Updated committee vice-chair assignments',
        committee_membership_updated: 'Updated committee memberships',
        committee_volunteer_submitted: 'Applied to volunteer for a committee or event',
        committee_volunteer_approved: 'Approved a committee volunteer',
        committee_volunteer_declined: 'Declined a committee volunteer',
        api_login: 'Signed in',
        api_password: 'Changed a password',
        api_event_register: 'Registered for an event',
        api_event_attendance: 'Updated event attendance',
        api_event_walkin: 'Recorded walk-in event attendance',
        api_meeting_minutes_save: 'Saved meeting minutes',
        api_meeting_minutes_approve: 'Approved meeting minutes',
        api_meeting_minutes_reopen: 'Reopened meeting minutes',
        api_meeting_attendance_save: 'Updated meeting attendance',
        api_meeting_attendance_delete: 'Deleted meeting attendance',
        api_role_assign: 'Assigned an SK role',
        api_role_update: 'Updated an SK role',
        api_role_end: 'Ended an SK role',
        api_role_archive: 'Archived an SK role',
        api_role_archive_expired: 'Archived expired SK roles',
        api_committee_save: 'Updated a committee',
        api_committee_chairs_save: 'Updated committee chairpersons',
        api_account_link: 'Linked a member account',
        api_account_link_google_request: 'Requested a Google account link',
        api_account_link_google_approve: 'Approved a Google account link',
        api_account_link_google_reject: 'Rejected a Google account link',
        google_account_linked_self_service: 'Bound a Google account to their own KK profile',
        api_account_google_unbind: 'Unbound a member Google account',
        api_account_google_clear_email: 'Cleared a member profile email and unbound Google',
        api_account_create: 'Created a member login account',
        api_account_reset: 'Reset an account password',
        api_account_reset_member: 'Reset a member password',
        api_account_username_member: 'Retrieved a member username',
        api_account_deactivate: 'Deactivated an account',
        api_member_add: 'Added a KK member',
        api_member_update: 'Updated a KK member record',
        api_member_delete: 'Deleted a KK member record',
        api_member_restore: 'Restored a KK member record',
        api_member_register: 'Registered a KK member',
        api_member_data_save: 'Updated member information',
        api_member_portal_data: 'Updated member profile information',
        api_member_portal_photo: 'Changed a member profile photo',
        api_kk_id_print: 'Printed a KK ID',
        api_kk_id_prints: 'Printed KK IDs',
        api_kk_id_scan: 'Scanned a KK ID',
        api_set: 'Updated application data',
        api_remove: 'Removed application data',
        api_profile_files: 'Updated profile files',
        api_receipt_upload: 'Uploaded an expense receipt',
    };
    const auditFieldNames = {
        api_action: 'System action',
        resource_key: 'Data section',
        kkId: 'KK member ID',
        memberNo: 'Member number',
        accountId: 'Account ID',
        account_id: 'Account ID',
        position: 'SK position',
        basis: 'Role basis',
        monthlyHonorarium: 'Monthly honorarium',
        termStart: 'Term start',
        termEnd: 'Term end',
        fy: 'Fiscal year',
        expenseIndex: 'Expense number',
        replacedReceipt: 'Existing receipt replaced',
        photoUpdated: 'Profile photo updated',
        salnUpdated: 'SALN files updated',
        salnYear: 'SALN year',
        guardian_consent: 'Guardian consent recorded',
        google_account_linked: 'Google account linked',
        google_email: 'Google email',
        email: 'Profile email',
        google_sign_in_required: 'Google sign-in required',
        email_verified: 'Email verified',
        is_active: 'Account active',
        must_change_password: 'Password change required',
        safety_backup_id: 'Safety backup ID',
        projectId: 'Project ID',
        backup_id: 'Backup ID',
    };
    const missingValue = Symbol('missing audit value');
    const isRecord = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);
    const parseAuditValue = (value) => {
        if (value === null || value === '') return missingValue;
        try { return JSON.parse(value); }
        catch (_) { return value; }
    };
    const valuesEqual = (left, right) => {
        if (left === right) return true;
        if (left === missingValue || right === missingValue || left === null || right === null) return false;
        if (Array.isArray(left) || Array.isArray(right)) {
            return Array.isArray(left) && Array.isArray(right) && left.length === right.length
                && left.every((item, index) => valuesEqual(item, right[index]));
        }
        if (isRecord(left) || isRecord(right)) {
            if (!isRecord(left) || !isRecord(right)) return false;
            const leftKeys = Object.keys(left).sort();
            const rightKeys = Object.keys(right).sort();
            return leftKeys.length === rightKeys.length
                && leftKeys.every((key, index) => key === rightKeys[index] && valuesEqual(left[key], right[key]));
        }
        return false;
    };
    const humanizeField = (field) => auditFieldNames[field] || String(field)
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
    const formatAuditValue = (value) => {
        if (value === missingValue) return 'Not present';
        if (value === null) return 'Not recorded';
        if (typeof value === 'boolean') return value ? 'Yes' : 'No';
        if (typeof value === 'string') return value.trim() === '' ? 'Empty' : value;
        if (typeof value === 'number') return String(value);
        if (Array.isArray(value)) {
            if (value.length === 0) return 'None';
            if (value.every((item) => item === null || ['string', 'number', 'boolean'].includes(typeof item))) {
                return value.map(formatAuditValue).join(', ');
            }
            return value.length + (value.length === 1 ? ' record' : ' records');
        }
        if (isRecord(value)) {
            const count = Object.keys(value).length;
            return count + (count === 1 ? ' detail' : ' details');
        }
        return String(value);
    };
    const auditItemIdentity = (item) => {
        if (!isRecord(item)) return null;
        for (const key of ['id', 'kkId', 'projectId', 'memberNo', 'accountId', 'account_id', 'requestId', 'request_id']) {
            if (item[key] !== undefined && item[key] !== null && item[key] !== '') {
                return key + ':' + String(item[key]);
            }
        }
        return null;
    };
    const collectAuditChanges = (before, after) => {
        const changes = [];
        let truncated = false;
        const maxChanges = 100;
        const walk = (oldValue, newValue, path) => {
            if (valuesEqual(oldValue, newValue)) return;
            if (changes.length >= maxChanges) {
                truncated = true;
                return;
            }
            if (path.length < 8
                && (isRecord(oldValue) || oldValue === missingValue)
                && (isRecord(newValue) || newValue === missingValue)) {
                const oldRecord = isRecord(oldValue) ? oldValue : {};
                const newRecord = isRecord(newValue) ? newValue : {};
                const keys = [...new Set([...Object.keys(oldRecord), ...Object.keys(newRecord)])];
                if (keys.length > 0) {
                    keys.forEach((key) => walk(
                        Object.prototype.hasOwnProperty.call(oldRecord, key) ? oldRecord[key] : missingValue,
                        Object.prototype.hasOwnProperty.call(newRecord, key) ? newRecord[key] : missingValue,
                        [...path, key]
                    ));
                    return;
                }
            }
            const oldArray = Array.isArray(oldValue) ? oldValue : null;
            const newArray = Array.isArray(newValue) ? newValue : null;
            if (path.length < 8 && (oldArray || newArray)
                && (oldValue === missingValue || oldArray) && (newValue === missingValue || newArray)) {
                const oldItems = oldArray || [];
                const newItems = newArray || [];
                const oldPrimitiveList = oldItems.every((item) => item === null || ['string', 'number', 'boolean'].includes(typeof item));
                const newPrimitiveList = newItems.every((item) => item === null || ['string', 'number', 'boolean'].includes(typeof item));
                if (oldPrimitiveList && newPrimitiveList) {
                    changes.push({ path, before: oldValue, after: newValue });
                    return;
                }
                const allIdentified = [...oldItems, ...newItems].every((item) => auditItemIdentity(item) !== null);
                if (allIdentified) {
                    const oldById = new Map(oldItems.map((item) => [auditItemIdentity(item), item]));
                    const newById = new Map(newItems.map((item) => [auditItemIdentity(item), item]));
                    const identities = [...new Set([...oldById.keys(), ...newById.keys()])];
                    identities.forEach((identity) => walk(
                        oldById.has(identity) ? oldById.get(identity) : missingValue,
                        newById.has(identity) ? newById.get(identity) : missingValue,
                        [...path, 'Record ' + identity.split(':').slice(1).join(':')]
                    ));
                    return;
                }
                for (let index = 0; index < Math.max(oldItems.length, newItems.length); index++) {
                    walk(
                        index < oldItems.length ? oldItems[index] : missingValue,
                        index < newItems.length ? newItems[index] : missingValue,
                        [...path, 'Record ' + (index + 1)]
                    );
                }
                return;
            }
            changes.push({ path, before: oldValue, after: newValue });
        };
        walk(before, after, []);
        return { changes, truncated };
    };
    const setAuditChangesMessage = (message) => {
        document.getElementById('auditChangesTable').classList.add('d-none');
        const empty = document.getElementById('auditChangesEmpty');
        empty.textContent = message;
        empty.classList.remove('d-none');
    };
    const renderAuditChanges = (oldRaw, newRaw) => {
        const before = parseAuditValue(oldRaw);
        const after = parseAuditValue(newRaw);
        const body = document.getElementById('auditChanges');
        const empty = document.getElementById('auditChangesEmpty');
        body.replaceChildren();
        const { changes, truncated } = collectAuditChanges(before, after);
        if (changes.length === 0) {
            setAuditChangesMessage(before === missingValue && after === missingValue
                ? 'This action was recorded without before-and-after field details.'
                : 'No differences were found in the recorded fields.');
            return;
        }
        document.getElementById('auditChangesTable').classList.remove('d-none');
        empty.classList.add('d-none');
        changes.forEach((change) => {
            const row = document.createElement('tr');
            const label = document.createElement('th');
            label.scope = 'row';
            label.className = 'audit-change-label';
            label.textContent = change.path.map((part) => /^\d+$/.test(part) ? 'Record ' + (Number(part) + 1)
                : part.startsWith('Record ') ? part : humanizeField(part)).join(' › ') || 'Details';
            const beforeCell = document.createElement('td');
            beforeCell.textContent = formatAuditValue(change.before);
            const afterCell = document.createElement('td');
            afterCell.textContent = formatAuditValue(change.after);
            row.append(label, beforeCell, afterCell);
            body.append(row);
        });
        if (truncated) {
            const row = document.createElement('tr');
            const note = document.createElement('td');
            note.colSpan = 3;
            note.className = 'text-muted small';
            note.textContent = 'Additional changes were recorded but are not shown here.';
            row.append(note);
            body.append(row);
        }
    };
    const formatAction = (value) => {
        const action = String(value || '');
        if (auditActionNames[action]) return auditActionNames[action];
        const readable = action.replace(/^api_/, '').replace(/_/g, ' ');
        return readable ? readable.replace(/\b\w/g, (letter) => letter.toUpperCase()) : 'Unknown action';
    };
    document.querySelectorAll('.js-audit-detail').forEach((button) => {
        button.addEventListener('click', async () => {
            const errorElement = document.getElementById('auditDetailError');
            errorElement.textContent = '';
            errorElement.classList.add('d-none');
            ['auditDetailSummary', 'auditActor', 'auditActorId', 'auditAction', 'auditTarget', 'auditDate',
                'auditDevice', 'auditBrowser', 'auditIp', 'auditLocation', 'auditUserAgent'].forEach((id) => {
                document.getElementById(id).textContent = 'Loading…';
            });
            setAuditChangesMessage('Loading recorded changes…');
            modal.show();
            try {
                const response = await fetch('audit-log.php?detail=' + encodeURIComponent(button.dataset.auditId), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });
                const detail = await response.json();
                if (!response.ok) throw new Error(detail.error || 'The audit details could not be loaded.');
                setText('auditDetailSummary', detail.created_at);
                setText('auditActor', detail.actor);
                setText('auditActorId', detail.actor_user_id === null ? null : String(detail.actor_user_id));
                setText('auditAction', formatAction(detail.action));
                setText('auditTarget', detail.target_legacy_id);
                setText('auditDate', detail.created_at);
                setText('auditDevice', detail.device_type);
                setText('auditBrowser', detail.browser);
                setText('auditIp', detail.ip_address);
                setText('auditLocation', detail.location);
                setText('auditUserAgent', detail.user_agent);
                renderAuditChanges(detail.old_value, detail.new_value);
            } catch (error) {
                errorElement.textContent = error.message || 'The audit details could not be loaded.';
                errorElement.classList.remove('d-none');
                setAuditChangesMessage('Change details could not be loaded.');
            }
        });
    });
})();
</script>
</body>
</html>
