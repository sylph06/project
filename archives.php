<?php
require_once __DIR__ . '/config.php';
require_viewer();
?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">
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
$archiveMembers = json_decode($__data['kkMembers'] ?? '[]', true);
$archiveMembers = is_array($archiveMembers) ? $archiveMembers : [];
$archiveRoles = json_decode($__data['skRoles'] ?? '[]', true);
$archiveRoles = is_array($archiveRoles) ? $archiveRoles : [];
$archiveAnnouncements = json_decode($__data['skAnnouncements'] ?? '[]', true);
$archiveAnnouncements = is_array($archiveAnnouncements) ? $archiveAnnouncements : [];
$archiveMembersById = [];
$archiveRows = [];

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
    if (!is_array($announcement) || empty($announcement['cancelled'])) continue;
    $title = trim((string)($announcement['title'] ?? ''));
    if ($title === '') $title = 'Untitled announcement';
    $archiveRows[] = [
        'type' => 'announcement',
        'typeLabel' => 'Cancelled event / announcement',
        'name' => $title,
        'identifier' => (string)($announcement['date'] ?? ''),
        'details' => trim((string)($announcement['category'] ?? 'Cancelled')) ?: 'Cancelled',
        'date' => (string)($announcement['updatedAt'] ?? ''),
        'source' => 'announcements.php',
        'sourceLabel' => 'Announcements',
        'search' => implode(' ', [$title, $announcement['date'] ?? '', $announcement['category'] ?? '', 'cancelled']),
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
<script src="sidebar.js?v=4"></script>
<main class="main page-content">
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
            <div><div class="header-title">Archives</div><small>KK Youth Profiling System</small></div>
        </div>
        <div class="user-area">
            <div><div class="fw-bold"><?= e(uname()) ?></div><small><?= e(strtoupper(urole())) ?></small></div>
            <div class="avatar"><?= e(uinit()) ?></div>
        </div>
    </header>
    <div class="content">
        <section class="hero">
            <h1><i class="bi bi-archive me-2"></i>Archives</h1>
            <p>Review archived KK members and SK roles, plus cancelled events and announcements. Use each record’s source page to restore or manage it.</p>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3"><button type="button" class="summary-card records-filter selected" data-filter="" aria-pressed="true"><div class="count"><?= count($archiveRows) ?></div><div class="label">Archived or cancelled records</div></button></div>
            <div class="col-sm-6 col-xl-3"><button type="button" class="summary-card records-filter" data-filter="member" aria-pressed="false"><div class="count"><?= $archiveCounts['member'] ?></div><div class="label">Archived KK members</div></button></div>
            <div class="col-sm-6 col-xl-3"><button type="button" class="summary-card records-filter" data-filter="role" aria-pressed="false"><div class="count"><?= $archiveCounts['role'] ?></div><div class="label">Ended / archived SK roles</div></button></div>
            <div class="col-sm-6 col-xl-3"><button type="button" class="summary-card records-filter" data-filter="announcement" aria-pressed="false"><div class="count"><?= $archiveCounts['announcement'] ?></div><div class="label">Cancelled events / announcements</div></button></div>
        </div>

        <section class="records-card">
            <div class="records-heading"><i class="bi bi-clock-history me-2"></i>Archived records</div>
            <div class="records-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-7"><label class="visually-hidden" for="archiveSearch">Search archives</label><input id="archiveSearch" class="form-control" type="search" placeholder="Search name, ID, position, or reason"></div>
                    <div class="col-md-5"><label class="visually-hidden" for="archiveType">Filter archive type</label>
                        <select id="archiveType" class="form-select">
                            <option value="">All archived record types</option>
                            <option value="member">KK members</option>
                            <option value="role">SK roles</option>
                            <option value="announcement">Cancelled events / announcements</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Type</th><th>Record</th><th>Archived / cancelled</th><th>Reason / details</th><th>Manage</th></tr></thead>
                        <tbody id="archiveRows">
                        <?php if (!$archiveRows): ?>
                            <tr id="archiveEmpty"><td colspan="5" class="text-center text-muted py-5">There are no archived or cancelled records.</td></tr>
                        <?php else: foreach ($archiveRows as $row): ?>
                            <tr data-type="<?= e($row['type']) ?>" data-search="<?= e($row['search']) ?>">
                                <td><span class="badge text-bg-secondary"><?= e($row['typeLabel']) ?></span></td>
                                <td><strong><?= e($row['name']) ?></strong><?php if ($row['identifier'] !== ''): ?><div class="small text-muted"><?= e($row['identifier']) ?></div><?php endif; ?></td>
                                <td><?= e($row['date'] !== '' ? substr($row['date'], 0, 10) : 'Date not recorded') ?></td>
                                <td><?= e($row['details']) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= e($row['source']) ?>"><?= e($row['sourceLabel']) ?> <i class="bi bi-arrow-up-right"></i></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                            <tr id="archiveNoMatches" hidden><td colspan="5" class="text-center text-muted py-4">No matching records.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-3 mb-0">Only record types with an existing archived or cancelled state are listed here. Records such as sign-ups, attendance, and reports are not archived by the application.</p>
            </div>
        </section>
        <div class="footer-note">KK Youth Profiling System &bull; SK Barangay Sikat, Alfonso, Cavite &bull; FY 2026&ndash;2027</div>
    </div>
</main>
<script>
(function () {
    var search = document.getElementById('archiveSearch');
    var type = document.getElementById('archiveType');
    var filterCards = Array.prototype.slice.call(document.querySelectorAll('.records-filter'));
    var rows = Array.prototype.slice.call(document.querySelectorAll('#archiveRows tr[data-type]'));
    var noMatches = document.getElementById('archiveNoMatches');
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
