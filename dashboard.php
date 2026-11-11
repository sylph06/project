<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="sidebar.css?v=20" rel="stylesheet">
    <style>
        .dashboard-hero{
            color:var(--bs-body-color);
            background:var(--bs-white);
            border:1px solid var(--bs-border-color);
            border-radius:.5rem;
            padding:clamp(1.5rem,4vw,2.5rem);
        }
        .dashboard-stat,.dashboard-panel{
            height:100%;
            background:var(--bs-white);
            border:1px solid var(--bs-border-color);
            border-radius:.5rem;
        }
        .dashboard-stat{padding:1.25rem}
        .dashboard-stat-icon{
            display:grid;
            place-items:center;
            width:2.75rem;
            height:2.75rem;
            border-radius:.5rem;
            color:var(--bs-primary-text-emphasis);
            background:var(--bs-primary-bg-subtle);
            font-size:1.25rem;
        }
        .dashboard-panel{padding:1.35rem;overflow:hidden}
        .dashboard-greeting{max-width:1080px}
        .dashboard-attention{
            background:var(--ui-surface);
            border:1px solid var(--ui-line);
            border-radius:var(--ui-radius);
            box-shadow:var(--ui-shadow);
            overflow:hidden;
        }
        .attention-pill{
            display:inline-flex;
            align-items:center;
            padding:.35rem .8rem;
            border-radius:999px;
            background:#f7e0d9;
            color:#c24a33;
            font-size:.8rem;
            font-weight:800;
        }
        .dashboard-queue-row{
            display:flex;
            align-items:center;
            gap:1rem;
            padding:1rem 1.25rem;
            border-top:1px solid var(--ui-line);
        }
        .dashboard-stat{
            min-height:118px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            padding:1.25rem;
        }
        .dashboard-stat .fs-2{
            letter-spacing:-.04em;
        }
        .dashboard-stat-row .dashboard-stat{
            border-radius:var(--ui-radius);
        }
        .budget-stat{
            height:100%;
            padding:1rem;
            border:1px solid var(--bs-border-color);
            border-radius:.8rem;
            background:var(--bs-tertiary-bg);
        }
        .announcement-row+.announcement-row{border-top:1px solid var(--bs-border-color)}
        .quick-link{
            display:flex;
            align-items:center;
            gap:.8rem;
            padding:.8rem;
            color:var(--bs-body-color);
            text-decoration:none;
            border:1px solid var(--bs-border-color);
            border-radius:.5rem;
        }
        .quick-link:hover{color:var(--bs-primary-text-emphasis);background:var(--bs-tertiary-bg)}
    </style>
    <?php include __DIR__ . '/init.php'; ?>
    <?php
    $dashboardMembers = json_decode($__data['kkMembers'] ?? '[]', true);
    $dashboardMembers = is_array($dashboardMembers) ? $dashboardMembers : [];
    $dashboardActiveMembers = count(array_filter($dashboardMembers, function ($member) {
        return is_array($member) && ($member['status'] ?? '') === 'Active';
    }));
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
    $dashboardBudgetPublished = isset($dashboardBudget['amount']) && is_numeric($dashboardBudget['amount']);
    $dashboardBudgetAmount = $dashboardBudgetPublished ? max(0, (float)$dashboardBudget['amount']) : 0;
    $dashboardExpenses = json_decode($__data['skTransparencyExpenses'] ?? '[]', true);
    $dashboardExpenses = is_array($dashboardExpenses) ? $dashboardExpenses : [];
    $dashboardExpenseRows = [];
    $dashboardCategoryTotals = [];
    $dashboardTotalSpent = 0;
    foreach ($dashboardExpenses as $expense) {
        if (!is_array($expense) || !isset($expense['amount']) || !is_numeric($expense['amount'])) continue;
        if ($dashboardBudgetYear !== '' && !empty($expense['fiscalYear'])
            && (string)$expense['fiscalYear'] !== $dashboardBudgetYear) continue;
        $amount = max(0, (float)$expense['amount']);
        $category = trim((string)($expense['category'] ?? '')) ?: 'Other';
        $dashboardTotalSpent += $amount;
        $dashboardCategoryTotals[$category] = ($dashboardCategoryTotals[$category] ?? 0) + $amount;
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
    arsort($dashboardCategoryTotals, SORT_NUMERIC);
    $dashboardEditRequests = json_decode($__data['kkEditRequests'] ?? '[]', true);
    $dashboardEditRequests = is_array($dashboardEditRequests) ? array_values(array_filter($dashboardEditRequests, function ($item) {
        return is_array($item) && ($item['status'] ?? '') === 'Edit Request';
    })) : [];
    $dashboardPendingRegistrations = json_decode($__data['kkPendingRegistrations'] ?? '[]', true);
    $dashboardPendingRegistrations = is_array($dashboardPendingRegistrations) ? array_values(array_filter($dashboardPendingRegistrations, function ($item) {
        return is_array($item) && ($item['status'] ?? '') === 'Pending';
    })) : [];
    $dashboardAttentionCount = count($dashboardEditRequests) + count($dashboardPendingRegistrations);
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
    ?>
</head>

<body class="dashboard-page">
<div class="top-strip"></div>
<script src="theme.js"></script>
<script src="sidebar.js?v=10"></script>

<div class="page-content">
    <header class="bg-white border-bottom sticky-top">
        <div class="d-flex align-items-center gap-2 gap-md-3 px-3 px-lg-4 py-2 py-lg-3">
            <button class="btn btn-outline-success d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div>
                <h1 class="h5 fw-bold mb-0">Dashboard</h1>
                <div class="text-muted small">KK Youth Profiling System</div>
            </div>
            <div class="ms-auto header-account-area">
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
        <section class="dashboard-greeting d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <h2 class="h3 fw-bold mb-1">Good morning, <?= e(uname()) ?></h2>
                <p class="text-muted mb-0"><?= e(date('l, F j, Y')) ?></p>
            </div>
            <a href="announcements.php" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i>New announcement
            </a>
        </section>

        <section class="dashboard-attention mb-4" aria-labelledby="attentionTitle">
            <div class="d-flex align-items-center gap-2 px-3 pt-3 pb-2">
                <span class="attention-pill">Needs attention</span>
                <span class="text-muted small"><?= (int)$dashboardAttentionCount ?> items waiting</span>
            </div>
            <?php if ($dashboardPendingRegistrations): ?>
                <div class="dashboard-queue-row">
                    <i class="bi bi-person-plus text-primary fs-5"></i>
                    <div class="flex-grow-1"><strong><?= count($dashboardPendingRegistrations) ?> new youth registration<?= count($dashboardPendingRegistrations) === 1 ? '' : 's' ?></strong><div class="text-muted small">Waiting for review</div></div>
                    <a href="request.php" class="btn btn-outline-secondary btn-sm px-3">Review</a>
                </div>
            <?php endif; ?>
            <?php if ($dashboardEditRequests): ?>
                <div class="dashboard-queue-row">
                    <i class="bi bi-pencil-square text-warning fs-5"></i>
                    <div class="flex-grow-1"><strong><?= count($dashboardEditRequests) ?> profile edit request<?= count($dashboardEditRequests) === 1 ? '' : 's' ?></strong><div class="text-muted small">Member information needs review</div></div>
                    <a href="request.php" class="btn btn-outline-secondary btn-sm px-3">Review</a>
                </div>
            <?php endif; ?>
            <?php if (!$dashboardAttentionCount): ?>
                <div class="dashboard-queue-row text-muted">Nothing needs your attention right now.</div>
            <?php endif; ?>
        </section>

        <section class="row g-3 mb-4 dashboard-stat-row" aria-label="System summary">
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="fs-2 fw-bold"><?= $dashboardActiveMembers ?></div>
                    <div class="text-muted">Active KK members</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="fs-2 fw-bold"><?= $upcomingAnnouncementCount ?></div>
                    <div class="text-muted">Upcoming events</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="fs-2 fw-bold"><?= count($dashboardExpenseRows) ?></div>
                    <div class="text-muted">Recent expense records</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="fs-2 fw-bold"><?= $dashboardBudgetPublished ? e($dashboardCurrency($dashboardBudgetAmount - $dashboardTotalSpent)) : '—' ?></div>
                    <div class="text-muted">Budget remaining</div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel mb-4" aria-labelledby="programInsightsTitle">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1" id="programInsightsTitle">
                        <i class="bi bi-fire text-primary me-2"></i>Program insights
                    </h2>
                    <p class="small text-muted mb-0">
                        Most attended completed programs from the last 12 months. Use this as a planning signal, not a guarantee of future turnout.
                    </p>
                </div>
                <a href="reports.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-bar-chart me-1"></i>View reports
                </a>
            </div>
            <?php if (!$dashboardProgramInsights): ?>
                <div class="text-muted small">No confirmed attendance records are available for completed programs in the last 12 months.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Program / event</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th class="text-end">Confirmed attendance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboardProgramInsights as $index => $program): ?>
                                <tr>
                                    <td class="fw-bold"><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= e($program['title']) ?></td>
                                    <td><?= e($program['category']) ?></td>
                                    <td><?= e($program['date']) ?></td>
                                    <td class="text-end fw-bold"><?= (int)$program['attendance'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel mb-4" aria-labelledby="budgetTransparencyTitle">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1" id="budgetTransparencyTitle">
                        <i class="bi bi-shield-check text-primary me-2"></i>Budget transparency
                    </h2>
                    <p class="small text-muted mb-0">
                        <?php if (!empty($dashboardBudget['fy'])): ?>
                            Fiscal year <?= e($dashboardBudget['fy']) ?>
                        <?php else: ?>
                            SK budget and recorded expenses
                        <?php endif; ?>
                    </p>
                </div>
                <a href="reports.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i>Manage budget &amp; reports
                </a>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-md-4">
                    <div class="budget-stat">
                        <div class="small text-muted mb-1">Available budget</div>
                        <div class="h4 fw-bold mb-0"><?= $dashboardBudgetPublished ? e($dashboardCurrency($dashboardBudgetAmount)) : 'Not published' ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="budget-stat">
                        <div class="small text-muted mb-1">Recorded expenses</div>
                        <div class="h4 fw-bold mb-0"><?= e($dashboardCurrency($dashboardTotalSpent)) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="budget-stat">
                        <div class="small text-muted mb-1">Remaining balance</div>
                        <div class="h4 fw-bold mb-0"><?= $dashboardBudgetPublished ? e($dashboardCurrency($dashboardBudgetAmount - $dashboardTotalSpent)) : '—' ?></div>
                    </div>
                </div>
            </div>

            <details class="mt-3">
                <summary class="fw-semibold">View spending breakdown and recent expenses</summary>
                <div class="row g-4 mt-1">
                <div class="col-lg-4">
                    <h3 class="h6 fw-bold">Spending by category</h3>
                    <?php if (!$dashboardCategoryTotals): ?>
                        <p class="small text-muted mb-0">No expenses recorded yet.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($dashboardCategoryTotals as $category => $amount): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                                    <span><?= e($category) ?></span>
                                    <strong class="text-nowrap"><?= e($dashboardCurrency($amount)) ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="col-lg-8">
                    <h3 class="h6 fw-bold">Recent expense records</h3>
                    <?php if (!$dashboardExpenseRows): ?>
                        <p class="small text-muted mb-0">No expenses recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th scope="col">Date</th><th scope="col">Program / expense</th><th scope="col">Category</th><th scope="col" class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    <?php foreach ($dashboardExpenseRows as $expense): ?>
                                        <tr>
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
                </div>
                </div>
            </details>
        </section>

        <div class="row g-3">
            <section class="col-12" aria-labelledby="upcomingTitle">
                <div class="dashboard-panel">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h2 class="h5 fw-bold mb-0" id="upcomingTitle">Upcoming activities</h2>
                        <a href="announcements.php" class="small fw-semibold text-decoration-none">View announcements</a>
                    </div>
                    <?php if (!$upcomingAnnouncements): ?>
                        <p class="text-muted mb-0 py-3">There are no published upcoming activities right now.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingAnnouncements as $item): ?>
                            <article class="announcement-row py-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h3 class="h6 fw-bold mb-1"><?= e($item['title'] ?? 'Untitled activity') ?></h3>
                                        <div class="small text-muted">
                                            <?= e($item['category'] ?? 'Announcement') ?>
                                            <?php if (!empty($item['venue'])): ?> · <?= e($item['venue']) ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <time class="small text-nowrap fw-semibold" datetime="<?= e($item['date']) ?>">
                                        <?= e(date('M j, Y', strtotime($item['date']))) ?>
                                    </time>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="app-alerts.js?v=2"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
