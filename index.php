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

$POSITIONS = ['SK Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad'];

function lp_json($key) {
    $st = db()->prepare('SELECT data_value FROM app_data WHERE data_key = ?');
    $st->execute([$key]);
    $v = json_decode((string)$st->fetchColumn(), true);
    return is_array($v) ? $v : [];
}

function lp_ann_status($a, $now) {
    if (!empty($a['cancelled'])) return 'Cancelled';
    $start = (string)($a['startTime'] ?? $a['time'] ?? '');
    if (!preg_match('/^\d{2}:\d{2}$/', $start)) $start = '00:00';
    $end = (string)($a['endTime'] ?? '');
    $startTs = strtotime($a['date'] . ' ' . $start);
    $endTs = preg_match('/^\d{2}:\d{2}$/', $end) ? strtotime($a['date'] . ' ' . $end) : strtotime($a['date'] . ' 23:59:59');
    if ($now < $startTs) return 'Upcoming';
    if ($now <= $endTs) return 'Ongoing';
    return 'Completed';
}

function lp_ann_image($src) {
    $src = (string)$src;
    if (preg_match('#^data:image/(?:png|jpe?g|gif|webp);base64,#', $src, $m)) {
        $payload = substr($src, strlen($m[0]));
        $valid = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
        return $payload !== '' && strspn($payload, $valid) === strlen($payload) ? $src : '';
    }
    if (strpos($src, 'data:image/svg+xml') === 0 && strpbrk($src, "\"'<>\r\n\t ") === false) return $src;
    if (preg_match('#^https://\S+$#', $src)) return $src;
    if (preg_match('#^images/[A-Za-z0-9._/-]+$#', $src) && strpos($src, '..') === false) return $src;
    return '';
}

function lp_ann_time($t) {
    return preg_match('/^\d{2}:\d{2}$/', (string)$t) ? date('g:i A', strtotime((string)$t)) : '';
}

function lp_ann_reschedule_banner($announcement) {
    $oldDate = (string)($announcement['rescheduledFrom'] ?? '');
    $newDate = (string)($announcement['date'] ?? '');
    foreach ([$oldDate, $newDate] as $date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            return '';
        }
    }
    if ($oldDate === $newDate) return '';
    $oldDateText = date('F j, Y', strtotime($oldDate));
    $newDateText = date('F j, Y', strtotime($newDate));
    return '<div class="lp-ann-rescheduled" aria-label="Rescheduled from ' . e($oldDateText) . ' to ' . e($newDateText) . '">' .
        '<span class="lp-ann-rescheduled-label">Rescheduled</span>' .
        '<span class="lp-ann-rescheduled-dates"><del class="lp-ann-rescheduled-old">' . e($oldDateText) . '</del>' .
        '<i class="bi bi-arrow-right" aria-hidden="true"></i><strong class="lp-ann-rescheduled-new">' . e($newDateText) . '</strong></span></div>';
}

$today = date('Y-m-d');
$memberCount = 0;
$annList = [];
$annCounts = ['Upcoming' => 0, 'Ongoing' => 0, 'Cancelled' => 0];
$annArchivedCount = 0;
$upcoming = [];
$upcomingCount = 0;
$officials = [];
$budget = ['fy' => '', 'amount' => 0.0];
$hasBudget = false;
$budgetExpenses = [];
$budgetSpent = 0.0;
$budgetProjectSpent = [];
$landingDataWarning = false;

