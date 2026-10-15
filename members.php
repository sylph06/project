<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member List | SK KK Profiling System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">

<style>

:root{
    --kk-primary:var(--bs-primary);
    --kk-primary-dark:var(--bs-primary-text-emphasis);
    --kk-primary-soft:rgba(var(--bs-primary-rgb),.12);
    --black:var(--bs-body-color);
    --dark:var(--bs-primary-text-emphasis);
    --cream:var(--bs-tertiary-bg);
    --line:var(--bs-border-color);
    --muted:var(--bs-secondary-color);
    --white:#ffffff;
}

*{ box-sizing:border-box; }

body{
    margin:0;
    background:var(--bs-tertiary-bg);
    color:var(--black);
    font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

a{ text-decoration:none; }

.top-strip{ height:7px; background:var(--bs-primary); }

header{
    background:#fff!important;
    border-bottom:1px solid var(--line)!important;
}

header h1{ font-weight:900; }

.btn-success{
    --bs-btn-bg:var(--bs-body-color);
    --bs-btn-border-color:var(--bs-body-color);
    --bs-btn-hover-bg:var(--kk-primary);
    --bs-btn-hover-border-color:var(--kk-primary);
    --bs-btn-hover-color:#fff;
    --bs-btn-active-bg:var(--bs-body-color);
    --bs-btn-active-border-color:var(--bs-body-color);
    font-weight:800;
}

.btn-outline-success{
    --bs-btn-color:var(--bs-body-color);
    --bs-btn-border-color:var(--bs-body-color);
    --bs-btn-hover-bg:var(--kk-primary);
    --bs-btn-hover-border-color:var(--kk-primary);
    --bs-btn-hover-color:#fff;
    --bs-btn-active-bg:var(--kk-primary);
    --bs-btn-active-border-color:var(--kk-primary);
    font-weight:700;
}

.member-hero{
    position:relative;
    overflow:hidden;
    background:var(--bs-primary);
    border-radius:26px;
    padding:28px;
    box-shadow:0 12px 30px rgba(20,20,20,.08);
}
.member-hero:after{
    content:"";
    position:absolute;
    width:220px;
    height:220px;
    border-radius:50%;
    right:-70px;
    top:-95px;
    background:var(--bs-primary-text-emphasis);
    opacity:.08;
}
.member-hero-content{ position:relative; z-index:2; }
.member-icon{
    width:58px;
    height:58px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:17px;
    background:var(--bs-primary-text-emphasis);
    color:var(--kk-primary);
    font-size:1.6rem;
}
.member-hero h2{ font-weight:950; letter-spacing:-.7px; }
.member-hero p{ color:var(--bs-body-color); max-width:680px; }

.stat-card{
    height:100%;
    border:1px solid var(--line);
    border-radius:20px;
    background:#fff;
    box-shadow:0 8px 20px rgba(20,20,20,.05);
    transition:.2s ease;
}
.stat-card:hover{ transform:translateY(-4px); box-shadow:0 14px 28px rgba(20,20,20,.09); }
.stat-icon{
    width:45px;
    height:45px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-size:1.2rem;
}
.stat-link{ cursor:pointer; }
.stat-link:focus-visible{ outline:3px solid var(--bs-primary-border-subtle); outline-offset:2px; }
.stat-link.selected{ border-color:var(--bs-primary); box-shadow:0 0 0 2px var(--bs-primary), 0 14px 28px rgba(20,20,20,.09); }
.stat-number{ font-size:1.8rem; font-weight:950; letter-spacing:-1px; }

/* overflow is visible so the Print / Manage dropdowns are not clipped */
.member-card{
    border:1px solid var(--line)!important;
    border-radius:22px!important;
    box-shadow:0 8px 22px rgba(20,20,20,.05)!important;
    background:#fff;
}
.member-card .card-header{
    background:#fff!important;
    border-bottom:1px solid var(--line);
    border-radius:22px 22px 0 0;
}
.member-card .card-footer{ border-radius:0 0 22px 22px; }

.search-box{ position:relative; }
.search-box i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:var(--bs-secondary-color);
}
.search-box input{ padding-left:42px; }

.form-control,
.form-select{
    border:1px solid var(--bs-border-color);
    border-radius:12px;
    min-height:43px;
    box-shadow:none!important;
}
textarea.form-control{ min-height:0; }
.form-control:focus,
.form-select:focus{
    border-color:var(--bs-body-color);
    box-shadow:0 0 0 3px rgba(var(--bs-primary-rgb),.28)!important;
}

.table{ --bs-table-hover-bg:rgba(var(--bs-primary-rgb),.08); }
.table thead th{
    background:rgba(var(--bs-primary-rgb),.08);
    color:var(--bs-secondary-color);
    font-size:.73rem;
    text-transform:uppercase;
    letter-spacing:.45px;
    white-space:nowrap;
}
.table td{ font-size:.86rem; }

