<?php require_once __DIR__ . '/config.php'; require_once __DIR__ . '/transparency_budget.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="css/sidebar.css?v=31" rel="stylesheet">
    <style>
        .dashboard-page{background:var(--ui-paper)}
        .dashboard-toolbar{
            min-height:68px;
            border-bottom:1px solid var(--ui-line);
        }
        .dashboard-search{
            width:min(340px,38vw);
            min-height:40px;
            border:1px solid var(--ui-line);
            border-radius:999px;
            background:var(--ui-paper);
        }
        .dashboard-search:focus{background:var(--ui-surface)}
        .dashboard-overview{padding-top:.25rem}
        .dashboard-stat,.dashboard-panel{
            height:100%;
            background:var(--ui-surface);
            border:1px solid var(--ui-line);
            border-radius:12px;
            box-shadow:0 2px 8px rgba(15,23,42,.035);
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
            color:var(--bs-primary);
            background:var(--bs-primary-bg-subtle);
            font-size:1.05rem;
        }
        .dashboard-panel{padding:1.1rem 1.2rem;overflow:hidden}
        .dashboard-greeting{max-width:none}
        .dashboard-attention{
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:.65rem 1rem;
            padding:.7rem 1rem;
            border:1px solid var(--ui-line);
            border-radius:10px;
            background:var(--ui-surface);
        }
        .attention-pill{
            display:inline-flex;
            align-items:center;
            gap:.4rem;
            background:var(--bs-warning-bg-subtle);
            color:var(--bs-warning-text-emphasis);
            padding:.25rem .55rem;
            border-radius:999px;
            font-size:.75rem;
            font-weight:700;
        }
        .dashboard-queue-row{
            display:flex;
            align-items:center;
            gap:.45rem;
            color:var(--ui-muted);
            font-size:.82rem;
        }
        .dashboard-queue-row .btn{padding:.2rem .55rem}
        .dashboard-stat .fs-2{
            color:var(--bs-emphasis-color);
            font-size:clamp(1.2rem,2vw,1.65rem)!important;
            font-weight:700;
            letter-spacing:-.035em;
            overflow-wrap:anywhere;
        }
        .budget-stat{
            height:100%;
            padding:.6rem .8rem;
            border-left:1px solid var(--ui-line);
        }
        .announcement-row+.announcement-row{border-top:1px solid var(--ui-line)}
        .dashboard-chart-wrap{position:relative;height:270px;min-height:220px}
        .dashboard-chart-wrap.dashboard-chart-gauge{height:175px;min-height:175px}
        .dashboard-chart-fallback{font-size:.875rem;color:var(--bs-secondary-color)}
        .dashboard-activity-table th{white-space:nowrap}
        .dashboard-activity-table td{vertical-align:middle}
        .dashboard-status{
            display:inline-flex;
            align-items:center;
            padding:.22rem .55rem;
            border-radius:999px;
            color:var(--bs-primary-text-emphasis);
            background:var(--bs-primary-bg-subtle);
            font-size:.72rem;
            font-weight:650;
            white-space:nowrap;
        }
        .dashboard-budget-center{
            margin-top:-1.55rem;
            text-align:center;
            pointer-events:none;
        }
        .dashboard-budget-center strong{display:block;color:var(--bs-emphasis-color);font-size:1.35rem}
        .dashboard-budget-center span{color:var(--bs-secondary-color);font-size:.75rem}
        .dashboard-stat-row>.col-6{min-width:0}
        @media(max-width:575.98px){
            .dashboard-toolbar .dashboard-search{width:100%;order:3}
            .dashboard-toolbar{flex-wrap:wrap}
            .budget-stat{border-left:0;border-top:1px solid var(--ui-line);padding:.75rem 0}
            .dashboard-chart-wrap{height:240px}
        }
    </style>
    <?php include __DIR__ . '/init.php'; ?>
    <?php
    $dashboardMembers = json_decode($__data['kkMembers'] ?? '[]', true);
    $dashboardMembers = is_array($dashboardMembers) ? $dashboardMembers : [];
    $dashboardActiveMembers = count(array_filter($dashboardMembers, function ($member) {
        return is_array($member) && ($member['status'] ?? '') === 'Active';
    }));
    $dashboardAgeGroups = ['15-17' => 0, '18-24' => 0, '25-30' => 0];
    $dashboardToday = new DateTimeImmutable('today');
    foreach ($dashboardMembers as $member) {
        if (!is_array($member) || ($member['status'] ?? '') !== 'Active' || empty($member['birthDate'])) continue;
        $birthDateValue = trim((string)$member['birthDate']);
        $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDateValue);
        if (!$birthDate || $birthDate->format('Y-m-d') !== $birthDateValue || $birthDate > $dashboardToday) continue;
        $age = $birthDate->diff($dashboardToday)->y;
        if ($age >= 15 && $age <= 17) $dashboardAgeGroups['15-17']++;
        elseif ($age >= 18 && $age <= 24) $dashboardAgeGroups['18-24']++;
        elseif ($age >= 25 && $age <= 30) $dashboardAgeGroups['25-30']++;
    }
    $dashboardAgeParticipationCount = array_sum($dashboardAgeGroups);
    $dashboardAnnouncements = json_decode($__data['skAnnouncements'] ?? '[]', true);
    $dashboardAnnouncements = is_array($dashboardAnnouncements) ? $dashboardAnnouncements : [];
    $today = date('Y-m-d');
    $publishedAnnouncements = array_values(array_filter($dashboardAnnouncements, function ($item) use ($today) {
        return is_array($item)
            && ($item['publishState'] ?? '') === 'Published'
            && (empty($item['publishDate']) || $item['publishDate'] <= $today);
    }));
    $upcomingAnnouncements = array_values(array_filter($publishedAnnouncements, function ($item) use ($today) {
        return !empty($item['date']) && $item['date'] >= $today && empty($item['cancelled']);
    }));
    usort($upcomingAnnouncements, function ($a, $b) {
        return strcmp((string)$a['date'], (string)$b['date']);
    });
    $upcomingAnnouncementCount = count($upcomingAnnouncements);
    $upcomingAnnouncements = array_slice($upcomingAnnouncements, 0, 5);
    $dashboardBudget = json_decode($__data['skTransparencyBudget'] ?? 'null', true);
    $dashboardBudget = is_array($dashboardBudget) ? $dashboardBudget : [];
    if (isset($dashboardBudget['budgets']) && is_array($dashboardBudget['budgets'])) {
        $dashboardBudgetYear = trim((string)($dashboardBudget['fy'] ?? ''));
        if ($dashboardBudgetYear !== '' && isset($dashboardBudget['budgets'][$dashboardBudgetYear])
            && is_array($dashboardBudget['budgets'][$dashboardBudgetYear])) {
            $dashboardBudget = $dashboardBudget['budgets'][$dashboardBudgetYear];
        }
    }
    $dashboardBudgetYear = trim((string)($dashboardBudget['fy'] ?? ''));
    $dashboardBudgetTotal = transparency_budget_year_total($dashboardBudget);
    $dashboardBudgetPublished = $dashboardBudgetTotal !== null;
    $dashboardBudgetAmount = $dashboardBudgetPublished ? $dashboardBudgetTotal : 0;
    $dashboardExpenses = json_decode($__data['skTransparencyExpenses'] ?? '[]', true);
    $dashboardExpenses = is_array($dashboardExpenses) ? $dashboardExpenses : [];
    $dashboardExpenseRows = [];
    $dashboardTotalSpent = 0;
    foreach ($dashboardExpenses as $expense) {
        if (!is_array($expense) || !isset($expense['amount']) || !is_numeric($expense['amount'])) continue;
        if ($dashboardBudgetYear !== '' && !empty($expense['fiscalYear'])
            && (string)$expense['fiscalYear'] !== $dashboardBudgetYear) continue;
        $amount = max(0, (float)$expense['amount']);
        $category = trim((string)($expense['category'] ?? '')) ?: 'Other';
        $dashboardTotalSpent += $amount;
        $dashboardExpenseRows[] = [
            'date' => (string)($expense['date'] ?? ''),
            'title' => trim((string)($expense['title'] ?? '')) ?: 'Expense',
            'category' => $category,
            'amount' => $amount,
        ];
    }
    usort($dashboardExpenseRows, function ($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    $dashboardExpenseRows = array_slice($dashboardExpenseRows, 0, 5);
    $dashboardEditRequests = json_decode($__data['kkEditRequests'] ?? '[]', true);
    $dashboardEditRequests = is_array($dashboardEditRequests) ? array_values(array_filter($dashboardEditRequests, function ($item) {
        return is_array($item) && ($item['status'] ?? '') === 'Edit Request';
    })) : [];
    $dashboardPendingRegistrations = json_decode($__data['kkPendingRegistrations'] ?? '[]', true);
    $dashboardPendingRegistrations = is_array($dashboardPendingRegistrations) ? array_values(array_filter($dashboardPendingRegistrations, function ($item) {
        return is_array($item) && ($item['status'] ?? '') === 'Pending';
    })) : [];
    $dashboardAttentionCount = count($dashboardEditRequests) + count($dashboardPendingRegistrations);
    $dashboardBudgetUtilization = $dashboardBudgetPublished && $dashboardBudgetAmount > 0
        ? ($dashboardTotalSpent / $dashboardBudgetAmount) * 100
        : 0;
    $dashboardGaugeSpent = min($dashboardTotalSpent, $dashboardBudgetAmount);
    $dashboardGaugeRemaining = max(0, $dashboardBudgetAmount - $dashboardTotalSpent);
    $dashboardCurrency = function ($amount) {
        return '₱' . number_format((float)$amount, 2);
    };

    /* Rank completed programs by confirmed attendance for planning. */
    $dashboardPrograms = json_decode($__data['skAnnouncements'] ?? '[]', true);
    $dashboardPrograms = is_array($dashboardPrograms) ? $dashboardPrograms : [];
    $dashboardProgramById = [];
    $dashboardProgramInsights = [];
    $dashboardInsightCutoff = (new DateTimeImmutable('today'))->modify('-12 months')->format('Y-m-d');
    foreach ($dashboardPrograms as $program) {
        if (!is_array($program) || empty($program['id']) || empty($program['date'])
            || $program['date'] < $dashboardInsightCutoff || $program['date'] > $today
            || !empty($program['cancelled'])) continue;
        $dashboardProgramById[(string)$program['id']] = [
            'title' => trim((string)($program['title'] ?? 'Untitled program')) ?: 'Untitled program',
            'category' => trim((string)($program['category'] ?? 'Other')) ?: 'Other',
            'date' => (string)$program['date'],
            'attendance' => 0,
            'members' => [],
        ];
    }
    $dashboardAttendedSignupMembers = [];
    $dashboardRegistrations = json_decode($__data['skEventRegistrations'] ?? '[]', true);
    $dashboardRegistrations = is_array($dashboardRegistrations) ? $dashboardRegistrations : [];
    foreach ($dashboardRegistrations as $registration) {
        if (!is_array($registration)) continue;
        $eventId = (string)($registration['eventId'] ?? '');
        if (!isset($dashboardProgramById[$eventId]) || ($registration['attendanceStatus'] ?? '') !== 'Attended') continue;
        $dashboardProgramById[$eventId]['attendance']++;
        if (!empty($registration['memberId'])) {
            $memberId = (string)$registration['memberId'];
            $dashboardProgramById[$eventId]['members'][$memberId] = true;
            $dashboardAttendedSignupMembers[$eventId . ':' . $memberId] = true;
        }
    }
    $dashboardWalkins = json_decode($__data['skEventAttendance'] ?? '[]', true);
    $dashboardWalkins = is_array($dashboardWalkins) ? $dashboardWalkins : [];
    $dashboardWalkinSeen = [];
    foreach ($dashboardWalkins as $walkin) {
        if (!is_array($walkin)) continue;
        $eventId = (string)($walkin['eventId'] ?? '');
        if (!isset($dashboardProgramById[$eventId])) continue;
        $memberId = trim((string)($walkin['memberId'] ?? ''));
        if ($memberId !== '' && isset($dashboardAttendedSignupMembers[$eventId . ':' . $memberId])) continue;
        $walkinKey = $eventId . ':' . ($memberId !== '' ? $memberId : (string)($walkin['id'] ?? ''));
        if ($walkinKey !== $eventId . ':' && isset($dashboardWalkinSeen[$walkinKey])) continue;
        $dashboardWalkinSeen[$walkinKey] = true;
        $dashboardProgramById[$eventId]['attendance']++;
        if ($memberId !== '') $dashboardProgramById[$eventId]['members'][$memberId] = true;
    }
    foreach ($dashboardProgramById as $program) {
        if ($program['attendance'] > 0) $dashboardProgramInsights[] = $program;
    }
    usort($dashboardProgramInsights, function ($a, $b) {
        return $b['attendance'] <=> $a['attendance']
            ?: strcmp($b['date'], $a['date'])
            ?: strcmp($a['title'], $b['title']);
    });
    $dashboardProgramInsights = array_slice($dashboardProgramInsights, 0, 5);
    $dashboardProgramChart = array_map(function ($program) {
        return [
            'label' => $program['title'],
            'value' => (int)$program['attendance'],
        ];
    }, $dashboardProgramInsights);
    $dashboardChartData = json_encode([
        'programs' => $dashboardProgramChart,
        'ageGroups' => array_map(function ($label, $count) {
            return ['label' => $label, 'value' => $count];
        }, array_keys($dashboardAgeGroups), array_values($dashboardAgeGroups)),
        'budgetProgress' => [
            'spent' => round($dashboardGaugeSpent, 2),
            'remaining' => round($dashboardGaugeRemaining, 2),
            'utilization' => round($dashboardBudgetUtilization, 1),
        ],
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    $dashboardGreeting = (int)date('G') < 12 ? 'Good morning' : ((int)date('G') < 18 ? 'Good afternoon' : 'Good evening');
    ?>
</head>

<body class="dashboard-page">
<div class="top-strip"></div>
<script src="js/theme.js?v=3"></script>
<script src="js/sidebar.js?v=15"></script>
<script type="application/json" id="dashboardChartData"><?= $dashboardChartData ?></script>

<div class="page-content">
    <header class="bg-white border-bottom sticky-top">
        <div class="dashboard-toolbar d-flex align-items-center gap-2 gap-md-3 px-3 px-lg-4 py-2">
            <button class="btn btn-outline-primary d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div>
                <h1 class="h5 fw-bold mb-0">Dashboard</h1>
            </div>
            <label class="dashboard-search input-group align-items-center px-3 ms-auto me-auto" aria-label="Search dashboard information">
                <i class="bi bi-search text-muted me-2" aria-hidden="true"></i>
                <input class="form-control border-0 bg-transparent shadow-none p-0" type="search" id="dashboardSearch" placeholder="Search activities and expenses..." autocomplete="off">
            </label>
            <div class="ms-auto header-account-area">
                <a class="header-notification" href="request.php" aria-label="<?= (int)$dashboardAttentionCount ?> requests need review">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <?php if ($dashboardAttentionCount > 0): ?><span class="header-notification-count"><?= (int)$dashboardAttentionCount ?></span><?php endif; ?>
                </a>
                <span class="header-avatar"><?= e(uinit()) ?></span>
                <div class="d-none d-md-block header-identity">
                    <div class="header-identity-name"><?= e(uname()) ?></div>
                    <div class="header-identity-role"><?= e(strtoupper(urole())) ?></div>
                </div>
                <a href="<?= is_public() ? 'login.php' : 'logout.php' ?>" class="btn btn-sm header-logout">
                    <i class="bi bi-box-arrow-right me-1"></i><?= is_public() ? 'Official Login' : 'Logout' ?>
                </a>
            </div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
        <section class="dashboard-greeting dashboard-overview d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Overview</h2>
                <p class="text-muted small mb-0"><?= e($dashboardGreeting) ?>, <?= e(uname()) ?> · <?= e(date('l, F j, Y')) ?></p>
            </div>
            <a href="announcements.php" class="btn btn-sm btn-primary px-3">
                <i class="bi bi-plus-lg me-1"></i>New activity
            </a>
        </section>

        <section class="dashboard-attention mb-3" aria-labelledby="attentionTitle">
            <span class="attention-pill" id="attentionTitle"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>Needs attention</span>
            <span class="dashboard-queue-row">
            <?php if ($dashboardPendingRegistrations): ?>
                <span><?= count($dashboardPendingRegistrations) ?> registration<?= count($dashboardPendingRegistrations) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
            <?php if ($dashboardEditRequests): ?>
                <span><?= count($dashboardEditRequests) ?> profile edit<?= count($dashboardEditRequests) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
            <?php if (!$dashboardAttentionCount): ?>
                <span>Nothing is waiting for review.</span>
            <?php endif; ?>
            </span>
            <?php if ($dashboardAttentionCount > 0): ?><a href="request.php" class="btn btn-sm btn-outline-primary ms-auto">Review requests <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a><?php endif; ?>
        </section>

        <section class="row g-3 mb-4 dashboard-stat-row" aria-label="System summary">
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold"><?= $dashboardActiveMembers ?></div>
                    <div class="text-muted">Active KK members</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold"><?= $upcomingAnnouncementCount ?></div>
                    <div class="text-muted">Upcoming events</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold"><?= (int)$dashboardAttentionCount ?></div>
                    <div class="text-muted">Pending requests</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <span class="dashboard-stat-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                    <div class="fs-2 fw-bold"><?= $dashboardBudgetPublished ? e($dashboardCurrency($dashboardBudgetAmount - $dashboardTotalSpent)) : '—' ?></div>
                    <div class="text-muted">Budget remaining</div>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-4 align-items-stretch">
        <section class="col-12 col-xl-7" aria-labelledby="upcomingTitle">
            <div class="dashboard-panel h-100">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1" id="upcomingTitle">Upcoming SK activities</h2>
                        <p class="small text-muted mb-0">Published activities scheduled for the coming days.</p>
                    </div>
                    <a href="announcements.php" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <?php if (!$upcomingAnnouncements): ?>
                    <div class="py-4 text-center text-muted small">No published activities are scheduled right now.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm dashboard-activity-table align-middle mb-0">
                            <thead><tr><th>Activity</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($upcomingAnnouncements, 0, 5) as $item): ?>
                                    <?php
                                    $activitySearch = trim(
                                        (string)($item['title'] ?? '') . ' '
                                        . (string)($item['category'] ?? '') . ' '
                                        . (string)($item['venue'] ?? '')
                                    );
                                    ?>
                                    <tr data-dashboard-search="<?= e($activitySearch) ?>">
                                        <td>
                                            <div class="fw-semibold"><?= e($item['title'] ?? 'Untitled activity') ?></div>
                                            <div class="small text-muted"><?= e($item['venue'] ?? $item['category'] ?? 'SK activity') ?></div>
                                        </td>
                                        <td class="text-nowrap"><?= e(date('M j, Y', strtotime($item['date']))) ?></td>
                                        <td><span class="dashboard-status">Upcoming</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <section class="col-12 col-xl-5" aria-labelledby="budgetGaugeTitle">
            <div class="dashboard-panel h-100">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1" id="budgetGaugeTitle">SK budget overview</h2>
                        <p class="small text-muted mb-0"><?= $dashboardBudgetYear !== '' ? 'Fiscal year ' . e($dashboardBudgetYear) : 'Recorded budget and expenses' ?></p>
                    </div>
                    <a href="reports.php" class="small text-decoration-none">Details</a>
                </div>
                <?php if ($dashboardBudgetPublished && $dashboardBudgetAmount > 0): ?>
                    <div class="dashboard-chart-wrap dashboard-chart-gauge mt-2">
                        <canvas id="budgetProgressChart" role="img" aria-label="SK budget utilization: <?= e(number_format($dashboardBudgetUtilization, 1)) ?> percent spent"></canvas>
                    </div>
                    <div class="dashboard-budget-center" aria-hidden="true">
                        <strong><?= e(number_format($dashboardBudgetUtilization, 1)) ?>%</strong>
                        <span>of budget spent</span>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mt-3">
                        <div>
                            <div class="small text-muted">Recorded expenses</div>
                            <strong><?= e($dashboardCurrency($dashboardTotalSpent)) ?></strong>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Remaining</div>
                            <strong><?= e($dashboardCurrency($dashboardBudgetAmount - $dashboardTotalSpent)) ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="py-4 text-center">
                        <div class="text-muted small">A positive published budget is needed to show utilization.</div>
                        <a href="reports.php" class="btn btn-sm btn-outline-primary mt-3">View budget transparency</a>
                    </div>
                <?php endif; ?>
                <p class="dashboard-chart-fallback mt-2 mb-0" id="budgetProgressFallback" hidden>Chart is unavailable; the budget figures are shown above.</p>
            </div>
        </section>
        </div>

        <div class="row g-3 mb-4 align-items-stretch">
        <section class="col-12 col-xl-7" aria-labelledby="programInsightsTitle">
            <div class="dashboard-panel h-100">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1" id="programInsightsTitle">Program participation</h2>
                        <p class="small text-muted mb-0">Confirmed attendance at completed activities in the past 12 months.</p>
                    </div>
                    <a href="participation.php" class="small text-decoration-none">Participation records</a>
                </div>
                <?php if (!$dashboardProgramInsights): ?>
                    <div class="py-5 text-center text-muted small">No confirmed attendance data is available yet.</div>
                <?php else: ?>
                    <div class="dashboard-chart-wrap">
                        <canvas id="programAttendanceChart" role="img" aria-label="Confirmed attendance for the most attended SK programs"></canvas>
                    </div>
                    <p class="dashboard-chart-fallback mb-0" id="programChartFallback" hidden>Chart is unavailable; see the participation records for attendance information.</p>
                <?php endif; ?>
            </div>
        </section>
        <section class="col-12 col-xl-5" aria-labelledby="ageParticipationTitle">
            <div class="dashboard-panel h-100">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                    <div>
                        <h2 class="h6 fw-bold mb-1" id="ageParticipationTitle">Age participation</h2>
                        <p class="small text-muted mb-0">Active KK members aged 15-30 by age group.</p>
                    </div>
                    <a href="members.php" class="small text-decoration-none">View members</a>
                </div>
                <?php if ($dashboardAgeParticipationCount > 0): ?>
                    <div class="dashboard-chart-wrap">
                        <canvas id="ageParticipationChart" role="img" aria-label="Active KK members by age group: <?= e(implode(', ', array_map(function ($label, $count) { return $label . ': ' . $count; }, array_keys($dashboardAgeGroups), array_values($dashboardAgeGroups)))) ?>"></canvas>
                    </div>
                    <p class="dashboard-chart-fallback mb-0" id="ageParticipationChartFallback" hidden>Chart is unavailable; age-group totals could not be displayed.</p>
                <?php else: ?>
                    <div class="py-5 text-center text-muted small">No age information is available for active KK members yet.</div>
                <?php endif; ?>
            </div>
        </section>
        </div>

        <section class="dashboard-panel mb-4" aria-labelledby="recentExpensesTitle">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-1" id="recentExpensesTitle">Recent SK expenses</h2>
                    <p class="small text-muted mb-0">Latest expense records for the selected fiscal year.</p>
                </div>
                <a href="reports.php" class="small text-decoration-none">View all</a>
            </div>
            <?php if (!$dashboardExpenseRows): ?>
                <p class="small text-muted mb-0 py-3">No expenses have been recorded for this fiscal year.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th scope="col">Date</th><th scope="col">Activity / expense</th><th scope="col">Type</th><th scope="col" class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <?php foreach ($dashboardExpenseRows as $expense): ?>
                                <?php $expenseSearch = trim($expense['title'] . ' ' . $expense['category']); ?>
                                <tr data-dashboard-search="<?= e($expenseSearch) ?>">
                                    <td class="text-nowrap"><?= e($expense['date']) ?></td>
                                    <td><?= e($expense['title']) ?></td>
                                    <td><?= e($expense['category']) ?></td>
                                    <td class="text-end text-nowrap"><?= e($dashboardCurrency($expense['amount'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">Showing the five most recent expenses. Recipient and description fields are not displayed here.</p>
            <?php endif; ?>
        </section>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/app-alerts.js?v=2"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var dataNode = document.getElementById('dashboardChartData');
        var chartData = JSON.parse(dataNode.textContent || '{}');
        var brand = getComputedStyle(document.documentElement).getPropertyValue('--ui-brand').trim() || '#2563eb';
        var grid = getComputedStyle(document.documentElement).getPropertyValue('--ui-line').trim() || '#e5eaf1';

        if (typeof Chart === 'undefined') {
            var programFallback = document.getElementById('programChartFallback');
            var ageParticipationFallback = document.getElementById('ageParticipationChartFallback');
            var budgetProgressFallback = document.getElementById('budgetProgressFallback');
            if (programFallback) programFallback.hidden = false;
            if (ageParticipationFallback) ageParticipationFallback.hidden = false;
            if (budgetProgressFallback) budgetProgressFallback.hidden = false;
            return;
        }

        var progressCanvas = document.getElementById('budgetProgressChart');
        if (progressCanvas && chartData.budgetProgress) {
            new Chart(progressCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Recorded expenses', 'Remaining budget'],
                    datasets: [{
                        data: [chartData.budgetProgress.spent, chartData.budgetProgress.remaining],
                        backgroundColor: [chartData.budgetProgress.utilization > 100 ? '#dc3545' : brand, grid],
                        borderWidth: 0,
                        hoverOffset: 3
                    }]
                },
                options: {
                    animation: { duration: 180 },
                    circumference: 180,
                    cutout: '78%',
                    maintainAspectRatio: false,
                    rotation: 270,
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' ' + context.label + ': ₱' + Number(context.raw).toLocaleString('en-PH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    }
                }
            });
        }

        var programCanvas = document.getElementById('programAttendanceChart');
        if (programCanvas && chartData.programs && chartData.programs.length) {
            new Chart(programCanvas, {
                type: 'bar',
                data: {
                    labels: chartData.programs.map(function (item) { return item.label; }),
                    datasets: [{
                        data: chartData.programs.map(function (item) { return item.value; }),
                        backgroundColor: brand,
                        borderRadius: 4,
                        maxBarThickness: 26
                    }]
                },
                options: {
                    animation: { duration: 180 },
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) { return ' Confirmed attendance: ' + context.parsed.x; }
                            }
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        var ageParticipationCanvas = document.getElementById('ageParticipationChart');
        if (ageParticipationCanvas && chartData.ageGroups && chartData.ageGroups.length) {
            new Chart(ageParticipationCanvas, {
                type: 'doughnut',
                data: {
                    labels: chartData.ageGroups.map(function (item) { return item.label; }),
                    datasets: [{
                        data: chartData.ageGroups.map(function (item) { return item.value; }),
                        backgroundColor: [brand, '#60a5fa', '#123b73'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    animation: { duration: 180 },
                    maintainAspectRatio: false,
                    responsive: true,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' ' + context.label + ': ' + Number(context.raw).toLocaleString('en-PH') + ' members';
                                }
                            }
                        }
                    }
                }
            });
        }

        var dashboardSearch = document.getElementById('dashboardSearch');
        if (dashboardSearch) {
            dashboardSearch.addEventListener('input', function () {
                var query = dashboardSearch.value.trim().toLocaleLowerCase();
                document.querySelectorAll('[data-dashboard-search]').forEach(function (row) {
                    var value = (row.getAttribute('data-dashboard-search') || row.textContent).toLocaleLowerCase();
                    row.hidden = query !== '' && value.indexOf(query) === -1;
                });
            });
        }
    })();
</script>
</body>
</html>
