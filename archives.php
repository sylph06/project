<?php
require_once __DIR__ . '/config.php';
require_viewer();
?>
<!DOCTYPE html>
<html lang="en"<?= is_limited_viewer() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=32" rel="stylesheet">
    <style>
        :root { --line:var(--bs-border-color); --muted:var(--bs-secondary-color); }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bs-tertiary-bg); color:var(--bs-body-color); font-family:Arial,sans-serif; }
        .top-strip { height:7px; background:var(--bs-primary); }
        .header { min-height:70px; background:#fff; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 26px; gap:14px; }
        .header-title { font-size:20px; font-weight:800; }
        .user-area { display:flex; align-items:center; gap:10px; }
        .avatar { width:38px; height:38px; border-radius:50%; background:var(--bs-primary); display:flex; align-items:center; justify-content:center; font-weight:800; }
        .content { padding:26px; max-width:1500px; margin:0 auto; }
        .hero { background:#fff; border:1px solid var(--line); border-radius:16px; padding:25px; margin-bottom:20px; }
        .hero h1 { font-weight:900; font-size:28px; margin:0 0 6px; }
        .hero p { color:var(--muted); margin:0; }
        .summary-card, .records-card { background:#fff; border:1px solid var(--line); border-radius:14px; }
        .summary-card { width:100%; padding:18px; height:100%; text-align:left; color:inherit; cursor:pointer; transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .summary-card:hover { transform:translateY(-2px); border-color:var(--bs-primary); }
        .summary-card:focus-visible { outline:3px solid var(--bs-primary); outline-offset:2px; }
        .summary-card.selected { border-color:var(--bs-primary); box-shadow:0 0 0 2px var(--bs-primary); }
        .summary-card .count { font-size:27px; font-weight:900; }
        .summary-card .label { color:var(--muted); font-size:14px; }
        .records-card { overflow:hidden; }
        .records-heading { padding:17px 20px; border-bottom:1px solid var(--line); font-weight:800; }
        .records-body { padding:18px 20px; }
        .table { font-size:14px; }
        .table th { white-space:nowrap; background:var(--bs-primary-bg-subtle); color:var(--bs-secondary-color); font-size:12px; text-transform:uppercase; }
        .table td, .table th { padding:12px; vertical-align:middle; }
        .footer-note { text-align:center; color:var(--muted); font-size:13px; padding:24px; }
        @media(max-width:767px) {
            .content { padding:16px; }
            .header { padding:0 16px; }
            .records-body { padding:14px; }
        }
    </style>
<?php include __DIR__ . '/init.php'; ?>
<?php
$archiveRequestItems = json_decode($__data['kkEditRequests'] ?? '[]', true);
$archiveRequestItems = is_array($archiveRequestItems)
    ? array_filter($archiveRequestItems, function ($request) {
        return is_array($request) && ($request['status'] ?? '') === 'Edit Request';
    })
    : [];
$archivePendingItems = json_decode($__data['kkPendingRegistrations'] ?? '[]', true);
$archivePendingItems = is_array($archivePendingItems)
    ? array_filter($archivePendingItems, function ($request) {
        return is_array($request) && ($request['status'] ?? '') === 'Pending';
    })
    : [];
$archiveAttentionCount = count($archiveRequestItems) + count($archivePendingItems);
$archiveMembers = json_decode($__data['kkMembers'] ?? '[]', true);
$archiveMembers = is_array($archiveMembers) ? $archiveMembers : [];
$archiveRoles = json_decode($__data['skRoles'] ?? '[]', true);
$archiveRoles = is_array($archiveRoles) ? $archiveRoles : [];
$archiveAnnouncements = json_decode($__data['skAnnouncements'] ?? '[]', true);
$archiveAnnouncements = is_array($archiveAnnouncements) ? $archiveAnnouncements : [];
$archiveMembersById = [];
$archiveRows = [];
$archiveLimitedViewer = is_limited_viewer();

function archive_announcement_is_completed($announcement, $now, $today) {
    if (!is_array($announcement)
        || !empty($announcement['cancelled'])
        || ($announcement['publishState'] ?? '') !== 'Published'
        || (!empty($announcement['publishDate']) && $announcement['publishDate'] > $today)) {
        return false;
    }

    $eventDate = (string)($announcement['date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)
        || !checkdate((int)substr($eventDate, 5, 2), (int)substr($eventDate, 8, 2), (int)substr($eventDate, 0, 4))) {
        return false;
    }

    $endTime = (string)($announcement['endTime'] ?? '');
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $endTime)) {
        $eventEnd = strtotime($eventDate . ' ' . $endTime . ':00');
    } else {
        $eventEnd = strtotime($eventDate . ' 23:59:59');
    }
    return $eventEnd !== false && $eventEnd < $now;
}

$archiveNow = time();
$archiveToday = date('Y-m-d', $archiveNow);
foreach ($archiveMembers as $member) {
    if (!is_array($member) || empty($member['id'])) continue;
    $archiveMembersById[(string)$member['id']] = $member;
    if (($member['status'] ?? '') !== 'Archived') continue;
    $name = trim(implode(' ', array_filter([
        $member['firstName'] ?? '', $member['middleName'] ?? '',
        $member['lastName'] ?? '', $member['suffix'] ?? '',
    ], function ($part) { return trim((string)$part) !== ''; })));
    $archiveRows[] = [
        'type' => 'member',
        'typeLabel' => 'KK member',
        'name' => $name !== '' ? $name : 'Unnamed member',
        'identifier' => (string)($member['memberNo'] ?? ''),
        'details' => (string)($member['archiveReason'] ?? 'Archived KK member'),
        'date' => (string)($member['archivedAt'] ?? ''),
        'source' => 'registration.php#archiveBody',
        'sourceLabel' => 'Member records',
        'search' => implode(' ', [$name, $member['memberNo'] ?? '', $member['archiveReason'] ?? '']),
    ];
}

foreach ($archiveRoles as $role) {
    if (!is_array($role) || !in_array(($role['status'] ?? ''), ['Archived', 'Inactive'], true)) continue;
    $memberId = (string)($role['kkId'] ?? '');
    $member = $archiveMembersById[$memberId] ?? [];
    $name = trim(implode(' ', array_filter([
        $member['firstName'] ?? '', $member['middleName'] ?? '',
        $member['lastName'] ?? '', $member['suffix'] ?? '',
    ], function ($part) { return trim((string)$part) !== ''; })));
    $history = isset($role['history']) && is_array($role['history']) ? array_reverse($role['history']) : [];
    $archivedHistory = null;
    foreach ($history as $historyEntry) {
        if (is_array($historyEntry) && preg_match('/archiv|ended/i', (string)($historyEntry['action'] ?? ''))) {
            $archivedHistory = $historyEntry;
            break;
        }
    }
    $position = (string)($role['position'] ?? 'SK role');
    $roleStatus = (string)$role['status'];
    $roleTypeLabel = $roleStatus === 'Archived' ? 'Archived SK role' : 'Ended / inactive SK role';
    $archiveRows[] = [
        'type' => 'role',
        'typeLabel' => $roleTypeLabel,
        'name' => $name !== '' ? $name : 'Member record unavailable',
        'identifier' => $position,
        'details' => (string)($archivedHistory['note'] ?? ($roleStatus === 'Archived' ? 'Archived SK role' : 'Inactive SK role')),
        'date' => (string)($archivedHistory['at'] ?? ''),
        'source' => 'members.php',
        'sourceLabel' => 'Member list',
        'search' => implode(' ', [$name, $member['memberNo'] ?? '', $position, $roleTypeLabel, $archivedHistory['note'] ?? '']),
    ];
}

foreach ($archiveAnnouncements as $announcement) {
    if (!is_array($announcement)) continue;
    $isCancelled = !empty($announcement['cancelled']);
    $isCompleted = archive_announcement_is_completed($announcement, $archiveNow, $archiveToday);
    if ($archiveLimitedViewer && !$isCompleted) continue;
    if (!$isCancelled && !$isCompleted) continue;
    $title = trim((string)($announcement['title'] ?? ''));
    if ($title === '') $title = 'Untitled announcement';
    $eventDate = (string)($announcement['date'] ?? '');
    $category = trim((string)($announcement['category'] ?? ''));
    $status = $isCompleted ? 'Completed' : 'Cancelled';
    $archiveRows[] = [
        'type' => 'announcement',
        'typeLabel' => $isCompleted ? 'Completed event' : 'Cancelled event / announcement',
        'name' => $title,
        'announcementId' => (string)($announcement['id'] ?? ''),
        'announcementStatus' => $status,
        'identifier' => $eventDate,
        'details' => $isCompleted
            ? trim('Completed event' . ($category !== '' ? ' · ' . $category : ''))
            : ($category !== '' ? $category : 'Cancelled'),
        'date' => $isCompleted ? $eventDate : (string)($announcement['updatedAt'] ?? ''),
        'source' => 'announcements.php',
        'sourceLabel' => 'Announcements',
        'search' => implode(' ', array_filter([
            $title,
            $eventDate,
            $category,
            $announcement['description'] ?? '',
            $announcement['details'] ?? '',
            $announcement['venue'] ?? '',
            $announcement['organizer'] ?? '',
            $announcement['contact'] ?? '',
            $announcement['notes'] ?? '',
            is_array($announcement['centers'] ?? null) ? implode(' ', array_filter($announcement['centers'], 'is_scalar')) : '',
            strtolower($status),
        ], 'is_scalar')),
    ];
}

usort($archiveRows, function ($a, $b) {
    return strcmp($b['date'], $a['date']) ?: strcasecmp($a['typeLabel'], $b['typeLabel']) ?: strcasecmp($a['name'], $b['name']);
});
$archiveCounts = ['member' => 0, 'role' => 0, 'announcement' => 0];
foreach ($archiveRows as $archiveRow) $archiveCounts[$archiveRow['type']]++;
?>
</head>
<body>
<div class="top-strip"></div>
<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=16"></script>
<main class="main page-content">
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <div><div class="header-title">Archives</div><small>KK Youth Profiling System</small></div>
        </div>
        <div class="header-account-area">
            <a href="<?= $archiveLimitedViewer ? 'announcements.php' : 'request.php' ?>" class="header-notification" aria-label="<?= $archiveLimitedViewer ? 'View announcements' : (int)$archiveAttentionCount . ' requests need review' ?>">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if (!$archiveLimitedViewer && $archiveAttentionCount > 0): ?><span class="header-notification-count"><?= (int)$archiveAttentionCount ?></span><?php endif; ?>
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
        <section class="hero">
            <h1><i class="bi bi-archive me-2"></i>Archives</h1>
            <p><?= $archiveLimitedViewer
                ? 'Browse published events that have ended. They remain available in the announcements list under Completed.'
                : 'Review archived KK members and SK roles, plus completed and cancelled events and announcements. Use each record’s source page to restore or manage it.' ?></p>
        </section>

        <div class="row g-3 mb-4">
            <?php if ($archiveLimitedViewer): ?>
                <div class="col-sm-6 col-lg-3"><button type="button" class="summary-card records-filter selected" data-filter="announcement" aria-pressed="true"><div class="count"><?= $archiveCounts['announcement'] ?></div><div class="label">Completed events</div></button></div>
            <?php else: ?>
                <div class="col-sm-6 col-lg-3"><button type="button" class="summary-card records-filter selected" data-filter="" aria-pressed="true"><div class="count"><?= count($archiveRows) ?></div><div class="label">Archived records</div></button></div>
                <div class="col-sm-6 col-lg-3"><button type="button" class="summary-card records-filter" data-filter="member" aria-pressed="false"><div class="count"><?= $archiveCounts['member'] ?></div><div class="label">Archived KK members</div></button></div>
                <div class="col-sm-6 col-lg-3"><button type="button" class="summary-card records-filter" data-filter="role" aria-pressed="false"><div class="count"><?= $archiveCounts['role'] ?></div><div class="label">Ended / archived SK roles</div></button></div>
                <div class="col-sm-6 col-lg-3"><button type="button" class="summary-card records-filter" data-filter="announcement" aria-pressed="false"><div class="count"><?= $archiveCounts['announcement'] ?></div><div class="label">Completed / cancelled announcements</div></button></div>
            <?php endif; ?>
        </div>

        <section class="records-card">
            <div class="records-heading"><i class="bi bi-clock-history me-2"></i><?= $archiveLimitedViewer ? 'Completed events' : 'Archived records' ?></div>
            <div class="records-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-7"><label class="visually-hidden" for="archiveSearch">Search archives</label><input id="archiveSearch" class="form-control" type="search" placeholder="<?= $archiveLimitedViewer ? 'Search event title, category, or date' : 'Search name, ID, position, or reason' ?>"></div>
                    <div class="col-md-5"><label class="visually-hidden" for="archiveType">Filter archive type</label>
                        <select id="archiveType" class="form-select">
                            <?php if ($archiveLimitedViewer): ?>
                                <option value="announcement">Completed events</option>
                            <?php else: ?>
                                <option value="">All archived record types</option>
                                <option value="member">KK members</option>
                                <option value="role">SK roles</option>
                                <option value="announcement">Completed / cancelled announcements</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Type</th><th>Record</th><th>Event / archive date</th><th>Reason / details</th><th>Manage</th></tr></thead>
                        <tbody id="archiveRows">
                        <?php if (!$archiveRows): ?>
                            <tr id="archiveEmpty"><td colspan="5" class="text-center text-muted py-5"><?= $archiveLimitedViewer ? 'There are no completed events to archive yet.' : 'There are no archived records.' ?></td></tr>
                        <?php else: foreach ($archiveRows as $row): ?>
                            <tr data-type="<?= e($row['type']) ?>" data-search="<?= e($row['search']) ?>">
                                <td><span class="badge text-bg-secondary"><?= e($row['typeLabel']) ?></span></td>
                                <td><strong><?= e($row['name']) ?></strong><?php if ($row['identifier'] !== ''): ?><div class="small text-muted"><?= e($row['identifier']) ?></div><?php endif; ?></td>
                                <td><?= e($row['date'] !== '' ? substr($row['date'], 0, 10) : 'Date not recorded') ?></td>
                                <td><?= e($row['details']) ?></td>
                                <td class="text-nowrap">
                                    <?php if ($row['type'] === 'announcement' && !empty($row['announcementId'])): ?>
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-archive-announcement="<?= e($row['announcementId']) ?>" data-archive-status="<?= e($row['announcementStatus']) ?>">View details</button>
                                    <?php endif; ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e($row['source']) ?>"><?= e($row['sourceLabel']) ?> <i class="bi bi-arrow-up-right"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                            <tr id="archiveNoMatches" hidden><td colspan="5" class="text-center text-muted py-4">No matching records.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-3 mb-0"><?= $archiveLimitedViewer
                    ? 'Published events appear here automatically after they end and remain in the announcements list under Completed.'
                    : 'Published events appear here automatically after they end. Completed events also remain in the announcements list under Completed. Sign-ups, attendance, and reports are not archived here.' ?></p>
            </div>
        </section>
        <div class="footer-note">KK Youth Profiling System &bull; SK Barangay Sikat, Alfonso, Cavite &bull; FY 2026&ndash;2027</div>
    </div>
</main>
<div class="modal fade" id="archiveAnnouncementModal" tabindex="-1" aria-labelledby="archiveAnnouncementTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="archiveAnnouncementTitle">Archived event details</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="archiveAnnouncementDetails"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var search = document.getElementById('archiveSearch');
    var type = document.getElementById('archiveType');
    var filterCards = Array.prototype.slice.call(document.querySelectorAll('.records-filter'));
    var rows = Array.prototype.slice.call(document.querySelectorAll('#archiveRows tr[data-type]'));
    var noMatches = document.getElementById('archiveNoMatches');
    var announcementRecords = [];
    try {
        var storedAnnouncements = JSON.parse(window.__DB.skAnnouncements || '[]');
        if (Array.isArray(storedAnnouncements)) announcementRecords = storedAnnouncements;
    } catch (error) {
        console.error('Could not read archived announcement details.', error);
    }

    function announcementArchiveStatus(record) {
        if (!record) return '';
        if (record.cancelled) return 'Cancelled';
        if (record.publishState !== 'Published') return '';

        var today = new Date();
        var todayText = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') +
            '-' + String(today.getDate()).padStart(2, '0');
        if (record.publishDate && record.publishDate > todayText) return '';

        var eventDate = String(record.date || '');
        if (!/^\d{4}-\d{2}-\d{2}$/.test(eventDate)) return '';
        var endTime = String(record.endTime || '');
        endTime = /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(endTime) ? endTime + ':00' : '23:59:59';
        var eventEnd = new Date(eventDate + 'T' + endTime);
        return !Number.isNaN(eventEnd.getTime()) && Date.now() > eventEnd.getTime() ? 'Completed' : '';
    }

    function showAnnouncementDetails(id, expectedStatus) {
        var announcement = announcementRecords.filter(function (record) {
            return record && String(record.id) === String(id)
                && announcementArchiveStatus(record) === expectedStatus;
        })[0];
        if (!announcement) return;

        var status = announcementArchiveStatus(announcement);
        document.getElementById('archiveAnnouncementTitle').textContent = status === 'Completed'
            ? 'Completed event details' : 'Cancelled announcement details';
        var details = document.getElementById('archiveAnnouncementDetails');
        details.replaceChildren();
        var banner = announcement.banner || announcement.poster || '';
        if (/^data:image\/(?:png|jpe?g|webp);base64,[A-Za-z0-9+/=]+$/i.test(banner)) {
            var image = document.createElement('img');
            image.src = banner;
            image.alt = 'Banner for ' + String(announcement.title || 'archived announcement');
            image.className = 'img-fluid rounded mb-3';
            details.appendChild(image);
        }

        var heading = document.createElement('h3');
        heading.className = 'h5 fw-bold';
        heading.textContent = String(announcement.title || 'Untitled announcement');
        details.appendChild(heading);

        var fields = [
            ['Status', status],
            ['Category', announcement.category],
            ['Centers of participation', Array.isArray(announcement.centers) ? announcement.centers.join(', ') : ''],
            ['Event date', announcement.date],
            ['Previously scheduled date', announcement.rescheduledFrom],
            ['Start time', announcement.startTime],
            ['End time', announcement.endTime],
            ['Venue', announcement.venue],
            ['Organizer', announcement.organizer],
            ['Contact', announcement.contact],
            ['Description', announcement.description],
            ['Details', announcement.details],
            ['Additional notes', announcement.notes],
            ['Publication status', announcement.publishState],
            ['Publication date', announcement.publishDate],
            ['Last updated', announcement.updatedAt],
        ];
        var list = document.createElement('dl');
        list.className = 'row mb-0';
        fields.forEach(function (field) {
            if (field[1] == null || field[1] === '') return;
            var label = document.createElement('dt');
            label.className = 'col-sm-4';
            label.textContent = field[0];
            var value = document.createElement('dd');
            value.className = 'col-sm-8';
            value.style.whiteSpace = 'pre-wrap';
            value.textContent = String(field[1]);
            list.appendChild(label);
            list.appendChild(value);
        });
        details.appendChild(list);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('archiveAnnouncementModal')).show();
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-archive-announcement]');
        if (button) showAnnouncementDetails(button.dataset.archiveAnnouncement, button.dataset.archiveStatus);
    });

    function filter() {
        var query = search.value.trim().toLowerCase();
        var selectedType = type.value;
        var visible = 0;
        rows.forEach(function (row) {
            var matches = (!selectedType || row.dataset.type === selectedType) &&
                (!query || (row.dataset.search + ' ' + row.innerText).toLowerCase().includes(query));
            row.hidden = !matches;
            if (matches) visible++;
        });
        noMatches.hidden = visible > 0 || rows.length === 0;
    }
    search.addEventListener('input', filter);
    type.addEventListener('change', function () {
        filterCards.forEach(function (card) {
            var selected = card.dataset.filter === type.value;
            card.classList.toggle('selected', selected);
            card.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        filter();
    });
    filterCards.forEach(function (card) {
        card.addEventListener('click', function () {
            type.value = card.dataset.filter;
            search.value = '';
            filterCards.forEach(function (item) {
                var selected = item === card;
                item.classList.toggle('selected', selected);
                item.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            filter();
        });
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
