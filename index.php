<?php
/* =========================================================
   index.php  - PUBLIC LANDING PAGE
   Open to everyone: no login and no "public viewer" click needed.
   Built on the server. It only shows public-safe information:
   counts, published upcoming activities, and SK official names/photos.
   Put this file in the same folder as config.php.
   ========================================================= */
require_once __DIR__ . '/config.php';
open_to_everyone();

$POSITIONS = ['SK Chairperson', 'SK Vice Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad'];

function lp_json($key) {
    $st = db()->prepare('SELECT data_value FROM app_data WHERE data_key = ?');
    $st->execute([$key]);
    $v = json_decode((string)$st->fetchColumn(), true);
    return is_array($v) ? $v : [];
}

$today = date('Y-m-d');
$memberCount = 0;
$upcoming = [];
$upcomingCount = 0;
$officials = [];
$budget = ['fy' => '', 'amount' => 0.0];
$hasBudget = false;
$budgetExpenses = [];
$budgetSpent = 0.0;
$budgetCategories = [];

try {
    /* ---- youth count (a number only, no personal details) ---- */
    $members = [];
    foreach (lp_json('kkMembers') as $m) {
        if (is_array($m) && !empty($m['id'])) $members[(string)$m['id']] = $m;
    }
    foreach ($members as $m) { if (($m['status'] ?? '') !== 'Archived') $memberCount++; }

    /* ---- published, upcoming activities ---- */
    foreach (lp_json('skAnnouncements') as $a) {
        if (!is_array($a)) continue;
        if (($a['publishState'] ?? '') !== 'Published') continue;
        if (!empty($a['publishDate']) && $a['publishDate'] > $today) continue;
        if (empty($a['date']) || $a['date'] < $today || !empty($a['cancelled'])) continue;
        $upcoming[] = $a;
    }
    usort($upcoming, function ($a, $b) { return strcmp((string)$a['date'], (string)$b['date']); });
    $upcomingCount = count($upcoming);
    $upcoming = array_slice($upcoming, 0, 3);

    /* ---- SK officials: name, position, photo only ---- */
    $seen = [];
    foreach (lp_json('skRoles') as $r) {
        if (!is_array($r)) continue;
        $kid = (string)($r['kkId'] ?? '');
        if (!isset($members[$kid]) || isset($seen[$kid])) continue;
        $m = $members[$kid];
        if (($r['status'] ?? '') !== 'Active' || ($m['status'] ?? '') === 'Archived') continue;
        if (!empty($r['termEnd']) && $r['termEnd'] < $today) continue;
        if (!in_array($r['position'] ?? '', $POSITIONS, true)) continue;
        $seen[$kid] = 1;
        $name = trim(($m['firstName'] ?? '') . ' ' . ($m['lastName'] ?? '') . (!empty($m['suffix']) ? ' ' . $m['suffix'] : ''));
        $photo = (string)($m['photo'] ?? '');
        if (!preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=]+$#', $photo)) $photo = '';
        $parts = preg_split('/\s+/', $name);
        $ini = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        $officials[] = ['position' => $r['position'], 'name' => $name, 'photo' => $photo, 'initials' => $ini,
                        'order' => array_search($r['position'], $POSITIONS, true)];
    }
    usort($officials, function ($a, $b) { return $a['order'] <=> $b['order'] ?: strcmp($a['name'], $b['name']); });

    /* ---- public budget summary and expense categories ---- */
    $savedBudget = lp_json('skTransparencyBudget');
    if (isset($savedBudget['amount']) && is_numeric($savedBudget['amount'])) {
        $hasBudget = true;
        $budget['amount'] = max(0, (float)$savedBudget['amount']);
        $budget['fy'] = trim((string)($savedBudget['fy'] ?? ''));
    }
    foreach (lp_json('skTransparencyExpenses') as $expense) {
        if (!is_array($expense) || !isset($expense['amount']) || !is_numeric($expense['amount'])) continue;
        $amount = max(0, (float)$expense['amount']);
        $budgetSpent += $amount;
        $category = trim((string)($expense['category'] ?? '')) ?: 'Other';
        $budgetCategories[$category] = ($budgetCategories[$category] ?? 0) + $amount;
        $budgetExpenses[] = [
            'date' => (string)($expense['date'] ?? ''),
            'title' => trim((string)($expense['title'] ?? '')) ?: 'Expense',
            'category' => $category,
            'amount' => $amount,
        ];
    }
    usort($budgetExpenses, function ($a, $b) { return strcmp($b['date'], $a['date']); });
    arsort($budgetCategories, SORT_NUMERIC);
} catch (Throwable $ex) { /* the landing page must still open if the database is busy */ }

