<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | SK Meetings</title>

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

*{
    box-sizing:border-box;
}

body{
    margin:0;
    background:var(--bs-tertiary-bg);
    color:var(--black);
    font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

a{
    text-decoration:none;
}

.top-strip{
    height:7px;
    background:var(--bs-primary);
}

header{
    background:#fff!important;
    border-bottom:1px solid var(--line)!important;
}

header h1{
    font-weight:900;
}

.btn-success{
    --bs-btn-bg:var(--bs-body-color);
    --bs-btn-border-color:var(--bs-body-color);
    --bs-btn-hover-bg:var(--kk-primary);
    --bs-btn-hover-border-color:var(--kk-primary);
    --bs-btn-hover-color:#fff;
    --bs-btn-active-bg:var(--bs-body-color);
    --bs-btn-active-border-color:var(--bs-body-color);
    --bs-btn-focus-shadow-rgb:255,230,0;
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

.btn-close-white{
    filter:none;
}

.dashboard-hero{
    position:relative;
    overflow:hidden;
    min-height:245px;
    padding:32px;
    border-radius:28px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    box-shadow:0 16px 35px rgba(31,31,31,.10);
}

.dashboard-hero:before{
    content:"";
    position:absolute;
    width:280px;
    height:280px;
    border-radius:50%;
    right:-80px;
    top:-120px;
    background:var(--bs-primary-text-emphasis);
    opacity:.08;
}

.dashboard-hero:after{
    content:"";
    position:absolute;
    width:170px;
    height:170px;
    border-radius:38px;
    right:145px;
    bottom:-105px;
    background:#fff;
    opacity:.28;
    transform:rotate(28deg);
}

.hero-copy{
    position:relative;
    z-index:2;
    max-width:610px;
}

.hero-copy h2{
    font-size:clamp(1.8rem,3vw,3rem);
    font-weight:950;
    letter-spacing:-1px;
}

.hero-copy p{
    max-width:560px;
    color:var(--bs-primary-text-emphasis);
}

.hero-tag{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 13px;
    border-radius:999px;
    background:var(--bs-primary-text-emphasis);
    color:#fff;
    font-size:.78rem;
    font-weight:800;
}

.hero-image-panel{
    position:absolute;
    right:24px;
    bottom:18px;
    width:390px;
    min-height:205px;
    z-index:1;
}

.image-panel-inner{
    background:rgba(255,255,255,.82);
    border:3px solid var(--bs-body-color);
    border-radius:24px;
    padding:15px;
    box-shadow:9px 9px 0 rgba(17,17,17,.88);
    backdrop-filter:blur(3px);
}

.image-panel-title{
    display:flex;
    align-items:center;
    gap:10px;
    color:var(--bs-body-color);
    margin-bottom:5px;
}

.image-panel-icon{
    width:40px;
    height:40px;
    border-radius:12px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:var(--bs-primary);
    border:2px solid var(--bs-body-color);
    font-size:1.1rem;
}

.image-panel-title strong{
    display:block;
    font-size:.88rem;
    font-weight:950;
    letter-spacing:.5px;
}

.image-panel-title small{
    display:block;
    color:var(--bs-secondary-color);
    font-size:.7rem;
}

.youth-illustration{
    display:block;
    width:100%;
    height:auto;
    max-height:150px;
}

.image-panel-stats{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:4px;
}

.image-panel-stats span{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 9px;
    border-radius:999px;
    background:var(--bs-primary-text-emphasis);
    color:#fff;
    font-size:.68rem;
    font-weight:800;
}

.image-panel-stats i{
    color:var(--kk-primary);
}

@media(max-width:1100px){
    .hero-image-panel{
        width:330px;
        opacity:.96;
    }
}

@media(max-width:900px){
    .hero-image-panel{
        right:18px;
        width:330px;
        opacity:.24;
    }
}

@media(max-width:575px){
    .hero-image-panel{
        display:none;
    }
}

.stat-card{
    height:100%;
    border:1px solid var(--line);
    border-radius:20px;
    background:#fff;
    box-shadow:0 8px 20px rgba(20,20,20,.05);
    transition:.2s ease;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 28px rgba(20,20,20,.10);
}

.stat-icon{
    width:46px;
    height:46px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-size:1.25rem;
}

.stat-number{
    font-size:2rem;
    font-weight:950;
    letter-spacing:-1px;
}

.dashboard-card{
    border:1px solid var(--line)!important;
    border-radius:22px!important;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(20,20,20,.045)!important;
}

.dashboard-card .card-header{
    background:#fff!important;
    border-bottom:1px solid var(--line);
}

.dashboard-card .card-header h2{
    font-weight:900;
}

.table{
    --bs-table-hover-bg:rgba(var(--bs-primary-rgb),.08);
}

.table thead th{
    background:rgba(var(--bs-primary-rgb),.08);
    color:var(--bs-secondary-color);
    font-size:.76rem;
    text-transform:uppercase;
    letter-spacing:.4px;
}

.table td{
    font-size:.88rem;
}

.badge.text-bg-success{
    color:var(--bs-body-color)!important;
    background:var(--bs-primary)!important;
}

.badge.text-bg-warning{
    color:#fff!important;
}

.date-box{
    width:56px;
    flex:none;
    text-align:center;
    padding:.45rem 0;
    border-radius:15px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
}

.quick-btn{
    min-height:52px;
    border-radius:14px;
}

.progress{
    background:rgba(var(--bs-primary-rgb),.12);
    border-radius:999px;
}

.progress-bar{
    background:var(--bs-primary)!important;
    border-radius:999px;
}

.avatar{
    width:40px;
    height:40px;
    flex:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-weight:900;
    border:2px solid var(--bs-body-color);
}

.fun-card{
    position:relative;
    overflow:hidden;
    background:var(--bs-primary-text-emphasis);
    color:#fff;
    border-radius:22px;
    padding:22px;
}

.fun-card:after{
    content:"";
    position:absolute;
    width:110px;
    height:110px;
    right:-35px;
    top:-35px;
    border-radius:50%;
    background:var(--bs-primary);
}

.fun-card h3{
    position:relative;
    z-index:2;
    font-weight:900;
}

.fun-card p{
    position:relative;
    z-index:2;
    color:var(--bs-border-color);
}

.fun-card .fun-icon{
    position:relative;
    z-index:2;
    width:42px;
    height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:13px;
    background:var(--bs-primary);
    color:var(--bs-body-color);
}

footer{
    display:none;
}

.sidebar-divider{
    height:1px;
    background:var(--bs-primary);
    margin:10px 0;
}

/* ---- SK Members page ---- */
.section-label{
    display:inline-flex;
    align-items:center;
    gap:.4rem;
    background:var(--bs-primary);
    color:var(--bs-body-color);
    font-size:.7rem;
    font-weight:800;
    letter-spacing:.6px;
    padding:.3rem .75rem;
    border-radius:999px;
}
.form-control,
.form-select{
    border:1px solid var(--bs-border-color);
    border-radius:12px;
    box-shadow:none!important;
}
.form-control:focus,
.form-select:focus{
    border-color:var(--bs-body-color);
    box-shadow:0 0 0 3px rgba(var(--bs-primary-rgb),.28)!important;
}
html{
    scroll-behavior:smooth;
}
    
/* sidebar.css makes modal titles white, so the header needs the primary colour */
.modal-header{ background:var(--bs-primary); border-bottom:0; border-radius:22px 22px 0 0; }

/* ---- meeting view / print ---- */
.kv-l{ font-size:.74rem; color:var(--bs-secondary-color); }
.kv-v{ font-size:.9rem; font-weight:700; word-break:break-word; }
.group-title{ font-weight:800; font-size:.82rem; padding-bottom:6px; margin:20px 0 10px; border-bottom:2px solid var(--bs-primary); }
.modal-content{ border-radius:22px; }
.min-w-0{ min-width:0; }
#printArea{ display:none; }
@media print{
    body > *:not(#printArea){ display:none!important; }
    #printArea{ display:block!important; padding:10px; color:#000; font-size:12px; }
    #printArea .group-title{ border-bottom:2px solid #000; }
    #printArea table{ width:100%; border-collapse:collapse; }
    #printArea th,#printArea td{ border:1px solid #999; padding:4px 6px; text-align:left; vertical-align:top; }
}

/* ---- add / edit meeting: scrollable form, Save always visible ---- */
#meetingModal .modal-body{ overflow-y:auto; max-height:calc(100vh - 190px); }
#meetingModal .modal-footer{ position:sticky; bottom:0; background:#fff; border-top:1px solid var(--bs-border-color); border-radius:0 0 22px 22px; }
</style>
<?php include __DIR__ . '/init.php'; ?>
</head>

<body>

<div class="top-strip"></div>

<script src="sidebar.js?v=3"></script>


<div class="page-content">

    <!-- HEADER -->
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
                <h1 class="h5 fw-bold mb-0">
                    SK Meetings
                </h1>

                <div class="text-muted small d-none d-sm-block">
                    Meetings and attendance of SK officials
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

                <a href="announcements.php"
                   class="btn btn-outline-success btn-sm position-relative"
                   aria-label="Notifications">

                    <i class="bi bi-bell"></i>

                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                          style="background:var(--bs-primary);color:var(--bs-body-color);">
                        3
                    </span>

                </a>

                <span class="avatar">
                    <?= e(uinit()) ?>
                </span>

                <div class="d-none d-md-block lh-sm">

                    <div class="fw-semibold small">
                        <?= e(uname()) ?>
                    </div>

                    <div class="text-muted" style="font-size:.75rem;">
                        <?= e(strtoupper(urole())) ?>
                    </div>

                </div>

                <a href="logout.php"
                   class="btn btn-success btn-sm">

                    <i class="bi bi-box-arrow-right me-1"></i>
                    <?= is_public() ? 'Official Login' : 'Logout' ?>

                </a>

            </div>

        </div>

    </header>


    <main class="container-fluid p-3 p-lg-4">

        <!-- ============================================================
             SK MEMBERS ARE KK MEMBERS WITH AN SK ROLE (managed in Member List)
             ============================================================ -->
        <section class="mb-4" id="sk-members">
            <div class="card dashboard-card">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <span class="section-label"><i class="bi bi-person-badge-fill"></i> SK OFFICIALS</span>
                        <h2 class="h5 fw-bold mt-2 mb-1">SK members come from the KK Member List</h2>
                        <p class="small text-muted mb-0">
                            Select a registered KK member in the Member List and assign an SK position. They appear here automatically
                            and can be invited to meetings and marked for attendance.
                            <span class="fw-semibold" id="skCountNote"></span>
                        </p>
                    </div>
                    <a href="members.php?type=SK" class="btn btn-success rounded-pill flex-none">
                        <i class="bi bi-people me-2"></i>Open SK Members
                    </a>
                </div>
            </div>
        </section>

        <!-- ============================================================
             SK MEETINGS AND SUMMARY
             ============================================================ -->
        <section class="mb-4" id="sk-meetings">

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card dashboard-card h-100">

                        <div class="card-header bg-white py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-calendar-event me-2"></i>SK Meetings</h2>
                                    <small class="text-muted">Search, view, edit and print SK meeting records.</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-success btn-sm" id="printListBtn">
                                        <i class="bi bi-printer me-1"></i>Print List
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" onclick="openMeetingForm()">
                                        <i class="bi bi-plus-lg me-1"></i>Add Meeting
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <input type="text" class="form-control" id="meetSearch" placeholder="Search title, type, location, agenda, attendee...">
                                </div>
                                <div class="col-6 col-md-3"><select class="form-select" id="meetTypeFilter"></select></div>
                                <div class="col-6 col-md-3"><select class="form-select" id="meetStatusFilter"></select></div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="list-group" id="meetingList"></div>
                            <div class="small text-muted mt-3" id="meetCount"></div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-header bg-white py-3">
                            <h2 class="h6 fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2"></i>SK Meeting Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6"><div class="stat-card p-3"><div class="small text-muted">Total SK Members</div><div class="stat-number" id="statTotalSk">0</div></div></div>
                                <div class="col-6"><div class="stat-card p-3"><div class="small text-muted">Active SK</div><div class="stat-number" id="statActiveSk">0</div></div></div>
                                <div class="col-6"><div class="stat-card p-3"><div class="small text-muted">Meetings</div><div class="stat-number" id="statMeetings">0</div></div></div>
                                <div class="col-6"><div class="stat-card p-3"><div class="small text-muted">Attendance</div><div class="stat-number" id="statAttendance">0%</div></div></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </section>

        <!-- ============================================================
             ATTENDANCE
             ============================================================ -->
        <section class="mb-4" id="attendance">
            <div class="card dashboard-card">

                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 fw-bold mb-1"><i class="bi bi-clipboard2-check me-2"></i>SK Meeting Attendance</h2>
                            <small class="text-muted">Record attendance of the meeting's attendees (SK members taken from the KK Member List).</small>
                        </div>
                        <a href="#sk-meetings" class="btn btn-outline-success btn-sm"><i class="bi bi-calendar-event me-1"></i>View Meetings</a>
                    </div>
                </div>

                <div class="card-body">
                    <div id="attendanceMessage"></div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting</label>
                            <select class="form-select" id="attendanceMeeting"></select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>SK Member</th><th>Position</th><th>Meeting Date</th><th>Status</th><th>Remarks</th></tr>
                            </thead>
                            <tbody id="attendanceBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-3">
                        <button type="button" class="btn btn-success" id="saveAttendanceBtn" onclick="saveAttendance()">
                            <i class="bi bi-save me-2"></i>Save Attendance
                        </button>
                        <small class="text-muted">Attendance is saved when you click this button.</small>
                    </div>
                </div>

            </div>
        </section>

    </main>
</div>

<!-- ================================================================
     ADD / EDIT MEETING POPUP
     ================================================================ -->
<div class="modal fade" id="meetingModal" tabindex="-1" aria-labelledby="meetingModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content" style="border-radius:22px;">
            <form id="meetingForm" novalidate>

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="meetingModalTitle">Add Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="meetingTitle">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="meetingTitle" maxlength="100" placeholder="e.g. Regular SK Session">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="meetingType">Meeting Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="meetingType"></select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 d-none" id="typeOtherBox">
                            <label class="form-label fw-semibold" for="meetingTypeOther">What kind of meeting is this? <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="meetingTypeOther" maxlength="60" placeholder="e.g. Orientation Meeting, Coordination Meeting">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="startDate">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="startDate">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="startTime">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="startTime">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="endDate">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="endDate">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="endTime">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="endTime">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="meetingLocation">Location / Venue <span class="text-danger">*</span></label>
                            <select class="form-select" id="meetingLocation"></select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 d-none" id="locOtherBox">
                            <label class="form-label fw-semibold" for="meetingLocationOther">Specify the venue <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="meetingLocationOther" maxlength="120" placeholder="e.g. Purok 3 Chapel">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="meetingStatus">Status</label>
                            <select class="form-select" id="meetingStatus"></select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="meetingAgenda">Description / Agenda</label>
                            <textarea class="form-control" id="meetingAgenda" rows="3" maxlength="800" placeholder="e.g. Resolution preparation, budget review"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">Attendees <span class="fw-normal text-muted">(active SK members)</span></label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" id="attAll">Select all</button>
                                    <button type="button" class="btn btn-outline-success" id="attNone">Clear</button>
                                </div>
                            </div>
                            <div class="border rounded-4 p-2" id="attendeeBox" style="max-height:210px;overflow:auto;"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="meetingRemarks">Remarks</label>
                            <textarea class="form-control" id="meetingRemarks" rows="2" maxlength="500" placeholder="Optional notes about this meeting"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="meetingSubmitBtn">Save Meeting</button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- ================================================================
     VIEW MEETING POPUP
     ================================================================ -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content" style="border-radius:22px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="viewModalTitle">Meeting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewBody"></div>
            <div class="modal-footer flex-wrap gap-2" id="viewFooter"></div>
        </div>
    </div>
</div>

<div id="printArea"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="sk-data.js"></script>
<script>
(function () {
    'use strict';

    var D = window.SKData;
    var MEET_KEY = D.KEYS.MEET, ATT_KEY = D.KEYS.ATT;
    var TYPES = ['Regular Meeting', 'Special Meeting', 'Emergency Meeting', 'Committee Meeting', 'Planning Meeting', 'Budget Meeting', 'Other'];
    var MSTATUS = ['Scheduled', 'Ongoing', 'Completed', 'Postponed', 'Cancelled'];
    var ASTATUS = ['Present', 'Late', 'Excused', 'Absent'];
    var VENUES = ['Barangay Hall', 'SK Office', 'Barangay Session Hall', 'Barangay Multi-purpose Hall', 'Barangay Covered Court', 'Barangay Health Center', 'Day Care Center', 'School / Classroom', 'Online (Zoom / Google Meet)', 'Other'];
    var BARANGAY = 'Sikat', CITY = 'Alfonso', PROVINCE = 'Cavite';

    function $(id) { return document.getElementById(id); }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function fmtDate(iso) {
        var d = new Date(iso + 'T00:00:00');
        return isNaN(d) ? '\u2014' : d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    function fmtTime(t) {
        if (!t) return '';
        var p = t.split(':'), h = +p[0], ap = h >= 12 ? 'PM' : 'AM';
        return ((h % 12) || 12) + ':' + p[1] + ' ' + ap;
    }
    function modal(id) { return bootstrap.Modal.getOrCreateInstance($(id)); }
    function isOpen(id) { return $(id).classList.contains('show'); }
    function afterHide(id, fn) {
        if (!isOpen(id)) { fn(); return; }
        $(id).addEventListener('hidden.bs.modal', function once() { $(id).removeEventListener('hidden.bs.modal', once); fn(); });
        modal(id).hide();
    }
    function note(box, type, text) {
        $(box).innerHTML = '<div class="alert alert-' + type + ' small">' + esc(text) + '</div>';
        setTimeout(function () { $(box).innerHTML = ''; }, 4000);
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
    function opts(sel, list, first) {
        sel.innerHTML = (first !== undefined ? '<option value="">' + esc(first) + '</option>' : '') + list.map(function (x) { return '<option>' + esc(x) + '</option>'; }).join('');
    }

    /* ---------- data ---------- */
    var meetings = [], attendance = {};

    function normMeeting(m) {          // also upgrades meetings saved by the older version of this page
        m.startDate = m.startDate || m.date || '';
        m.startTime = m.startTime || m.time || '';
        m.endDate = m.endDate || m.startDate;
        m.endTime = m.endTime || '';
        m.location = m.location != null ? m.location : (m.venue || '');
        m.type = TYPES.indexOf(m.type) > -1 ? m.type : 'Regular Meeting';
        m.typeOther = m.type === 'Other' ? (m.typeOther || '') : '';
        m.agenda = m.agenda || '';
        m.remarks = m.remarks || '';
        m.attendees = Array.isArray(m.attendees) ? m.attendees : [];
        if (MSTATUS.indexOf(m.status) < 0) m.status = (m.startDate && m.startDate < D.todayISO()) ? 'Completed' : 'Scheduled';
        delete m.date; delete m.time; delete m.venue;
        return m;
    }
    function load() {
        D.load();
        var mm = D.read(MEET_KEY, []);
        meetings = (Array.isArray(mm) ? mm : []).filter(function (m) { return m && m.id; }).map(normMeeting);
        attendance = D.read(ATT_KEY, {});
        if (!attendance || typeof attendance !== 'object' || Array.isArray(attendance)) attendance = {};
    }
    function saveMeetings() { return D.write(MEET_KEY, meetings); }
    function typeText(m) { return m.type === 'Other' && m.typeOther ? m.typeOther : m.type; }
    function stamp(m) { return m.startDate + 'T' + (m.startTime || '00:00'); }
    function nameOf(id) { var k = D.kkById(id); return k ? D.kkName(k) : 'Removed member'; }
    function posOf(id) { var r = D.roleOf(id); return r ? r.position : '\u2014'; }
    function rangeText(m) {
        var s = fmtDate(m.startDate) + (m.startTime ? ', ' + fmtTime(m.startTime) : '');
        var sameDay = m.endDate === m.startDate;
        var e = m.endTime || m.endDate !== m.startDate ? (sameDay ? fmtTime(m.endTime) : fmtDate(m.endDate) + (m.endTime ? ', ' + fmtTime(m.endTime) : '')) : '';
        return s + (e ? ' \u2013 ' + e : '');
    }
    function statusBadge(s) {
        var cls = s === 'Scheduled' || s === 'Ongoing' ? 'bg-dark' : 'bg-secondary';
        return '<span class="badge ' + cls + '">' + esc(s) + '</span>';
    }
    // people whose attendance is tracked for a meeting: invited attendees, or all active SK if none were picked
    function attendeeIds(m) {
        return m.attendees.length ? m.attendees.filter(function (id) { return D.kkById(id); }) : D.officers().map(function (o) { return o.id; });
    }

    /* ---------- meeting list ---------- */
    function matches(m) {
        var q = D.norm($('meetSearch').value), t = $('meetTypeFilter').value, s = $('meetStatusFilter').value;
        if (t && m.type !== t) return false;
        if (s && m.status !== s) return false;
        if (!q) return true;
        var text = [m.title, m.type, m.typeOther, m.location, m.agenda, m.remarks, m.status, m.startDate, fmtDate(m.startDate)]
            .concat(m.attendees.map(nameOf)).join(' ').toLowerCase();
        return text.indexOf(q) > -1;
    }
    function filteredMeetings() {
        var today = D.todayISO();
        var up = meetings.filter(function (m) { return m.startDate >= today && matches(m); }).sort(function (a, b) { return stamp(a).localeCompare(stamp(b)); });
        var past = meetings.filter(function (m) { return m.startDate < today && matches(m); }).sort(function (a, b) { return stamp(b).localeCompare(stamp(a)); });
        return up.concat(past);
    }
    function renderMeetings() {
        var list = filteredMeetings();
        $('meetCount').textContent = meetings.length ? 'Showing ' + list.length + ' of ' + meetings.length + ' meeting' + (meetings.length !== 1 ? 's' : '') : '';
        if (!meetings.length) {
            $('meetingList').innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>' +
                '<div class="small mb-2">No meetings yet.</div><button type="button" class="btn btn-success btn-sm" onclick="openMeetingForm()"><i class="bi bi-plus-lg me-1"></i>Add Meeting</button></div>';
            return;
        }
        if (!list.length) { $('meetingList').innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-search fs-1 d-block mb-2"></i>No meetings match your search or filters.</div>'; return; }
        $('meetingList').innerHTML = list.map(function (m) {
            var d = new Date(m.startDate + 'T00:00:00');
            var rec = attendance[m.id] ? Object.keys(attendance[m.id]).length : 0;
            return '<div class="list-group-item d-flex gap-3 align-items-start">' +
                '<div class="date-box"><div class="fw-bold" style="font-size:.7rem;">' + esc(d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()) + '</div><div class="fw-bold fs-5 lh-1">' + d.getDate() + '</div></div>' +
                '<div class="flex-grow-1 min-w-0"><div class="fw-bold">' + esc(m.title) + ' ' + statusBadge(m.status) + ' <span class="badge bg-light text-dark border ms-1">' + esc(typeText(m)) + '</span></div>' +
                '<div class="small text-muted">' + esc(rangeText(m)) + (m.location ? ' \u2022 ' + esc(m.location) : '') + '</div>' +
                (m.agenda ? '<div class="small text-truncate">' + esc(m.agenda) + '</div>' : '') +
                '<div class="small text-muted">Attendees: ' + m.attendees.length + ' \u2022 Attendance recorded: ' + rec + '</div></div>' +
                '<div class="d-flex gap-1 flex-wrap justify-content-end flex-none" style="max-width:150px;">' +
                '<button type="button" class="btn btn-sm btn-outline-success" title="View meeting" data-view="' + esc(m.id) + '"><i class="bi bi-eye"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-success" title="Record attendance" data-att="' + esc(m.id) + '"><i class="bi bi-clipboard2-check"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-success" title="Edit meeting" data-edit="' + esc(m.id) + '"><i class="bi bi-pencil"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-success" title="Print meeting" data-print="' + esc(m.id) + '"><i class="bi bi-printer"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete meeting" data-del="' + esc(m.id) + '"><i class="bi bi-trash"></i></button></div></div>';
        }).join('');
    }

    /* ---------- add / edit meeting ---------- */
    var editingMeeting = null, formAttendees = [];

    function renderAttendeeBox() {
        var offs = D.officers(), ids = offs.map(function (o) { return o.id; });
        // keep already-invited people visible even if they are no longer active
        var extra = formAttendees.filter(function (id) { return ids.indexOf(id) < 0 && D.kkById(id); });
        var rows = offs.map(function (o) { return { id: o.id, name: o.name, pos: o.position, note: '' }; })
            .concat(extra.map(function (id) { return { id: id, name: nameOf(id), pos: posOf(id), note: ' (not active)' }; }));
        $('attendeeBox').innerHTML = rows.length ? rows.map(function (r) {
            return '<div class="form-check py-1"><input class="form-check-input att-check" type="checkbox" id="ac_' + esc(r.id) + '" value="' + esc(r.id) + '"' + (formAttendees.indexOf(r.id) > -1 ? ' checked' : '') + '>' +
                '<label class="form-check-label small" for="ac_' + esc(r.id) + '"><strong>' + esc(r.name) + '</strong> \u2013 ' + esc(r.pos) + esc(r.note) + '</label></div>';
        }).join('') : '<div class="small text-muted p-2">No active SK members yet. Assign SK roles to KK members in the <a href="members.php?type=SK">Member List</a>.</div>';
    }
    function checkedAttendees() { return Array.prototype.map.call(document.querySelectorAll('#attendeeBox .att-check:checked'), function (c) { return c.value; }); }

    window.openMeetingForm = function (id) {
        D.load();
        var m = id ? meetings.filter(function (x) { return x.id === id; })[0] : null;
        editingMeeting = m ? m.id : null;
        var f = $('meetingForm'); f.reset(); clearErrs(f);
        opts($('meetingType'), TYPES); opts($('meetingStatus'), MSTATUS); opts($('meetingLocation'), VENUES, 'Select venue');
        $('meetingModalTitle').textContent = m ? 'Edit Meeting' : 'Add Meeting';
        $('meetingSubmitBtn').textContent = m ? 'Update Meeting' : 'Save Meeting';
        formAttendees = m ? m.attendees.slice() : [];
        if (m) {
            $('meetingTitle').value = m.title; $('meetingType').value = m.type; $('startDate').value = m.startDate; $('startTime').value = m.startTime;
            $('endDate').value = m.endDate; $('endTime').value = m.endTime; $('meetingStatus').value = m.status;
            $('meetingTypeOther').value = m.typeOther || '';
            if (VENUES.indexOf(m.location) > -1 && m.location !== 'Other') $('meetingLocation').value = m.location;
            else if (m.location) { $('meetingLocation').value = 'Other'; $('meetingLocationOther').value = m.location; }
            $('meetingAgenda').value = m.agenda; $('meetingRemarks').value = m.remarks;
        }
        syncOther();
        renderAttendeeBox();
        modal('meetingModal').show();
    };
    function syncOther() {
        $('typeOtherBox').classList.toggle('d-none', $('meetingType').value !== 'Other');
        $('locOtherBox').classList.toggle('d-none', $('meetingLocation').value !== 'Other');
    }
    $('meetingType').addEventListener('change', function () { syncOther(); if (this.value === 'Other') $('meetingTypeOther').focus(); });
    $('meetingLocation').addEventListener('change', function () { syncOther(); if (this.value === 'Other') $('meetingLocationOther').focus(); });
    $('startDate').addEventListener('change', function () { if (this.value && (!$('endDate').value || $('endDate').value < this.value)) $('endDate').value = this.value; });
    $('attAll').addEventListener('click', function () { document.querySelectorAll('#attendeeBox .att-check').forEach(function (c) { c.checked = true; }); });
    $('attNone').addEventListener('click', function () { document.querySelectorAll('#attendeeBox .att-check').forEach(function (c) { c.checked = false; }); });

    $('meetingForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = this; clearErrs(form);
        var rec = {
            id: editingMeeting || D.uid('mt'), title: $('meetingTitle').value.trim(), type: $('meetingType').value,
            startDate: $('startDate').value, startTime: $('startTime').value, endDate: $('endDate').value, endTime: $('endTime').value,
            typeOther: $('meetingType').value === 'Other' ? $('meetingTypeOther').value.trim() : '',
            location: $('meetingLocation').value === 'Other' ? $('meetingLocationOther').value.trim() : $('meetingLocation').value, agenda: $('meetingAgenda').value.trim(), status: $('meetingStatus').value,
            attendees: checkedAttendees(), remarks: $('meetingRemarks').value.trim()
        };
        var ok = true;
        function bad(el, msg) { setErr(el, msg); ok = false; }
        if (rec.title.length < 3) bad($('meetingTitle'), 'Enter the meeting title.');
        if (!rec.type) bad($('meetingType'), 'Select a meeting type.');
        if (rec.type === 'Other' && rec.typeOther.length < 3) bad($('meetingTypeOther'), 'Enter what kind of meeting this is.');
        if (!rec.startDate) bad($('startDate'), 'Start date is required.');
        if (!rec.startTime) bad($('startTime'), 'Start time is required.');
        if (!rec.endDate) bad($('endDate'), 'End date is required.');
        if (!rec.endTime) bad($('endTime'), 'End time is required.');
        if (rec.startDate && rec.endDate && rec.endDate < rec.startDate) bad($('endDate'), 'End date cannot be before the start date.');
        else if (rec.startDate && rec.endDate === rec.startDate && rec.startTime && rec.endTime && rec.endTime <= rec.startTime) bad($('endTime'), 'End time must be after the start time.');
        if (!$('meetingLocation').value) bad($('meetingLocation'), 'Select the venue.');
        else if ($('meetingLocation').value === 'Other' && rec.location.length < 3) bad($('meetingLocationOther'), 'Enter the venue.');
        if (ok && meetings.some(function (m) { return m.id !== rec.id && m.startDate === rec.startDate && m.startTime === rec.startTime && m.title.toLowerCase() === rec.title.toLowerCase(); }))
            bad($('meetingTitle'), 'A meeting with the same title, date and time already exists.');
        if (!ok) { var first = form.querySelector('.is-invalid'); if (first) first.focus(); return; }

        var before = meetings.slice();
        if (editingMeeting) meetings = meetings.map(function (m) { return m.id === rec.id ? rec : m; });
        else meetings.push(rec);
        if (!saveMeetings()) { meetings = before; alert('Could not save the meeting (browser storage problem).'); return; }
        modal('meetingModal').hide();
        renderAll(rec.id);
    });

    function deleteMeeting(id) {
        var m = meetings.filter(function (x) { return x.id === id; })[0];
        if (!m || !confirm('Delete the meeting "' + m.title + '"? Its attendance record will also be deleted.')) return;
        meetings = meetings.filter(function (x) { return x.id !== id; });
        delete attendance[id];
        saveMeetings(); D.write(ATT_KEY, attendance);
        renderAll();
    }

    /* ---------- view / print ---------- */
    function kv(l, v) { return '<div class="col-sm-6"><div class="kv-l">' + esc(l) + '</div><div class="kv-v">' + (v === '' || v == null ? '\u2014' : esc(v)) + '</div></div>'; }
    function meetingHtml(m) {
        var rec = attendance[m.id] || {}, ids = attendeeIds(m);
        var rows = ids.length ? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>SK Member</th><th>Position</th><th>Attendance</th><th>Remarks</th></tr></thead><tbody>' +
            ids.map(function (id) {
                var a = rec[id] || {};
                return '<tr><td>' + esc(nameOf(id)) + '</td><td>' + esc(posOf(id)) + '</td><td>' + esc(a.status || 'Not recorded') + '</td><td>' + esc(a.remarks || '') + '</td></tr>';
            }).join('') + '</tbody></table></div>' : '<div class="small text-muted">No attendees.</div>';
        return '<div class="d-flex flex-wrap gap-2 align-items-center mb-2"><h4 class="fw-bold mb-0">' + esc(m.title) + '</h4>' + statusBadge(m.status) + '</div>' +
            '<div class="row g-3">' + kv('Meeting type', typeText(m)) + kv('Status', m.status) + kv('Start', fmtDate(m.startDate) + (m.startTime ? ', ' + fmtTime(m.startTime) : '')) +
            kv('End', fmtDate(m.endDate) + (m.endTime ? ', ' + fmtTime(m.endTime) : '')) + kv('Location', m.location) + kv('Attendees invited', String(m.attendees.length)) + '</div>' +
            '<div class="group-title">Description / Agenda</div><div class="small">' + esc(m.agenda || 'No agenda added.').replace(/\n/g, '<br>') + '</div>' +
            '<div class="group-title">Attendees and Attendance</div>' + rows +
            '<div class="group-title">Remarks</div><div class="small">' + esc(m.remarks || 'No remarks.').replace(/\n/g, '<br>') + '</div>';
    }
    function openView(id) {
        var m = meetings.filter(function (x) { return x.id === id; })[0];
        if (!m) return;
        $('viewBody').innerHTML = meetingHtml(m);
        $('viewFooter').innerHTML =
            '<button type="button" class="btn btn-outline-success btn-sm" data-print="' + esc(id) + '"><i class="bi bi-printer me-1"></i>Print</button>' +
            '<button type="button" class="btn btn-outline-success btn-sm" data-att="' + esc(id) + '"><i class="bi bi-clipboard2-check me-1"></i>Attendance</button>' +
            '<button type="button" class="btn btn-outline-success btn-sm" data-edit="' + esc(id) + '"><i class="bi bi-pencil me-1"></i>Edit</button>' +
            '<button type="button" class="btn btn-dark btn-sm ms-auto" data-bs-dismiss="modal">Close</button>';
        modal('viewModal').show();
    }
    function printHeader(title) {
        return '<div style="text-align:center;margin-bottom:12px;"><div style="font-weight:800;font-size:15px;">Sangguniang Kabataan \u2013 Barangay ' + BARANGAY + '</div>' +
            '<div>' + CITY + ', ' + PROVINCE + '</div><div style="font-weight:700;margin-top:6px;">' + esc(title) + '</div>' +
            '<div style="font-size:11px;">Printed ' + fmtDate(D.todayISO()) + '</div></div>';
    }
    function doPrint(html) { $('printArea').innerHTML = html; setTimeout(function () { window.print(); }, 150); }
    function printMeeting(id) {
        var m = meetings.filter(function (x) { return x.id === id; })[0];
        if (m) doPrint(printHeader('SK Meeting Record') + meetingHtml(m));
    }
    function printList() {
        var list = filteredMeetings();
        if (!list.length) { alert('There are no meetings to print.'); return; }
        doPrint(printHeader('SK Meetings') + '<table><thead><tr><th>#</th><th>Title</th><th>Type</th><th>Start</th><th>End</th><th>Location</th><th>Status</th><th>Attendees</th><th>Remarks</th></tr></thead><tbody>' +
            list.map(function (m, i) {
                return '<tr><td>' + (i + 1) + '</td><td>' + esc(m.title) + '</td><td>' + esc(typeText(m)) + '</td><td>' + esc(fmtDate(m.startDate) + ' ' + fmtTime(m.startTime)) + '</td><td>' +
                    esc(fmtDate(m.endDate) + ' ' + fmtTime(m.endTime)) + '</td><td>' + esc(m.location) + '</td><td>' + esc(m.status) + '</td><td>' + m.attendees.length + '</td><td>' + esc(m.remarks) + '</td></tr>';
            }).join('') + '</tbody></table>');
    }

    /* ---------- attendance ---------- */
    function renderAttendanceSelect(selectId) {
        var sel = $('attendanceMeeting'), keep = selectId || sel.value;
        var list = meetings.slice().sort(function (a, b) { return stamp(b).localeCompare(stamp(a)); });
        sel.innerHTML = list.length
            ? list.map(function (m) { return '<option value="' + esc(m.id) + '">' + esc(m.title) + ' \u2013 ' + esc(fmtDate(m.startDate)) + '</option>'; }).join('')
            : '<option value="">No meetings available</option>';
        if (keep && list.some(function (m) { return m.id === keep; })) sel.value = keep;
    }
    function renderAttendanceRows() {
        var mid = $('attendanceMeeting').value, m = meetings.filter(function (x) { return x.id === mid; })[0];
        var body = $('attendanceBody'), saved = attendance[mid] || {};
        $('saveAttendanceBtn').disabled = !m;
        if (!m) { body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Add a meeting first to record attendance.</td></tr>'; return; }
        var ids = attendeeIds(m);
        if (!ids.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No active SK members yet. Assign SK roles in the <a href="members.php?type=SK">Member List</a>.</td></tr>';
            $('saveAttendanceBtn').disabled = true; return;
        }
        body.innerHTML = ids.map(function (id) {
            var s = saved[id] || {};
            return '<tr data-id="' + esc(id) + '"><td class="fw-semibold">' + esc(nameOf(id)) + '</td><td>' + esc(posOf(id)) + '</td><td>' + esc(fmtDate(m.startDate)) + '</td>' +
                '<td><select class="form-select form-select-sm att-status"><option value="">Select</option>' +
                ASTATUS.map(function (x) { return '<option' + (s.status === x ? ' selected' : '') + '>' + x + '</option>'; }).join('') + '</select></td>' +
                '<td><input type="text" class="form-control form-control-sm att-remarks" maxlength="120" value="' + esc(s.remarks || '') + '" placeholder="Optional"></td></tr>';
        }).join('');
    }
    window.saveAttendance = function () {
        var mid = $('attendanceMeeting').value;
        if (!mid) return;
        var rec = {}, n = 0;
        document.querySelectorAll('#attendanceBody tr[data-id]').forEach(function (tr) {
            var st = tr.querySelector('.att-status').value;
            if (st) { rec[tr.dataset.id] = { status: st, remarks: tr.querySelector('.att-remarks').value.trim() }; n++; }
        });
        if (!n) { note('attendanceMessage', 'warning', 'Select an attendance status for at least one member.'); return; }
        attendance[mid] = rec;
        if (!D.write(ATT_KEY, attendance)) { note('attendanceMessage', 'danger', 'Could not save attendance (browser storage problem).'); return; }
        note('attendanceMessage', 'success', 'Attendance saved for ' + n + ' member' + (n > 1 ? 's' : '') + '.');
        renderSummary(); renderMeetings();
    };

    /* ---------- summary ---------- */
    function renderSummary() {
        var active = D.officers().length;
        $('statTotalSk').textContent = D.roles.length;
        $('statActiveSk').textContent = active;
        $('statMeetings').textContent = meetings.length;
        var tot = 0, ok = 0;
        Object.keys(attendance).forEach(function (mid) {
            if (!meetings.some(function (m) { return m.id === mid; })) return;
            Object.keys(attendance[mid]).forEach(function (pid) {
                tot++;
                if (attendance[mid][pid].status === 'Present' || attendance[mid][pid].status === 'Late') ok++;
            });
        });
        $('statAttendance').textContent = tot ? Math.round(ok / tot * 100) + '%' : '0%';
        $('skCountNote').textContent = D.roles.length + ' SK member' + (D.roles.length !== 1 ? 's' : '') + ' (' + active + ' active)';
    }
    function renderAll(selectMeeting) {
        renderMeetings(); renderSummary(); renderAttendanceSelect(selectMeeting); renderAttendanceRows();
    }

    /* ---------- events ---------- */
    function goAttendance(id) { renderAttendanceSelect(id); renderAttendanceRows(); $('attendance').scrollIntoView({ behavior: 'smooth' }); }
    document.addEventListener('click', function (e) {
        var b;
        if ((b = e.target.closest('[data-view]'))) openView(b.dataset.view);
        else if ((b = e.target.closest('[data-edit]'))) { var id = b.dataset.edit; afterHide('viewModal', function () { openMeetingForm(id); }); }
        else if ((b = e.target.closest('[data-del]'))) deleteMeeting(b.dataset.del);
        else if ((b = e.target.closest('[data-print]'))) printMeeting(b.dataset.print);
        else if ((b = e.target.closest('[data-att]'))) { var aid = b.dataset.att; afterHide('viewModal', function () { goAttendance(aid); }); }
    });
    $('printListBtn').addEventListener('click', printList);
    $('attendanceMeeting').addEventListener('change', renderAttendanceRows);
    $('meetSearch').addEventListener('input', renderMeetings);
    $('meetTypeFilter').addEventListener('change', renderMeetings);
    $('meetStatusFilter').addEventListener('change', renderMeetings);
    opts($('meetTypeFilter'), TYPES, 'All Types');
    opts($('meetStatusFilter'), MSTATUS, 'All Status');

    function refresh() { if (isOpen('meetingModal')) return; load(); renderAll(); }
    window.addEventListener('storage', function (e) { if (e.key === D.KEYS.KK || e.key === D.KEYS.ROLE || e.key === MEET_KEY || e.key === ATT_KEY || e.key === null) refresh(); });
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
    window.addEventListener('pageshow', function (e) { if (e.persisted) refresh(); });

    load();
    if (meetings.length) saveMeetings();     // store upgraded meeting fields once
    renderAll();
})();
</script>

</body>
</html>