.member-avatar{
    width:39px;
    height:39px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-weight:900;
    flex:none;
}
.thumb{ flex:none; object-fit:cover; border-radius:50%; background:var(--bs-primary); display:inline-flex; align-items:center; justify-content:center; font-weight:900; color:#fff; }
.member-name{ font-weight:800; }
.member-id{ font-size:.72rem; color:var(--bs-secondary-color); }

.status-active{ background:var(--bs-primary); color:var(--bs-body-color); }
.status-pending,
.status-inactive{ background:var(--bs-secondary-bg-subtle); color:var(--bs-secondary-text-emphasis); }
.status-archived{ background:var(--bs-secondary); color:#fff; }

.action-btn{
    width:34px;
    height:34px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    padding:0;
}

.pagination .page-link{ color:var(--bs-body-color); border-color:var(--line); }
.pagination .page-item.active .page-link{
    background:var(--bs-primary-text-emphasis);
    border-color:var(--bs-body-color);
    color:#fff;
}

.empty-state{ padding:50px 20px; text-align:center; color:var(--bs-secondary-color); }
.empty-state i{ font-size:3rem; color:var(--bs-border-color); }

/* ---- profile cards ---- */
.person-card{
    border:1px solid var(--line);
    border-radius:20px;
    background:#fff;
    transition:.2s ease;
}
.person-card:hover{ box-shadow:0 12px 26px rgba(20,20,20,.09); transform:translateY(-3px); }
.person-card .desc{
    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;
    overflow:hidden;
    color:var(--bs-secondary-color);
    font-size:.84rem;
    min-height:3.9em;
}
.person-card .meta{ font-size:.82rem; color:var(--bs-secondary-color); }
.person-card .meta i{ width:1.3em; display:inline-block; }
.person-card.is-archived{ opacity:.78; }
.type-pill{
    font-size:.68rem;
    font-weight:800;
    letter-spacing:.4px;
    padding:.3em .65em;
    border-radius:999px;
    background:var(--bs-secondary-bg-subtle);
    color:var(--bs-secondary-text-emphasis);
}

/* ---- profile modal ---- */
.kv-l{ font-size:.74rem; color:var(--bs-secondary-color); }
.kv-v{ font-size:.9rem; font-weight:700; word-break:break-word; }
.group-title{
    font-weight:800;
    font-size:.82rem;
    padding-bottom:6px;
    margin:20px 0 10px;
    border-bottom:2px solid var(--bs-primary);
}
.modal-content{ border-radius:22px; }
.btn-close-white-fix{ filter:invert(1); }
#toastBox{ position:fixed; top:16px; right:16px; z-index:2000; width:min(380px,calc(100vw - 32px)); }

/* ---- printing ---- */
#printArea{ display:none; }
@media print{
    body > *:not(#printArea){ display:none!important; }
    #printArea{ display:block!important; padding:10px; color:#000; font-size:12px; }
    #printArea .group-title{ border-bottom:2px solid #000; }
    #printArea table{ width:100%; border-collapse:collapse; }
    #printArea th,#printArea td{ border:1px solid #999; padding:4px 6px; text-align:left; vertical-align:top; }
    #printArea .thumb{ border:1px solid #999; }
}

@media(max-width:575px){
    .member-hero{ padding:22px; }
    .member-hero h2{ font-size:1.55rem; }
    .stat-number{ font-size:1.5rem; }
}

/* sidebar.css makes modal titles white, so the header needs the primary colour */
.modal-header{ background:var(--bs-primary); border-bottom:0; border-radius:22px 22px 0 0; }
.modal-body .search-box i{ z-index:3; }
#pickMember{ border-radius:12px; }

/* ---- compact member card (same style as the announcement cards, smaller) ---- */
.mini-card{ border:1px solid var(--line); border-radius:16px; background:#fff; overflow:hidden; transition:.2s ease; }
.mini-card:hover{ box-shadow:0 10px 22px rgba(20,20,20,.09); transform:translateY(-2px); }
.mini-card.is-archived{ opacity:.75; }
.mini-banner{ position:relative; height:84px; background:rgba(var(--bs-primary-rgb),.14); }
.mini-banner:before{ content:""; position:absolute; width:110px; height:110px; border-radius:50%; right:-30px; top:-55px; background:rgba(var(--bs-primary-rgb),.2); }
.mini-pill{ position:absolute; font-size:.62rem; font-weight:800; padding:.28em .7em; border-radius:999px; line-height:1.2; max-width:62%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mini-pill-main{ top:8px; left:8px; background:var(--bs-primary); color:#fff; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,.15); }
.mini-pill-dark{ bottom:8px; left:8px; background:#fff; color:var(--bs-body-color); border:1.5px solid var(--bs-body-color); }
.mini-pill-status{ bottom:8px; right:8px; background:var(--bs-primary-text-emphasis); color:#fff; }
.mini-age{ position:absolute; top:8px; right:8px; width:38px; text-align:center; background:var(--bs-primary); color:#fff; border:2px solid var(--bs-body-color); border-radius:10px; padding:2px 0; line-height:1; }
.mini-age small{ display:block; font-size:.5rem; font-weight:800; letter-spacing:.4px; }
.mini-age b{ font-size:.95rem; }
.mini-avatar{ position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); }
.mini-avatar .thumb{ border:3px solid #fff; box-shadow:0 2px 8px rgba(0,0,0,.15); }
.mini-body{ padding:10px 12px 12px; }
.mini-name{ font-weight:800; font-size:.92rem; }
.mini-desc{ font-size:.74rem; color:var(--bs-secondary-color); display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; margin-bottom:6px; min-height:1.2em; }
.mini-row{ display:flex; align-items:center; gap:8px; font-size:.76rem; margin-bottom:4px; min-width:0; }
.mini-row i{ flex:none; width:22px; height:22px; border-radius:6px; background:var(--bs-primary); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; }
.mini-actions{ display:flex; gap:5px; margin-top:8px; }
.mini-view{ flex:1; background:var(--bs-body-color); color:#fff; font-weight:800; font-size:.78rem; border-radius:9px; padding:.3rem .5rem; }
.mini-view:hover{ background:var(--bs-primary); color:#fff; }
.mini-ico{ width:30px; height:30px; padding:0; border:1px solid var(--bs-body-color); border-radius:50%; background:#fff; color:var(--bs-body-color); font-size:.8rem; display:inline-flex; align-items:center; justify-content:center; }
.mini-ico:hover{ background:var(--bs-primary); border-color:var(--bs-primary); color:#fff; }
.mini-del{ border-color:var(--bs-danger); color:var(--bs-danger); }
.mini-del:hover{ background:var(--bs-danger); border-color:var(--bs-danger); }

/* ---- assign SK role: scrollable form + position choices + always-visible Save ---- */
#roleModal .modal-body{ overflow-y:auto; max-height:calc(100vh - 190px); }
#pickMember{ max-height:190px; overflow-y:auto; }
.pos-choices{ display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:8px; }
.pos-choice{ text-align:left; border:1.5px solid var(--bs-border-color); border-radius:12px; background:#fff; padding:8px 10px; cursor:pointer; transition:.15s ease; color:var(--bs-body-color); }
.pos-choice:hover:not(:disabled){ border-color:var(--bs-primary); background:rgba(var(--bs-primary-rgb),.06); }
.pos-choice.active{ background:var(--bs-primary); border-color:var(--bs-primary); color:#fff; }
.pos-choice:disabled{ opacity:.5; cursor:not-allowed; }
.pos-choice b{ display:block; font-size:.85rem; }
.pos-choice small{ font-size:.7rem; opacity:.85; }
#roleModal .modal-footer{ position:sticky; bottom:0; background:#fff; border-top:1px solid var(--bs-border-color); border-radius:0 0 22px 22px; }
</style>

<?php include __DIR__ . '/init.php'; ?>
</head>

<body>

<div class="top-strip"></div>

<script src="sidebar.js?v=3"></script>

<div class="page-content">

<header class="bg-white border-bottom sticky-top">

    <div class="d-flex align-items-center gap-2 gap-md-3 px-3 px-lg-4 py-2 py-lg-3">

        <button class="btn btn-outline-success d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebar"
                aria-controls="sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div>
            <h1 class="h5 fw-bold mb-0">Member List</h1>
            <div class="text-muted small d-none d-sm-block">
                All KK youth members, including SK officials
            </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

            <a href="announcements.php"
               class="btn btn-outline-success btn-sm position-relative"
               aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                      style="background:var(--bs-primary);color:var(--bs-body-color);">3</span>
            </a>

            <span class="avatar"
                  style="width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:var(--bs-primary);color:var(--bs-body-color);font-weight:900;border:2px solid var(--bs-body-color);">
                <?= e(uinit()) ?>
            </span>

            <div class="d-none d-md-block lh-sm">
                <div class="fw-semibold small"><?= e(uname()) ?></div>
                <div class="text-muted" style="font-size:.75rem;"><?= e(strtoupper(urole())) ?></div>
            </div>

            <a href="logout.php" class="btn btn-success btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>
                <?= is_public() ? 'Official Login' : 'Logout' ?>
            </a>

        </div>

    </div>

</header>

<main class="container-fluid p-3 p-lg-4">

    <!-- ================= HERO + QUICK ACTIONS ================= -->
    <section class="member-hero mb-4">
        <div class="member-hero-content">

            <div class="member-icon mb-3">
                <i class="bi bi-people-fill"></i>
            </div>

            <h2 class="mb-2">Member List</h2>

            <p class="mb-3">
                One central list of all registered KK youth of Barangay Sikat.
                SK officials are KK members too &ndash; select a member and assign an SK position.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-dark rounded-pill px-4" id="heroViewSk">
                    <i class="bi bi-person-vcard me-2"></i>View SK Members
                </button>
                <button type="button" class="btn btn-dark rounded-pill px-4" id="heroViewKk">
                    <i class="bi bi-people me-2"></i>View Regular KK Members
                </button>
                <button type="button" class="btn btn-dark rounded-pill px-4 admin-only role-manager-only" id="heroAssign">
                    <i class="bi bi-person-badge me-2"></i>Assign SK Member
                </button>
                <a href="registration.php" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-person-plus-fill me-2"></i>Register KK Youth
                </a>
            </div>

        </div>
    </section>

    <div id="unlinkedBox"></div>

    <!-- ================= SUMMARY CARDS ================= -->
    <section class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">

        <div class="col">
            <div class="card stat-card stat-link" data-stat="ALL" role="button" tabindex="0" title="Click to show these members"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="fw-semibold text-muted small">All KK Members</span>
                    <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
                </div>
                <h3 class="stat-number mb-1" id="statTotal">0</h3>
                <p class="small text-muted mb-0" id="statTotalSub">Registered KK youth</p>
            </div></div>
        </div>

        <div class="col">
            <div class="card stat-card stat-link" data-stat="SK" role="button" tabindex="0" title="Click to show these members"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="fw-semibold text-muted small">SK Members</span>
                    <span class="stat-icon"><i class="bi bi-person-vcard-fill"></i></span>
                </div>
                <h3 class="stat-number mb-1" id="statSk">0</h3>
                <p class="small text-muted mb-0" id="statSkSub">KK members with an SK position</p>
            </div></div>
        </div>

        <div class="col">
            <div class="card stat-card stat-link" data-stat="KK" role="button" tabindex="0" title="Click to show these members"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="fw-semibold text-muted small">Regular KK Members</span>
                    <span class="stat-icon"><i class="bi bi-person-lines-fill"></i></span>
                </div>
                <h3 class="stat-number mb-1" id="statKk">0</h3>
                <p class="small text-muted mb-0" id="statKkSub">No SK position</p>
            </div></div>
        </div>

        <div class="col">
            <div class="card stat-card stat-link" data-stat="ACTIVE_SK" role="button" tabindex="0" title="Click to show these members"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="fw-semibold text-muted small">Active SK Members</span>
                    <span class="stat-icon"><i class="bi bi-person-check-fill"></i></span>
                </div>
                <h3 class="stat-number mb-1" id="statActiveSk">0</h3>
                <p class="small text-muted mb-0">Currently serving</p>
            </div></div>
        </div>

    </section>

    <!-- ================= MEMBER MANAGEMENT ================= -->
    <div class="card member-card">

        <div class="card-header p-3 p-lg-4">

            <div class="d-flex flex-column flex-xl-row gap-3 justify-content-between mb-3">

                <div>
                    <h2 class="h6 fw-bold mb-1">Member Directory</h2>
                    <div class="small text-muted" id="directorySub">Search, filter and manage member profiles</div>
                </div>

                <div class="d-flex flex-wrap gap-2">

                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="true" aria-controls="filterPanel">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>

                    <div class="dropdown admin-only">
                        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" type="button" id="printListBtn"><i class="bi bi-list-ul me-2"></i>Print member list</button></li>
                        </ul>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear me-1"></i><?= is_public() ? 'Options' : 'Manage' ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="admin-only role-manager-only"><button class="dropdown-item" type="button" id="assignBtn"><i class="bi bi-person-badge me-2"></i>Assign SK member</button></li>
                            <li class="admin-only"><button class="dropdown-item" type="button" id="exportBtn"><i class="bi bi-filetype-csv me-2"></i>Export list (CSV)</button></li>
                            <li class="admin-only role-manager-only"><button class="dropdown-item" type="button" id="termCheckBtn"><i class="bi bi-calendar-check me-2"></i>Check ended SK terms</button></li>
                            <li class="admin-only"><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item" type="button" id="resetBtn"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset filters</button></li>
                        </ul>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
                        <button type="button" class="btn btn-outline-success" id="viewCards" title="Card view"><i class="bi bi-grid"></i></button>
                        <button type="button" class="btn btn-outline-success" id="viewTable" title="Table view"><i class="bi bi-table"></i></button>
                    </div>

                </div>
            </div>

            <div class="row g-2">
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label small fw-semibold mb-1" for="typeFilter">Member Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="ALL">All KK Members</option>
                        <option value="SK">SK Members</option>
                        <option value="KK">Regular KK Members</option>
                    </select>
                </div>
                <div class="col-12 col-md-8 col-xl-9">
                    <label class="form-label small fw-semibold mb-1" for="searchInput">Search</label>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search name, SK position, contact, email, Purok, ID...">
                    </div>
                </div>
            </div>

            <div class="collapse show" id="filterPanel">
                <div class="row g-2 mt-1">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="positionFilter">SK Position</label>
                        <select class="form-select" id="positionFilter"></select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="statusFilter">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="purokFilter">Purok</label>
                        <select class="form-select" id="purokFilter"></select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="sortSelect">Sort by</label>
                        <select class="form-select" id="sortSelect">
                            <option value="name-asc">Name (A to Z)</option>
                            <option value="name-desc">Name (Z to A)</option>
                            <option value="position">SK position</option>
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <div class="card-body p-3 p-lg-4" id="results" aria-live="polite"></div>

        <div class="card-footer bg-white border-top p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="small text-muted" id="resultCount">Showing 0 members</div>
                <nav aria-label="Member list pagination">
                    <ul class="pagination pagination-sm mb-0" id="pager"></ul>
                </nav>
            </div>
        </div>

    </div>

    <div class="text-center text-muted small py-4">
        KK Youth Profiling System &bull; SK Barangay Sikat, Alfonso, Cavite &bull; FY 2026&ndash;2027
    </div>

</main>

</div>

<!-- ================= PROFILE MODAL ================= -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="profileTitle">Member Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="profileBody"></div>
            <div class="modal-footer flex-wrap gap-2" id="profileFooter"></div>
        </div>
    </div>
</div>

<!-- ================= ASSIGN / EDIT SK ROLE MODAL ================= -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <form id="roleForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="roleModalTitle">Assign SK Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- choose an existing KK member -->
                    <div id="pickBox">
                        <label class="form-label fw-semibold" for="pickSearch">Select KK Member <span class="text-danger">*</span></label>
                        <div class="search-box mb-2">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" id="pickSearch" placeholder="Search a registered KK youth by name, ID or Purok...">
                        </div>
                        <select class="form-select" id="pickMember" size="6"></select>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">Only registered, active KK youth without an SK position are listed. Not in the list? Register them first in KK Youth Registration.</div>
                    </div>

                    <!-- member summary (read-only: comes from the KK record) -->
                    <div id="memberSummary" class="d-none"></div>

                    <div class="group-title">SK Position and Status</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">SK Position <span class="text-danger">*</span></label>
                            <div class="pos-choices" id="posChoices" role="radiogroup" aria-label="SK position"></div>
                            <select class="d-none" id="rolePosition"></select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="roleStatus">SK Status</label>
                            <select class="form-select" id="roleStatus">
                                <option>Active</option>
                                <option>Inactive</option>
                                <option>Archived</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="roleBasis">Basis <span class="text-danger">*</span></label>
                            <select class="form-select" id="roleBasis">
                                <option value="">Select basis</option>
                                <option value="elected">Elected</option>
                                <option value="appointed">Appointed</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="group-title">Election and Term</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="roleYear">Election Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="roleYear" min="2000" max="2100" placeholder="e.g. 2026">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="roleElected">Date Elected <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="roleElected">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4"></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="roleStart">Term Start <span class="text-muted">(optional)</span></label>
                            <input type="date" class="form-control" id="roleStart">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="roleEnd">Term End <span class="text-muted">(optional)</span></label>
                            <input type="date" class="form-control" id="roleEnd">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="group-title">Description</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="roleDesc">Short Description</label>
                            <textarea class="form-control" id="roleDesc" rows="2" maxlength="500" placeholder="Short description of this SK official"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="roleResp">Responsibilities</label>
                            <textarea class="form-control" id="roleResp" rows="3" maxlength="600" placeholder="Filled in automatically from the position. You can edit it."></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="roleSaveBtn">
                        <i class="bi bi-save me-2"></i><span id="roleSaveLabel">Assign SK Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= LINK EXISTING MEMBER ACCOUNT MODAL ================= -->
<div class="modal fade" id="linkAccountModal" tabindex="-1" aria-labelledby="linkAccountTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="linkAccountTitle">Link an Existing Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Linking connects one existing login to this KK member. It does not create an account or change its credentials.</p>
                <label class="form-label" for="linkAccountSelect">Unlinked account</label>
                <select class="form-select" id="linkAccountSelect"></select>
                <div class="small text-danger mt-2" id="linkAccountError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmLinkAccount">Link account</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= CREATE MEMBER ACCOUNT MODAL ================= -->
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="createAccountTitle">Create Separate Member Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">This is separate from assigning an SK role. Each KK member may have only one login.</p>
                <label class="form-label" for="newAccountUsername">Username</label>
                <input class="form-control mb-3" id="newAccountUsername" maxlength="50" autocomplete="off">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="guardianConsent">
                    <label class="form-check-label" for="guardianConsent">Guardian consent has been obtained (when required)</label>
                </div>
                <label class="form-label" for="consentVersion">Consent form version (if consent is recorded)</label>
                <input class="form-control" id="consentVersion" maxlength="50" placeholder="Enter the actual form version">
                <div class="small text-danger mt-2" id="createAccountError"></div>
                <div class="alert alert-success d-none mt-3 mb-0" id="temporaryPasswordBox"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="confirmCreateAccount">Create account</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= CONFIRMATION MODAL ================= -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="cfTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="cfTitle">Are you sure?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p class="mb-0" id="cfText"></p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" id="cfOk">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div id="toastBox" aria-live="assertive"></div>
<div id="printArea"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="sk-data.js"></script>
<script>
(function () {
    'use strict';

    var D = window.SKData;
    if (!window.SK_CAN_MANAGE_ROLES) {
        document.querySelectorAll('.role-manager-only').forEach(function (element) {
            element.classList.add('d-none');
        });
    }
    var MIN_AGE = 15, MAX_AGE = 30, PAGE_SIZE = 12;
    var BARANGAY = 'Sikat', CITY = 'Alfonso', PROVINCE = 'Cavite';
    var POSITIONS = D.POSITIONS, ROLES = D.ROLES;
    var PUROKS = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5'];
    var kkName = D.kkName, calcAge = D.calcAge, norm = D.norm, todayISO = D.todayISO;

    /* ---------- helpers ---------- */
    function $(id) { return document.getElementById(id); }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function fmtDate(iso) {
        if (!iso) return '\u2014';
        var d = new Date(String(iso).length === 10 ? iso + 'T00:00:00' : iso);
        return isNaN(d) ? '\u2014' : d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    function yearOf(iso) { return iso ? String(iso).slice(0, 4) : ''; }
    function modal(id) { return bootstrap.Modal.getOrCreateInstance($(id)); }
    function isOpen(id) { return $(id).classList.contains('show'); }
    function afterHide(id, fn) {
        if (!isOpen(id)) { fn(); return; }
        $(id).addEventListener('hidden.bs.modal', function once() { $(id).removeEventListener('hidden.bs.modal', once); fn(); });
        modal(id).hide();
    }
    function notify(type, text) {
        var div = document.createElement('div');
        div.className = 'alert alert-' + type + ' shadow small mb-2';
        div.textContent = text;
        $('toastBox').appendChild(div);
        setTimeout(function () { div.remove(); }, 5000);
    }
    function setErr(el, msg) {
        el.classList.add('is-invalid');
        var fb = el.parentElement.querySelector('.invalid-feedback');
        if (fb) { fb.textContent = msg; fb.style.display = 'block'; }
    }
    function clearErrs(scope) {
        scope.querySelectorAll('.is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); });
        scope.querySelectorAll('.invalid-feedback').forEach(function (e) { e.textContent = ''; e.style.display = ''; });
    }
    function fillSelect(sel, list, first) {
        sel.innerHTML = (first !== undefined ? '<option value="">' + esc(first) + '</option>' : '') +
            list.map(function (t) { return '<option>' + esc(t) + '</option>'; }).join('');
    }
    function reload() { D.load(); }

    /* ---------- member records (one per KK member, SK info joined in) ---------- */
    function addrOf(m) { return [m.houseNo, m.street, m.purok, 'Brgy. ' + (m.barangay || BARANGAY), m.city || CITY, m.province || PROVINCE].filter(Boolean).join(', '); }
    function eligibility(m) {
        var a = m.birthDate ? calcAge(m.birthDate) : NaN;
        if (isNaN(a)) return 'Unknown';
        return a < MIN_AGE ? 'Under age' : (a > MAX_AGE ? 'Over age' : 'Eligible');
    }
    function buildRecords() {
        return D.kk.map(function (m) {
            var r = D.roleOf(m.id), age = m.birthDate ? calcAge(m.birthDate) : '';
            var kkStatus = m.status === 'Archived' ? 'Archived' : 'Active';
            return {
                key: m.id, kk: m, role: r || null, isSK: !!r, name: kkName(m), photo: m.photo || '', age: age,
                contact: m.mobile || '', email: m.email || '', purok: m.purok || '', address: addrOf(m), code: m.memberNo || '',
                kkStatus: kkStatus, position: r ? r.position : '', skStatus: r ? r.status : '',
                status: r ? r.status : kkStatus, rank: r ? Math.max(0, POSITIONS.indexOf(r.position)) + 1 : 9,
                created: m.createdAt || '',
                text: [kkName(m), m.mobile, m.email, m.purok, m.memberNo, m.street, m.houseNo, m.gender, r ? r.position : '', r ? r.electionYear : ''].join(' ').toLowerCase()
            };
        });
    }
    function getRec(key) { return buildRecords().filter(function (r) { return r.key === key; })[0]; }

    function termLabel(r) { return r && r.termStart ? yearOf(r.termStart) + '\u2013' + yearOf(r.termEnd) : ''; }
    function termLine(r) {
        if (!r.role) return '\u2014';
        var t = termLabel(r.role);
        return (t ? 'Term ' + t : 'No term') + (r.role.electionYear ? ' \u2022 Elected ' + r.role.electionYear : '');
    }
    function shortDesc(r) {
        if (r.isSK) return r.role.description || r.role.responsibilities || ROLES[r.position] || '';
        return [r.age !== '' ? r.age + ' years old' : '', r.kk.gender, r.kk.classification].filter(Boolean).join(' \u2022 ');
    }

    /* ---------- filters + list ---------- */
    var view = 'cards', page = 1, loading = true;

    function filtered() {
        var q = norm($('searchInput').value), t = $('typeFilter').value, pos = $('positionFilter').value,
            st = $('statusFilter').value, pu = $('purokFilter').value, so = $('sortSelect').value;
        var list = buildRecords().filter(function (r) {
            if (t === 'SK' && !r.isSK) return false;
            if (t === 'KK' && r.isSK) return false;
            if (pos && r.position !== pos) return false;
            if (st && r.status !== st) return false;
            if (pu && r.purok !== pu) return false;
            if (q && r.text.indexOf(q) < 0) return false;
            return true;
        });
        list.sort(function (a, b) {
            if (so === 'name-desc') return norm(b.name).localeCompare(norm(a.name));
            if (so === 'position') return a.rank - b.rank || norm(a.name).localeCompare(norm(b.name));
            if (so === 'newest') return String(b.created).localeCompare(String(a.created));
            if (so === 'oldest') return String(a.created).localeCompare(String(b.created));
            return norm(a.name).localeCompare(norm(b.name));
        });
        return list;
    }

    function refreshFilterOptions() {
        var pSel = $('positionFilter'), uSel = $('purokFilter'), pv = pSel.value, uv = uSel.value;
        fillSelect(pSel, POSITIONS, 'All SK Positions');
        if (POSITIONS.indexOf(pv) > -1) pSel.value = pv;
        var puroks = PUROKS.slice();
        D.kk.forEach(function (m) { if (m.purok && puroks.indexOf(m.purok) < 0) puroks.push(m.purok); });
        fillSelect(uSel, puroks, 'All Purok');
        if (puroks.indexOf(uv) > -1) uSel.value = uv;
    }

    function renderStats() {
        var sk = D.roles.length, total = D.kk.length;
        var activeSk = D.roles.filter(function (r) { return r.status === 'Active'; }).length;
        var archivedKk = D.kk.filter(function (m) { return m.status === 'Archived'; }).length;
        $('statTotal').textContent = total;
        $('statTotalSub').textContent = (total - archivedKk) + ' active \u2022 ' + archivedKk + ' archived';
        $('statSk').textContent = sk;
        $('statSkSub').textContent = D.roles.filter(function (r) { return r.status === 'Archived'; }).length + ' term ended \u2022 ' + D.roles.filter(function (r) { return r.status === 'Inactive'; }).length + ' inactive';
        $('statKk').textContent = total - sk;
        $('statKkSub').textContent = 'No SK position';
        $('statActiveSk').textContent = activeSk;
    }

    function avatarHtml(r, size) {
        var st = 'width:' + size + 'px;height:' + size + 'px;font-size:' + Math.round(size / 2.6) + 'px;';
        if (r.photo) return '<img src="' + esc(r.photo) + '" alt="" class="thumb" style="' + st + '">';
        var parts = String(r.name || '?').trim().split(/\s+/);
        var ini = (parts[0].charAt(0) + (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '')).toUpperCase();
        return '<span class="thumb" style="' + st + '">' + esc(ini) + '</span>';
    }
    function statusBadge(s, label) {
        var cls = s === 'Active' ? 'status-active' : (s === 'Archived' ? 'status-archived' : 'status-inactive');
        return '<span class="badge ' + cls + '">' + esc(label ? label + ': ' + s : s) + '</span>';
    }
    function actBtn(key, act, icon, title, cls) {
        return '<button type="button" class="btn btn-sm action-btn ' + cls + '" data-act="' + act + '" data-key="' + esc(key) + '" title="' + esc(title) + '" aria-label="' + esc(title) + '"><i class="bi ' + icon + '"></i></button>';
    }
    function rowActions(r) {
        var h = actBtn(r.key, 'view', 'bi-eye', 'View profile', 'btn-outline-success');
        if (r.kkStatus !== 'Archived' || r.isSK) h += r.isSK
            ? actBtn(r.key, 'role', 'bi-person-gear', 'Edit SK role and term', 'btn-outline-success')
            : actBtn(r.key, 'role', 'bi-person-badge', 'Assign SK role', 'btn-outline-success');
        if (r.isSK) h += actBtn(r.key, 'remove', 'bi-person-x', 'Remove SK role', 'btn-outline-danger');
        if (r.kkStatus !== 'Archived') h += actBtn(r.key, 'kkedit', 'bi-pencil', 'Edit KK information (Registration)', 'btn-outline-secondary');
        return h;
    }
    function pills(r) {
        return '<span class="type-pill">KK MEMBER</span>' + (r.isSK ? '<span class="type-pill">SK MEMBER</span>' : '');
    }

    function cardHtml(r) {
        var term = r.isSK ? termLine(r) : (r.kk.classification || 'Registered ' + fmtDate(r.created));
        return '<div class="col-12 col-sm-6 col-lg-4 col-xxl-3"><div class="card mini-card h-100' + (r.kkStatus === 'Archived' ? ' is-archived' : '') + '">' +
            '<div class="mini-banner">' +
                '<span class="mini-pill mini-pill-main">' + esc(r.isSK ? r.position : 'KK Member') + '</span>' +
                (r.age !== '' ? '<span class="mini-age"><small>AGE</small><b>' + esc(r.age) + '</b></span>' : '') +
                '<span class="mini-avatar">' + avatarHtml(r, 54) + '</span>' +
                '<span class="mini-pill mini-pill-dark">' + esc(r.isSK ? 'KK + SK' : 'KK') + '</span>' +
                '<span class="mini-pill mini-pill-status">' + esc(r.isSK ? r.skStatus : r.kkStatus) + '</span>' +
            '</div>' +
            '<div class="mini-body">' +
                '<div class="mini-name text-truncate" title="' + esc(r.name) + '">' + esc(r.name) + '</div>' +
                '<div class="mini-desc">' + esc(shortDesc(r)) + '</div>' +
                '<div class="mini-row"><i class="bi bi-telephone"></i><span class="text-truncate">' + esc(r.contact || '\u2014') + '</span></div>' +
                '<div class="mini-row"><i class="bi bi-geo-alt"></i><span class="text-truncate">' + esc(r.purok || '\u2014') + '</span></div>' +
                '<div class="mini-row"><i class="bi bi-calendar3"></i><span class="text-truncate">' + esc(term) + '</span></div>' +
                '<div class="mini-actions">' +
                    '<button type="button" class="btn mini-view" data-act="view" data-key="' + esc(r.key) + '"><i class="bi bi-eye me-1"></i>View</button>' +
                    (r.kkStatus !== 'Archived' || r.isSK ? '<button type="button" class="btn mini-ico" data-act="role" data-key="' + esc(r.key) + '" title="' + (r.isSK ? 'Edit SK role and term' : 'Assign SK role') + '"><i class="bi ' + (r.isSK ? 'bi-person-gear' : 'bi-person-badge') + '"></i></button>' : '') +
                    (r.kkStatus !== 'Archived' ? '<button type="button" class="btn mini-ico" data-act="kkedit" data-key="' + esc(r.key) + '" title="Edit KK information"><i class="bi bi-pencil"></i></button>' : '') +
                    (r.isSK ? '<button type="button" class="btn mini-ico mini-del" data-act="remove" data-key="' + esc(r.key) + '" title="Remove SK role"><i class="bi bi-person-x"></i></button>' : '') +
                '</div>' +
            '</div></div></div>';
    }

    function tableHtml(list) {
        return '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr>' +
            '<th>Member</th><th>Age</th><th>Contact</th><th>Address / Purok</th><th>KK Status</th><th>SK Position</th><th>SK Status</th><th>Election / Term</th><th class="text-end">Actions</th></tr></thead><tbody>' +
            list.map(function (r) {
                return '<tr><td style="min-width:210px;"><div class="d-flex align-items-center gap-2">' + avatarHtml(r, 39) +
                    '<div><div class="member-name">' + esc(r.name) + '</div><div class="member-id">' + esc(r.code) + ' ' + (r.isSK ? 'KK + SK' : 'KK') + '</div>' +
                    (shortDesc(r) ? '<div class="member-id text-truncate" style="max-width:220px;">' + esc(shortDesc(r)) + '</div>' : '') + '</div></div></td>' +
                    '<td>' + esc(r.age) + '</td>' +
                    '<td><div>' + esc(r.contact || '\u2014') + '</div><div class="member-id">' + esc(r.email || '') + '</div></td>' +
                    '<td>' + esc(r.purok || '\u2014') + '</td>' +
                    '<td>' + statusBadge(r.kkStatus) + '</td>' +
                    '<td>' + esc(r.position || '\u2014') + '</td>' +
                    '<td>' + (r.isSK ? statusBadge(r.skStatus) : '\u2014') + '</td>' +
                    '<td class="text-nowrap">' + esc(termLine(r)) + '</td>' +
                    '<td class="text-end text-nowrap">' + rowActions(r) + '</td></tr>';
            }).join('') + '</tbody></table></div>';
    }

    function emptyHtml(total) {
        if (!total) {
            return '<div class="empty-state"><i class="bi bi-people"></i><h5 class="fw-bold mt-3">No KK members yet</h5>' +
                '<p class="small mb-3">Register KK youth first. Once registered they appear here and an SK position can be assigned to them.</p>' +
                '<a href="registration.php" class="btn btn-success btn-sm"><i class="bi bi-person-plus-fill me-1"></i>Register KK Youth</a></div>';
        }
        return '<div class="empty-state"><i class="bi bi-person-x"></i><h5 class="fw-bold mt-3">No members found</h5><p class="small mb-0">Try changing your search or filters.</p></div>';
    }

    function renderList() {
        var box = $('results');
        if (loading) {
            box.innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border mb-3" role="status"></div><div class="small">Loading members...</div></div>';
            $('pager').innerHTML = ''; $('resultCount').textContent = 'Loading...';
            return;
        }
        var list = filtered(), pages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
        if (page > pages) page = pages;
        var slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        if (!slice.length) box.innerHTML = emptyHtml(D.kk.length);
        else if (view === 'cards') box.innerHTML = '<div class="row g-3">' + slice.map(cardHtml).join('') + '</div>';
        else box.innerHTML = tableHtml(slice);

        $('resultCount').textContent = list.length
            ? 'Showing ' + ((page - 1) * PAGE_SIZE + 1) + '\u2013' + Math.min(page * PAGE_SIZE, list.length) + ' of ' + list.length + ' member' + (list.length !== 1 ? 's' : '')
            : 'Showing 0 members';
        $('directorySub').textContent = { ALL: 'All KK members', SK: 'SK members (KK members with an SK position)', KK: 'Regular KK members (no SK position)' }[$('typeFilter').value] + ' \u2022 search, filter and manage profiles';

        var p = '', from = Math.max(1, page - 2), to = Math.min(pages, page + 2);
        p += '<li class="page-item' + (page === 1 ? ' disabled' : '') + '"><button class="page-link" data-page="' + (page - 1) + '">Previous</button></li>';
        for (var i = from; i <= to; i++) p += '<li class="page-item' + (i === page ? ' active' : '') + '"><button class="page-link" data-page="' + i + '">' + i + '</button></li>';
        p += '<li class="page-item' + (page === pages ? ' disabled' : '') + '"><button class="page-link" data-page="' + (page + 1) + '">Next</button></li>';
        $('pager').innerHTML = pages > 1 ? p : '';
        $('viewCards').classList.toggle('active', view === 'cards');
        $('viewTable').classList.toggle('active', view === 'table');
        markStats();
    }

    /* ---------- clickable summary cards: click one to show those members ---------- */
    function markStats() {
        var t = $('typeFilter').value, s = $('statusFilter').value;
        var plain = !$('positionFilter').value && !$('purokFilter').value && !$('searchInput').value.trim();
        document.querySelectorAll('.stat-link').forEach(function (c) {
            var k = c.dataset.stat;
            var on = plain && ((k === 'ALL' && t === 'ALL' && !s) || (k === 'SK' && t === 'SK' && !s) ||
                               (k === 'KK' && t === 'KK' && !s) || (k === 'ACTIVE_SK' && t === 'SK' && s === 'Active'));
            c.classList.toggle('selected', on);
            c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }
    function showStat(k) {
        $('searchInput').value = ''; $('positionFilter').value = ''; $('purokFilter').value = '';
        $('typeFilter').value = k === 'ALL' ? 'ALL' : (k === 'KK' ? 'KK' : 'SK');
        $('statusFilter').value = k === 'ACTIVE_SK' ? 'Active' : '';
        page = 1; renderList();
        document.querySelector('.member-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    document.querySelectorAll('.stat-link').forEach(function (c) {
        c.addEventListener('click', function () { showStat(c.dataset.stat); });
        c.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showStat(c.dataset.stat); } });
    });

    function renderUnlinked() {
        var u = D.read(D.KEYS.UNLINKED, []);
        if (!Array.isArray(u) || !u.length) { $('unlinkedBox').innerHTML = ''; return; }
        $('unlinkedBox').innerHTML = '<div class="alert alert-secondary d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-4">' +
            '<div class="small"><strong>' + u.length + ' SK official' + (u.length > 1 ? 's' : '') + ' from the old SK list could not be matched to a registered KK member:</strong> ' +
            esc(u.map(function (x) { return x.name + ' (' + x.position + ')'; }).join('; ')) +
            '. Register them in KK Youth Registration, then assign their SK position here.</div>' +
            '<div class="d-flex gap-2 flex-none"><a href="registration.php" class="btn btn-sm btn-outline-success">Register KK Youth</a>' +
            '<button type="button" class="btn btn-sm btn-dark" id="dismissUnlinked">Dismiss</button></div></div>';
    }

    function renderAll() { refreshFilterOptions(); renderStats(); renderUnlinked(); renderList(); }

    /* ---------- profile (view / print) ---------- */
    function raw(html) { return { html: html }; }
    function kv(l, v) {
        var out = (v == null || v === '' || (Array.isArray(v) && !v.length)) ? '\u2014' : (v.html !== undefined ? v.html : esc(Array.isArray(v) ? v.join(', ') : v));
        return '<div class="col-sm-6 col-lg-4"><div class="kv-l">' + esc(l) + '</div><div class="kv-v">' + out + '</div></div>';
    }
    function sec(title, rows) { return '<div class="group-title">' + esc(title) + '</div><div class="row g-3">' + rows + '</div>'; }
    function histList(h) {
        h = (h || []).slice().reverse();
        if (!h.length) return '<div class="small text-muted">No history.</div>';
        return '<ul class="ps-3 mb-0">' + h.map(function (x) {
            return '<li class="small"><strong>' + esc(x.action) + '</strong> \u2013 ' + fmtDate(x.at) + ' <span class="text-muted">(' + esc(x.by) + ')</span>' +
                (x.note ? '<br><span class="text-muted">' + esc(x.note) + '</span>' : '') + '</li>';
        }).join('') + '</ul>';
    }

    function profileHtml(r) {
        var m = r.kk, ro = r.role;
        var head = '<div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-2">' + avatarHtml(r, 120) +
            '<div><h4 class="fw-bold mb-1">' + esc(r.name) + '</h4>' +
            '<div class="text-muted mb-2">' + esc(r.isSK ? r.position + ' \u2022 KK Member' : 'KK Member (Youth)') + '</div>' +
            '<div class="d-flex flex-wrap gap-2 align-items-center">' + statusBadge(r.kkStatus, 'KK') + (r.isSK ? statusBadge(r.skStatus, 'SK') : '') + pills(r) +
            (r.code ? '<span class="member-id">' + esc(r.code) + '</span>' : '') + '</div></div></div>';

        var skPart = '';
        if (r.isSK) {
            skPart =
                (ro.status === 'Archived' ? '<div class="alert alert-secondary small mt-3 mb-0">This SK term has ended, so the SK role is archived. Use <strong>Edit SK Role</strong> to record a new term.</div>' : '') +
                sec('SK Information', kv('SK position', ro.position) + kv('SK status', ro.status) + kv('Election year', ro.electionYear) + kv('Date elected', fmtDate(ro.dateElected)) +
                    kv('Term start', fmtDate(ro.termStart)) + kv('Term end', fmtDate(ro.termEnd)) + kv('Term', termLabel(ro))) +
                '<div class="group-title">Short Description</div><div class="small">' + esc(ro.description || 'No description added.') + '</div>' +
                '<div class="group-title">Responsibilities</div><div class="small">' + esc(ro.responsibilities || ROLES[ro.position] || 'No responsibilities recorded.') + '</div>';
        }
        var a = r.age;
        return head +
            (m.status === 'Archived' ? '<div class="alert alert-secondary small mt-3 mb-0">' + esc(m.archiveReason || 'This KK record is archived.') + '</div>' : '') +
            skPart +
            sec('Personal Information', kv('Age', a) + kv('Sex', m.gender === 'Prefer to self-describe' ? m.genderOther : m.gender) + (window.SK_PUBLIC ? '' : kv('Date of birth', fmtDate(m.birthDate))) + kv('Civil status', m.civilStatus) + kv('Religion', m.religion === 'Other' ? m.religionOther : m.religion)) +
            sec('Contact Information', kv('Mobile number', m.mobile) + kv('Email', m.email) + kv('Facebook', m.facebook)) +
            sec('Address / Purok', kv('Purok', m.purok) + kv('Full address', addrOf(m))) +
            sec('KK Membership', kv('KK member no.', m.memberNo) + kv('KK status', r.kkStatus) + kv('Registration date', fmtDate(m.createdAt)) + kv('Eligibility', eligibility(m) + ' (ages ' + MIN_AGE + '\u2013' + MAX_AGE + ')') + kv('Registered SK voter', m.skVoter)) +
            sec('Youth Profiling', kv('Classification', m.classification === 'Other' ? m.classificationOther : m.classification) + kv('Educational attainment', m.educationLevel) + kv('Education status', m.educationStatus) +
                kv('School / institution', m.school === 'Other' ? m.schoolOther : m.school) + kv('Employment status', m.employment) + kv('Occupation / job', m.occupation) + kv('Program interests', m.interests)) +
            (r.isSK ? '<div class="group-title">SK Role History</div>' + histList(ro.history) : '');
    }

    function openProfile(key) {
        var r = getRec(key);
        if (!r) return;
        $('profileTitle').textContent = r.isSK ? 'SK Member Profile' : 'KK Member Profile';
        $('profileBody').innerHTML = profileHtml(r);
        var f = '<button type="button" class="btn btn-outline-success btn-sm" data-act="print" data-key="' + esc(key) + '"><i class="bi bi-printer me-1"></i>Print Profile</button>';
        if (window.SK_CAN_MANAGE_ROLES && (r.kkStatus !== 'Archived' || r.isSK))
            f += '<button type="button" class="btn btn-outline-success btn-sm" data-act="role" data-key="' + esc(key) + '"><i class="bi ' + (r.isSK ? 'bi-person-gear' : 'bi-person-badge') + ' me-1"></i>' + (r.isSK ? 'Edit SK Role' : 'Assign SK Role') + '</button>';
        if (window.SK_CAN_MANAGE_ROLES && r.isSK) {
            f += '<button type="button" class="btn btn-outline-danger btn-sm" data-act="remove" data-key="' + esc(key) + '"><i class="bi bi-person-x me-1"></i>End SK Role</button>';
            f += '<button type="button" class="btn btn-outline-secondary btn-sm" data-act="archive-role" data-key="' + esc(key) + '"><i class="bi bi-archive me-1"></i>Archive SK Role</button>';
        }
        if (window.SK_CAN_MANAGE_ROLES) f += '<button type="button" class="btn btn-outline-primary btn-sm" data-act="link-account" data-key="' + esc(key) + '"><i class="bi bi-link-45deg me-1"></i>Link Existing Account</button>';
        if (window.SK_CAN_MANAGE_ROLES) f += '<button type="button" class="btn btn-outline-primary btn-sm" data-act="create-account" data-key="' + esc(key) + '"><i class="bi bi-person-plus me-1"></i>Create Member Login</button>';
        if (r.kkStatus !== 'Archived') f += '<button type="button" class="btn btn-outline-secondary btn-sm" data-act="kkedit" data-key="' + esc(key) + '"><i class="bi bi-pencil me-1"></i>Edit in Registration</button>';
        f += '<button type="button" class="btn btn-dark btn-sm ms-auto" data-bs-dismiss="modal">Close</button>';
        $('profileFooter').innerHTML = f;
        modal('profileModal').show();
    }

    var linkAccountTarget = null;
    function openAccountLink(legacyId) {
        var error = $('linkAccountError');
        error.textContent = '';
        $('linkAccountSelect').innerHTML = '<option value="">Loading accounts...</option>';
        try {
            var result = DB.listUnlinkedAccounts();
            if (result.linkedLegacyIds.map(String).indexOf(String(legacyId)) !== -1) {
                notify('warning', 'This member already has an account linked.');
                return;
            }
            var accounts = result.accounts.filter(function (account) { return Number(account.is_active) === 1; });
            if (!accounts.length) {
                notify('info', 'There are no active unlinked accounts to link.');
                return;
            }
            linkAccountTarget = legacyId;
            $('linkAccountSelect').innerHTML = '<option value="">Select an account</option>' + accounts.map(function (account) {
                return '<option value="' + esc(account.id) + '">' + esc(account.full_name) + ' (' + esc(account.username) + ' · ' + esc(account.role) + ')</option>';
            }).join('');
            modal('linkAccountModal').show();
        } catch (e) {
            notify('danger', e.message || 'Could not load unlinked accounts.');
        }
    }

    $('confirmLinkAccount').addEventListener('click', function () {
        var accountId = $('linkAccountSelect').value;
        if (!accountId || !linkAccountTarget) {
            $('linkAccountError').textContent = 'Select an unlinked account.';
            return;
        }
        try {
            DB.linkMemberAccount(accountId, linkAccountTarget);
            hideModal('linkAccountModal');
            notify('success', 'The existing account is now linked. Its username and password were not changed.');
            linkAccountTarget = null;
        } catch (e) {
            $('linkAccountError').textContent = e.message || 'Could not link the account.';
        }
    });

    var createAccountTarget = null;
    function openCreateAccount(legacyId) {
        $('createAccountError').textContent = '';
        $('temporaryPasswordBox').classList.add('d-none');
        $('temporaryPasswordBox').textContent = '';
        $('newAccountUsername').value = '';
        $('guardianConsent').checked = false;
        $('consentVersion').value = 'v1';
        try {
            var result = DB.listUnlinkedAccounts();
            if (result.linkedLegacyIds.map(String).indexOf(String(legacyId)) !== -1) {
                notify('warning', 'This member already has an account. Link or promote that account instead; creating another is not allowed.');
                return;
            }
            createAccountTarget = legacyId;
            modal('createAccountModal').show();
        } catch (e) {
            notify('danger', e.message || 'Could not check the member account.');
        }
    }

    $('confirmCreateAccount').addEventListener('click', function () {
        var username = $('newAccountUsername').value.trim();
        if (!createAccountTarget || !username) {
            $('createAccountError').textContent = 'Enter a username.';
            return;
        }
        try {
            var result = DB.createMemberAccount(
                createAccountTarget,
                username,
                $('guardianConsent').checked,
                $('consentVersion').value.trim()
            );
            $('temporaryPasswordBox').textContent = 'Temporary password (show only once): ' + result.temporaryPassword +
                '. The member must change it at first sign-in.';
            $('temporaryPasswordBox').classList.remove('d-none');
            $('confirmCreateAccount').disabled = true;
            createAccountTarget = null;
        } catch (e) {
            $('createAccountError').textContent = e.message || 'Could not create the member account.';
        }
    });
    $('createAccountModal').addEventListener('hidden.bs.modal', function () {
        $('confirmCreateAccount').disabled = false;
        createAccountTarget = null;
    });

    function printHeader(title) {
        return '<div style="text-align:center;margin-bottom:12px;"><div style="font-weight:800;font-size:15px;">Sangguniang Kabataan \u2013 Barangay ' + BARANGAY + '</div>' +
            '<div>' + CITY + ', ' + PROVINCE + '</div><div style="font-weight:700;margin-top:6px;">' + esc(title) + '</div>' +
            '<div style="font-size:11px;">Printed ' + fmtDate(D.nowISO()) + '</div></div>';
    }
    function doPrint(html) { $('printArea').innerHTML = html; setTimeout(function () { window.print(); }, 150); }
    function printProfile(key) { if (window.SK_PUBLIC) return; var r = getRec(key); if (r) doPrint(printHeader(r.isSK ? 'SK Member Profile' : 'KK Member Profile') + profileHtml(r)); }
    function printList() {
        if (window.SK_PUBLIC) return;
        var list = filtered();
        if (!list.length) { notify('warning', 'There are no members to print.'); return; }
        var f = [];
        if ($('typeFilter').value !== 'ALL') f.push($('typeFilter').selectedOptions[0].text);
        ['positionFilter', 'statusFilter', 'purokFilter'].forEach(function (id) { if ($(id).value) f.push($(id).value); });
        if ($('searchInput').value.trim()) f.push('Search: ' + $('searchInput').value.trim());
        doPrint(printHeader('Member List') + '<div style="font-size:11px;margin-bottom:6px;">' + esc(f.join(' | ') || 'All KK members') + ' \u2013 ' + list.length + ' record(s)</div>' +
            '<table><thead><tr><th>#</th><th>Name</th><th>Age</th><th>Contact</th><th>Purok</th><th>KK Status</th><th>SK Position</th><th>SK Status</th><th>Election / Term</th></tr></thead><tbody>' +
            list.map(function (r, i) {
                return '<tr><td>' + (i + 1) + '</td><td>' + esc(r.name) + '</td><td>' + esc(r.age) + '</td><td>' + esc(r.contact) + '</td><td>' + esc(r.purok) + '</td><td>' + esc(r.kkStatus) + '</td><td>' +
                    esc(r.position) + '</td><td>' + esc(r.skStatus) + '</td><td>' + esc(r.isSK ? termLine(r) : '') + '</td></tr>';
            }).join('') + '</tbody></table>');
    }
    function exportCsv() {
        var list = filtered();
        if (!list.length) { notify('warning', 'There are no members to export.'); return; }
        var head = ['KK No.', 'Full Name', 'Age', 'Sex', 'Contact', 'Email', 'Purok', 'Address', 'KK Status', 'SK Position', 'SK Status', 'Election Year', 'Date Elected', 'Term Start', 'Term End'];
        var rows = list.map(function (r) {
            var o = r.role || {};
            return [r.code, r.name, r.age, r.kk.gender, r.contact, r.email, r.purok, r.address, r.kkStatus, r.position, r.skStatus, o.electionYear, o.dateElected, o.termStart, o.termEnd];
        });
        var csv = [head].concat(rows).map(function (row) {
            return row.map(function (c) { c = c == null ? '' : String(c); return /[",\n]/.test(c) ? '"' + c.replace(/"/g, '""') + '"' : c; }).join(',');
        }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' }));
        a.download = 'member-list-' + todayISO() + '.csv';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
        notify('success', 'Exported ' + list.length + ' member(s).');
    }

    /* ---------- confirm dialog ---------- */
    var cfCallback = null;
    function ask(o, cb) {
        $('cfTitle').textContent = o.title; $('cfText').textContent = o.text;
        $('cfOk').className = 'btn ' + (o.cls || 'btn-dark'); $('cfOk').textContent = o.ok || 'Confirm';
        cfCallback = cb; modal('confirmModal').show();
    }
    $('cfOk').addEventListener('click', function () {
        var cb = cfCallback; cfCallback = null;
        afterHide('confirmModal', function () { if (cb) cb(); });
    });

    /* ---------- assign / edit SK role ---------- */
    var roleKkId = null, roleEditing = false, respTouched = false, yearTouched = false;

    function pickable() {
        return D.kk.filter(function (m) { return m.status !== 'Archived' && !D.roleOf(m.id); })
            .sort(function (a, b) { return norm(kkName(a)).localeCompare(norm(kkName(b))); });
    }
    function renderPick() {
        var q = norm($('pickSearch').value), sel = $('pickMember'), keep = sel.value;
        var list = pickable().filter(function (m) { return !q || norm([kkName(m), m.memberNo, m.purok].join(' ')).indexOf(q) > -1; });
        sel.innerHTML = list.length
            ? list.map(function (m) {
                var a = m.birthDate ? calcAge(m.birthDate) : '';
                return '<option value="' + esc(m.id) + '">' + esc(kkName(m)) + (a !== '' ? ' \u2022 ' + a + ' yrs' : '') + (m.purok ? ' \u2022 ' + m.purok : '') + (m.memberNo ? ' \u2022 ' + m.memberNo : '') + '</option>';
            }).join('')
            : '<option value="" disabled>' + (D.kk.length ? 'No matching KK member available' : 'No KK members registered yet') + '</option>';
        if (keep && list.some(function (m) { return m.id === keep; })) sel.value = keep;
    }
    function summaryHtml(m) {
        var r = { name: kkName(m), photo: m.photo || '' };
        var a = m.birthDate ? calcAge(m.birthDate) : '';
        return '<div class="d-flex align-items-center gap-3 p-3 border rounded-4 mb-1">' + avatarHtml(r, 56) +
            '<div class="small"><div class="member-name">' + esc(r.name) + '</div>' +
            '<div class="text-muted">' + esc([m.memberNo, a !== '' ? a + ' years old' : '', m.purok, m.mobile].filter(Boolean).join(' \u2022 ')) + '</div>' +
            '<div class="text-muted">Photo, contact and address come from the KK record.</div></div></div>';
    }

    function openRoleForm(kkId) {
        var f = $('roleForm');
        f.reset(); clearErrs(f);
        fillSelect($('rolePosition'), POSITIONS, 'Select SK position');
        var role = kkId ? D.roleOf(kkId) : null, m = kkId ? D.kkById(kkId) : null;
        roleKkId = kkId || null; roleEditing = !!role; respTouched = false; yearTouched = false;
        $('roleModalTitle').textContent = role ? 'Edit SK Role and Term' : 'Assign SK Role';
        $('roleSaveLabel').textContent = role ? 'Update SK Role' : 'Assign SK Role';
        $('pickBox').classList.toggle('d-none', !!m);
        $('memberSummary').classList.toggle('d-none', !m);
        $('memberSummary').innerHTML = m ? summaryHtml(m) : '';
        $('pickSearch').value = '';
        if (!m) { renderPick(); }
        if (role) {
            $('rolePosition').value = role.position; $('roleStatus').value = role.status === 'Archived' ? 'Inactive' : (role.status || 'Active');
            $('roleStatus').disabled = false;
            $('roleBasis').value = role.basis || '';
            $('roleYear').value = role.electionYear || ''; $('roleElected').value = role.dateElected || '';
            $('roleStart').value = role.termStart || ''; $('roleEnd').value = role.termEnd || '';
            $('roleDesc').value = role.description || ''; $('roleResp').value = role.responsibilities || '';
            respTouched = !!role.responsibilities; yearTouched = !!role.electionYear;
        } else {
            $('roleStatus').disabled = false;
            $('roleBasis').value = '';
            $('roleYear').value = new Date().getFullYear();
        }
        renderPosChoices();
        modal('roleModal').show();
    }


    /* position choices (synced with the hidden select used by the form) */
    function renderPosChoices() {
        var cur = $('rolePosition').value, exceptId = null;
        if (roleKkId) { var ex = D.roleOf(roleKkId); if (ex) exceptId = ex.id; }
        $('posChoices').innerHTML = POSITIONS.map(function (p) {
            var cap = D.SEATS[p] || 1;
            var used = D.roles.filter(function (x) { return x.id !== exceptId && x.status === 'Active' && x.position === p; }).length;
            var left = Math.max(0, cap - used), full = left === 0 && p !== cur;
            return '<button type="button" class="pos-choice' + (p === cur ? ' active' : '') + '" data-pos="' + esc(p) + '" role="radio" aria-checked="' + (p === cur) + '"' + (full ? ' disabled' : '') + '>' +
                '<b>' + esc(p.replace('SK ', '')) + '</b><small>' + (full ? 'Seat filled' : left + ' of ' + cap + ' seat' + (cap > 1 ? 's' : '') + ' open') + '</small></button>';
        }).join('');
    }
    $('posChoices').addEventListener('click', function (e) {
        var b = e.target.closest('.pos-choice');
        if (!b || b.disabled) return;
        $('rolePosition').value = b.dataset.pos;
        $('rolePosition').dispatchEvent(new Event('change'));
        $('rolePosition').classList.remove('is-invalid');
        var fb = $('rolePosition').parentElement.querySelector('.invalid-feedback'); if (fb) { fb.textContent = ''; fb.style.display = ''; }
        renderPosChoices();
    });

    $('pickSearch').addEventListener('input', renderPick);
    $('rolePosition').addEventListener('change', function () {
        if (!respTouched || !$('roleResp').value.trim()) { $('roleResp').value = ROLES[this.value] || ''; respTouched = false; }
    });
    $('roleResp').addEventListener('input', function () { respTouched = true; });
    $('roleYear').addEventListener('input', function () { yearTouched = true; });
    $('roleElected').addEventListener('change', function () { if (this.value && !yearTouched) $('roleYear').value = yearOf(this.value); });

    $('roleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = this; clearErrs(form); reload();
        var ok = true;
        function bad(el, msg) { setErr(el, msg); ok = false; }

        var kkId = roleKkId || $('pickMember').value;
        if (!kkId) bad($('pickMember'), 'Select a KK member.');
        var m = kkId ? D.kkById(kkId) : null;
        if (kkId && !m) { notify('danger', 'This KK member no longer exists.'); modal('roleModal').hide(); renderAll(); return; }
        var existing = kkId ? D.roleOf(kkId) : null;
        if (existing && !roleEditing) { notify('danger', kkName(m) + ' already has an SK position. Edit that role instead.'); modal('roleModal').hide(); renderAll(); return; }
        if (m && m.status === 'Archived' && !existing) bad($('pickMember'), 'This KK record is archived and cannot be assigned an SK role.');

        var pos = $('rolePosition').value, st = $('roleStatus').value, basis = $('roleBasis').value,
            yr = $('roleYear').value.trim(), el = $('roleElected').value, ts = $('roleStart').value, te = $('roleEnd').value;
        if (!pos) bad($('rolePosition'), 'Select an SK position.');
        if (!existing && st !== 'Active') bad($('roleStatus'), 'A new SK role must start as Active.');
        if (!basis) bad($('roleBasis'), 'Select elected or appointed.');
        if (!yr) bad($('roleYear'), 'Election year is required.');
        else if (!/^\d{4}$/.test(yr) || +yr < 2000 || +yr > 2100) bad($('roleYear'), 'Enter a valid 4-digit year.');
        if (!el) bad($('roleElected'), 'Date elected is required.');
        if (el && ts && ts < el) bad($('roleStart'), 'Term cannot start before the date elected.');
        if (ts && te && te <= ts) bad($('roleEnd'), 'Term end must be after the term start.');
        if (yr && el && /^\d{4}$/.test(yr) && yearOf(el) !== yr) bad($('roleYear'), 'Election year should match the year of the date elected (' + yearOf(el) + ').');
        var ended = te && te < todayISO();
        if (ok && st === 'Active' && !ended) {
            var err = D.seatError(pos, existing ? existing.id : null);
            if (err) bad($('rolePosition'), err);
        }
        if (!ok) { var first = form.querySelector('.is-invalid'); if (first) first.focus(); return; }

        var expected = existing ? JSON.parse(JSON.stringify(existing)) : null;
        var desc = $('roleDesc').value.trim(), resp = $('roleResp').value.trim() || ROLES[pos] || '';
        var r;
        if (existing) {
            r = existing;
            var note = [];
            if (r.position !== pos) note.push('position changed from ' + r.position + ' to ' + pos);
            if (r.termStart !== ts || r.termEnd !== te) note.push('term set to ' + fmtDate(ts) + ' \u2013 ' + fmtDate(te));
            r.position = pos; r.status = st; r.basis = basis; r.electionYear = yr; r.dateElected = el; r.termStart = ts; r.termEnd = te;
            r.description = desc; r.responsibilities = resp;
            D.addHist(r, 'Updated', note.join('; ') || 'SK role information updated.');
        } else {
            r = {
                id: D.uid('r'), kkId: kkId, position: pos, status: 'Active', basis: basis, electionYear: yr, dateElected: el, termStart: ts, termEnd: te,
                description: desc, responsibilities: resp, history: [], createdAt: D.nowISO()
            };
            D.addHist(r, 'SK role assigned', pos + ', term ' + fmtDate(ts) + ' \u2013 ' + fmtDate(te) + '.');
            D.roles.push(r);
        }
        try {
            if (existing) DB.updateRole(r, expected);
            else DB.assignRole(r);
        } catch (err) {
            reload();
            notify('danger', err.message || 'Could not save the SK role.');
            return;
        }
        modal('roleModal').hide();
        var nm = kkName(m);
        notify('success', existing ? nm + '\'s SK role was updated.' : nm + ' is now an SK member (' + pos + ').' + (r.status === 'Archived' ? ' The term has already ended, so the role is archived.' : ''));
        if (!existing) { $('typeFilter').value = 'SK'; $('positionFilter').value = ''; $('statusFilter').value = ''; $('purokFilter').value = ''; $('searchInput').value = ''; page = 1; }
        renderAll();
    });

    function removeRole(key) {
        var r = getRec(key);
        if (!r || !r.isSK) return;
        var expected = JSON.parse(JSON.stringify(r.role));
        ask({ title: 'End SK Role?', text: r.name + ' will stay in the KK Member List, but this SK role will end and the person will become a regular KK member unless another active role applies. Continue?', ok: 'End SK Role', cls: 'btn-danger' }, function () {
            try {
                DB.endRole(r.role, expected, false);
                reload();
                notify('success', r.name + '\'s SK role was ended.');
                renderAll();
            } catch (err) {
                reload();
                notify('danger', err.message || 'Could not end the SK role.');
            }
        });
    }

    function archiveRole(key) {
        var r = getRec(key);
        if (!r || !r.isSK) return;
        var expected = JSON.parse(JSON.stringify(r.role));
        ask({ title: 'Archive SK Role?', text: r.name + '\'s role history will be kept, and this role will no longer grant SK access. Continue?', ok: 'Archive SK Role', cls: 'btn-secondary' }, function () {
            try {
                DB.endRole(r.role, expected, true);
                reload();
                notify('success', r.name + '\'s SK role was archived.');
                renderAll();
            } catch (err) {
                reload();
                notify('danger', err.message || 'Could not archive the SK role.');
            }
        });
    }

    /* ---------- events ---------- */
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (b) {
            var key = b.dataset.key, act = b.dataset.act;
            if (act === 'view') openProfile(key);
            else if (act === 'print') printProfile(key);
            else if (act === 'kkedit') window.location.href = 'registration.php?edit=' + encodeURIComponent(key);
            else if (act === 'role') afterHide('profileModal', function () { reload(); openRoleForm(key); });
            else if (act === 'remove') afterHide('profileModal', function () { removeRole(key); });
            else if (act === 'archive-role') afterHide('profileModal', function () { archiveRole(key); });
            else if (act === 'link-account') afterHide('profileModal', function () { openAccountLink(key); });
            else if (act === 'create-account') afterHide('profileModal', function () { openCreateAccount(key); });
            return;
        }
        if (e.target.closest('#dismissUnlinked')) { D.write(D.KEYS.UNLINKED, []); renderUnlinked(); return; }
        var pg = e.target.closest('[data-page]');
        if (pg) { page = +pg.dataset.page; renderList(); $('results').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });

    function resetPage() { page = 1; renderList(); }
    $('searchInput').addEventListener('input', resetPage);
    ['positionFilter', 'statusFilter', 'purokFilter', 'sortSelect', 'typeFilter'].forEach(function (id) { $(id).addEventListener('change', resetPage); });

    function jumpTo(type) {
        $('typeFilter').value = type; page = 1; renderList();
        document.querySelector('.member-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    $('heroViewSk').addEventListener('click', function () { jumpTo('SK'); });
    $('heroViewKk').addEventListener('click', function () { jumpTo('KK'); });
    $('viewCards').addEventListener('click', function () { view = 'cards'; renderList(); });
    $('viewTable').addEventListener('click', function () { view = 'table'; renderList(); });
    $('printListBtn').addEventListener('click', printList);
    function assignSk() { reload(); openRoleForm(null); }
    $('heroAssign').addEventListener('click', assignSk);
    $('assignBtn').addEventListener('click', assignSk);
    $('exportBtn').addEventListener('click', exportCsv);
    $('resetBtn').addEventListener('click', function () {
        $('searchInput').value = ''; $('typeFilter').value = 'ALL'; $('positionFilter').value = ''; $('statusFilter').value = ''; $('purokFilter').value = ''; $('sortSelect').value = 'name-asc';
        resetPage();
    });
    $('termCheckBtn').addEventListener('click', function () {
        try {
            var result = DB.archiveExpiredRoles();
            reload();
            notify(result.archived ? 'info' : 'success', result.archived ? result.archived + ' expired SK role' + (result.archived > 1 ? 's were' : ' was') + ' archived.' : 'No SK terms have ended.');
            renderAll();
        } catch (err) {
            notify('danger', err.message || 'Could not archive expired SK roles.');
        }
    });

    function refresh() { reload(); renderAll(); }
    window.addEventListener('storage', function (e) {
        if (e.key === D.KEYS.KK || e.key === D.KEYS.ROLE || e.key === null) { if (!isOpen('roleModal')) refresh(); }
    });
    document.addEventListener('visibilitychange', function () { if (!document.hidden && !isOpen('roleModal')) refresh(); });
    window.addEventListener('pageshow', function (e) { if (e.persisted) refresh(); });

    /* ---------- start ---------- */
    var qType = new URLSearchParams(location.search).get('type');
    if (qType && /^(SK|KK|ALL)$/i.test(qType)) $('typeFilter').value = qType.toUpperCase();
    renderList();
    setTimeout(function () { reload(); loading = false; renderAll(); }, 250);
})();
</script>

</body>
</html>