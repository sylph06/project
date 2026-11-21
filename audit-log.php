<?php
require_once __DIR__ . '/config.php';
require_official();
if (!can_view_background_history()) {
    http_response_code(403);
    exit('You do not have permission to view the audit log.');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="css/sidebar.css?v=31" rel="stylesheet">
    <style>
        :root { --line:var(--bs-border-color); --muted:var(--bs-secondary-color); }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bs-tertiary-bg); color:var(--bs-body-color); font-family:Arial,sans-serif; }
        .top-strip { height:7px; background:var(--bs-primary); }
        .header { min-height:70px; background:var(--bs-body-bg); border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 26px; gap:14px; }
        .header-title { font-size:20px; font-weight:800; }
        .content { padding:24px; max-width:1600px; margin:0 auto; }
        .intro, .audit-card { background:var(--bs-body-bg); border:1px solid var(--line); border-radius:14px; }
        .intro { padding:20px; margin-bottom:16px; }
        .intro h1 { font-size:24px; font-weight:900; margin:0 0 5px; }
        .intro p { color:var(--muted); margin:0; }
        .audit-card { overflow:hidden; }
        .audit-heading { padding:16px 18px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .audit-heading h2 { font-size:17px; font-weight:800; margin:0; }
        .table-responsive { max-height:calc(100vh - 350px); min-height:220px; overflow:auto; }
        .table { margin:0; font-size:14px; }
        .table th { white-space:nowrap; background:var(--bs-primary-bg-subtle); color:var(--bs-secondary-color); font-size:12px; text-transform:uppercase; }
        .table td, .table th { padding:12px; vertical-align:top; }
        .audit-action { font-weight:700; white-space:nowrap; }
        .audit-target { overflow-wrap:anywhere; }
        .audit-device { min-width:150px; }
        .audit-detail-dialog { max-width:1120px; }
        .audit-detail-modal { border:0; border-radius:18px; overflow:hidden; box-shadow:0 24px 70px rgba(20,35,60,.22); }
        .audit-detail-header { gap:16px; padding:22px 26px; background:linear-gradient(120deg,var(--bs-primary-bg-subtle),var(--bs-secondary-bg) 68%); border-bottom:1px solid var(--bs-border-color); }
        .audit-detail-heading { display:flex; align-items:center; gap:15px; min-width:0; }
        .audit-detail-icon { display:grid; place-items:center; width:48px; height:48px; flex:none; border-radius:14px; color:var(--bs-primary-text-emphasis); background:var(--bs-primary-bg-subtle); font-size:22px; }
        .audit-detail-copy { min-width:0; }
        .audit-detail-kicker { color:var(--bs-secondary-color); font-size:10px; font-weight:800; letter-spacing:.12em; }
        .audit-detail-title { margin:2px 0 3px; font-size:20px; font-weight:800; }
        .audit-detail-summary { color:var(--bs-secondary-color); font-size:13px; overflow-wrap:anywhere; }
        .audit-detail-body { padding:22px 26px 26px; background:var(--bs-tertiary-bg); }
        .audit-action-summary { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:15px 18px; margin-bottom:18px; background:var(--bs-secondary-bg); border:1px solid var(--bs-border-color); border-left:4px solid var(--bs-primary); border-radius:12px; }
        .audit-summary-label { display:block; margin-bottom:4px; color:var(--bs-secondary-color); font-size:10px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
        .audit-summary-action { font-size:17px; font-weight:750; overflow-wrap:anywhere; }
        .audit-record-badge { background:var(--bs-tertiary-bg); }
        .audit-section { height:100%; padding:17px; background:var(--bs-secondary-bg); border:1px solid var(--bs-border-color); border-radius:14px; }
        .audit-section-heading { display:flex; align-items:center; gap:9px; margin:0 0 15px; font-size:14px; font-weight:800; }
        .audit-section-heading i { color:var(--bs-primary); font-size:16px; }
        .audit-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 12px; }
        .audit-info-item { min-width:0; }
        .audit-info-label { display:block; margin-bottom:4px; color:var(--bs-secondary-color); font-size:10px; font-weight:750; letter-spacing:.06em; text-transform:uppercase; }
        .audit-info-value { color:var(--bs-body-color); font-size:13px; font-weight:600; overflow-wrap:anywhere; }
        .audit-ua { margin-top:15px; padding-top:12px; border-top:1px solid var(--bs-border-color); }
        .audit-ua summary { color:var(--bs-secondary-color); cursor:pointer; font-size:12px; font-weight:700; }
        .audit-ua-value { margin:8px 0 0; color:var(--bs-secondary-color); font-size:11px; overflow-wrap:anywhere; }
        .audit-changes { margin-top:18px; }
        .audit-change-card { height:100%; padding:15px; background:var(--bs-secondary-bg); border:1px solid var(--bs-border-color); border-radius:14px; }
        .audit-change-heading { display:flex; align-items:center; gap:8px; margin:0 0 11px; font-size:13px; font-weight:800; }
        .audit-change-heading i { color:var(--bs-secondary-color); }
        .audit-change-heading.is-after i { color:var(--bs-success-text-emphasis); }
        .audit-value { max-height:36vh; min-height:100px; overflow:auto; white-space:pre-wrap; overflow-wrap:anywhere; background:var(--bs-tertiary-bg); border:1px solid var(--bs-border-color); border-radius:9px; padding:12px; color:var(--bs-body-color); font:12px/1.55 ui-monospace,SFMono-Regular,Consolas,monospace; margin:0; }
        .audit-detail-note { margin:14px 2px 0; color:var(--bs-secondary-color); font-size:11px; }
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
            .audit-value { max-height:32vh; }
        }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
    <?php
    try {
        $auditCount = (int)db()->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
        $pageCount = max(1, (int)ceil($auditCount / $pageSize));
        $page = min($page, $pageCount);
        $offset = ($page - 1) * $pageSize;
        $statement = db()->prepare('SELECT id, actor, target_legacy_id, action, device_type, browser, created_at
            FROM audit_log
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset');
        $statement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $auditRows = $statement->fetchAll(PDO::FETCH_ASSOC);
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
<script src="js/sidebar.js?v=15"></script>
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
                            <a class="btn btn-outline-secondary btn-sm" href="audit-log.php?page=<?= (int)($page - 1) ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $pageCount): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="audit-log.php?page=<?= (int)($page + 1) ?>">Next</a>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body audit-detail-body">
                <section class="audit-action-summary" aria-label="Selected action">
                    <div>
                        <span class="audit-summary-label">Action performed</span>
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
                    <h3 class="audit-section-heading" id="auditChangesHeading"><i class="bi bi-arrow-left-right"></i>Recorded changes</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <section class="audit-change-card">
                                <h4 class="audit-change-heading"><i class="bi bi-arrow-counterclockwise"></i>Before</h4>
                                <pre class="audit-value" id="auditBefore"></pre>
                            </section>
                        </div>
                        <div class="col-md-6">
                            <section class="audit-change-card">
                                <h4 class="audit-change-heading is-after"><i class="bi bi-check2-circle"></i>After</h4>
                                <pre class="audit-value" id="auditAfter"></pre>
                            </section>
                        </div>
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
    const formatValue = (value) => {
        if (value === null || value === '') return 'No data recorded.';
        try { return JSON.stringify(JSON.parse(value), null, 2); }
        catch (_) { return value; }
    };
    const formatAction = (value) => String(value || 'Unknown action')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
    document.querySelectorAll('.js-audit-detail').forEach((button) => {
        button.addEventListener('click', async () => {
            const errorElement = document.getElementById('auditDetailError');
            errorElement.textContent = '';
            errorElement.classList.add('d-none');
            ['auditDetailSummary', 'auditActor', 'auditActorId', 'auditAction', 'auditTarget', 'auditDate',
                'auditDevice', 'auditBrowser', 'auditIp', 'auditLocation', 'auditUserAgent',
                'auditBefore', 'auditAfter'].forEach((id) => {
                document.getElementById(id).textContent = 'Loading…';
            });
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
                document.getElementById('auditBefore').textContent = formatValue(detail.old_value);
                document.getElementById('auditAfter').textContent = formatValue(detail.new_value);
            } catch (error) {
                errorElement.textContent = error.message || 'The audit details could not be loaded.';
                errorElement.classList.remove('d-none');
            }
        });
    });
})();
</script>
</body>
</html>
