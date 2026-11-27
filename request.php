<?php
require_once __DIR__ . '/config.php';
require_official();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Requests | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">
    <style>
        :root { --line:var(--mem-line); --muted:var(--mem-muted); }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--mem-bg)!important; color:var(--mem-ink)!important; font-family:Arial,sans-serif; }
        .header { min-height:70px; background:var(--mem-surface)!important; border-bottom:1px solid var(--mem-line)!important; display:flex; align-items:center; justify-content:space-between; padding:0 26px; gap:14px; }
        .header-title { font-size:20px; font-weight:800; color:var(--mem-ink)!important; }
        .content { padding:20px; max-width:1680px; margin:0 auto; }
        .records-card { background:var(--mem-surface)!important; border:1px solid var(--mem-line)!important; border-radius:16px; overflow:hidden; box-shadow:0 10px 28px rgba(0,0,0,.07); }
        .review-welcome {
            position:relative;
            overflow:hidden;
            background:linear-gradient(112deg,#1249a8 0%,#1765d6 58%,#4385ec 100%);
            color:#fff;
            border:0;
            border-radius:14px;
            padding:18px 20px;
            margin-bottom:16px;
            box-shadow:0 10px 24px rgba(24,78,174,.16);
        }
        .review-welcome:after{
            content:"";
            position:absolute;
            width:150px;
            height:150px;
            right:-38px;
            top:-88px;
            border:1px solid rgba(255,255,255,.2);
            border-radius:50%;
            box-shadow:0 0 0 22px rgba(255,255,255,.05),0 0 0 48px rgba(255,255,255,.035);
            pointer-events:none;
        }
        .review-welcome .fw-bold{position:relative;z-index:1;font-size:1.05rem}
        .review-welcome small{position:relative;z-index:1;color:rgba(255,255,255,.82)!important}
        .review-date-tools { display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-bottom:12px; }
        .review-date-tools .form-control { width:140px; min-width:125px; height:36px; font-size:13px; }
        .review-date-tools .btn { padding-right:6px; padding-left:6px; font-size:12px; font-weight:700; white-space:nowrap; }
        .review-date-tools .btn.active { background:var(--mem-brand)!important; border-color:var(--mem-brand)!important; color:#fff; }
        .review-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(130px,1fr)); gap:10px; margin-bottom:14px; }
        .review-summary-card { min-height:82px; padding:12px 14px; border:1px solid var(--mem-line)!important; border-top:3px solid var(--review-accent,var(--mem-brand)); border-radius:14px; background:var(--mem-surface)!important; color:var(--mem-ink)!important; text-align:left; box-shadow:0 4px 14px rgba(0,0,0,.04); transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
        .review-summary-card:hover { transform:translateY(-2px); box-shadow:0 10px 22px rgba(0,0,0,.1); }
        .review-summary-card.active { background:var(--mem-surface-alt)!important; box-shadow:0 0 0 2px rgba(var(--mem-brand-rgb),.14),0 8px 18px rgba(0,0,0,.08); }
        .review-summary-number { display:block; font-size:24px; font-weight:800; line-height:1.1; }
        .review-summary-label { display:block; color:var(--mem-muted)!important; font-size:12px; }
        .card-header { background:var(--mem-surface-alt)!important; color:var(--mem-ink)!important; padding:14px 16px; font-size:16px; font-weight:bold; border-bottom:1px solid var(--mem-line)!important; }
        .request-tabs { gap:6px; }
        .request-tabs .nav-link { padding:7px 12px; border-radius:8px; color:inherit; font-size:14px; font-weight:700; white-space:nowrap; }
        .request-tabs .nav-link:hover,.request-tabs .nav-link:focus { background:var(--mem-brand-soft)!important; color:inherit; }
        .request-tabs .nav-link.active { background:var(--mem-brand)!important; color:#fff; }
        .table { font-size:14px; }
        .table th { white-space:nowrap; }
        .table td,.table th { padding:12px; vertical-align:middle; }
        .btn-outline { background:var(--mem-surface)!important; color:var(--mem-ink)!important; border:1px solid var(--mem-line)!important; padding:8px 14px; border-radius:7px; font-weight:700; }
        .btn-black { background:var(--mem-brand-text-emphasis)!important; color:#fff; border:0; padding:8px 14px; border-radius:7px; font-weight:700; }
        .btn-black:hover { background:var(--mem-brand)!important; color:#fff; }
        .footer-note { text-align:center; color:var(--mem-muted)!important; font-size:13px; padding:20px; }
        #toastBox { position:fixed; right:20px; bottom:20px; z-index:2000; width:min(400px,calc(100% - 40px)); }
        @media(max-width:800px) { .review-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:767px) { .content { padding:14px; } .header { padding:0 16px; } .review-date-tools .form-control { flex:1; min-width:120px; } }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
    <?php
    $requestEditItems = json_decode($__data['kkEditRequests'] ?? '[]', true);
    $requestEditItems = is_array($requestEditItems)
        ? array_filter($requestEditItems, function ($request) {
            return is_array($request) && ($request['status'] ?? '') === 'Edit Request';
        })
        : [];
    $requestPendingItems = json_decode($__data['kkPendingRegistrations'] ?? '[]', true);
    $requestPendingItems = is_array($requestPendingItems)
        ? array_filter($requestPendingItems, function ($request) {
            return is_array($request) && ($request['status'] ?? '') === 'Pending';
        })
        : [];
    $requestAttentionCount = count($requestEditItems) + count($requestPendingItems);
    ?>
    <script>
        window.SK_CAN_REVIEW_MEMBER_REQUESTS = <?= in_array(urole(), MEMBER_DATA_ADMIN_ROLES, true) ? 'true' : 'false' ?>;
    </script>
</head>
<body class="review-requests-page">
<div class="top-strip"></div>
<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=20"></script>
<main class="main page-content">
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <div><div class="header-title">Review Requests</div><small>KK Youth Profiling System</small></div>
        </div>
        <div class="header-account-area">
            <span class="header-avatar"><?= e(uinit()) ?></span>
            <div class="d-none d-md-block header-identity"><div class="header-identity-name"><?= e(uname()) ?></div><div class="header-identity-role"><?= e(strtoupper(urole())) ?></div></div>
            <a href="logout.php" class="btn btn-sm header-logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </header>
    <div class="content">
        <section class="records-card">
            <div class="p-3 pb-0">
                <div class="review-welcome">
                    <div class="fw-bold">Review and manage submitted requests</div>
                    <small class="text-muted" id="requestDateLabel">Showing requests from all dates</small>
                </div>
                <div class="review-date-tools" aria-label="Filter requests by date">
                    <label class="visually-hidden" for="requestDateFrom">From date</label>
                    <input class="form-control" type="date" id="requestDateFrom" aria-label="From date">
                    <span class="text-muted">to</span>
                    <label class="visually-hidden" for="requestDateTo">To date</label>
                    <input class="form-control" type="date" id="requestDateTo" aria-label="To date">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-request-range="today">Today</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-request-range="week">This week</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-request-range="first-half">First half</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-request-range="second-half">Second half</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-request-range="month">This month</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-request-range="all">Show all dates</button>
                </div>
                <div class="review-summary-grid" aria-label="Request totals">
                    <button type="button" class="review-summary-card active" style="--review-accent:#d6a62b" data-request-status="pending"><span class="review-summary-number" id="pendingRequestTotal">0</span><span class="review-summary-label">Pending Requests</span></button>
                    <button type="button" class="review-summary-card" style="--review-accent:#3675b9" data-request-status="approved"><span class="review-summary-number" id="approvedRequestTotal">0</span><span class="review-summary-label">Approved</span></button>
                    <button type="button" class="review-summary-card" style="--review-accent:#d94848" data-request-status="rejected"><span class="review-summary-number" id="rejectedRequestTotal">0</span><span class="review-summary-label">Rejected</span></button>
                    <button type="button" class="review-summary-card" style="--review-accent:#0d6efd" data-request-status="done"><span class="review-summary-number" id="doneRequestTotal">0</span><span class="review-summary-label">Done Requests</span></button>
                    <button type="button" class="review-summary-card" style="--review-accent:var(--mem-brand)" data-request-status="all"><span class="review-summary-number" id="allRequestTotal">0</span><span class="review-summary-label">All Requests</span></button>
                </div>
            </div>
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-inbox-fill me-1"></i>Requests to Review <small class="text-white-50 fw-normal ms-2" id="requestTotalCount">0 pending</small></span>
                <div class="nav nav-pills request-tabs" role="tablist" aria-label="Request types">
                    <button class="nav-link active" id="publicRequestsTab" data-bs-toggle="tab" data-bs-target="#publicRequestsPanel" type="button" role="tab" aria-controls="publicRequestsPanel" aria-selected="true">Public Registrations <span class="badge text-bg-light ms-1" id="pubCount">0</span></button>
                    <button class="nav-link" id="editRequestsTab" data-bs-toggle="tab" data-bs-target="#editRequestsPanel" type="button" role="tab" aria-controls="editRequestsPanel" aria-selected="false">Profile Edits <span class="badge text-bg-light ms-1" id="reqCount">0</span></button>
                </div>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="publicRequestsPanel" role="tabpanel" aria-labelledby="publicRequestsTab" tabindex="0">
                    <div class="table-responsive" style="max-height:420px;overflow:auto;"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Applicant</th><th>Purok / Sitio</th><th>Submitted</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody id="pubBody"></tbody>
                    </table></div>
                </div>
                <div class="tab-pane fade" id="editRequestsPanel" role="tabpanel" aria-labelledby="editRequestsTab" tabindex="0">
                    <div class="table-responsive" style="max-height:420px;overflow:auto;"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Member</th><th>Requested On</th><th>Fields Changed</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody id="requestsBody"></tbody>
                    </table></div>
                </div>
            </div>
        </section>
        <div class="footer-note">KK Youth Profiling System &bull; SK Barangay Sikat, Alfonso, Cavite &bull; FY 2026&ndash;2027</div>
    </div>
</main>

<div id="toastBox" aria-live="polite"></div>
<div class="modal fade" id="pubModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header bg-dark text-white"><h6 class="modal-title fw-bold"><i class="bi bi-inbox-fill me-2"></i>Public Registration</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div id="pubBodyDetail"></div><div id="pubRemarksBox" class="mt-3"><label class="form-label small fw-bold" for="pubRemarks">Remarks (required when rejecting)</label><textarea id="pubRemarks" class="form-control" rows="2" maxlength="200"></textarea></div></div>
        <div class="modal-footer" id="pubFooter"></div>
    </div></div>
</div>
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header bg-dark text-white"><h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Request</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body" id="reviewBody"></div>
        <div class="modal-footer"><button type="button" class="btn-outline" data-bs-dismiss="modal">Close</button><button type="button" class="btn-black" id="rejectBtn"><i class="bi bi-x-circle"></i> Reject Changes</button><button type="button" class="btn btn-primary" id="approveBtn"><i class="bi bi-check-circle"></i> Approve Changes</button></div>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="js/request.js?v=<?= filemtime(__DIR__ . '/js/request.js') ?>"></script>
</body>
</html>