$officialCount = count($officials);
$officials = array_slice($officials, 0, 5);
$next = $upcoming[0] ?? null;
$landingUser = current_user();
$isOfficer = $landingUser !== null && ($landingUser['role'] ?? '') !== 'KK Member';

/* links: ?view=public lets a first-time visitor open view-only pages without a login screen */
$L_REGISTER = 'registration.php?view=public';
$L_EVENTS   = 'announcements.php?view=public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sangguniang Kabataan of Barangay Sikat | KK Youth Profiling System</title>
    <meta name="description" content="Register as a KK youth, see upcoming SK activities and meet your youth officials in Barangay Sikat, Alfonso, Cavite.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root{
            --lp-display:"Bricolage Grotesque",Arial,sans-serif;
            --lp-ink:var(--bs-body-color);
            --lp-line:var(--bs-border-color);
        }
        html{scroll-behavior:smooth;scroll-padding-top:5rem}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
        body{background:var(--bs-white);color:var(--lp-ink);line-height:1.6}
        h1,h2,h3,.display-font{font-family:var(--lp-display);letter-spacing:-.02em}
        a:focus-visible,button:focus-visible{outline:3px solid var(--bs-primary);outline-offset:3px}

        /* nav */
        .lp-nav{background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-bottom:1px solid var(--lp-line)}
        .lp-logo{width:42px;height:42px;border-radius:50%;object-fit:cover}
        .lp-nav .nav-link{color:var(--lp-ink);font-weight:600}
        .lp-nav .nav-link:hover{color:var(--bs-primary)}

        /* hero */
        .lp-hero{background:var(--bs-primary);color:#fff;position:relative;overflow:hidden}
        .lp-hero::after{ /* a large, quiet SK logo mark */
            content:"";position:absolute;right:-8rem;bottom:-9rem;width:30rem;height:30rem;border-radius:50%;
            background:url("images/sk-logo.jpg") center/cover;opacity:.08;pointer-events:none}
        .lp-hero .container{position:relative;z-index:1}
        .lp-place{color:rgba(255,255,255,.8);font-weight:600}
        .lp-hero h1{font-size:clamp(2.4rem,6vw,4.4rem);font-weight:800;line-height:1.02;max-width:13ch}
        .lp-hero .lead{color:rgba(255,255,255,.9);max-width:34rem}
        .btn-lp-light{background:#fff;color:var(--bs-primary-text-emphasis);font-weight:700;border:2px solid #fff}
        .btn-lp-light:hover{background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis);border-color:var(--bs-primary-bg-subtle)}
        .btn-lp-ghost{color:#fff;border:2px solid rgba(255,255,255,.7);font-weight:700}
        .btn-lp-ghost:hover{color:var(--bs-primary-text-emphasis);background:#fff;border-color:#fff}

        /* "next up" ticket: the one memorable element */
        .lp-ticket{background:#fff;color:var(--lp-ink);border-radius:1.25rem;overflow:hidden;box-shadow:0 1.5rem 3rem rgba(0,0,0,.25)}
        .lp-ticket-top{display:flex;gap:1.1rem;padding:1.5rem 1.5rem 1.25rem;align-items:center}
        .lp-datebox{flex:0 0 auto;width:5.2rem;text-align:center;border-radius:1rem;background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis);padding:.6rem 0}
        .lp-datebox .mo{font-weight:700;font-size:.95rem}
        .lp-datebox .dy{font-family:var(--lp-display);font-weight:800;font-size:2.4rem;line-height:1}
        .lp-ticket-cut{border-top:2px dashed var(--lp-line);position:relative}
        .lp-ticket-cut::before,.lp-ticket-cut::after{content:"";position:absolute;top:-.8rem;width:1.6rem;height:1.6rem;border-radius:50%;background:var(--bs-primary)}
        .lp-ticket-cut::before{left:-.8rem}.lp-ticket-cut::after{right:-.8rem}
        .lp-ticket-bottom{padding:1.1rem 1.5rem 1.5rem}

        /* facts row */
        .lp-facts{border-bottom:1px solid var(--lp-line)}
        .lp-fact{padding:1.6rem 0}
        .lp-fact .n{font-family:var(--lp-display);font-weight:800;font-size:2.2rem;line-height:1;color:var(--bs-primary-text-emphasis)}
        @media(min-width:768px){.lp-fact+.lp-fact{border-left:1px solid var(--lp-line);padding-left:2rem}}

        /* sections */
        .lp-section{padding:clamp(3rem,7vw,5.5rem) 0}
        .lp-section h2{font-weight:800;font-size:clamp(1.8rem,4vw,2.6rem)}
        .lp-muted-bg{background:var(--bs-tertiary-bg)}

        /* activity list */
        .lp-event{display:flex;gap:1.2rem;align-items:center;padding:1.15rem 0;border-top:1px solid var(--lp-line)}
        .lp-event:last-child{border-bottom:1px solid var(--lp-line)}
        .lp-event .lp-datebox{width:4.4rem}
        .lp-event .lp-datebox .dy{font-size:1.9rem}

        /* public budget transparency */
        .lp-budget-card{height:100%;padding:1.5rem;background:#fff;border:1px solid var(--lp-line);border-radius:1rem}
        .lp-budget-value{font-family:var(--lp-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:800;line-height:1.2}
        .lp-budget-table th,.lp-budget-table td{padding:.75rem .5rem;vertical-align:middle}

        /* steps (a real sequence: apply, review, listed) */
        .lp-step{display:flex;gap:1rem;padding:1.1rem 0}
        .lp-step+.lp-step{border-top:1px solid var(--lp-line)}
        .lp-stepnum{flex:0 0 auto;width:2.4rem;height:2.4rem;border-radius:50%;background:var(--bs-primary);color:#fff;
            display:grid;place-items:center;font-family:var(--lp-display);font-weight:800}

        /* officials */
        .lp-off{text-align:center}
        .lp-off .pic{width:104px;height:104px;border-radius:50%;object-fit:cover;margin:0 auto .8rem;display:block;border:3px solid var(--bs-primary-bg-subtle)}
        .lp-off .ph{display:grid;place-items:center;background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis);font-family:var(--lp-display);font-weight:800;font-size:2rem}
        .lp-off .nm{font-weight:700;line-height:1.25}
        .lp-off .pos{color:var(--bs-primary);font-weight:600;font-size:.88rem}

        /* closing band + footer */
        .lp-cta{background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis)}
        .lp-footer{background:var(--bs-primary-text-emphasis);color:rgba(255,255,255,.8);font-size:.9rem}
        .lp-footer a{color:#fff}
    </style>
</head>
<body>

<!-- ============ NAV ============ -->
<nav class="lp-nav navbar navbar-expand-md sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 py-0" href="index.php">
            <img src="images/sk-logo.jpg" alt="" class="lp-logo">
            <span class="lh-1">
                <span class="d-block fw-bold display-font" style="font-size:1rem">SK Barangay Sikat</span>
                <span class="d-block text-secondary" style="font-size:.75rem">KK Youth Profiling</span>
            </span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#lpMenu" aria-controls="lpMenu" aria-expanded="false" aria-label="Open menu">
            <i class="bi bi-list fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="lpMenu">
            <ul class="navbar-nav ms-auto align-items-md-center gap-md-2 py-3 py-md-0">
                <li class="nav-item"><a class="nav-link" href="#activities">Activities</a></li>
                <li class="nav-item"><a class="nav-link" href="#budget">Budget</a></li>
                <li class="nav-item"><a class="nav-link" href="#join">How to join</a></li>
                <li class="nav-item"><a class="nav-link" href="#officials">Officials</a></li>
                <li class="nav-item ms-md-2">
                    <?php if ($isOfficer): ?>
                        <a class="btn btn-primary btn-sm px-3" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-1"></i>Open dashboard</a>
                    <?php else: ?>
                        <a class="btn btn-outline-primary btn-sm px-3" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Official login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ============ HERO ============ -->
<header class="lp-hero">
    <div class="container py-5">
        <div class="row align-items-center g-5 py-lg-4">
            <div class="col-lg-7">
                <p class="lp-place mb-3">Barangay Sikat, Alfonso, Cavite</p>
                <h1 class="mb-3">Every young voice in Sikat, counted.</h1>
                <p class="lead mb-4">Register as a Katipunan ng Kabataan member, find out what the Sangguniang Kabataan is organizing, and sign up for events. It is free and takes about five minutes.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= e($L_REGISTER) ?>" class="btn btn-lp-light btn-lg px-4"><i class="bi bi-person-plus-fill me-2"></i>Register as KK youth</a>
                    <a href="<?= e($L_EVENTS) ?>" class="btn btn-lp-ghost btn-lg px-4"><i class="bi bi-megaphone me-2"></i>See announcements</a>
                </div>
                <p class="small mt-3 mb-0" style="color:rgba(255,255,255,.8)"><i class="bi bi-info-circle me-1"></i>For youth aged 15 to 30 who live in the barangay.</p>
            </div>

            <div class="col-lg-5">
                <div class="lp-ticket" role="region" aria-label="Next SK activity">
                    <?php if ($next): ?>
                        <div class="lp-ticket-top">
                            <div class="lp-datebox" aria-hidden="true">
                                <div class="mo"><?= e(date('M', strtotime($next['date']))) ?></div>
                                <div class="dy"><?= e(date('j', strtotime($next['date']))) ?></div>
                            </div>
                            <div>
                                <div class="small text-secondary fw-semibold mb-1">Next SK activity</div>
                                <h2 class="h4 fw-bold mb-1"><?= e($next['title'] ?? 'Upcoming activity') ?></h2>
                                <div class="small text-secondary">
                                    <?= e(date('l, F j, Y', strtotime($next['date']))) ?>
                                    <?php if (!empty($next['startTime'])): ?><br><?= e($next['startTime']) ?><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="lp-ticket-cut"></div>
                        <div class="lp-ticket-bottom">
                            <?php if (!empty($next['venue'])): ?>
                                <div class="mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i><?= e($next['venue']) ?></div>
                            <?php endif; ?>
                            <a href="<?= e($L_EVENTS) ?>" class="btn btn-primary w-100 fw-semibold">View details and sign up</a>
                        </div>
                    <?php else: ?>
                        <div class="lp-ticket-top">
                            <div class="lp-datebox" aria-hidden="true"><div class="dy"><i class="bi bi-calendar-event fs-2"></i></div></div>
                            <div>
                                <div class="small text-secondary fw-semibold mb-1">Next SK activity</div>
                                <h2 class="h4 fw-bold mb-1">Nothing scheduled yet</h2>
                                <div class="small text-secondary">New activities are posted here as soon as the SK publishes them.</div>
                            </div>
                        </div>
                        <div class="lp-ticket-cut"></div>
                        <div class="lp-ticket-bottom">
                            <a href="<?= e($L_REGISTER) ?>" class="btn btn-primary w-100 fw-semibold">Register so you hear first</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ============ FACTS ============ -->
<section class="lp-facts" aria-label="Barangay youth at a glance">
    <div class="container">
        <div class="row">
            <div class="col-md-4 lp-fact">
                <div class="n"><?= (int)$memberCount ?></div>
                <div class="text-secondary">youth registered in the KK list</div>
            </div>
            <div class="col-md-4 lp-fact">
                <div class="n"><?= (int)$upcomingCount ?></div>
                <div class="text-secondary">upcoming SK <?= $upcomingCount === 1 ? 'activity' : 'activities' ?></div>
            </div>
            <div class="col-md-4 lp-fact">
                <div class="n"><?= (int)$officialCount ?></div>
                <div class="text-secondary">SK officials serving this term</div>
            </div>
        </div>
    </div>
</section>

<!-- ============ ACTIVITIES ============ -->
<section class="lp-section" id="activities">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h2 class="mb-3">What is coming up</h2>
                <p class="text-secondary mb-4">Sports, clean-up drives, learning sessions and more. You do not need an account to sign up for an event.</p>
                <a href="<?= e($L_EVENTS) ?>" class="btn btn-outline-primary fw-semibold">All announcements</a>
            </div>
            <div class="col-lg-8">
                <?php if (!$upcoming): ?>
                    <div class="text-center text-secondary py-5">
                        <i class="bi bi-calendar-event fs-1 d-block mb-2"></i>
                        No activities are posted right now. Please check back soon.
                    </div>
                <?php else: foreach ($upcoming as $ev): ?>
                    <a class="lp-event text-decoration-none" href="<?= e($L_EVENTS) ?>" style="color:inherit">
                        <div class="lp-datebox" aria-hidden="true">
                            <div class="mo"><?= e(date('M', strtotime($ev['date']))) ?></div>
                            <div class="dy"><?= e(date('j', strtotime($ev['date']))) ?></div>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="h5 fw-bold mb-1"><?= e($ev['title'] ?? 'Untitled activity') ?></h3>
                            <div class="small text-secondary">
                                <?= e($ev['category'] ?? 'Announcement') ?>
                                <?php if (!empty($ev['venue'])): ?> at <?= e($ev['venue']) ?><?php endif; ?>
                                on <time datetime="<?= e($ev['date']) ?>"><?= e(date('F j, Y', strtotime($ev['date']))) ?></time>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-secondary"></i>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============ BUDGET TRANSPARENCY ============ -->
<section class="lp-section lp-muted-bg" id="budget">
    <div class="container">
        <div class="row align-items-end g-3 mb-4">
            <div class="col-lg">
                <p class="text-primary fw-semibold mb-1"><i class="bi bi-shield-check me-1"></i>Public accountability</p>
                <h2 class="mb-2">Budget transparency</h2>
                <p class="text-secondary mb-0">
                    <?php if ($budget['fy'] !== ''): ?>
                        SK budget and recorded expenses for FY <?= e($budget['fy']) ?>.
                    <?php elseif ($hasBudget): ?>
                        SK budget and recorded expenses.
                    <?php else: ?>
                        The SK budget and recorded expenses will appear here once published.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($budget['fy'] !== ''): ?>
                <div class="col-lg-auto"><span class="badge text-bg-primary fs-6">FY <?= e($budget['fy']) ?></span></div>
            <?php endif; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="lp-budget-card">
                    <div class="small text-secondary mb-2">Available budget</div>
                    <div class="lp-budget-value"><?= $hasBudget ? '₱' . e(number_format($budget['amount'], 2)) : 'Not published' ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="lp-budget-card">
                    <div class="small text-secondary mb-2">Recorded expenses</div>
                    <div class="lp-budget-value">₱<?= e(number_format($budgetSpent, 2)) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="lp-budget-card">
                    <div class="small text-secondary mb-2">Remaining balance</div>
                    <div class="lp-budget-value"><?= $hasBudget ? '₱' . e(number_format($budget['amount'] - $budgetSpent, 2)) : '—' ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="lp-budget-card">
                    <h3 class="h5 fw-bold mb-3">Spending by category</h3>
                    <?php if (!$budgetCategories): ?>
                        <p class="text-secondary mb-0">No expenses have been recorded yet.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($budgetCategories as $category => $amount): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                    <span><?= e($category) ?></span>
                                    <strong class="text-nowrap">₱<?= e(number_format($amount, 2)) ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="lp-budget-card">
                    <h3 class="h5 fw-bold mb-3">Recorded expense details</h3>
                    <?php if (!$budgetExpenses): ?>
                        <p class="text-secondary mb-0">No expenses have been recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm lp-budget-table mb-0">
                                <thead><tr><th scope="col">Date</th><th scope="col">Program / expense</th><th scope="col">Category</th><th scope="col" class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    <?php foreach ($budgetExpenses as $expense): ?>
                                        <tr>
                                            <td class="text-nowrap"><?= e($expense['date']) ?></td>
                                            <td><?= e($expense['title']) ?></td>
                                            <td><?= e($expense['category']) ?></td>
                                            <td class="text-end text-nowrap">₱<?= e(number_format($expense['amount'], 2)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <p class="small text-secondary mt-3 mb-0">This summary reflects records entered by the SK and is provided for public information.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ HOW TO JOIN ============ -->
<section class="lp-section lp-muted-bg" id="join">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <h2 class="mb-3">Join the KK list in three steps</h2>
                <p class="text-secondary mb-4">Being on the list lets the SK plan programs around what the youth of Sikat actually need, from scholarships to sports.</p>
                <a href="<?= e($L_REGISTER) ?>" class="btn btn-primary btn-lg px-4 fw-semibold"><i class="bi bi-person-plus-fill me-2"></i>Start registration</a>
            </div>
            <div class="col-lg-7">
                <div class="bg-white rounded-4 border px-4 py-2">
                    <div class="lp-step">
                        <div class="lp-stepnum">1</div>
                        <div>
                            <h3 class="h5 fw-bold mb-1">Fill out the form</h3>
                            <p class="mb-0 text-secondary">Share your name, birth date, address in the barangay, contact details and interests. Only SK officials can see your full record.</p>
                        </div>
                    </div>
                    <div class="lp-step">
                        <div class="lp-stepnum">2</div>
                        <div>
                            <h3 class="h5 fw-bold mb-1">The SK reviews it</h3>
                            <p class="mb-0 text-secondary">Your request waits for approval. You get a reference number right after you submit, so keep it.</p>
                        </div>
                    </div>
                    <div class="lp-step">
                        <div class="lp-stepnum">3</div>
                        <div>
                            <h3 class="h5 fw-bold mb-1">You are on the member list</h3>
                            <p class="mb-0 text-secondary">Once approved, you are counted among the KK youth of Barangay Sikat and can take part in SK activities.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ OFFICIALS ============ -->
<section class="lp-section" id="officials">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-2">Your SK officials</h2>
            <p class="text-secondary mb-0">The youth leaders of Barangay Sikat for FY 2026&ndash;2027.</p>
        </div>
        <?php if (!$officials): ?>
            <div class="text-center text-secondary py-4">
                <i class="bi bi-people fs-1 d-block mb-2"></i>
                The list of SK officials has not been posted yet.
            </div>
        <?php else: ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($officials as $o): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <article class="lp-off">
                            <?php if ($o['photo']): ?>
                                <img class="pic" src="<?= e($o['photo']) ?>" alt="Photo of <?= e($o['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="pic ph" aria-hidden="true"><?= e($o['initials']) ?></div>
                            <?php endif; ?>
                            <div class="nm"><?= e($o['name']) ?></div>
                            <div class="pos"><?= e($o['position']) ?></div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="text-center mt-5">
            <a href="officials.php" class="btn btn-outline-primary fw-semibold">Meet all officials</a>
        </div>
    </div>
</section>

<!-- ============ CLOSING BAND ============ -->
<section class="lp-cta lp-section py-5">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-lg">
                <h2 class="h3 fw-bold mb-1">Not registered yet?</h2>
                <p class="mb-0">It takes about five minutes, and the SK will know what the youth of Sikat need.</p>
            </div>
            <div class="col-lg-auto d-flex flex-wrap gap-2">
                <a href="<?= e($L_REGISTER) ?>" class="btn btn-primary btn-lg px-4 fw-semibold">Register as KK youth</a>
                <a href="public.php" class="btn btn-outline-primary btn-lg px-4 fw-semibold">Browse as public viewer</a>
            </div>
        </div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="lp-footer py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
        <div>Sangguniang Kabataan of Barangay Sikat, Alfonso, Cavite &bull; FY 2026&ndash;2027</div>
        <div>
            <?php if ($isOfficer): ?>
                <a href="dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="login.php">SK official login</a>
            <?php endif; ?>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>