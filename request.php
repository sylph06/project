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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="sidebar.css?v=12" rel="stylesheet">
    <style>
        :root { --line:var(--bs-border-color); --muted:var(--bs-secondary-color); }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bs-tertiary-bg); color:var(--bs-body-color); font-family:Arial,sans-serif; }
        .header { min-height:70px; background:#fff; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 26px; gap:14px; }
        .header-title { font-size:20px; font-weight:800; }
        .content { padding:20px; max-width:1500px; margin:0 auto; }
        .records-card { background:#fff; border:1px solid var(--line); border-radius:14px; overflow:hidden; }
        .review-welcome { background:#fff; border:1px solid var(--line); border-left:4px solid var(--bs-primary); border-radius:12px; padding:14px 18px; margin-bottom:16px; }
        .review-date-tools { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:12px; }
        .review-date-tools .form-control { width:auto; min-width:145px; height:36px; font-size:13px; }
        .review-date-tools .btn { font-size:12px; font-weight:700; white-space:nowrap; }
        .review-date-tools .btn.active { background:var(--bs-primary-text-emphasis); border-color:var(--bs-primary-text-emphasis); color:#fff; }
        .review-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(130px,1fr)); gap:10px; margin-bottom:14px; }
        .review-summary-card { min-height:70px; padding:10px 12px; border:1px solid var(--line); border-left:4px solid var(--review-accent,var(--bs-primary)); border-radius:10px; background:#fff; color:var(--bs-body-color); text-align:left; }
        .review-summary-card.active { box-shadow:0 0 0 2px var(--bs-primary-text-emphasis); }
        .review-summary-number { display:block; font-size:22px; font-weight:800; line-height:1.1; }
        .review-summary-label { display:block; color:var(--muted); font-size:12px; }
        .card-header { background:var(--bs-primary-text-emphasis); color:#fff; padding:14px 16px; font-size:16px; font-weight:bold; }
        .request-tabs { gap:6px; }
        .request-tabs .nav-link { padding:7px 12px; border-radius:8px; color:#fff; font-size:14px; font-weight:700; white-space:nowrap; }
        .request-tabs .nav-link:hover,.request-tabs .nav-link:focus { background:rgba(255,255,255,.14); color:#fff; }
        .request-tabs .nav-link.active { background:#fff; color:var(--bs-primary-text-emphasis); }
        .table { font-size:14px; }
        .table th { white-space:nowrap; }
        .table td,.table th { padding:12px; vertical-align:middle; }
        .btn-outline { background:#fff; color:var(--bs-body-color); border:1px solid var(--line); padding:8px 14px; border-radius:7px; font-weight:700; }
        .btn-black { background:var(--bs-primary-text-emphasis); color:#fff; border:0; padding:8px 14px; border-radius:7px; font-weight:700; }
        .btn-black:hover { background:var(--bs-primary); color:#fff; }
        .footer-note { text-align:center; color:var(--muted); font-size:13px; padding:20px; }
        #toastBox { position:fixed; right:20px; bottom:20px; z-index:2000; width:min(400px,calc(100% - 40px)); }
        @media(max-width:800px) { .review-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:767px) { .content { padding:14px; } .header { padding:0 16px; } .review-date-tools .form-control { flex:1; min-width:120px; } }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
</head>
<body>
<div class="top-strip"></div>
<script src="theme.js"></script>
<script src="sidebar.js?v=7"></script>
<main class="main page-content">
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <div><div class="header-title">Review Requests</div><small>KK Youth Profiling System</small></div>
        </div>
        <div class="header-account-area">
            <a href="announcements.php" class="header-notification" aria-label="Notifications"><i class="bi bi-bell"></i><span class="header-notification-count">3</span></a>
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
                    <button type="button" class="review-summary-card" style="--review-accent:#219653" data-request-status="done"><span class="review-summary-number" id="doneRequestTotal">0</span><span class="review-summary-label">Done Requests</span></button>
                    <button type="button" class="review-summary-card" style="--review-accent:var(--bs-primary)" data-request-status="all"><span class="review-summary-number" id="allRequestTotal">0</span><span class="review-summary-label">All Requests</span></button>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="request.js?v=1"></script>
</body>
</html>
