<?php
require_once __DIR__ . '/config.php';
$memberPortalUser = require_member_account();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">
    <link href="css/kk-id.css?v=1" rel="stylesheet">
    <link href="css/participation-heatmap.css?v=1" rel="stylesheet">
    <style>
        .dashboard-page{background:var(--ui-paper)}
        .dashboard-toolbar{
            min-height:68px;
            border-bottom:1px solid var(--ui-line);
        }
        .dashboard-overview{padding-top:.25rem}
        .portal-welcome{
            position:relative;
            isolation:isolate;
            overflow:hidden;
            min-height:138px;
            padding:1.35rem clamp(1.1rem,2.5vw,1.75rem);
            border:1px solid rgba(255,255,255,.2);
            border-radius:20px;
            color:#fff;
            background:linear-gradient(112deg,#1249a8 0%,#1765d6 58%,#4385ec 100%);
            box-shadow:0 14px 32px rgba(24,78,174,.18);
        }
        .portal-welcome:after{
            position:absolute;
            z-index:-1;
            content:"";
            width:230px;
            height:230px;
            right:-55px;
            top:-145px;
            border:1px solid rgba(255,255,255,.16);
            border-radius:50%;
            box-shadow:0 0 0 25px rgba(255,255,255,.05),0 0 0 55px rgba(255,255,255,.035);
            pointer-events:none;
        }
        .portal-welcome h2{color:#fff}
        .portal-welcome p{color:rgba(255,255,255,.84)!important}
        .portal-welcome .btn-outline-primary{
            color:#fff;
            border-color:rgba(255,255,255,.72);
        }
        .portal-welcome .btn-outline-primary:hover{
            color:var(--mem-brand);
            background:#fff;
            border-color:#fff;
        }
        .portal-welcome .btn-primary{
            color:var(--mem-brand);
            background:#fff;
            border-color:#fff;
        }
        .portal-welcome .btn-primary:hover{
            color:#1249a8;
            background:var(--mem-brand-soft);
            border-color:var(--mem-brand-soft);
        }
        .dashboard-stat,.dashboard-panel{
            height:100%;
            background:var(--ui-surface);
            border:1px solid var(--ui-line);
            border-radius:18px;
            box-shadow:0 10px 28px rgba(37,76,151,.08);
        }
        .dashboard-stat{
            min-height:130px;
            padding:1rem 1.1rem;
            display:flex;
            flex-direction:column;
            justify-content:center;
            gap:.12rem;
        }
        .dashboard-stat-icon{
            display:grid;
            place-items:center;
            width:2.35rem;
            height:2.35rem;
            margin-bottom:.35rem;
            border-radius:50%;
            color:var(--mem-brand);
            background:var(--mem-brand-soft);
            font-size:1.05rem;
        }
        .dashboard-stat .fs-2{
            color:var(--mem-ink);
            font-size:clamp(1.2rem,2vw,1.65rem)!important;
            font-weight:700;
            letter-spacing:-.035em;
            overflow-wrap:anywhere;
        }
        .dashboard-stat-row>.col-6{min-width:0}
        .dashboard-panel{padding:1.1rem 1.2rem;overflow:hidden}
        .dashboard-panel-head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            flex-wrap:wrap;
            gap:.5rem;
            margin-bottom:.85rem;
        }
        .dashboard-status{
            display:inline-flex;
            align-items:center;
            padding:.22rem .55rem;
            border-radius:999px;
            color:var(--mem-brand-text-emphasis);
            background:var(--mem-brand-soft);
            font-size:.72rem;
            font-weight:650;
            white-space:nowrap;
        }
        .dashboard-table th{white-space:nowrap;font-size:.8rem;color:var(--ui-muted);font-weight:600}
        .dashboard-table td{vertical-align:middle}
        .portal-profile{
            display:flex;
            align-items:center;
            gap:1rem;
            padding-bottom:1rem;
            margin-bottom:1rem;
            border-bottom:1px solid var(--ui-line);
        }
        .portal-details{margin:0}
        .portal-details>div{
            display:flex;
            justify-content:space-between;
            gap:1rem;
            padding:.6rem 0;
            border-bottom:1px solid var(--ui-line);
            font-size:.875rem;
        }
        .portal-details>div:last-child{border-bottom:0}
        .portal-details dt{color:var(--ui-muted);font-weight:500}
        .portal-details dd{margin:0;text-align:right;overflow-wrap:anywhere;font-weight:500}
        .portal-upload{
            margin-top:1rem;
            padding-top:1rem;
            border-top:1px solid var(--ui-line);
        }
        .portal-empty{
            text-align:center;
            color:var(--ui-muted);
            padding:2rem 1rem;
            font-size:.875rem;
        }
        .portal-empty i{display:block;font-size:1.6rem;margin-bottom:.4rem;color:var(--mem-brand)}
        .member-heatmap-section{
            padding:.85rem 0 1rem;
            margin-bottom:1rem;
            border-bottom:1px solid var(--ui-line);
        }
        .member-heatmap-year{width:auto;min-width:100px}
        @media(max-width:575.98px){
            .dashboard-toolbar{flex-wrap:wrap}
            .portal-details>div{flex-direction:column;gap:.1rem}
            .portal-details dd{text-align:left}
        }
    </style>
    <link href="css/site-theme.css?v=2" rel="stylesheet">
    <script>
        window.SK_CSRF_TOKEN = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
</head>

<body class="dashboard-page">
<div class="top-strip"></div>
<script>
    window.SK_PUBLIC = true;
    window.SK_MEMBER_PORTAL = true;
</script>
<script src="js/theme.js?v=3"></script>
<script src="js/sidebar.js?v=20"></script>

<div class="page-content">
    <header class="bg-white border-bottom sticky-top">
        <div class="dashboard-toolbar d-flex align-items-center gap-2 gap-md-3 px-3 px-lg-4 py-2">
            <button class="btn btn-outline-primary d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div>
                <h1 class="h5 fw-bold mb-0">Dashboard</h1>
            </div>
            <div class="ms-auto header-account-area">
                <span class="header-avatar"><?= e(uinit()) ?></span>
                <div class="d-none d-md-block header-identity">
                    <div class="header-identity-name"><?= e(uname()) ?></div>
                    <div class="header-identity-role">KK MEMBER</div>
                </div>
                <a href="logout.php" class="btn btn-sm header-logout">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
        <section class="dashboard-overview portal-welcome d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1" id="welcomeName">Loading your dashboard...</h2>
                <p class="text-muted small mb-0"><?= e(date('l, F j, Y')) ?> · Your profile, participation record and digital KK ID.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php#announcements" class="btn btn-sm btn-outline-primary px-3">
                    <i class="bi bi-megaphone me-1"></i>Announcements
                </a>
                <button type="button" class="btn btn-sm btn-primary px-3" id="digitalIdButton">
                    <i class="bi bi-qr-code me-1"></i>My ID / Digital ID
                </button>
            </div>
        </section>

        <div class="alert alert-danger d-none" id="portalError" role="alert"></div>
        <div class="alert alert-success d-none" id="portalNotice" role="status"></div>

        <section class="row g-3 mb-3 dashboard-stat-row" aria-label="My summary">
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold" id="statStatus">—</div>
                    <div class="text-muted">Membership status</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold" id="statParticipation">0</div>
                    <div class="text-muted">Participation records</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-printer" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold" id="statPrints">0</div>
                    <div class="text-muted">ID prints</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold" id="statSince">—</div>
                    <div class="text-muted">Registered on</div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel mb-3 d-none" id="digitalIdPanel">
            <div class="dashboard-panel-head">
                <div>
                    <h2 class="h6 fw-bold mb-1">My Digital KK ID</h2>
                    <div class="small text-muted">Show this screen to SK staff for QR verification. The QR contains an opaque code, not your personal details.</div>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                <div id="digitalIdCard"></div>
                <div class="text-center text-md-start">
                    <div class="kk-portal-qr-wrap mb-2" id="digitalIdQr"></div>
                    <div class="small text-muted">Keep your digital ID private. QR verification is available only to authorized SK staff.</div>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-head">
                        <h2 class="h6 fw-bold mb-0">My Profile</h2>
                    </div>
                    <div class="portal-profile">
                        <img src="images/sk-logo.jpg" alt="Profile picture" class="kk-portal-profile-photo" id="memberPhoto">
                        <div class="min-w-0">
                            <div class="fw-bold" id="memberName">Loading...</div>
                            <div class="small text-muted mb-2" id="memberNumber"></div>
                            <span class="dashboard-status" id="memberStatus">Active</span>
                        </div>
                    </div>
                    <dl class="portal-details">
                        <div><dt>Date of birth</dt><dd id="memberBirthDate">—</dd></div>
                        <div><dt>Gender</dt><dd id="memberGender">—</dd></div>
                        <div><dt>Purok</dt><dd id="memberPurok">—</dd></div>
                        <div><dt>Mobile</dt><dd id="memberMobile">—</dd></div>
                        <div><dt>Email</dt><dd id="memberEmail">—</dd></div>
                        <div><dt>Registered</dt><dd id="memberCreated">—</dd></div>
                    </dl>
                    <form id="photoForm" class="portal-upload">
                        <label for="profilePicture" class="form-label small fw-semibold">Change my picture</label>
                        <input type="file" class="form-control form-control-sm" id="profilePicture" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">JPG, PNG, or WebP; up to 10 MB. The image is resized before it is saved.</div>
                        <button type="submit" class="btn btn-sm btn-outline-primary mt-3" id="savePhotoButton">
                            <i class="bi bi-image me-1"></i>Save picture
                        </button>
                    </form>
                </section>
            </div>
            <div class="col-lg-8">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-head">
                        <div>
                            <h2 class="h6 fw-bold mb-1">My Participation</h2>
                            <div class="small text-muted">Events and activities recorded for your member profile.</div>
                        </div>
                        <span class="dashboard-status" id="participationCount">0 records</span>
                    </div>
                    <section class="member-heatmap-section" aria-labelledby="memberHeatmapTitle">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div>
                                <h3 class="h6 fw-bold mb-1" id="memberHeatmapTitle">My attendance activity</h3>
                                <p class="small text-muted mb-0">Only confirmed attendance linked to your KK profile is counted.</p>
                            </div>
                            <div>
                                <label class="visually-hidden" for="memberHeatmapYear">Heatmap year</label>
                                <select class="form-select form-select-sm member-heatmap-year" id="memberHeatmapYear">
                                    <option value="<?= e(date('Y')) ?>"><?= e(date('Y')) ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="heatmap-summary" id="memberHeatmapSummary" aria-live="polite"></div>
                        <div class="heatmap-scroll">
                            <div id="memberHeatmapCalendar"></div>
                        </div>
                        <div class="heatmap-legend" aria-label="Attendance intensity legend">
                            <span>Less</span><span class="heatmap-day"></span><span class="heatmap-day" data-level="1"></span><span class="heatmap-day" data-level="2"></span><span class="heatmap-day" data-level="3"></span><span class="heatmap-day" data-level="4"></span><span>More</span>
                        </div>
                    </section>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 dashboard-table">
                            <thead><tr><th scope="col">Activity</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col">Record</th></tr></thead>
                            <tbody id="participationRows"><tr><td colspan="4" class="text-muted text-center py-4">Loading your records...</td></tr></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2 class="h6 fw-bold mb-1">ID Printing History</h2>
                    <div class="small text-muted">Digital ID display is not counted as printing. SK-initiated prints are recorded with their method and date.</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 dashboard-table">
                    <thead><tr><th scope="col">Date and time</th><th scope="col">Method</th></tr></thead>
                    <tbody id="idPrintRows"><tr><td colspan="2" class="text-muted">No ID prints recorded.</td></tr></tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div id="printArea"></div>
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js" integrity="sha384-lQXOAyZwHXE55JFyrOMB7nY2Wv+m5ZWNtJcHrd1rceRQXAYNLak8ukN5TjBTcIwz" crossorigin="anonymous"></script>
<script src="js/kk-id.js?v=1"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="js/participation-heatmap.js?v=1"></script>
<script>
(function () {
    'use strict';
    var member = null;
    var participationRecords = [];
    var memberHeatmapInitialized = false;
    var defaultHeatmapYear = <?= json_encode(date('Y')) ?>;
    var $ = function (id) { return document.getElementById(id); };

    function showError(message) {
        $('portalError').textContent = message;
        $('portalError').classList.remove('d-none');
    }

    function showNotice(message) {
        $('portalNotice').textContent = message;
        $('portalNotice').classList.remove('d-none');
        setTimeout(function () { $('portalNotice').classList.add('d-none'); }, 5000);
    }

    function display(value) { return value ? String(value) : '—'; }

    function formatDate(value) {
        if (!value) return '—';
        var date = new Date(String(value).slice(0, 10) + 'T12:00:00');
        return isNaN(date.getTime()) ? String(value) : date.toLocaleDateString();
    }

    function formatDateTime(value) {
        if (!value) return '—';
        var date = new Date(String(value).replace(' ', 'T'));
        return isNaN(date.getTime()) ? String(value) : date.toLocaleString();
    }

    function emptyRow(colspan, icon, message) {
        var row = document.createElement('tr');
        var cell = document.createElement('td');
        cell.colSpan = colspan;
        cell.className = 'portal-empty';
        var mark = document.createElement('i');
        mark.className = 'bi ' + icon;
        cell.appendChild(mark);
        cell.appendChild(document.createTextNode(message));
        row.appendChild(cell);
        return row;
    }

    function participationDateYear(value) {
        var dateText = String(value || '').slice(0, 10);
        var match = dateText.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return null;
        var date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
        if (date.getUTCFullYear() !== Number(match[1]) ||
            date.getUTCMonth() !== Number(match[2]) - 1 ||
            date.getUTCDate() !== Number(match[3])) return null;
        return match[1];
    }

    function renderMemberHeatmap() {
        var year = Number($('memberHeatmapYear').value);
        var dates = participationRecords.filter(function (record) {
            return record.status === 'Attended';
        }).map(function (record) {
            return String(record.date || '').slice(0, 10);
        });
        var total = window.KKParticipationHeatmap.render(
            $('memberHeatmapCalendar'),
            year,
            dates,
            'Your confirmed attendance in ' + year
        );
        $('memberHeatmapSummary').textContent = total
            ? total + ' confirmed attendance record' + (total === 1 ? '' : 's') + ' in ' + year
            : 'No confirmed attendance records in ' + year + '.';
    }

    function initializeMemberHeatmap(records) {
        if (memberHeatmapInitialized) return;
        var years = new Set([defaultHeatmapYear]);
        records.forEach(function (record) {
            var year = participationDateYear(record.date);
            if (year) years.add(year);
        });
        var yearFilter = $('memberHeatmapYear');
        yearFilter.innerHTML = Array.from(years).sort(function (left, right) {
            return Number(right) - Number(left);
        }).map(function (year) {
            return '<option value="' + year + '">' + year + '</option>';
        }).join('');
        yearFilter.value = defaultHeatmapYear;
        yearFilter.addEventListener('change', renderMemberHeatmap);
        memberHeatmapInitialized = true;
    }

    function renderParticipation(records) {
        participationRecords = records;
        initializeMemberHeatmap(records);
        renderMemberHeatmap();
        var label = records.length + ' record' + (records.length === 1 ? '' : 's');
        $('participationCount').textContent = label;
        $('statParticipation').textContent = records.length;
        var body = $('participationRows');
        body.replaceChildren();
        if (!records.length) {
            body.appendChild(emptyRow(4, 'bi-calendar-x', 'No activity records are linked to your member profile yet.'));
            return;
        }
        records.forEach(function (record) {
            var row = document.createElement('tr');
            [record.title, formatDate(record.date), record.status, record.source].forEach(function (value, index) {
                var cell = document.createElement('td');
                if (index === 2) {
                    var pill = document.createElement('span');
                    pill.className = 'dashboard-status';
                    pill.textContent = display(value);
                    cell.appendChild(pill);
                } else {
                    cell.textContent = display(value);
                }
                row.appendChild(cell);
            });
            body.appendChild(row);
        });
    }

    function renderPrints(records) {
        $('statPrints').textContent = records.length;
        var body = $('idPrintRows');
        body.replaceChildren();
        if (!records.length) {
            body.appendChild(emptyRow(2, 'bi-printer', 'No ID prints recorded.'));
            return;
        }
        records.forEach(function (record) {
            var row = document.createElement('tr');
            var date = document.createElement('td');
            date.textContent = formatDateTime(record.printed_at);
            var method = document.createElement('td');
            method.textContent = record.print_method === 'manual' ? 'Manual' : 'Automatic';
            row.appendChild(date);
            row.appendChild(method);
            body.appendChild(row);
        });
    }

    function renderProfile(data) {
        member = data.member;
        $('welcomeName').textContent = 'Welcome, ' + (member.firstName || member.name) + '.';
        $('memberName').textContent = member.name;
        $('memberNumber').textContent = 'KK No. ' + display(member.memberNo);
        $('memberStatus').textContent = display(member.status);
        $('statStatus').textContent = display(member.status);
        $('statSince').textContent = formatDate(member.createdAt);
        $('memberBirthDate').textContent = formatDate(member.birthDate);
        $('memberGender').textContent = display(member.gender);
        $('memberPurok').textContent = display(member.purok);
        $('memberMobile').textContent = display(member.mobile);
        $('memberEmail').textContent = display(member.email);
        $('memberCreated').textContent = formatDate(member.createdAt);
        if (member.photo) $('memberPhoto').src = member.photo;
        renderParticipation(data.tracking || []);
        renderPrints(data.prints || []);
    }

    function loadPortal() {
        KKId.api('member_portal_data', {}).then(renderProfile).catch(function (error) {
            $('welcomeName').textContent = 'Dashboard';
            showError(error.message || 'Could not load your member portal.');
        });
    }

    function resizePicture(file) {
        return new Promise(function (resolve, reject) {
            if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) {
                reject(new Error('Choose a JPG, PNG, or WebP image.'));
                return;
            }
            if (file.size > 10485760) {
                reject(new Error('This picture is too big. The maximum size is 10 MB.'));
                return;
            }
            var reader = new FileReader();
            reader.onerror = function () { reject(new Error('Could not read the selected picture.')); };
            reader.onload = function () {
                var image = new Image();
                image.onerror = function () { reject(new Error('The selected file is not a valid image.')); };
                image.onload = function () {
                    var scale = Math.min(1, 600 / Math.max(image.naturalWidth, image.naturalHeight));
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                    var context = canvas.getContext('2d');
                    if (!context) { reject(new Error('Could not resize the selected picture.')); return; }
                    context.drawImage(image, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function (blob) {
                        if (!blob) { reject(new Error('Could not resize the selected picture.')); return; }
                        var output = new FileReader();
                        output.onerror = function () { reject(new Error('Could not prepare the selected picture.')); };
                        output.onload = function () { resolve(output.result); };
                        output.readAsDataURL(blob);
                    }, 'image/jpeg', 0.84);
                };
                image.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    $('photoForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var file = $('profilePicture').files[0];
        if (!file) { showError('Choose a picture before saving.'); return; }
        $('savePhotoButton').disabled = true;
        $('portalError').classList.add('d-none');
        resizePicture(file).then(function (photo) {
            return KKId.api('member_portal_photo', { photo: photo });
        }).then(function (result) {
            member.photo = result.photo;
            $('memberPhoto').src = result.photo;
            if (!$('digitalIdPanel').classList.contains('d-none')) {
                return KKId.getToken(member.id).then(function (token) {
                    KKId.renderCard($('digitalIdCard'), member, token);
                });
            }
        }).then(function () {
            $('profilePicture').value = '';
            showNotice('Your profile picture was updated.');
        }).catch(function (error) {
            showError(error.message || 'Could not save your profile picture.');
        }).finally(function () {
            $('savePhotoButton').disabled = false;
        });
    });

    $('digitalIdButton').addEventListener('click', function () {
        var panel = $('digitalIdPanel');
        if (!panel.classList.contains('d-none')) {
            panel.classList.add('d-none');
            return;
        }
        if (!member) return;
        $('portalError').classList.add('d-none');
        KKId.getToken(member.id).then(function (token) {
            var card = KKId.renderCard($('digitalIdCard'), member, token);
            var qr = document.createElement('img');
            qr.src = card.querySelector('.kk-id-qr').src;
            qr.alt = 'QR code for your KK digital ID';
            $('digitalIdQr').replaceChildren(qr);
            panel.classList.remove('d-none');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }).catch(function (error) {
            showError(error.message || 'Could not load your digital ID.');
        });
    });

    loadPortal();
})();
</script>
</body>
</html>