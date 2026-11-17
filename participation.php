<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Participation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="sidebar.css?v=31" rel="stylesheet">

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

        .footer-note {
            text-align:center;
            color:var(--muted);
            font-size:13px;
            padding:25px;
        }

        @media(max-width:991px) {
            .content { padding:18px; }
        }
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

<script src="theme.js"></script>
<script src="sidebar.js?v=15"></script>

<main class="main page-content">

<header class="header">

    <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
        <div class="header-title">Participation</div>
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

    <section class="hero hero-primary">

        <h1>Youth Participation</h1>

        <p>
            Monitor youth involvement and participation in SK activities and programs.
        </p>

    </section>

    <div class="row g-3">

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-people"></i>
                <div class="number"><?= $participationActiveMembers ?></div>
                <div class="label">Active KK members</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-person-check"></i>
                <div class="number" id="uniqueAttendeeCount"><?= count($participationLinkedMembers) ?></div>
                <div class="label">Unique KK attendees</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-trophy"></i>
                <div class="number" id="confirmedAttendanceCount"><?= $participationAttendedCount ?></div>
                <div class="label">Confirmed attendances</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-calendar-event"></i>
                <div class="number" id="programCount"><?= count($participationEventOptions) ?></div>
                <div class="label">Activities with records</div>
            </div>
        </div>

    </div>

    <div class="card-box">
        <div class="card-header"><i class="bi bi-funnel me-2"></i>Participation filters</div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="participationActivityFilter">Activity</label>
                    <select class="form-select" id="participationActivityFilter">
                        <option value="">All activities</option>
                        <?php foreach ($participationEventOptions as $eventId => $eventTitle): ?>
                            <option value="<?= e($eventId) ?>"><?= e($eventTitle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="participationDateFrom">From</label>
                    <input type="date" class="form-control" id="participationDateFrom">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="participationDateTo">To</label>
                    <input type="date" class="form-control" id="participationDateTo">
                </div>
                <div class="col-lg-2 col-md-6">
                    <button class="btn btn-outline-primary w-100" type="button" id="clearParticipationFilters">
                        <i class="bi bi-x-circle me-1"></i>Clear filters
                    </button>
                </div>
            </div>
            <div id="participationFilterError" class="small text-danger mt-2" role="alert" aria-live="polite"></div>
            <p class="small text-muted mb-0 mt-2">Activity and date filters apply to attendance totals, the heatmap, age and Purok tables, and records. Active KK members is the current roster count; search and attendance status only filter the records list.</p>
        </div>
    </div>
    <p class="small text-muted mb-3">Unique KK attendees counts linked member profiles; confirmed attendance also includes unlinked sign-ups.</p>

    <div class="card-box">
        <div class="card-header"><i class="bi bi-grid-3x3 me-2"></i>COP Participation Heatmap</div>
        <div class="card-body">
            <p class="small text-muted">Counts confirmed attendee-event participations by Center of Participation and month.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center mb-0">
                    <thead id="copHeatmapHead"></thead>
                    <tbody id="copHeatmapBody"></tbody>
                </table>
            </div>
        </div>
    </div>
    <script type="application/json" id="participationHeatmapData"><?= json_encode($participationHeatmapRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>

    <div class="row g-3">
        <div class="col-12">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-bar-chart-steps me-2"></i>Participation by age group</div>
                <div class="card-body">
                    <p class="small text-muted">Confirmed attendance by activity. Member age is calculated on the event date when a birth date is available.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead id="ageBreakdownHead"></thead>
                            <tbody id="ageBreakdownBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Participation by Purok</div>
                <div class="card-body">
                    <p class="small text-muted">Confirmed attendance by activity and Purok. Linked profiles are preferred; the sign-up value is used when the profile is unavailable or has no Purok.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead id="purokBreakdownHead"></thead>
                            <tbody id="purokBreakdownBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                <div class="col-md-3 small text-muted align-self-center" id="participationRecordCount" aria-live="polite">
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
                                        ? 'text-bg-success'
                                        : ($row['attendanceStatus'] === 'Did not attend' ? 'text-bg-secondary' : 'text-bg-warning');
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
                Event sign-ups and confirmed attendance are shown separately. Walk-ins are included, and a member with an attended sign-up is not counted twice. Search and attendance status filter this table; activity and date filters apply to the whole page.
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
const ageGroups = ['15-17', '18-20', '21-24', '25-30', 'Outside ages 15-30', 'Unknown'];
const activityFilter = document.getElementById('participationActivityFilter');
const dateFromFilter = document.getElementById('participationDateFrom');
const dateToFilter = document.getElementById('participationDateTo');
const filterError = document.getElementById('participationFilterError');

function isValidIsoDate(value) {
    const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return false;
    const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
    return date.getUTCFullYear() === Number(match[1]) &&
        date.getUTCMonth() === Number(match[2]) - 1 &&
        date.getUTCDate() === Number(match[3]);
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

function renderCopHeatmap(rows) {
    const cells = {}, months = {};
    rows.forEach(function (row) {
        if (row.attendanceStatus !== 'Attended') return;
        const month = isValidIsoDate(row.eventDate) ? row.eventDate.slice(0, 7) : 'Undated';
        months[month] = true;
        (Array.isArray(row.centers) ? row.centers : []).forEach(function (center) {
            if (copCategories.indexOf(center) === -1) return;
            cells[center] = cells[center] || {};
            cells[center][month] = cells[center][month] || {};
            cells[center][month][String(row.memberKey) + ':' + String(row.eventId)] = true;
        });
    });
    const monthList = Object.keys(months).sort();
    const head = document.getElementById('copHeatmapHead');
    const body = document.getElementById('copHeatmapBody');
    if (!monthList.length) {
        head.innerHTML = '';
        body.innerHTML = '<tr><td class="text-muted py-4">No confirmed attendance matches these filters.</td></tr>';
        return;
    }
    let max = 0;
    copCategories.forEach(function (center) {
        monthList.forEach(function (month) {
            const count = Object.keys((cells[center] || {})[month] || {}).length;
            max = Math.max(max, count);
        });
    });
    head.innerHTML = '<tr><th>Center of Participation</th>' + monthList.map(function (month) {
        return '<th>' + escapeCopHtml(month.replace('-', ' / ')) + '</th>';
    }).join('') + '</tr>';
    body.innerHTML = copCategories.map(function (center) {
        return '<tr><th class="text-start">' + escapeCopHtml(center) + '</th>' + monthList.map(function (month) {
            const count = Object.keys((cells[center] || {})[month] || {}).length;
            const alpha = count && max ? Math.min(0.8, 0.12 + 0.68 * count / max) : 0;
            return '<td style="' + (count ? 'background-color:rgba(13,110,253,' + alpha + ');color:' + (alpha > 0.55 ? '#fff' : 'inherit') : '') +
                '" aria-label="' + escapeCopHtml(center + ', ' + month + ': ' + count + ' attendances') + '">' + (count || '—') + '</td>';
        }).join('') + '</tr>';
    }).join('');
}

function renderDemographicBreakdowns(rows) {
    const activities = new Map();
    const purokLabels = new Map();
    rows.forEach(function (row) {
        if (row.attendanceStatus !== 'Attended') return;
        const activityKey = String(row.eventId || row.eventTitle || 'unknown');
        let activity = activities.get(activityKey);
        if (!activity) {
            activity = { title: String(row.eventTitle || 'Unknown event'), ages: new Map(), puroks: new Map() };
            activities.set(activityKey, activity);
        }
        const ageGroup = ageGroups.indexOf(row.ageGroup) === -1 ? 'Unknown' : row.ageGroup;
        activity.ages.set(ageGroup, (activity.ages.get(ageGroup) || 0) + 1);
        const label = String(row.purok || '').trim().replace(/\s+/g, ' ') || 'Not recorded';
        const key = label.toLocaleLowerCase();
        if (!purokLabels.has(key)) purokLabels.set(key, label);
        activity.puroks.set(key, (activity.puroks.get(key) || 0) + 1);
    });

    const sortedActivities = Array.from(activities.values()).sort(function (a, b) {
        return a.title.localeCompare(b.title);
    });
    const ageHead = ['Activity'].concat(ageGroups, ['Total']);
    document.getElementById('ageBreakdownHead').innerHTML =
        '<tr>' + ageHead.map(function (label) { return '<th>' + escapeCopHtml(label) + '</th>'; }).join('') + '</tr>';
    document.getElementById('ageBreakdownBody').innerHTML = sortedActivities.length
        ? sortedActivities.map(function (activity) {
            const counts = ageGroups.map(function (group) { return activity.ages.get(group) || 0; });
            const total = counts.reduce(function (sum, count) { return sum + count; }, 0);
            return '<tr><th class="text-start">' + escapeCopHtml(activity.title) + '</th>' +
                counts.map(function (count) { return '<td>' + count + '</td>'; }).join('') +
                '<td><strong>' + total + '</strong></td></tr>';
        }).join('')
        : '<tr><td colspan="' + ageHead.length + '" class="text-muted py-4">No confirmed attendance matches these filters.</td></tr>';

    const purokKeys = Array.from(purokLabels.keys()).sort(function (a, b) {
        if (a === 'not recorded') return 1;
        if (b === 'not recorded') return -1;
        return purokLabels.get(a).localeCompare(purokLabels.get(b), undefined, { numeric: true, sensitivity: 'base' });
    });
    const purokHead = ['Activity'].concat(purokKeys.map(function (key) { return purokLabels.get(key); }), ['Total']);
    document.getElementById('purokBreakdownHead').innerHTML =
        '<tr>' + purokHead.map(function (label) { return '<th>' + escapeCopHtml(label) + '</th>'; }).join('') + '</tr>';
    document.getElementById('purokBreakdownBody').innerHTML = sortedActivities.length
        ? sortedActivities.map(function (activity) {
            const counts = purokKeys.map(function (key) { return activity.puroks.get(key) || 0; });
            const total = counts.reduce(function (sum, count) { return sum + count; }, 0);
            return '<tr><th class="text-start">' + escapeCopHtml(activity.title) + '</th>' +
                counts.map(function (count) { return '<td>' + count + '</td>'; }).join('') +
                '<td><strong>' + total + '</strong></td></tr>';
        }).join('')
        : '<tr><td colspan="' + Math.max(purokHead.length, 2) + '" class="text-muted py-4">No confirmed attendance matches these filters.</td></tr>';
}

function escapeCopHtml(value) {
    return String(value).replace(/[&<>"']/g, function (character) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
    });
}

function renderParticipation() {
    const rows = getFilteredRows();
    const attendees = rows.filter(function (row) { return row.attendanceStatus === 'Attended'; });
    const uniqueMembers = new Set(attendees.filter(function (row) { return !!row.memberId; })
        .map(function (row) { return String(row.memberId); }));
    const activities = new Set(rows.map(function (row) {
        return String(row.eventId || row.eventTitle || '');
    }).filter(Boolean));
    document.getElementById('uniqueAttendeeCount').textContent = String(uniqueMembers.size);
    document.getElementById('confirmedAttendanceCount').textContent = String(attendees.length);
    document.getElementById('programCount').textContent = String(activities.size);
    renderCopHeatmap(rows);
    renderDemographicBreakdowns(rows);

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
        visibleCount + ' of ' + eligibleCount + ' activity/date-matched records shown';
}

['participationActivityFilter', 'participationDateFrom', 'participationDateTo', 'search', 'attendanceStatusFilter']
    .forEach(function (id) {
        document.getElementById(id).addEventListener('input', renderParticipation);
        document.getElementById(id).addEventListener('change', renderParticipation);
    });
document.getElementById('clearParticipationFilters').addEventListener('click', function () {
    activityFilter.value = '';
    dateFromFilter.value = '';
    dateToFilter.value = '';
    document.getElementById('search').value = '';
    document.getElementById('attendanceStatusFilter').value = '';
    renderParticipation();
});
renderParticipation();

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>