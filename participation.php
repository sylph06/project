<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Participation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="sidebar.css?v=20" rel="stylesheet">

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
            background:white;
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
            background:white;
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
            background:white;
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
$participationSignupMemberEvents = [];
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
    if ($memberId !== '') $participationSignupMemberEvents[$eventId . ':' . $memberId] = true;
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
    if ($member) {
        $participationLinkedMembers[$memberId] = true;
        if ($status === 'Attended') $participationAttendedCount++;
    } elseif ($status === 'Attended') {
        $participationAttendedCount++;
    }
    $participationRows[] = [
        'memberId' => $member ? (string)($member['memberNo'] ?? $memberId) : 'Not linked',
        'memberName' => $name,
        'memberStatus' => $member['status'] ?? 'Unlinked signup',
        'eventId' => $eventId,
        'eventTitle' => $eventTitle,
        'eventDate' => $eventDate,
        'submittedAt' => substr((string)($signup['submittedAt'] ?? ''), 0, 10),
        'attendanceStatus' => $status,
    ];
    $participationHeatmapRows[] = [
        'memberKey' => $member ? $memberId : 'signup:' . (string)($signup['id'] ?? $signup['name'] ?? 'unlinked'),
        'eventId' => $eventId,
        'eventDate' => $eventDate,
        'centers' => participation_event_centers($event, $participationCopCategories),
        'attendanceStatus' => $status,
    ];
    if ($eventId !== '') $participationEventOptions[$eventId] = $eventTitle;
}
foreach ($participationWalkins as $walkin) {
    if (!is_array($walkin)) continue;
    $eventId = (string)($walkin['eventId'] ?? '');
    $memberId = (string)($walkin['memberId'] ?? '');
    if ($memberId !== '' && isset($participationSignupMemberEvents[$eventId . ':' . $memberId])) continue;
    if ($memberId !== '' && isset($participationWalkinMemberEvents[$eventId . ':' . $memberId])) continue;
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
    $participationAttendedCount++;
    $participationRows[] = [
        'memberId' => $member ? (string)($member['memberNo'] ?? $memberId) : 'Unknown member',
        'memberName' => $name !== '' ? $name : 'Unnamed member',
        'memberStatus' => $member['status'] ?? 'Member record unavailable',
        'eventId' => $eventId,
        'eventTitle' => $eventTitle,
        'eventDate' => (string)($event['date'] ?? ''),
        'submittedAt' => 'Walk-in',
        'attendanceStatus' => 'Attended',
    ];
    $participationHeatmapRows[] = [
        'memberKey' => $member ? $memberId : 'walkin:' . (string)($walkin['id'] ?? $walkin['name'] ?? 'unlinked'),
        'eventId' => $eventId,
        'eventDate' => (string)($event['date'] ?? ''),
        'centers' => participation_event_centers($event, $participationCopCategories),
        'attendanceStatus' => 'Attended',
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
<script src="sidebar.js?v=10"></script>

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
            <i class="bi bi-bell"></i><span class="header-notification-count">3</span>
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
                <div class="label">Active Youth</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-person-check"></i>
                <div class="number"><?= count($participationLinkedMembers) ?></div>
                <div class="label">KK members tracked</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-trophy"></i>
                <div class="number"><?= $participationAttendedCount ?></div>
                <div class="label">Confirmed attendances</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-calendar-event"></i>
                <div class="number"><?= count($participationEventOptions) ?></div>
                <div class="label">Programs tracked</div>
            </div>
        </div>

    </div>

    <div class="card-box">
        <div class="card-header"><i class="bi bi-grid-3x3 me-2"></i>COP Participation Heatmap</div>
        <div class="card-body">
            <p class="small text-muted">Counts confirmed attendee-event participations by Center of Participation and month.</p>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="copEventFilter">Activity</label>
                    <select class="form-select" id="copEventFilter">
                        <option value="">All activities</option>
                        <?php foreach ($participationEventOptions as $eventId => $eventTitle): ?>
                            <option value="<?= e($eventId) ?>"><?= e($eventTitle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="copDateFrom">From</label>
                    <input type="date" class="form-control" id="copDateFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="copDateTo">To</label>
                    <input type="date" class="form-control" id="copDateTo">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center mb-0">
                    <thead id="copHeatmapHead"></thead>
                    <tbody id="copHeatmapBody"></tbody>
                </table>
            </div>
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
                           placeholder="Search member or event...">
                </div>

                <div class="col-md-4">

                    <select class="form-select" id="activityFilter">
                        <option value="">All Activities</option>
                        <?php foreach ($participationEventOptions as $eventId => $eventTitle): ?>
                            <option value="<?= e($eventId) ?>"><?= e($eventTitle) ?></option>
                        <?php endforeach; ?>
                    </select>

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
                            <th>Attendance</th>
                            <th>Member Status</th>
                        </tr>

                    </thead>

                    <tbody id="participationTable">
                        <?php if (!$participationRows): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No event sign-ups or walk-in attendances have been recorded yet.</td></tr>
                        <?php else: foreach ($participationRows as $row): ?>
                            <tr data-event-id="<?= e($row['eventId']) ?>">
                                <td><?= e($row['memberId']) ?></td>
                                <td><strong><?= e($row['memberName']) ?></strong></td>
                                <td><?= e($row['eventTitle']) ?><?php if ($row['eventDate'] !== ''): ?><div class="small text-muted"><?= e($row['eventDate']) ?></div><?php endif; ?></td>
                                <td><?= e($row['submittedAt'] ?: '—') ?></td>
                                <td>
                                    <?php
                                    $attendanceBadge = $row['attendanceStatus'] === 'Attended'
                                        ? 'text-bg-success'
                                        : ($row['attendanceStatus'] === 'Did not attend' ? 'text-bg-secondary' : 'text-bg-warning');
                                    ?>
                                    <span class="badge <?= $attendanceBadge ?>"><?= e($row['attendanceStatus']) ?></span>
                                </td>
                                <td><?= e($row['memberStatus']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>

                </table>

            </div>
            <p class="small text-muted px-3 pb-3 mb-0">
                Event sign-ups and confirmed attendance are shown separately. Walk-in attendance is included; older sign-ups and accounts not linked to a KK member remain marked as unlinked.
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
function renderCopHeatmap() {
    const from = document.getElementById('copDateFrom').value;
    const to = document.getElementById('copDateTo').value;
    const eventId = document.getElementById('copEventFilter').value;
    const cells = {}, months = {};
    copRows.forEach(function (row) {
        if (row.attendanceStatus !== 'Attended' || (eventId && row.eventId !== eventId)) return;
        const date = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/.test(row.eventDate || '')
            ? row.eventDate : '';
        if ((from || to) && (!date || (from && date < from) || (to && date > to))) return;
        const month = date ? date.slice(0, 7) : 'Undated';
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
        return '<th>' + month.replace('-', ' / ') + '</th>';
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
function escapeCopHtml(value) {
    return String(value).replace(/[&<>"']/g, function (character) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
    });
}
document.getElementById("search").addEventListener("input", function() {
    const value = this.value.toLowerCase();
    filterRows(value, document.getElementById("activityFilter").value);
});

document.getElementById("activityFilter").addEventListener("change", function() {
    filterRows(document.getElementById("search").value.toLowerCase(), this.value);
});
["copEventFilter", "copDateFrom", "copDateTo"].forEach(function (id) {
    document.getElementById(id).addEventListener("change", renderCopHeatmap);
});
renderCopHeatmap();

function filterRows(search, eventId) {
    document.querySelectorAll("#participationTable tr[data-event-id]").forEach(function(row) {
        const matchesSearch = row.innerText.toLowerCase().includes(search);
        const matchesEvent = !eventId || row.dataset.eventId === eventId;
        row.style.display = matchesSearch && matchesEvent ? "" : "none";
    });
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>