try {
    $officialProfiles = lp_json('skOfficialProfiles');
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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($a['date'] ?? ''))) continue;
        $a['_status'] = lp_ann_status($a, time());
        if ($a['_status'] === 'Completed') {
            $annArchivedCount++;
            continue;
        }
        $annCounts[$a['_status']]++;
        $annList[] = $a;
        if ($a['date'] < $today || !empty($a['cancelled'])) continue;
        $upcoming[] = $a;
    }
    $annRank = ['Ongoing' => 0, 'Upcoming' => 1, 'Cancelled' => 2];
    usort($annList, function ($a, $b) use ($annRank) {
        $ra = $annRank[$a['_status']];
        $rb = $annRank[$b['_status']];
        if ($ra !== $rb) return $ra <=> $rb;
        return $ra <= 1 ? strcmp((string)$a['date'], (string)$b['date']) : strcmp((string)$b['date'], (string)$a['date']);
    });
    usort($upcoming, function ($a, $b) { return strcmp((string)$a['date'], (string)$b['date']); });
    $upcomingCount = count($upcoming);
    $upcoming = array_slice($upcoming, 0, 3);

    /* ---- SK officials: name, position, photo only ---- */
    $seen = [];
    $seenPositions = [];
    foreach (lp_json('skRoles') as $r) {
        if (!is_array($r)) continue;
        $kid = (string)($r['kkId'] ?? '');
        if (!isset($members[$kid]) || isset($seen[$kid])) continue;
        $m = $members[$kid];
        if (($r['status'] ?? '') !== 'Active' || ($m['status'] ?? '') === 'Archived') continue;
        if (!empty($r['termStart']) && $r['termStart'] > $today) continue;
        if (!empty($r['termEnd']) && $r['termEnd'] < $today) continue;
        $position = ($r['position'] ?? '') === 'SK Chairman' ? 'SK Chairperson' : ($r['position'] ?? '');
        if (!in_array($position, $POSITIONS, true)) continue;
        if ($position !== 'SK Kagawad' && isset($seenPositions[$position])) continue;
        $seen[$kid] = 1;
        $name = trim(($m['firstName'] ?? '') . ' ' . ($m['lastName'] ?? '') . (!empty($m['suffix']) ? ' ' . $m['suffix'] : ''));
        $profile = is_array($officialProfiles[(string)($r['id'] ?? '')] ?? null)
            ? $officialProfiles[(string)$r['id']] : [];
        $photoId = (string)($profile['photoId'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $photoId)) $photoId = '';
        $photo = $photoId !== ''
            ? 'official_public_file.php?type=photo&id=' . rawurlencode($photoId)
            : (string)($m['photo'] ?? '');
        if ($photoId === '' && !preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=]+$#', $photo)) $photo = '';
        $parts = preg_split('/\s+/', $name);
        $ini = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        $saln = is_array($profile['saln'] ?? null) ? $profile['saln'] : [];
        $publicSalnId = (string)($saln['publicId'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $publicSalnId)) $publicSalnId = '';
        $officials[] = [
            'roleId' => (string)($r['id'] ?? ''),
            'position' => $position,
            'name' => $name,
            'photo' => $photo,
            'initials' => $ini,
            'bio' => mb_substr(trim((string)($profile['bio'] ?? '')), 0, 1000),
            'monthlyHonorarium' => isset($profile['monthlyHonorarium']) && is_numeric($profile['monthlyHonorarium'])
                ? max(0, (float)$profile['monthlyHonorarium']) : null,
            'salnId' => $publicSalnId,
            'salnYear' => preg_match('/^\d{4}$/', (string)($saln['year'] ?? '')) ? (string)$saln['year'] : '',
            'order' => array_search($position, $POSITIONS, true),
        ];
        if ($position !== 'SK Kagawad') $seenPositions[$position] = true;
    }
    $accountOfficials = db()->query("SELECT full_name, role FROM users
        WHERE is_active = 1 AND legacy_id IS NULL
          AND role IN ('SK Chairman', 'SK Chairperson', 'SK Secretary')
        ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($accountOfficials as $account) {
        $position = $account['role'] === 'SK Chairman' ? 'SK Chairperson' : $account['role'];
        if (isset($seenPositions[$position])) continue;
        $name = trim((string)$account['full_name']);
        if ($name === '') continue;
        if (strcasecmp($name, (string)$account['role']) === 0
            || ($position === 'SK Chairperson' && strcasecmp($name, 'SK Chairperson') === 0)) {
            $name = $position === 'SK Chairperson' ? 'Chairperson' : 'Secretary';
        }
        $parts = preg_split('/\s+/', $name);
        $ini = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        $officials[] = [
            'roleId' => '',
            'position' => $position,
            'name' => $name,
            'photo' => '',
            'initials' => $ini,
            'bio' => '',
            'monthlyHonorarium' => null,
            'salnId' => '',
            'salnYear' => '',
            'order' => array_search($position, $POSITIONS, true),
        ];
        $seenPositions[$position] = true;
    }
    usort($officials, function ($a, $b) { return $a['order'] <=> $b['order'] ?: strcmp($a['name'], $b['name']); });

    /* ---- public budget summary and expense categories ---- */
    $savedBudget = lp_json('skTransparencyBudget');
    $budgetYears = isset($savedBudget['budgets']) && is_array($savedBudget['budgets']) ? $savedBudget['budgets'] : [];
    $budget['fy'] = trim((string)($savedBudget['fy'] ?? ''));
    if ($budget['fy'] === '' || !isset($budgetYears[$budget['fy']])) {
        $budgetYearKeys = array_keys($budgetYears);
        sort($budgetYearKeys, SORT_STRING);
        $budget['fy'] = $budgetYearKeys ? end($budgetYearKeys) : '';
    }
    $yearBudget = $budget['fy'] !== '' && isset($budgetYears[$budget['fy']]) && is_array($budgetYears[$budget['fy']])
        ? $budgetYears[$budget['fy']] : [];
    $budgetProjects = isset($yearBudget['projects']) && is_array($yearBudget['projects']) ? $yearBudget['projects'] : [];
    if (!$budgetProjects && isset($yearBudget['amount']) && is_numeric($yearBudget['amount']) && (float)$yearBudget['amount'] > 0) {
        $budgetProjects[] = ['id' => '', 'name' => 'Legacy budget — add project breakdown', 'amount' => (float)$yearBudget['amount']];
    }
    if (!$budgetProjects && isset($savedBudget['amount']) && is_numeric($savedBudget['amount']) && (float)$savedBudget['amount'] > 0) {
        $budgetProjects[] = ['id' => '', 'name' => 'Legacy budget — add project breakdown', 'amount' => (float)$savedBudget['amount']];
        if ($budget['fy'] === '') $budget['fy'] = trim((string)($savedBudget['fy'] ?? ''));
    }
    $budgetProjectNames = [];
    $legacyBudgetProjectName = null;
    foreach ($budgetProjects as $project) {
        if (!is_array($project)) continue;
        $amount = isset($project['amount']) && is_numeric($project['amount']) ? max(0, (float)$project['amount']) : 0;
        $budget['amount'] += $amount;
        $projectId = (string)($project['id'] ?? '');
        $projectName = trim((string)($project['name'] ?? '')) ?: 'Unnamed project';
        if ($projectId !== '') $budgetProjectNames[$projectId] = $projectName;
        if (!empty($project['legacy']) || strpos($projectName, 'Legacy budget') === 0) {
            $legacyBudgetProjectName = $projectName;
        }
    }
    $hasBudget = $budget['amount'] > 0;
    $budgetFiscalYear = $budget['fy'];
    foreach (lp_json('skTransparencyExpenses') as $expense) {
        if (!is_array($expense) || !isset($expense['amount']) || !is_numeric($expense['amount'])) continue;
        if ($budgetFiscalYear !== '' && !empty($expense['fiscalYear'])
            && (string)$expense['fiscalYear'] !== $budgetFiscalYear) continue;
        $amount = max(0, (float)$expense['amount']);
        $budgetSpent += $amount;
        $projectId = (string)($expense['projectId'] ?? '');
        if ($projectId !== '') $budgetProjectSpent[$projectId] = ($budgetProjectSpent[$projectId] ?? 0) + $amount;
        $project = $budgetProjectNames[$projectId]
            ?? $legacyBudgetProjectName ?? 'Unlinked legacy expense';
        $budgetExpenses[] = [
            'date' => (string)($expense['date'] ?? ''),
            'project' => $project,
            'supplier' => trim((string)($expense['recipient'] ?? '')) ?: 'Not recorded',
            'amount' => $amount,
        ];
    }
    usort($budgetExpenses, function ($a, $b) { return strcmp($b['date'], $a['date']); });
} catch (Throwable $ex) {
    error_log('Public landing page data could not be loaded: ' . $ex->getMessage());
    $landingDataWarning = true;
}

