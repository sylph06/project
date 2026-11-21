<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Participation Tracking</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=31" rel="stylesheet">

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
            background:var(--bs-body-bg);
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
            padding:30px;
        }

        .hero {
            background:var(--bs-primary);
            border-radius:16px;
            padding:28px;
            margin-bottom:25px;
        }

        .hero h1 {
            font-size:32px;
            font-weight:800;
        }

        .hero p {
            color:var(--bs-primary-text-emphasis);
            font-size:16px;
            margin:0;
        }

        .card-box {
            background:var(--bs-body-bg);
            border:1px solid var(--line);
            border-radius:14px;
            margin-bottom:22px;
            overflow:hidden;
        }

        .card-header {
            padding:17px 20px;
            border-bottom:1px solid var(--line);
            font-weight:bold;
        }

        .card-body {
            padding:20px;
        }

        .stat {
            background:var(--bs-body-bg);
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

        .table th {
            background:var(--bs-primary-text-emphasis);
            color:white;
            font-size:13px;
            padding:13px;
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

        .heatmap-card-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
        }

        .heatmap-category-filter {
            width:auto;
            min-width:190px;
            max-width:280px;
            font-size:13px;
        }

        .footer-note {
            text-align:center;
            color:var(--muted);
            font-size:13px;
            padding:25px;
        }

        @media(max-width:991px) {
            .content { padding:18px; }
        }

        @media(max-width:575px) {
            .heatmap-card-header { align-items:flex-start; flex-direction:column; }
            .heatmap-category-filter { width:100%; max-width:none; }
        }

        .filter-bar { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; padding:14px 18px; }
        .filter-bar .form-label { font-size:12px; color:var(--muted); margin-bottom:3px; }
        .filter-bar .grow { flex:1 1 200px; min-width:160px; }
        .filter-bar .fixed { flex:0 0 150px; }
        .stat .sub { font-size:12px; color:var(--muted); margin-top:2px; min-height:16px; }
        .view-controls { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
        .view-controls .form-select { width:auto; min-width:150px; font-size:13px; }
        .heatmap-scroll { overflow-x:auto; }
        /* ---- v2 visual refresh ---- */
        .card-box { border-radius:16px; box-shadow:0 1px 2px rgba(16,24,40,.05); }
        .card-header { background:transparent; font-size:15px; padding:16px 20px; }
        .stat { border-radius:16px; box-shadow:0 1px 2px rgba(16,24,40,.05); height:100%; }
        .stat i { background:var(--bs-primary-bg-subtle); color:var(--bs-primary); width:44px; height:44px; border-radius:12px; font-size:21px; }
        .stat .number { font-size:32px; line-height:1.1; letter-spacing:-.5px; }
        .stat .label { font-weight:600; color:var(--bs-body-color); }
        .stat .progress { height:6px; margin-top:10px; }
        .filter-bar .form-select, .filter-bar .form-control, .filter-bar .btn { height:40px; }
        .filter-bar { align-items:flex-end; }
        .table th { background:var(--bs-tertiary-bg); color:var(--muted); text-transform:uppercase; letter-spacing:.04em; font-size:11.5px; font-weight:600; border-bottom:1px solid var(--line); }
        .table td { border-color:var(--line); }
        .table tbody tr:hover td { background:var(--bs-tertiary-bg); }

        .pc-head { display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; }
        .pc-chip { display:inline-flex; align-items:center; gap:6px; background:var(--bs-primary-bg-subtle); color:var(--bs-primary-text-emphasis); border-radius:999px; padding:3px 6px 3px 12px; font-size:13px; font-weight:600; }
        .pc-chip button { border:0; background:transparent; color:inherit; line-height:1; padding:2px 6px; border-radius:999px; }
        .pc-chip button:hover { background:rgba(0,0,0,.08); }

        .pc-cal-wrap { overflow-x:auto; padding-bottom:4px; }
        .pc-cal { min-width:calc(var(--weeks) * 15px + 34px); display:grid; grid-template-columns:30px 1fr; column-gap:6px; row-gap:6px; }
        .pc-months { grid-column:2; display:grid; grid-template-columns:repeat(var(--weeks), 1fr); font-size:11px; color:var(--muted); height:14px; }
        .pc-months span { white-space:nowrap; }
        .pc-dow { display:grid; grid-template-rows:repeat(7, 1fr); font-size:11px; color:var(--muted); }
        .pc-dow span { display:flex; align-items:center; }
        .pc-grid { display:grid; grid-template-columns:repeat(var(--weeks), 1fr); grid-template-rows:repeat(7, auto); grid-auto-flow:column; gap:3px; }
        .pc-cell { aspect-ratio:1; border-radius:4px; background:var(--bs-secondary-bg); min-width:10px; }
        .pc-cell.pc-pad { background:transparent; }
        .pc-cell[data-l="1"], .pc-key i[data-l="1"] { background:color-mix(in srgb, var(--bs-primary) 28%, var(--bs-body-bg)); }
        .pc-cell[data-l="2"], .pc-key i[data-l="2"] { background:color-mix(in srgb, var(--bs-primary) 50%, var(--bs-body-bg)); }
        .pc-cell[data-l="3"], .pc-key i[data-l="3"] { background:color-mix(in srgb, var(--bs-primary) 75%, var(--bs-body-bg)); }
        .pc-cell[data-l="4"], .pc-key i[data-l="4"] { background:var(--bs-primary); }
        .pc-cell:not(.pc-pad):hover { outline:2px solid var(--bs-primary-text-emphasis); outline-offset:1px; }
        .pc-key { display:flex; align-items:center; gap:4px; justify-content:flex-end; font-size:12px; color:var(--muted); margin-top:10px; }
        .pc-key i { width:14px; height:14px; border-radius:3px; background:var(--bs-secondary-bg); display:inline-block; }

        .pc-seg .btn { font-size:13px; }
        .pc-matrix-wrap { overflow-x:auto; }
        .pc-matrix { border-collapse:separate; border-spacing:3px; width:100%; }
        .pc-matrix th { font-weight:600; font-size:12px; color:var(--muted); text-align:center; padding:2px 4px; vertical-align:bottom; }
        .pc-matrix th.pc-rowh { text-align:left; color:var(--bs-body-color); font-size:13px; white-space:nowrap; padding-right:14px; }
        .pc-matrix th .pc-coltitle { display:block; max-width:96px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin:0 auto; }
        .pc-matrix th small { display:block; font-weight:400; font-size:11px; }
        .pc-matrix td.pc-m { height:38px; min-width:46px; border-radius:7px; text-align:center; font-size:12px; font-weight:600; background:var(--bs-secondary-bg); color:var(--bs-body-color); }
        .pc-matrix td.pc-m[data-l="1"] { background:color-mix(in srgb, var(--bs-primary) 28%, var(--bs-body-bg)); }
        .pc-matrix td.pc-m[data-l="2"] { background:color-mix(in srgb, var(--bs-primary) 50%, var(--bs-body-bg)); }
        .pc-matrix td.pc-m[data-l="3"] { background:color-mix(in srgb, var(--bs-primary) 75%, var(--bs-body-bg)); color:#fff; }
        .pc-matrix td.pc-m[data-l="4"] { background:var(--bs-primary); color:#fff; }
        .pc-matrix td.pc-total { text-align:right; font-weight:700; font-size:13px; padding-left:12px; font-variant-numeric:tabular-nums; }
        .pc-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
        .pc-fchip { border:1px solid var(--line); background:var(--bs-body-bg); color:var(--bs-body-color); border-radius:999px; padding:6px 14px; font-size:13px; font-weight:600; }
        .pc-fchip b { font-weight:700; margin-left:6px; opacity:.75; }
        .pc-fchip:hover { background:var(--bs-tertiary-bg); }
        .pc-fchip.active { background:var(--bs-primary); border-color:var(--bs-primary); color:#fff; }
        .pc-rate { display:flex; align-items:center; gap:10px; min-width:130px; }
        .pc-rate .progress { flex:1; height:6px; }
        .pc-rate span { font-size:13px; font-weight:600; width:38px; text-align:right; font-variant-numeric:tabular-nums; }
        .pc-bar { display:grid; grid-template-columns:minmax(120px, 240px) 1fr 36px; gap:14px; align-items:center; width:100%; text-align:left; background:transparent; border:0; border-radius:10px; padding:7px 10px; color:inherit; }
        .pc-bar:hover { background:var(--bs-tertiary-bg); }
        .pc-bar[aria-pressed="true"] { background:var(--bs-primary-bg-subtle); }
        .pc-bar-label { font-size:14px; font-weight:500; }
        .pc-bar-track { height:10px; background:var(--bs-secondary-bg); border-radius:999px; overflow:hidden; }
        .pc-bar-fill { display:block; height:100%; background:var(--bs-primary); border-radius:999px; min-width:0; }
        .pc-bar-count { text-align:right; font-weight:700; font-size:14px; font-variant-numeric:tabular-nums; }
        .pc-bar.pc-zero .pc-bar-label, .pc-bar.pc-zero .pc-bar-count { color:var(--muted); font-weight:400; }
        @media(max-width:575px) { .pc-bar { grid-template-columns:1fr 36px; } .pc-bar-track { grid-column:1 / -1; grid-row:2; } }
    </style>
<?php include __DIR__ . '/init.php'; ?>
<?php
require_once __DIR__ . '/participation_logic.php';
$participationMembers = json_decode($__data['kkMembers'] ?? '[]', true);
$participationMembers = is_array($participationMembers) ? $participationMembers : [];
$participationCopCategories = ['Health', 'Education', 'Economic Empowerment', 'Social Inclusion and Equity', 'Peace-Building and Security', 'Governance', 'Active Citizenship', 'Environment', 'Global Mobility', 'Agriculture'];
function participation_event_centers($event, $categories) {
    $centers = is_array($event['centers'] ?? null) ? $event['centers'] : [];
    $legacy = [
        'General' => 'Governance', 'Community' => 'Active Citizenship', 'Youth' => 'Active Citizenship',
        'Program' => 'Education', 'Meeting' => 'Governance', 'Event' => 'Active Citizenship',
        'Emergency' => 'Peace-Building and Security', 'Other' => 'Governance',
    ];
    $category = (string)($event['category'] ?? '');
    if (!$centers) $centers[] = $legacy[$category] ?? $category;
    return array_values(array_unique(array_filter($centers, function ($center) use ($categories) {
        return is_string($center) && in_array($center, $categories, true);
    })));
}
$participationRegistrations = json_decode($__data['skEventRegistrations'] ?? '[]', true);
$participationRegistrations = is_array($participationRegistrations) ? $participationRegistrations : [];
$participationAnnouncements = json_decode($__data['skAnnouncements'] ?? '[]', true);
$participationAnnouncements = is_array($participationAnnouncements) ? $participationAnnouncements : [];
$participationWalkins = json_decode($__data['skEventAttendance'] ?? '[]', true);
$participationWalkins = is_array($participationWalkins) ? $participationWalkins : [];
$participationMembersById = [];
foreach ($participationMembers as $member) {
    if (is_array($member) && isset($member['id'])) {
        $participationMembersById[(string)$member['id']] = $member;
    }
}
$participationEventsById = [];
foreach ($participationAnnouncements as $event) {
    if (is_array($event) && isset($event['id'])) {
        $participationEventsById[(string)$event['id']] = $event;
    }
}
$participationQuickEvent = null;
$participationToday = date('Y-m-d');
foreach ($participationEventsById as $quickEvent) {
    $quickDate = (string)($quickEvent['date'] ?? '');
    if ($quickDate === '' || $quickDate > $participationToday) continue;
    if ($participationQuickEvent === null || $quickDate > (string)$participationQuickEvent['date']) $participationQuickEvent = $quickEvent;
}
$participationRows = [];
$participationHeatmapRows = [];
$participationEventOptions = [];
$participationLinkedMembers = [];
$participationAttendedSignupMemberEvents = [];
$participationWalkinMemberEvents = [];
$participationAttendedCount = 0;
$participationActiveMembers = 0;
foreach ($participationMembers as $member) {
    if (is_array($member) && ($member['status'] ?? '') === 'Active') $participationActiveMembers++;
}
$participationRoster = [];
foreach ($participationMembers as $member) {
    if (!is_array($member) || ($member['status'] ?? '') !== 'Active') continue;
    $participationRoster[] = [
        'id' => (string)($member['id'] ?? ''),
        'memberNo' => (string)($member['memberNo'] ?? $member['id'] ?? ''),
        'name' => trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '',
            $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return trim((string)$part) !== ''; }))),
        'purok' => trim((string)($member['purok'] ?? '')),
    ];
}
foreach ($participationRegistrations as $signup) {
    if (!is_array($signup)) continue;
    $eventId = (string)($signup['eventId'] ?? '');
    $event = $participationEventsById[$eventId] ?? [];
    $eventTitle = trim((string)($signup['eventTitle'] ?? $event['title'] ?? 'Unknown event'));
    if ($eventTitle === '') $eventTitle = 'Unknown event';
    $eventDate = (string)($event['date'] ?? '');
    $memberId = isset($signup['memberId']) ? (string)$signup['memberId'] : '';
    $member = $memberId !== '' ? ($participationMembersById[$memberId] ?? null) : null;
    $name = $member
        ? trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '',
            $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return trim((string)$part) !== ''; })))
        : trim((string)($signup['name'] ?? ''));
    if ($name === '') $name = 'Unnamed participant';
    $status = in_array($signup['attendanceStatus'] ?? '', ['Attended', 'Did not attend'], true)
        ? $signup['attendanceStatus']
        : 'Not recorded';
    if ($status === 'Attended') {
        $participationAttendedCount++;
        if ($member) {
            $participationLinkedMembers[$memberId] = true;
            $participationAttendedSignupMemberEvents[$eventId . ':' . $memberId] = true;
        }
    }
    $age = participation_age_at_event($member, $eventDate, $signup['age'] ?? null);
    $ageGroup = participation_age_group($age);
    $memberPurok = $member ? trim((string)($member['purok'] ?? '')) : '';
    $purok = $memberPurok !== '' ? $memberPurok : trim((string)($signup['purok'] ?? ''));
    if ($purok === '') $purok = 'Not recorded';
    $participationRows[] = [
        'memberId' => $member ? (string)($member['memberNo'] ?? $memberId) : 'Not linked',
        'memberName' => $name,
        'memberStatus' => $member['status'] ?? 'Unlinked signup',
        'eventId' => $eventId,
        'eventAvailable' => isset($participationEventsById[$eventId]),
        'eventTitle' => $eventTitle,
        'eventDate' => $eventDate,
        'submittedAt' => substr((string)($signup['submittedAt'] ?? ''), 0, 10),
        'attendanceStatus' => $status,
        'age' => $age,
        'ageGroup' => $ageGroup,
        'purok' => $purok,
    ];
    $participationHeatmapRows[] = [
        'memberKey' => $member ? $memberId : 'signup:' . (string)($signup['id'] ?? $signup['name'] ?? 'unlinked'),
        'memberId' => $member ? $memberId : '',
        'eventTitle' => $eventTitle,
        'eventId' => $eventId,
        'eventDate' => $eventDate,
        'centers' => participation_event_centers($event, $participationCopCategories),
        'attendanceStatus' => $status,
        'ageGroup' => $ageGroup,
        'purok' => $purok,
    ];
    if ($eventId !== '') $participationEventOptions[$eventId] = $eventTitle;
}
foreach ($participationWalkins as $walkin) {
    if (!is_array($walkin)) continue;
    $eventId = (string)($walkin['eventId'] ?? '');
    $memberId = (string)($walkin['memberId'] ?? '');
    if (!participation_walkin_should_count(
        $eventId,
        $memberId,
        $participationAttendedSignupMemberEvents,
        $participationWalkinMemberEvents
    )) continue;
    if ($memberId !== '') $participationWalkinMemberEvents[$eventId . ':' . $memberId] = true;
    $member = $memberId !== '' ? ($participationMembersById[$memberId] ?? null) : null;
    $event = $participationEventsById[$eventId] ?? [];
    $eventTitle = trim((string)($walkin['eventTitle'] ?? $event['title'] ?? 'Unknown event'));
    if ($eventTitle === '') $eventTitle = 'Unknown event';
    $name = $member
        ? trim(implode(' ', array_filter([
            $member['firstName'] ?? '', $member['middleName'] ?? '',
            $member['lastName'] ?? '', $member['suffix'] ?? '',
        ], function ($part) { return trim((string)$part) !== ''; })))
        : 'KK member';
    if ($member) $participationLinkedMembers[$memberId] = true;
    $eventDate = (string)($event['date'] ?? '');
    $age = participation_age_at_event($member, $eventDate);
    $ageGroup = participation_age_group($age);
    $purok = trim((string)($member['purok'] ?? ''));
    if ($purok === '') $purok = 'Not recorded';
    $participationAttendedCount++;
    $participationRows[] = [
        'memberId' => $member ? (string)($member['memberNo'] ?? $memberId) : 'Unknown member',
        'memberName' => $name !== '' ? $name : 'Unnamed member',
        'memberStatus' => $member['status'] ?? 'Member record unavailable',
        'eventId' => $eventId,
        'eventAvailable' => isset($participationEventsById[$eventId]),
        'eventTitle' => $eventTitle,
        'eventDate' => $eventDate,
        'submittedAt' => 'Walk-in',
        'attendanceStatus' => 'Attended',
        'age' => $age,
        'ageGroup' => $ageGroup,
        'purok' => $purok,
    ];
    $participationHeatmapRows[] = [
        'memberKey' => $member ? $memberId : 'walkin:' . (string)($walkin['id'] ?? $walkin['name'] ?? 'unlinked'),
        'memberId' => $member ? $memberId : '',
        'eventTitle' => $eventTitle,
        'eventId' => $eventId,
        'eventDate' => $eventDate,
        'centers' => participation_event_centers($event, $participationCopCategories),
        'attendanceStatus' => 'Attended',
        'ageGroup' => $ageGroup,
        'purok' => $purok,
    ];
    if ($eventId !== '') $participationEventOptions[$eventId] = $eventTitle;
}
usort($participationRows, function ($a, $b) {
    return strcmp($b['eventDate'], $a['eventDate'])
        ?: strcmp($a['eventTitle'], $b['eventTitle'])
        ?: strcmp($a['memberName'], $b['memberName']);
});
asort($participationEventOptions, SORT_NATURAL | SORT_FLAG_CASE);
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
        <div class="header-title">Participation Tracking</div>
        <small>KK Youth Profiling System</small>
    </div></div>

    <div class="header-account-area">

        <a href="announcements.php" class="header-notification" aria-label="Notifications">
            <i class="bi bi-bell"></i>
        </a>

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

    <div class="row g-3 mb-3">

        <div class="col-6 col-lg-3">
            <div class="stat">
                <i class="bi bi-people"></i>
                <div class="number"><?= $participationActiveMembers ?></div>
                <div class="label">Active KK members</div>
                <div class="sub">&nbsp;</div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <i class="bi bi-percent"></i>
                <div class="number" id="participationRate">0%</div>
                <div class="label">Participation rate</div>
                <div class="progress" role="progressbar" aria-label="Participation rate"><div class="progress-bar" id="participationRateBar" style="width:0%"></div></div>
                <div class="sub mt-2" id="participationRateNote"></div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <i class="bi bi-trophy"></i>
                <div class="number" id="confirmedAttendanceCount"><?= $participationAttendedCount ?></div>
                <div class="label">Total attendances</div>
                <div class="sub" id="attendanceNote"></div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat">
                <i class="bi bi-calendar-event"></i>
                <div class="number" id="programCount"><?= count($participationEventOptions) ?></div>
                <div class="label">Activities with records</div>
                <div class="sub">&nbsp;</div>
            </div>
        </div>

    </div>

    <div class="card-box">
        <div class="filter-bar">
            <div class="grow">
                <label class="form-label" for="participationActivityFilter">Activity</label>
                <select class="form-select" id="participationActivityFilter">
                    <option value="">All activities</option>
                    <?php foreach ($participationEventOptions as $eventId => $eventTitle): ?>
                        <option value="<?= e($eventId) ?>"><?= e($eventTitle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fixed">
                <label class="form-label" for="participationDateFrom">From</label>
                <input type="date" class="form-control" id="participationDateFrom">
            </div>
            <div class="fixed">
                <label class="form-label" for="participationDateTo">To</label>
                <input type="date" class="form-control" id="participationDateTo">
            </div>
            <button class="btn btn-outline-primary" type="button" id="clearParticipationFilters">
                <i class="bi bi-x-circle me-1"></i>Clear
            </button>
            <?php if (!is_public()): ?>
            <a class="btn btn-primary ms-lg-auto" style="height:40px;display:inline-flex;align-items:center"
               href="<?= $participationQuickEvent ? 'announcements.php?eventId=' . rawurlencode((string)$participationQuickEvent['id']) . '&amp;showRoster=1' : 'announcements.php' ?>"
               title="<?= e($participationQuickEvent ? 'Latest activity: ' . ($participationQuickEvent['title'] ?? '') : 'Open announcements and events') ?>">
                <i class="bi bi-clipboard-check me-2"></i>Record attendance
            </a>
            <?php endif; ?>
        </div>
        <div id="participationFilterError" class="small text-danger px-3 pb-2" role="alert" aria-live="polite"></div>
    </div>

    <?php if (!is_public()): ?>
    <div class="card-box">
        <div class="card-header pc-head">
            <span><i class="bi bi-person-lines-fill me-2"></i>Member engagement</span>
            <label class="visually-hidden" for="engageSearch">Search members</label>
            <input type="search" class="form-control" id="engageSearch" placeholder="Search name, ID or Purok" style="max-width:260px">
        </div>
        <div class="card-body">
            <div class="pc-chips" id="engageChips" role="group" aria-label="Filter members by engagement"></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Member</th><th>Purok</th><th>Attended</th><th>Rate</th><th>Last attended</th><th>Status</th></tr></thead>
                    <tbody id="engageBody"></tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <span class="small text-muted" id="engageNote"></span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="engageMore" hidden></button>
            </div>
            <p class="small text-muted mt-2 mb-0">Regular = attended 50% or more of the activities. Occasional = attended some. Not yet attended = none. Based on the activity and date filters above.</p>
        </div>
    </div>
    <script type="application/json" id="participationRosterData"><?= json_encode($participationRoster, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
    <?php endif; ?>

    <div class="card-box">
        <div class="card-header pc-head">
            <span><i class="bi bi-grid-3x3-gap me-2"></i>Participation heatmap</span>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group pc-seg" role="group" aria-label="Rows">
                    <button type="button" class="btn btn-outline-primary active" data-view="cop">Center of Participation</button>
                    <button type="button" class="btn btn-outline-primary" data-view="age">Age group</button>
                    <button type="button" class="btn btn-outline-primary" data-view="purok">Purok</button>
                </div>
                <div class="btn-group pc-seg" role="group" aria-label="Columns">
                    <button type="button" class="btn btn-outline-secondary active" data-col="month">By month</button>
                    <button type="button" class="btn btn-outline-secondary" data-col="event">By activity</button>
                </div>
                <label class="visually-hidden" for="heatmapYearFilter">Year</label>
                <select class="form-select" id="heatmapYearFilter" style="width:auto;min-width:86px"></select>
            </div>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-3" id="heatmapSummary" aria-live="polite"></div>
            <div class="pc-matrix-wrap"><div id="heatmapMatrix"></div></div>
            <div class="pc-key" aria-label="Attendance intensity legend"><span>Less</span><i></i><i data-l="1"></i><i data-l="2"></i><i data-l="3"></i><i data-l="4"></i><span>More</span></div>
        </div>
    </div>
    <script type="application/json" id="participationHeatmapData"><?= json_encode($participationHeatmapRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>

    <div class="card-box">

        <div class="card-header">
            <i class="bi bi-person-check me-2"></i>
            Youth Participation Records
        </div>

        <div class="card-body">

            <div class="row g-2 mb-4">

                <div class="col-md-5">
                    <input type="text"
                           id="search"
                           class="form-control"
                           placeholder="Search member, age, Purok or event...">
                </div>

                <div class="col-md-4">
                    <label class="visually-hidden" for="attendanceStatusFilter">Attendance status</label>
                    <select class="form-select" id="attendanceStatusFilter">
                        <option value="">All attendance statuses</option>
                        <option value="Attended">Attended</option>
                        <option value="Did not attend">Did not attend</option>
                        <option value="Not recorded">Not recorded</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center justify-content-between gap-2">
                    <span class="small text-muted" id="participationRecordCount" aria-live="polite"></span>
                    <?php if (!is_public()): ?>
                    <button class="btn btn-sm btn-outline-primary text-nowrap" type="button" id="exportCsv">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </button>
                    <?php endif; ?>
                </div>

            </div>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Member ID</th>
                            <th>Full Name</th>
                            <th>Program / Event</th>
                            <th>Registration / record</th>
                            <th>Age</th>
                            <th>Purok</th>
                            <th>Attendance</th>
                            <th>Member Status</th>
                            <?php if (!is_public()): ?><th>Action</th><?php endif; ?>
                        </tr>

                    </thead>

                    <tbody id="participationTable">
                        <?php foreach ($participationRows as $row): ?>
                            <tr data-event-id="<?= e($row['eventId']) ?>" data-event-date="<?= e($row['eventDate']) ?>" data-attendance-status="<?= e($row['attendanceStatus']) ?>">
                                <td><?= e($row['memberId']) ?></td>
                                <td><strong><?= e($row['memberName']) ?></strong></td>
                                <td><?= e($row['eventTitle']) ?><?php if ($row['eventDate'] !== ''): ?><div class="small text-muted"><?= e($row['eventDate']) ?></div><?php endif; ?></td>
                                <td><?= e($row['submittedAt'] ?: '—') ?></td>
                                <td><?= $row['age'] === null ? '—' : e($row['age']) ?></td>
                                <td><?= e($row['purok']) ?></td>
                                <td>
                                    <?php
                                    $attendanceBadge = $row['attendanceStatus'] === 'Attended'
                                        ? 'bg-success-subtle text-success-emphasis'
                                        : ($row['attendanceStatus'] === 'Did not attend' ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-warning-subtle text-warning-emphasis');
                                    ?>
                                    <span class="badge <?= $attendanceBadge ?>"><?= e($row['attendanceStatus']) ?></span>
                                </td>
                                <td><?= e($row['memberStatus']) ?></td>
                                <?php if (!is_public()): ?>
                                    <td>
                                        <?php if ($row['eventId'] !== '' && $row['eventAvailable']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="announcements.php?eventId=<?= rawurlencode($row['eventId']) ?>&amp;showRoster=1" aria-label="<?= e('Manage attendance for ' . $row['eventTitle']) ?>">
                                                <i class="bi bi-clipboard-check me-1"></i>Manage attendance
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="participationNoRows" class="<?= $participationRows ? 'd-none' : '' ?>">
                            <td colspan="<?= is_public() ? 8 : 9 ?>" class="text-center text-muted py-4">No participation records match these filters.</td>
                        </tr>
                    </tbody>

                </table>

            </div>
            <p class="small text-muted px-3 pb-3 mb-0">
                Counts attended records only. Walk-ins are included and nobody is counted twice for the same activity.
            </p>
        </div>

    </div>

    <div class="footer-note">
        KK Youth Profiling System • SK Barangay Sikat, Alfonso, Cavite • FY 2026–2027
    </div>

</div>

</main>


<script>

const copCategories = <?= json_encode($participationCopCategories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const copRows = JSON.parse(document.getElementById('participationHeatmapData').textContent || '[]');
const activeMembers = <?= (int)$participationActiveMembers ?>;
const ageGroups = ['15-17', '18-20', '21-24', '25-30', 'Outside ages 15-30', 'Unknown'];
const defaultHeatmapYear = <?= json_encode(date('Y')) ?>;
const activityFilter = document.getElementById('participationActivityFilter');
const dateFromFilter = document.getElementById('participationDateFrom');
const dateToFilter = document.getElementById('participationDateTo');
const heatmapYearFilter = document.getElementById('heatmapYearFilter');
const filterError = document.getElementById('participationFilterError');
let view = 'cop';
let colMode = 'month';

function isValidIsoDate(value) {
    const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return false;
    const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
    return date.getUTCFullYear() === Number(match[1]) &&
        date.getUTCMonth() === Number(match[2]) - 1 &&
        date.getUTCDate() === Number(match[3]);
}

function normalizePurok(value) {
    return String(value || '').trim().replace(/\s+/g, ' ') || 'Not recorded';
}

function escapeCopHtml(value) {
    return String(value).replace(/[&<>"']/g, function (character) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
    });
}

function getFilteredRows() {
    const from = dateFromFilter.value;
    const to = dateToFilter.value;
    if (from && to && from > to) {
        filterError.textContent = 'The start date must be on or before the end date.';
        return [];
    }
    filterError.textContent = '';
    const eventId = activityFilter.value;
    return copRows.filter(function (row) {
        if (eventId && String(row.eventId || '') !== eventId) return false;
        if (from || to) {
            if (!isValidIsoDate(row.eventDate)) return false;
            if ((from && row.eventDate < from) || (to && row.eventDate > to)) return false;
        }
        return true;
    });
}

function monthName(m) { return ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][m]; }

function rowGroups(row) {
    if (view === 'cop') return Array.isArray(row.centers) ? row.centers : [];
    if (view === 'age') return [ageGroups.indexOf(row.ageGroup) === -1 ? 'Unknown' : row.ageGroup];
    return [normalizePurok(row.purok)];
}
function groupKey(label) { return view === 'purok' ? label.toLocaleLowerCase() : label; }

function inYear(row, year) {
    return isValidIsoDate(row.eventDate) && Number(row.eventDate.slice(0, 4)) === year;
}

function renderMatrix(rows) {
    const year = Number(heatmapYearFilter.value);
    const attended = rows.filter(function (row) { return row.attendanceStatus === 'Attended' && inYear(row, year); });

    // columns
    let columns = [];
    if (colMode === 'month') {
        for (let m = 0; m < 12; m++) columns.push({ key: String(m), title: monthName(m), sub: '' });
    } else {
        const seen = new Map();
        attended.forEach(function (row) {
            const key = String(row.eventId || row.eventTitle || '');
            if (key && !seen.has(key)) seen.set(key, { key: key, title: row.eventTitle || 'Activity', date: row.eventDate });
        });
        columns = Array.from(seen.values()).sort(function (a, b) { return a.date < b.date ? -1 : a.date > b.date ? 1 : 0; })
            .map(function (c) { return { key: c.key, title: c.title, sub: formatDate(c.date).replace(/, \d{4}$/, '') }; });
    }
    const colKey = function (row) {
        return colMode === 'month' ? String(Number(row.eventDate.slice(5, 7)) - 1) : String(row.eventId || row.eventTitle || '');
    };

    // rows
    const labels = new Map();
    const data = new Map();
    const ensure = function (key, label) {
        if (!data.has(key)) { data.set(key, { label: label, cells: new Map(), total: 0 }); }
    };
    if (view === 'cop') copCategories.forEach(function (c) { ensure(c, c); });
    if (view === 'age') ageGroups.slice(0, 4).forEach(function (g) { ensure(g, g); });
    attended.forEach(function (row) {
        rowGroups(row).forEach(function (label) {
            const key = groupKey(label);
            ensure(key, label);
            const entry = data.get(key);
            const ck = colKey(row);
            entry.cells.set(ck, (entry.cells.get(ck) || 0) + 1);
            entry.total++;
        });
    });
    let list = Array.from(data.values());
    if (view !== 'age') {
        list.sort(function (a, b) {
            if (a.label === 'Not recorded') return 1;
            if (b.label === 'Not recorded') return -1;
            return b.total - a.total || a.label.localeCompare(b.label, undefined, { numeric: true });
        });
    }
    let max = 0;
    list.forEach(function (r) { r.cells.forEach(function (v) { max = Math.max(max, v); }); });
    const box = document.getElementById('heatmapMatrix');
    const grouping = { cop: 'Center of Participation', age: 'age group', purok: 'Purok' }[view];
    const total = list.reduce(function (n, r) { return n + r.total; }, 0);
    document.getElementById('heatmapSummary').textContent = total
        ? 'Attendance records in ' + year + ' by ' + grouping + ' and ' + (colMode === 'month' ? 'month' : 'activity') + '. Darker means more attendance.'
        : 'No attendance recorded in ' + year + ' with the current filters.';
    if (!columns.length) { box.innerHTML = '<div class="text-muted py-3">No activities with attendance in ' + year + '.</div>'; return; }

    let html = '<table class="pc-matrix"><thead><tr><th></th>' + columns.map(function (c) {
        return '<th scope="col"><span class="pc-coltitle" title="' + escapeCopHtml(c.title) + '">' + escapeCopHtml(c.title) + '</span>' +
            (c.sub ? '<small>' + escapeCopHtml(c.sub) + '</small>' : '') + '</th>';
    }).join('') + '<th scope="col" style="text-align:right">Total</th></tr></thead><tbody>';
    list.forEach(function (r) {
        html += '<tr><th scope="row" class="pc-rowh">' + escapeCopHtml(r.label) + '</th>';
        columns.forEach(function (c) {
            const n = r.cells.get(c.key) || 0;
            const level = n ? Math.max(1, Math.ceil(n / max * 4)) : 0;
            const tip = r.label + ' · ' + c.title + ': ' + n + ' attendance' + (n === 1 ? '' : 's');
            html += '<td class="pc-m"' + (level ? ' data-l="' + level + '"' : '') + ' title="' + escapeCopHtml(tip) + '">' + (n || '') + '</td>';
        });
        html += '<td class="pc-total">' + r.total + '</td></tr>';
    });
    box.innerHTML = html + '</tbody></table>';
}

function initializeFilters() {
    const years = new Set([defaultHeatmapYear]);
    copRows.forEach(function (row) {
        if (isValidIsoDate(row.eventDate)) years.add(row.eventDate.slice(0, 4));
    });
    heatmapYearFilter.innerHTML = Array.from(years).sort(function (a, b) {
        return Number(b) - Number(a);
    }).map(function (year) {
        return '<option value="' + escapeCopHtml(year) + '">' + escapeCopHtml(year) + '</option>';
    }).join('');
    heatmapYearFilter.value = defaultHeatmapYear;
}


const rosterEl = document.getElementById('participationRosterData');
const roster = rosterEl ? JSON.parse(rosterEl.textContent || '[]') : [];
let engageFilter = 'all';
let engageShowAll = false;
const ENGAGE_LIMIT = 8;
const ENGAGE_LABELS = { all: 'All', regular: 'Regular', occasional: 'Occasional', none: 'Not yet attended' };

function formatDate(iso) {
    if (!isValidIsoDate(iso)) return '—';
    return monthName(Number(iso.slice(5, 7)) - 1) + ' ' + Number(iso.slice(8, 10)) + ', ' + iso.slice(0, 4);
}

function renderEngagement(rows) {
    const body = document.getElementById('engageBody');
    if (!body) return;
    const attended = rows.filter(function (row) { return row.attendanceStatus === 'Attended'; });
    const held = new Set(rows.map(function (row) { return String(row.eventId || row.eventTitle || ''); }).filter(Boolean)).size;
    const per = new Map();
    attended.forEach(function (row) {
        if (!row.memberId) return;
        const key = String(row.memberId);
        let entry = per.get(key);
        if (!entry) { entry = { events: new Set(), last: '' }; per.set(key, entry); }
        entry.events.add(String(row.eventId || row.eventTitle || ''));
        if (isValidIsoDate(row.eventDate) && row.eventDate > entry.last) entry.last = row.eventDate;
    });
    const list = roster.map(function (m) {
        const entry = per.get(m.id);
        const n = entry ? entry.events.size : 0;
        const rate = held ? Math.min(100, Math.round(n / held * 100)) : 0;
        return { id: m.id, memberNo: m.memberNo, name: m.name || 'Unnamed member', purok: m.purok || 'Not recorded',
                 n: n, rate: rate, last: entry ? entry.last : '', status: n === 0 ? 'none' : (rate >= 50 ? 'regular' : 'occasional') };
    });
    const counts = { all: list.length, regular: 0, occasional: 0, none: 0 };
    list.forEach(function (m) { counts[m.status]++; });
    document.getElementById('engageChips').innerHTML = Object.keys(ENGAGE_LABELS).map(function (key) {
        return '<button type="button" class="pc-fchip' + (engageFilter === key ? ' active' : '') + '" data-f="' + key +
            '" aria-pressed="' + (engageFilter === key) + '">' + ENGAGE_LABELS[key] + '<b>' + counts[key] + '</b></button>';
    }).join('');

    const q = document.getElementById('engageSearch').value.trim().toLocaleLowerCase();
    let shown = list.filter(function (m) {
        if (engageFilter !== 'all' && m.status !== engageFilter) return false;
        return !q || (m.name + ' ' + m.memberNo + ' ' + m.purok).toLocaleLowerCase().indexOf(q) !== -1;
    });
    shown.sort(function (a, b) {
        if (engageFilter === 'none') return a.name.localeCompare(b.name);
        return b.rate - a.rate || b.n - a.n || a.name.localeCompare(b.name);
    });
    const total = shown.length;
    if (!engageShowAll) shown = shown.slice(0, ENGAGE_LIMIT);
    const badge = {
        regular: '<span class="badge bg-success-subtle text-success-emphasis">Regular</span>',
        occasional: '<span class="badge bg-warning-subtle text-warning-emphasis">Occasional</span>',
        none: '<span class="badge bg-danger-subtle text-danger-emphasis">Not yet attended</span>'
    };
    body.innerHTML = shown.length ? shown.map(function (m) {
        return '<tr><td><strong>' + escapeCopHtml(m.name) + '</strong><div class="small text-muted">' + escapeCopHtml(m.memberNo) + '</div></td>' +
            '<td>' + escapeCopHtml(m.purok) + '</td>' +
            '<td>' + m.n + ' of ' + held + '</td>' +
            '<td><div class="pc-rate"><div class="progress" role="progressbar" aria-label="Attendance rate"><div class="progress-bar" style="width:' + m.rate + '%"></div></div><span>' + m.rate + '%</span></div></td>' +
            '<td>' + formatDate(m.last) + '</td><td>' + badge[m.status] + '</td></tr>';
    }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">' +
        (held ? 'No members match this view.' : 'No attendance recorded yet for these filters.') + '</td></tr>';
    document.getElementById('engageNote').textContent = total ? 'Showing ' + shown.length + ' of ' + total + ' members' : '';
    const more = document.getElementById('engageMore');
    more.hidden = total <= ENGAGE_LIMIT;
    more.textContent = engageShowAll ? 'Show fewer' : 'Show all ' + total;
}

function renderParticipation() {
    const rows = getFilteredRows();
    const attendees = rows.filter(function (row) { return row.attendanceStatus === 'Attended'; });
    const linked = new Set(attendees.filter(function (row) { return !!row.memberId; })
        .map(function (row) { return String(row.memberId); }));
    const unlinked = attendees.filter(function (row) { return !row.memberId; }).length;
    const activities = new Set(rows.map(function (row) {
        return String(row.eventId || row.eventTitle || '');
    }).filter(Boolean));

    const rate = activeMembers ? Math.min(100, Math.round(linked.size / activeMembers * 100)) : 0;
    document.getElementById('participationRate').textContent = rate + '%';
    document.getElementById('participationRateBar').style.width = rate + '%';
    document.getElementById('participationRateNote').textContent =
        linked.size + ' of ' + activeMembers + ' members attended';
    document.getElementById('confirmedAttendanceCount').textContent = String(attendees.length);
    document.getElementById('attendanceNote').textContent =
        unlinked ? unlinked + ' from unlinked sign-ups' : '';
    document.getElementById('programCount').textContent = String(activities.size);

    renderMatrix(rows);
    renderEngagement(rows);

    const search = document.getElementById('search').value.trim().toLocaleLowerCase();
    const status = document.getElementById('attendanceStatusFilter').value;
    const tableRows = Array.prototype.slice.call(document.querySelectorAll('#participationTable tr[data-event-id]'));
    let visibleCount = 0, eligibleCount = 0;
    tableRows.forEach(function (row) {
        const eventDate = row.dataset.eventDate || '';
        const validRange = !(dateFromFilter.value && dateToFilter.value && dateFromFilter.value > dateToFilter.value);
        const matchesDate = validRange && (!(dateFromFilter.value || dateToFilter.value) ||
            (isValidIsoDate(eventDate) &&
                (!dateFromFilter.value || eventDate >= dateFromFilter.value) &&
                (!dateToFilter.value || eventDate <= dateToFilter.value)));
        const matchesActivity = !activityFilter.value || row.dataset.eventId === activityFilter.value;
        const matchesGlobalFilters = matchesDate && matchesActivity;
        const matchesSearch = !search || row.textContent.toLocaleLowerCase().includes(search);
        const matchesStatus = !status || row.dataset.attendanceStatus === status;
        const visible = matchesGlobalFilters && matchesSearch && matchesStatus;
        if (matchesGlobalFilters) eligibleCount++;
        row.classList.toggle('d-none', !visible);
        if (visible) visibleCount++;
    });
    document.getElementById('participationNoRows').classList.toggle('d-none', visibleCount > 0);
    document.getElementById('participationRecordCount').textContent =
        visibleCount + ' of ' + eligibleCount + ' shown';
}

function csvCell(value) {
    return '"' + String(value).replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"';
}

function exportCsv() {
    const table = document.querySelector('.table');
    const headers = Array.prototype.slice.call(table.querySelectorAll('thead th'))
        .filter(function (th) { return th.textContent.trim() !== 'Action'; });
    const lines = [headers.map(function (th) { return csvCell(th.textContent); }).join(',')];
    document.querySelectorAll('#participationTable tr[data-event-id]:not(.d-none)').forEach(function (tr) {
        const cells = Array.prototype.slice.call(tr.children).slice(0, headers.length);
        lines.push(cells.map(function (td) { return csvCell(td.textContent); }).join(','));
    });
    const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'participation-records-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);
}

['participationActivityFilter', 'participationDateFrom', 'participationDateTo', 'search', 'attendanceStatusFilter']
    .forEach(function (id) {
        document.getElementById(id).addEventListener('input', renderParticipation);
        document.getElementById(id).addEventListener('change', renderParticipation);
    });
heatmapYearFilter.addEventListener('change', renderParticipation);
document.querySelectorAll('.pc-seg [data-view]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        view = btn.dataset.view;
        document.querySelectorAll('.pc-seg [data-view]').forEach(function (b) { b.classList.toggle('active', b === btn); });
        renderParticipation();
    });
});
document.querySelectorAll('.pc-seg [data-col]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        colMode = btn.dataset.col;
        document.querySelectorAll('.pc-seg [data-col]').forEach(function (b) { b.classList.toggle('active', b === btn); });
        renderParticipation();
    });
});
document.getElementById('clearParticipationFilters').addEventListener('click', function () {
    activityFilter.value = '';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    heatmapYearFilter.value = defaultHeatmapYear;
    document.getElementById('search').value = '';
    document.getElementById('attendanceStatusFilter').value = '';
    renderParticipation();
});
if (document.getElementById('engageChips')) {
    document.getElementById('engageChips').addEventListener('click', function (event) {
        const chip = event.target.closest('.pc-fchip');
        if (!chip) return;
        engageFilter = chip.dataset.f;
        engageShowAll = false;
        renderParticipation();
    });
    document.getElementById('engageSearch').addEventListener('input', function () { engageShowAll = false; renderParticipation(); });
    document.getElementById('engageMore').addEventListener('click', function () { engageShowAll = !engageShowAll; renderParticipation(); });
}
const exportButton = document.getElementById('exportCsv');
if (exportButton) exportButton.addEventListener('click', exportCsv);
initializeFilters();
renderParticipation();

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>