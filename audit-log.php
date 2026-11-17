<?php
require_once __DIR__ . '/config.php';
require_official();
if (!can_view_background_history()) {
    http_response_code(403);
    exit('You do not have permission to view the audit log.');
}

function audit_log_display_value($value) {
    if ($value === null) return '';
    $decoded = json_decode((string)$value, true);
    if (json_last_error() !== JSON_ERROR_NONE) return (string)$value;
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    <link href="sidebar.css?v=31" rel="stylesheet">
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
        .audit-details { min-width:190px; }
        .audit-details summary { color:var(--bs-primary-text-emphasis); cursor:pointer; font-weight:700; }
        .audit-values { display:grid; grid-template-columns:repeat(2,minmax(180px,1fr)); gap:10px; min-width:min(620px,70vw); margin-top:10px; }
        .audit-values section { min-width:0; }
        .audit-values h3 { font-size:12px; font-weight:800; margin-bottom:5px; }
        .audit-values pre { max-height:220px; overflow:auto; white-space:pre-wrap; overflow-wrap:anywhere; background:var(--bs-tertiary-bg); border:1px solid var(--line); border-radius:8px; padding:9px; font-size:12px; margin:0; }
        .audit-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; }
        .footer-note { text-align:center; color:var(--muted); font-size:13px; padding:20px; }
        @media(max-width:767px) {
            .content { padding:14px; }
            .header { padding:0 16px; }
            .audit-heading { align-items:flex-start; flex-direction:column; }
            .table-responsive { max-height:none; }
            .audit-values { grid-template-columns:1fr; min-width:0; }
            .audit-pagination { align-items:flex-start; flex-direction:column; }
        }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
    <?php
    try {
        $auditCount = (int)db()->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
        $pageCount = max(1, (int)ceil($auditCount / $pageSize));
        $page = min($page, $pageCount);
        $offset = ($page - 1) * $pageSize;
        $statement = db()->prepare('SELECT id, actor, target_legacy_id, action, old_value, new_value, created_at
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
<script src="theme.js"></script>
<script src="sidebar.js?v=15"></script>
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
            <p>Review recent changes and actions recorded by the system. Newest entries appear first.</p>
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
                                <th scope="col">Recorded changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$auditRows): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">No audit entries have been recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($auditRows as $row): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e($row['created_at']) ?></td>
                                        <td><?= e($row['actor']) ?></td>
                                        <td class="audit-action"><?= e(ucwords(str_replace('_', ' ', $row['action']))) ?></td>
                                        <td class="audit-target"><?= e($row['target_legacy_id'] ?? '—') ?></td>
                                        <td class="audit-details">
                                            <?php if ($row['old_value'] !== null || $row['new_value'] !== null): ?>
                                                <details>
                                                    <summary>View details</summary>
                                                    <div class="audit-values">
                                                        <section>
                                                            <h3>Before</h3>
                                                            <pre><?= e(audit_log_display_value($row['old_value'])) ?></pre>
                                                        </section>
                                                        <section>
                                                            <h3>After</h3>
                                                            <pre><?= e(audit_log_display_value($row['new_value'])) ?></pre>
                                                        </section>
                                                    </div>
                                                </details>
                                            <?php else: ?>
                                                <span class="text-muted">No change details</span>
                                            <?php endif; ?>
                                        </td>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