$officialCount = count($officials);
$officials = array_slice($officials, 0, 5);
$next = $upcoming[0] ?? null;
$landingUser = current_user();
$isOfficer = $landingUser !== null && ($landingUser['role'] ?? '') !== 'KK Member';
$hasMemberPortal = $landingUser !== null && !empty($landingUser['legacy_id']);

/* links: ?view=public lets a first-time visitor open view-only pages without a login screen */
$L_EVENTS   = '#announcements';
?>
<!DOCTYPE html>
<html lang="en"<?= is_limited_viewer() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sangguniang Kabataan of Barangay Sikat | KK Youth Profiling System</title>
    <meta name="description" content="Register as a KK youth, see upcoming SK activities and meet your youth officials in Barangay Sikat, Alfonso, Cavite.">

    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="css/sidebar.css?v=31" rel="stylesheet">
    <script src="js/theme.js?v=3"></script>

    <style>
        :root{
            --lp-display:Arial,sans-serif;
            --lp-ink:var(--bs-body-color);
            --lp-line:var(--bs-border-color);
            --bs-success:#0d6efd;
            --bs-success-rgb:13,110,253;
            --bs-success-text-emphasis:#084298;
            --bs-success-bg-subtle:#cfe2ff;
            --bs-success-border-subtle:#9ec5fe;
        }
        html[data-bs-theme="dark"]{
            color-scheme:dark;
            --bs-body-bg:#151a24;
            --bs-body-color:#e8edf5;
            --bs-emphasis-color:#f7f9fc;
            --bs-secondary-color:#aeb8c7;
            --bs-tertiary-bg:#1c2432;
            --bs-secondary-bg:#222d3d;
            --bs-border-color:#384457;
            --bs-border-color-translucent:rgba(230,239,255,.12);
            --bs-primary:#0d6efd;
            --bs-primary-rgb:13,110,253;
            --bs-primary-text-emphasis:#9ec5fe;
            --bs-primary-bg-subtle:#1b3558;
            --bs-primary-border-subtle:#264f84;
            --bs-link-color:#9ec5fe;
            --bs-link-hover-color:#cfe2ff;
            --bs-success:#0d6efd;
            --bs-success-rgb:13,110,253;
            --bs-success-text-emphasis:#9ec5fe;
            --bs-success-bg-subtle:#1b3558;
            --bs-success-border-subtle:#264f84;
        }
        html{scroll-behavior:smooth;scroll-padding-top:5rem}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
        body{background:#f5f2ea;color:var(--lp-ink);line-height:1.6;overflow-x:hidden}
        h1,h2,h3,.display-font{font-family:var(--lp-display);letter-spacing:-.02em}
        a:focus-visible,button:focus-visible{outline:3px solid var(--bs-primary);outline-offset:3px}

        .lp-logo{width:42px;height:42px;border-radius:50%;object-fit:cover}
        .lp-mobilebar{
            position:sticky;
            top:0;
            z-index:1020;
            background:var(--bs-body-bg);
            border-bottom:1px solid var(--lp-line);
        }
        .lp-mobilebar .navbar-toggler{
            width:44px;
            height:44px;
            padding:0;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:.5rem;
            color:var(--lp-ink);
        }
        .lp-mobilebar .navbar-toggler:focus{box-shadow:none}
        .lp-mobilebar .navbar-toggler:focus-visible{outline-offset:0}
        .lp-main{min-width:0}
        @media(min-width:992px){
            .lp-main .container{padding-left:1.5rem;padding-right:1.5rem}
        }

        .lp-ann-card{display:flex;flex-direction:column;height:100%;background:#fff;border:1px solid var(--lp-line);border-radius:16px;overflow:hidden;box-shadow:0 1px 2px rgba(28,26,26,.05),0 8px 24px -12px rgba(28,26,26,.12)}
        .lp-ann-card.is-cancelled{opacity:.8}
        .lp-ann-banner{position:relative;aspect-ratio:16/9;background:var(--bs-primary-bg-subtle);display:grid;place-items:center;color:var(--bs-primary-text-emphasis);font-size:2.4rem;overflow:hidden}
        .lp-ann-banner img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
        .lp-ann-status{position:absolute;top:.75rem;left:.75rem;z-index:1}
        .lp-ann-rescheduled{position:absolute;z-index:2;top:3.2rem;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:.15rem;width:max-content;max-width:calc(100% - 1.5rem);padding:.45rem .75rem;border:1px solid rgba(255,255,255,.55);border-radius:.75rem;background:rgba(13,110,253,.94);box-shadow:0 4px 12px rgba(0,0,0,.2);color:#fff;font-size:.74rem;line-height:1.2;text-align:center}
        .lp-ann-rescheduled-label{font-size:.62rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase}
        .lp-ann-rescheduled-dates{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:.35rem}
        .lp-ann-rescheduled-old{opacity:.86;text-decoration-thickness:2px}
        .lp-ann-rescheduled-new{font-weight:900}
        .lp-ann-body{display:flex;flex-direction:column;flex:1;padding:1.1rem 1.2rem 1.2rem}
        .lp-ann-cat{font-size:.78rem;font-weight:700;color:var(--bs-primary);text-transform:uppercase;letter-spacing:.04em}
        .lp-ann-desc{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;color:var(--bs-secondary-color);font-size:.9rem}
        .lp-ann-meta{font-size:.85rem;color:var(--bs-secondary-color);display:grid;gap:.25rem;margin-bottom:1rem}
        .lp-ann-meta i{width:1.2rem;display:inline-block;color:var(--bs-primary)}
        .lp-ann-filters .btn{font-weight:650}
        .lp-ann-detail-wrap{position:relative}
        .lp-ann-detail-banner{aspect-ratio:16/9;overflow:hidden;background:var(--bs-primary-bg-subtle);display:grid;place-items:center;font-size:3rem;color:var(--bs-primary-text-emphasis)}
        .lp-ann-detail-banner img{width:100%;height:100%;object-fit:cover}
        .lp-ann-text{white-space:pre-line}

        /* hero */
        .lp-hero{background:var(--bs-primary);color:#fff}
        .lp-hero .container{position:relative;z-index:1}
        .lp-place{color:rgba(255,255,255,.8);font-weight:600}
        .lp-hero h1{font-size:clamp(2.4rem,6vw,4.4rem);font-weight:800;line-height:1.02;max-width:13ch}
        .lp-hero .lead{color:rgba(255,255,255,.9);max-width:34rem}
        .btn-lp-light{background:#fff;color:var(--bs-primary-text-emphasis);font-weight:700;border:2px solid #fff}
        .btn-lp-light:hover{background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis);border-color:var(--bs-primary-bg-subtle)}
        .btn-lp-ghost{color:#fff;border:2px solid rgba(255,255,255,.7);font-weight:700}
        .btn-lp-ghost:hover{color:var(--bs-primary-text-emphasis);background:#fff;border-color:#fff}

        /* "next up" ticket: the one memorable element */
        .lp-ticket{background:#fff;color:var(--lp-ink);border-radius:16px;overflow:hidden;border:1px solid var(--lp-line);box-shadow:0 1px 2px rgba(28,26,26,.05),0 8px 24px -12px rgba(28,26,26,.12)}
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
        @media(max-width:767.98px){
            .lp-fact{text-align:center}
        }

        /* sections */
        .lp-section{padding:clamp(3rem,7vw,5.5rem) 0}
        .lp-section h2{font-weight:800;font-size:clamp(1.8rem,4vw,2.6rem)}
        .lp-muted-bg{background:var(--bs-tertiary-bg)}

        /* activity list */

        /* public budget transparency */
        .lp-budget-card{height:100%;padding:1.5rem;background:#fff;border:1px solid var(--lp-line);border-radius:16px;box-shadow:0 1px 2px rgba(28,26,26,.05),0 8px 24px -12px rgba(28,26,26,.12)}
        .lp-budget-card .list-group-item{background:transparent;color:inherit;border-color:var(--lp-line)}
        .lp-budget-value{font-family:var(--lp-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:800;line-height:1.2}
        .lp-budget-table th,.lp-budget-table td{padding:.75rem .5rem;vertical-align:middle}
        .lp-steps-card{background:#fff;border:1px solid var(--lp-line);border-radius:16px;padding:0 1.5rem;box-shadow:0 1px 2px rgba(28,26,26,.05),0 8px 24px -12px rgba(28,26,26,.12)}
        @media(max-width:767.98px){
            .lp-hero .row,.lp-section .row,.lp-cta .row{margin-left:0;margin-right:0}
            .lp-budget-card{overflow:hidden}
        }

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
        .lp-off .bio{font-size:.875rem;color:var(--bs-secondary-color);margin-top:.65rem}
        .lp-off .honorarium{font-size:.875rem;font-weight:700;margin-top:.75rem}
        .lp-off .saln-link{font-size:.8rem;display:inline-block;margin-top:.5rem}

        /* closing band + footer */
        .lp-cta{background:var(--bs-primary-bg-subtle);color:var(--bs-primary-text-emphasis)}
        .lp-footer{background:var(--bs-primary-text-emphasis);color:rgba(255,255,255,.8);font-size:.9rem}
        .lp-footer a{color:#fff}
        html.dark-mode body,
        html.dark-mode .lp-facts,
        html.dark-mode .lp-muted-bg{background:var(--bs-body-bg);color:var(--bs-body-color)}
        html.dark-mode .lp-mobilebar{background:var(--bs-tertiary-bg);border-color:var(--bs-border-color)}
        html.dark-mode .lp-mobilebar .text-secondary{color:var(--bs-body-color)!important}
        html.dark-mode .lp-fact .text-secondary,
        html.dark-mode .lp-section .text-secondary{color:var(--bs-secondary-color)!important}
        html.dark-mode .lp-ann-card,
        html.dark-mode .lp-ticket,
        html.dark-mode .lp-budget-card,
        html.dark-mode .lp-steps-card{background:var(--bs-secondary-bg);border-color:var(--bs-border-color)}
        html.dark-mode .lp-budget-table{--bs-table-bg:transparent;--bs-table-color:var(--bs-body-color);--bs-table-border-color:var(--bs-border-color)}
        html.dark-mode .lp-cta{background:var(--bs-secondary-bg);color:var(--bs-body-color)}
        html.dark-mode .btn-lp-light{color:#084298}
        html.dark-mode .btn-lp-light:hover{color:var(--bs-primary-text-emphasis)}
        html.dark-mode .lp-footer{background:#084298;color:rgba(255,255,255,.9)}
    </style>
</head>
<body>

<!-- ============ NAV ============ -->
<a class="visually-hidden-focusable" href="#main">Skip to main content</a>
<div class="top-strip"></div>
<script>
window.SK_PUBLIC = <?= is_limited_viewer() ? 'true' : 'false' ?>;
window.SK_MEMBER_PORTAL = <?= $hasMemberPortal ? 'true' : 'false' ?>;
</script>
<script src="js/sidebar.js?v=16"></script>

<div class="page-content lp-main">
    <nav class="lp-mobilebar navbar" aria-label="Landing page header">
        <div class="container-fluid">
            <button class="navbar-toggler border-0 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open navigation menu">
                <i class="bi bi-list fs-2"></i>
            </button>
            <a class="navbar-brand d-lg-none d-flex align-items-center gap-2 me-0" href="#home">
                <img src="images/sk-logo.jpg" alt="" class="lp-logo">
                <span class="lh-1">
                    <span class="d-block fw-bold display-font" style="font-size:.95rem">SK Barangay Sikat</span>
                    <span class="d-block text-secondary" style="font-size:.7rem">KK Youth Profiling</span>
                </span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <?php if ($hasMemberPortal): ?>
                    <a class="btn btn-primary btn-sm px-3" href="member-portal.php"><i class="bi bi-grid-1x2-fill me-1"></i>Dashboard</a>
                <?php elseif ($isOfficer): ?>
                    <a class="btn btn-primary btn-sm px-3" href="dashboard.php"><i class="bi bi-grid-1x2-fill me-1"></i><span class="d-none d-sm-inline">Open dashboard</span><span class="d-sm-none">Dashboard</span></a>
                    <a class="btn btn-outline-primary btn-sm px-3" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm px-3" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Official login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

<main id="main">
<?php if ($landingDataWarning): ?>
    <div class="container pt-3">
        <div class="alert alert-warning mb-0" role="status">
            Some public information could not be loaded. Please refresh the page later.
        </div>
    </div>
<?php endif; ?>

<!-- ============ HERO ============ -->
<header class="lp-hero" id="home">
    <div class="container py-5">
        <div class="row align-items-center g-5 py-lg-4">
            <div class="col-lg-7">
                <p class="lp-place mb-3">Barangay Sikat, Alfonso, Cavite</p>
                <h1 class="mb-3">Every young voice in Sikat, counted.</h1>
                <p class="lead mb-4">Find out what the Sangguniang Kabataan is organizing, sign up for events, and learn how to register as a Katipunan ng Kabataan member.</p>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-lp-light btn-lg px-4" data-registration-advice><i class="bi bi-person-plus-fill me-2"></i>Register as KK youth</button>
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
                            <a href="<?= e($L_EVENTS) ?>" class="btn btn-primary w-100 fw-semibold">View details</a>
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
                            <button type="button" class="btn btn-primary w-100 fw-semibold" data-registration-advice>Register so you hear first</button>
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

<!-- ============ SK OFFICIALS ============ -->
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
                    <div class="col-12 col-md-6 col-lg-4">
                        <article class="lp-off lp-budget-card">
                            <?php if ($o['photo']): ?>
                                <img class="pic" src="<?= e($o['photo']) ?>" alt="Photo of <?= e($o['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="pic ph" aria-hidden="true"><?= e($o['initials']) ?></div>
                            <?php endif; ?>
                            <div class="nm"><?= e($o['name']) ?></div>
                            <div class="pos"><?= e($o['position']) ?></div>
                            <?php if ($o['bio'] !== ''): ?><p class="bio mb-0"><?= nl2br(e($o['bio'])) ?></p><?php endif; ?>
                            <?php if ($o['monthlyHonorarium'] !== null): ?>
                                <div class="honorarium">Monthly honorarium: ₱<?= e(number_format($o['monthlyHonorarium'], 2)) ?></div>
                            <?php endif; ?>
                            <?php if ($o['salnId'] !== ''): ?>
                                <a class="saln-link" href="official_public_file.php?type=saln&amp;id=<?= e(rawurlencode($o['salnId'])) ?>" target="_blank" rel="noopener noreferrer">
                                    View public SALN<?= $o['salnYear'] !== '' ? ' (' . e($o['salnYear']) . ')' : '' ?>
                                </a>
                            <?php endif; ?>
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

<!-- ============ EXPENSE DISCLOSURE ============ -->
<section class="lp-section lp-muted-bg" id="expenses">
    <div class="container">
        <div class="row align-items-end g-3 mb-4">
            <div class="col-lg">
                <p class="text-primary fw-semibold mb-1"><i class="bi bi-receipt me-1"></i>Public disclosure</p>
                <h2 class="mb-2">Recorded expenses</h2>
                <p class="text-secondary mb-0">
                    <?php if ($budget['fy'] !== ''): ?>Recorded project expenses for FY <?= e($budget['fy']) ?>, including the supplier or recipient.
                    <?php else: ?>Recorded project expenses and suppliers will appear here once entered.<?php endif; ?>
                    Receipt files remain available only to signed-in SK officials.
                </p>
            </div>
            <?php if ($budget['fy'] !== ''): ?>
                <div class="col-lg-auto"><span class="badge text-bg-primary fs-6">FY <?= e($budget['fy']) ?></span></div>
            <?php endif; ?>
        </div>
        <?php if (!$budgetExpenses): ?>
            <div class="lp-budget-card"><p class="text-secondary mb-0">No expenses have been recorded for this fiscal year.</p></div>
        <?php else: ?>
            <div class="lp-budget-card table-responsive">
                <table class="table table-sm lp-budget-table mb-0">
                    <thead><tr>
                        <th scope="col">Date</th><th scope="col">Project / Expense</th>
                        <th scope="col">Supplier / recipient</th><th scope="col" class="text-end">Amount</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($budgetExpenses as $expense): ?>
                            <tr>
                                <td class="text-nowrap"><?= e($expense['date']) ?></td>
                                <td><?= e($expense['project']) ?></td>
                                <td><?= e($expense['supplier']) ?></td>
                                <td class="text-end text-nowrap">₱<?= e(number_format($expense['amount'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
                    <div class="small text-secondary mb-2">Total published budget</div>
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

        <div class="lp-budget-card">
            <h3 class="h5 fw-bold mb-3">Project budget versus expenses</h3>
            <?php if (!$budgetProjects): ?>
                <p class="text-secondary mb-0">No project budgets have been published yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm lp-budget-table mb-0">
                        <thead><tr>
                            <th scope="col">Project</th><th scope="col">Plan</th><th scope="col">COP</th>
                            <th scope="col">Committee</th><th scope="col" class="text-end">Budget</th>
                            <th scope="col" class="text-end">Expenses</th><th scope="col" class="text-end">Balance</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($budgetProjects as $project): ?>
                                <?php
                                    if (!is_array($project)) continue;
                                    $projectId = (string)($project['id'] ?? '');
                                    $projectAmount = isset($project['amount']) && is_numeric($project['amount']) ? max(0, (float)$project['amount']) : 0;
                                    $projectSpent = $projectId !== '' ? ($budgetProjectSpent[$projectId] ?? 0) : 0;
                                ?>
                                <tr>
                                    <td><?= e(trim((string)($project['name'] ?? '')) ?: 'Unnamed project') ?></td>
                                    <td><?= e(['Annual' => 'Annual budget', 'Supplemental' => 'Supplemental budget', 'Continuing' => 'Continuing fund'][(string)($project['type'] ?? 'Annual')] ?? 'Annual budget') ?></td>
                                    <td><?= e((string)($project['cop'] ?? '') ?: 'Not set') ?></td>
                                    <td><?= e((string)($project['committee'] ?? '') ?: 'Not set') ?></td>
                                    <td class="text-end text-nowrap">₱<?= e(number_format($projectAmount, 2)) ?></td>
                                    <td class="text-end text-nowrap">₱<?= e(number_format($projectSpent, 2)) ?></td>
                                    <td class="text-end text-nowrap">₱<?= e(number_format($projectAmount - $projectSpent, 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <p class="small text-secondary mt-3 mb-0">Project-level balances are calculated from the recorded expenses above. Receipt images and private expense descriptions are not published.</p>
        </div>
    </div>
</section>

<!-- ============ ANNOUNCEMENTS ============ -->
<section class="lp-section" id="announcements">
    <div class="container">
        <div class="row g-3 align-items-end mb-4">
            <div class="col-lg">
                <h2 class="mb-2">Announcements &amp; activities</h2>
                <p class="text-secondary mb-0">Sports, clean-up drives, learning sessions and notices published by the Sangguniang Kabataan. Completed events are in the <a href="archives.php">Archives</a>.</p>
            </div>
            <div class="col-lg-4">
                <label class="input-group" aria-label="Search announcements">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" class="form-control" id="annSearch" placeholder="Search title, venue or category" autocomplete="off">
                </label>
            </div>
        </div>
        <?php if (!$annList): ?>
            <div class="text-center text-secondary py-5">
                <i class="bi bi-megaphone fs-1 d-block mb-2"></i>
                <?php if ($annArchivedCount > 0): ?>
                    All published events have ended. View them in <a href="archives.php">Archives</a>.
                <?php else: ?>
                    No announcements are posted right now. Please check back soon.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="lp-ann-filters d-flex flex-wrap gap-2 mb-4" id="annFilters" role="group" aria-label="Filter announcements">
                <button type="button" class="btn btn-sm btn-primary" data-ann-filter="all" aria-pressed="true">All <span class="badge text-bg-light ms-1"><?= count($annList) ?></span></button>
                <?php foreach ($annCounts as $annLabel => $annNumber): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-ann-filter="<?= e($annLabel) ?>" aria-pressed="false"><?= e($annLabel) ?> <span class="badge text-bg-light ms-1"><?= (int)$annNumber ?></span></button>
                <?php endforeach; ?>
            </div>
            <div class="row g-4" id="annGrid">
                <?php foreach ($annList as $annIndex => $ann):
                    $annStatus = $ann['_status'];
                    $annBadge = ['Upcoming' => 'text-bg-primary', 'Ongoing' => 'text-bg-success', 'Completed' => 'text-bg-secondary', 'Cancelled' => 'text-bg-danger'][$annStatus];
                    $annImage = lp_ann_image($ann['banner'] ?? '');
                    $annStart = lp_ann_time($ann['startTime'] ?? ($ann['time'] ?? ''));
                    $annEnd = lp_ann_time($ann['endTime'] ?? '');
                    $annTimeText = $annStart !== '' ? $annStart . ($annEnd !== '' ? ' – ' . $annEnd : '') : '';
                    $annDateText = date('F j, Y', strtotime($ann['date']));
                    $annDays = (int)ceil((strtotime($ann['date'] . ' ' . ($annStart !== '' ? date('H:i', strtotime($annStart)) : '00:00')) - time()) / 86400);
                    $annSearch = strtolower(trim(($ann['title'] ?? '') . ' ' . ($ann['category'] ?? '') . ' ' . ($ann['venue'] ?? '') . ' ' . ($ann['description'] ?? '')));
                ?>
                    <div class="col-md-6 col-xl-4" data-ann-item data-status="<?= e($annStatus) ?>" data-search="<?= e($annSearch) ?>">
                        <article class="lp-ann-card<?= $annStatus === 'Cancelled' ? ' is-cancelled' : '' ?>">
                            <div class="lp-ann-banner">
                                <?php if ($annImage !== ''): ?><img src="<?= e($annImage) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-megaphone-fill" aria-hidden="true"></i><?php endif; ?>
                                <span class="lp-ann-status badge <?= e($annBadge) ?>"><?= e($annStatus) ?></span>
                                <?= lp_ann_reschedule_banner($ann) ?>
                            </div>
                            <div class="lp-ann-body">
                                <div class="lp-ann-cat mb-1"><?= e($ann['category'] ?? 'Announcement') ?></div>
                                <h3 class="h5 fw-bold mb-2"><?= e($ann['title'] ?? 'Untitled activity') ?></h3>
                                <p class="lp-ann-desc mb-3"><?= e($ann['description'] ?? '') ?></p>
                                <div class="lp-ann-meta mt-auto">
                                    <div><i class="bi bi-calendar-event" aria-hidden="true"></i><?= e($annDateText) ?></div>
                                    <?php if ($annTimeText !== ''): ?><div><i class="bi bi-clock" aria-hidden="true"></i><?= e($annTimeText) ?></div><?php endif; ?>
                                    <?php if (!empty($ann['venue'])): ?><div><i class="bi bi-geo-alt" aria-hidden="true"></i><?= e($ann['venue']) ?></div><?php endif; ?>
                                    <?php if ($annStatus === 'Upcoming' && $annDays >= 0): ?><div><i class="bi bi-hourglass-split" aria-hidden="true"></i><?= $annDays === 0 ? 'Starts today' : $annDays . ' day' . ($annDays === 1 ? '' : 's') . ' remaining' ?></div><?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-primary w-100 fw-semibold" data-ann-open="annDetail<?= (int)$annIndex ?>">View details</button>
                            </div>
                        </article>
                        <template id="annDetail<?= (int)$annIndex ?>">
                            <div class="lp-ann-detail-wrap">
                                <div class="lp-ann-detail-banner" data-ann-banner><i class="bi bi-megaphone-fill" aria-hidden="true"></i></div>
                                <?= lp_ann_reschedule_banner($ann) ?>
                            </div>
                            <div class="p-4">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge <?= e($annBadge) ?>"><?= e($annStatus) ?></span>
                                    <span class="badge text-bg-light"><?= e($ann['category'] ?? 'Announcement') ?></span>
                                </div>
                                <h2 class="h4 fw-bold mb-3"><?= e($ann['title'] ?? 'Untitled activity') ?></h2>
                                <?php if ($annStatus === 'Cancelled'): ?><div class="alert alert-danger">This event has been cancelled.</div><?php endif; ?>
                                <div class="row g-3 mb-4 small">
                                    <div class="col-md-6"><i class="bi bi-calendar-event text-primary me-2" aria-hidden="true"></i><?= e($annDateText) ?></div>
                                    <?php if ($annTimeText !== ''): ?><div class="col-md-6"><i class="bi bi-clock text-primary me-2" aria-hidden="true"></i><?= e($annTimeText) ?></div><?php endif; ?>
                                    <?php if (!empty($ann['venue'])): ?><div class="col-md-6"><i class="bi bi-geo-alt text-primary me-2" aria-hidden="true"></i><?= e($ann['venue']) ?></div><?php endif; ?>
                                    <?php if (!empty($ann['organizer'])): ?><div class="col-md-6"><i class="bi bi-person-badge text-primary me-2" aria-hidden="true"></i><?= e($ann['organizer']) ?></div><?php endif; ?>
                                    <?php if (!empty($ann['contact'])): ?><div class="col-12"><i class="bi bi-telephone text-primary me-2" aria-hidden="true"></i><?= e($ann['contact']) ?></div><?php endif; ?>
                                </div>
                                <h3 class="h6 fw-bold">About this announcement</h3>
                                <p class="lp-ann-text mb-0"><?= e(($ann['details'] ?? '') !== '' ? $ann['details'] : ($ann['description'] ?? '')) ?></p>
                                <?php if (!empty($ann['notes'])): ?><div class="alert alert-warning mt-3 mb-0"><strong>Additional notes</strong><br><span class="lp-ann-text"><?= e($ann['notes']) ?></span></div><?php endif; ?>
                            </div>
                        </template>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-center text-secondary py-4 mb-0 d-none" id="annNone">No announcements match your search.</p>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade" id="annModal" tabindex="-1" aria-label="Announcement details" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content overflow-hidden">
            <div class="modal-body p-0" id="annModalBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- ============ HOW TO JOIN ============ -->
<section class="lp-section lp-muted-bg" id="join">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <h2 class="mb-3">Register with the SK in person</h2>
                <p class="text-secondary mb-4">Being on the list lets the SK plan programs around what the youth of Sikat actually need, from scholarships to sports.</p>
            </div>
            <div class="col-lg-7">
                <div class="lp-steps-card py-2">
                    <div class="lp-step">
                        <div class="lp-stepnum">1</div>
                        <div>
                            <h3 class="h5 fw-bold mb-1">Visit the SK Office</h3>
                            <p class="mb-0 text-secondary">Go to the SK Office at the Barangay Hall in Barangay Sikat, Alfonso, Cavite, to register.</p>
                        </div>
                    </div>
                    <div class="lp-step">
                        <div class="lp-stepnum">2</div>
                        <div>
                            <h3 class="h5 fw-bold mb-1">Get help from an SK official</h3>
                            <p class="mb-0 text-secondary">An SK official will assist you with the registration process and the information needed.</p>
                        </div>
                    </div>
                    <div class="lp-step">
                        <div class="lp-stepnum">3</div>
                        <div>
                            <h3 class="h5 fw-bold mb-1">Ask about your registration</h3>
                            <p class="mb-0 text-secondary">The SK Office can explain what happens next and help with any questions about your registration.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CLOSING BAND ============ -->
<section class="lp-cta lp-section py-5">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-lg">
                <h2 class="h3 fw-bold mb-1">Not registered yet?</h2>
                <p class="mb-0">Visit the SK Office at the Barangay Hall in Barangay Sikat to register in person.</p>
            </div>
            <div class="col-lg-auto d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-lg px-4 fw-semibold" data-registration-advice>Register as KK youth</button>
                <a href="<?= e($L_EVENTS) ?>" class="btn btn-outline-primary btn-lg px-4 fw-semibold">See announcements</a>
            </div>
        </div>
    </div>
</section>
</main>

<!-- ============ FOOTER ============ -->
<footer class="lp-footer py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
        <div>Sangguniang Kabataan of Barangay Sikat, Alfonso, Cavite &bull; FY 2026&ndash;2027</div>
        <?php if ($isOfficer): ?>
            <div><a href="dashboard.php">Dashboard</a></div>
        <?php endif; ?>
    </div>
</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/app-alerts.js?v=2"></script>
<script>
document.querySelectorAll('#sidebar a[href*="#"]').forEach(function (link) {
    link.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('sidebar')).hide();
        }
    });
});
(function () {
    var grid = document.getElementById('annGrid');
    if (!grid) return;
    var search = document.getElementById('annSearch');
    var none = document.getElementById('annNone');
    var buttons = document.querySelectorAll('[data-ann-filter]');
    var filter = 'all';
    function apply() {
        var query = search.value.trim().toLowerCase();
        var shown = 0;
        grid.querySelectorAll('[data-ann-item]').forEach(function (item) {
            var match = (filter === 'all' || item.getAttribute('data-status') === filter)
                && (query === '' || item.getAttribute('data-search').indexOf(query) !== -1);
            item.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        none.classList.toggle('d-none', shown !== 0);
    }
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            filter = button.getAttribute('data-ann-filter');
            buttons.forEach(function (other) {
                var on = other === button;
                other.classList.toggle('btn-primary', on);
                other.classList.toggle('btn-outline-primary', !on);
                other.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            apply();
        });
    });
    search.addEventListener('input', apply);
    grid.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-ann-open]');
        if (!trigger) return;
        var source = document.getElementById(trigger.getAttribute('data-ann-open'));
        if (!source) return;
        var body = document.getElementById('annModalBody');
        body.innerHTML = source.innerHTML;
        var cardImage = trigger.closest('[data-ann-item]').querySelector('.lp-ann-banner img');
        var slot = body.querySelector('[data-ann-banner]');
        if (cardImage && slot) {
            var full = document.createElement('img');
            full.alt = '';
            full.src = cardImage.src;
            slot.replaceChildren(full);
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('annModal')).show();
    });
})();
document.querySelectorAll('[data-registration-advice]').forEach(function (button) {
    button.addEventListener('click', function () {
        window.AppAlerts.show(
            'KK youth registration is handled in person. Please visit the SK Office at the Barangay Hall in Barangay Sikat, Alfonso, Cavite. An SK official will assist you with registering.',
            'info',
            'Register in person'
        );
    });
});
</script>
</body>
</html>