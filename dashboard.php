<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">
    <style>
        .dashboard-hero{
            color:#fff;
            background:linear-gradient(135deg,var(--bs-primary),var(--bs-primary-text-emphasis));
            border-radius:1.25rem;
            padding:clamp(1.5rem,4vw,2.5rem);
        }
        .dashboard-stat,.dashboard-panel{
            height:100%;
            background:var(--bs-white);
            border:1px solid var(--bs-border-color);
            border-radius:1rem;
            box-shadow:0 .25rem 1rem rgba(0,0,0,.04);
        }
        .dashboard-stat{padding:1.25rem}
        .dashboard-stat-icon{
            display:grid;
            place-items:center;
            width:2.75rem;
            height:2.75rem;
            border-radius:.85rem;
            color:var(--bs-primary-text-emphasis);
            background:var(--bs-primary-bg-subtle);
            font-size:1.25rem;
        }
        .dashboard-panel{padding:1.35rem}
        .announcement-row+.announcement-row{border-top:1px solid var(--bs-border-color)}
        .quick-link{
            display:flex;
            align-items:center;
            gap:.8rem;
            padding:.8rem;
            color:var(--bs-body-color);
            text-decoration:none;
            border:1px solid var(--bs-border-color);
            border-radius:.8rem;
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
    ?>
</head>

<body>
<div class="top-strip"></div>
<script src="sidebar.js?v=3"></script>

<div class="page-content">
    <header class="bg-white border-bottom sticky-top">
        <div class="d-flex align-items-center gap-3 px-3 px-lg-4 py-3">
            <button class="btn btn-outline-success d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div>
                <h1 class="h5 fw-bold mb-0">Dashboard</h1>
                <div class="text-muted small">KK Youth Profiling System</div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="d-none d-sm-block text-end lh-sm">
                    <div class="fw-semibold small"><?= e(uname()) ?></div>
                    <div class="text-muted" style="font-size:.75rem;"><?= e(urole()) ?></div>
                </div>
                <a href="logout.php" class="btn btn-success btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i><?= is_public() ? 'Official Login' : 'Logout' ?>
                </a>
            </div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
        <section class="dashboard-hero mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg">
                    <div class="text-white-50 small fw-semibold text-uppercase mb-2">Barangay Sikat · Alfonso, Cavite</div>
                    <h2 class="h3 fw-bold mb-2">Welcome, <?= e(uname()) ?></h2>
                    <p class="mb-0">A quick overview of KK youth records, announcements, and upcoming SK activities.</p>
                </div>
                <div class="col-lg-auto">
                    <a href="registration.php" class="btn btn-light fw-semibold">
                        <i class="bi bi-person-plus me-1"></i>KK Youth Registration
                    </a>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4" aria-label="System summary">
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="dashboard-stat-icon mb-3"><i class="bi bi-people"></i></div>
                    <div class="text-muted small">Registered KK youth</div>
                    <div class="fs-3 fw-bold"><?= count($dashboardMembers) ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="dashboard-stat-icon mb-3"><i class="bi bi-person-check"></i></div>
                    <div class="text-muted small">Active members</div>
                    <div class="fs-3 fw-bold"><?= $dashboardActiveMembers ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="dashboard-stat-icon mb-3"><i class="bi bi-megaphone"></i></div>
                    <div class="text-muted small">Published announcements</div>
                    <div class="fs-3 fw-bold"><?= count($publishedAnnouncements) ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dashboard-stat">
                    <div class="dashboard-stat-icon mb-3"><i class="bi bi-calendar-event"></i></div>
                    <div class="text-muted small">Upcoming activities</div>
                    <div class="fs-3 fw-bold"><?= $upcomingAnnouncementCount ?></div>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <section class="col-lg-7" aria-labelledby="upcomingTitle">
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

            <section class="col-lg-5" aria-labelledby="quickLinksTitle">
                <div class="dashboard-panel">
                    <h2 class="h5 fw-bold mb-3" id="quickLinksTitle">Quick links</h2>
                    <div class="d-grid gap-2">
                        <a class="quick-link" href="registration.php">
                            <i class="bi bi-person-plus fs-5 text-primary"></i><span>KK Youth Registration</span>
                        </a>
                        <a class="quick-link" href="members.php">
                            <i class="bi bi-people fs-5 text-primary"></i><span>Member List</span>
                        </a>
                        <a class="quick-link" href="announcements.php">
                            <i class="bi bi-megaphone fs-5 text-primary"></i><span>Announcements &amp; Events</span>
                        </a>
                        <a class="quick-link" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph fs-5 text-primary"></i><span>Reports &amp; Analytics</span>
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
