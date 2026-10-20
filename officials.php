<?php
/* =========================================================
   officials.php  - PUBLIC page: the barangay SK officials (name, position, photo)
   Open to everyone: no login and no "public viewer" click needed.
   It is built on the server, so only these three things are sent to the visitor.
   (Other member details, such as contacts and other photos, stay private.)
   ========================================================= */
require_once __DIR__ . '/config.php';
if (USE_MEMBERS_TABLE) require_once __DIR__ . '/members_table.php';
open_to_everyone();

$POSITIONS = ['SK Chairperson', 'SK Vice Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad'];

function app_json($key) {
    if ($key === 'kkMembers' && USE_MEMBERS_TABLE) return kk_members_array_from_table(db());
    if ($key === 'skRoles' && USE_SK_ROLES_TABLE) {
        return array_map(function ($role) {
            $decoded = json_decode($role, true);
            return is_array($decoded) ? $decoded : [];
        }, db()->query('SELECT role_data FROM sk_roles ORDER BY role_code')->fetchAll(PDO::FETCH_COLUMN));
    }
    $st = db()->prepare('SELECT data_value FROM app_data WHERE data_key = ?');
    $st->execute([$key]);
    $v = json_decode((string)$st->fetchColumn(), true);
    return is_array($v) ? $v : [];
}

$members = [];
foreach (app_json('kkMembers') as $m) { if (is_array($m) && !empty($m['id'])) $members[(string)$m['id']] = $m; }

$today = date('Y-m-d');
$officials = [];
$seen = [];
foreach (app_json('skRoles') as $r) {
    if (!is_array($r)) continue;
    $kid = (string)($r['kkId'] ?? '');
    if (!isset($members[$kid]) || isset($seen[$kid])) continue;
    $m = $members[$kid];
    if (($r['status'] ?? '') !== 'Active' || ($m['status'] ?? '') === 'Archived') continue;
    if (!empty($r['termEnd']) && $r['termEnd'] < $today) continue;           // term already ended
    if (!in_array($r['position'] ?? '', $POSITIONS, true)) continue;
    $seen[$kid] = 1;
    $name = trim(($m['firstName'] ?? '') . ' ' . (!empty($m['middleName']) ? mb_substr($m['middleName'], 0, 1) . '. ' : '') . ($m['lastName'] ?? '') . (!empty($m['suffix']) ? ' ' . $m['suffix'] : ''));
    $photo = (string)($m['photo'] ?? '');
    if (!preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=]+$#', $photo)) $photo = '';
    $parts = preg_split('/\s+/', $name);
    $ini = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    $officials[] = ['position' => $r['position'], 'name' => $name, 'photo' => $photo, 'initials' => $ini,
                    'order' => array_search($r['position'], $POSITIONS, true)];
}
usort($officials, function ($a, $b) { return $a['order'] <=> $b['order'] ?: strcmp($a['name'], $b['name']); });

$groups = [];
foreach ($officials as $o) { $groups[$o['position']][] = $o; }
?>
<!DOCTYPE html>
<html lang="en"<?= is_limited_viewer() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | SK Officials</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">

    <style>
        :root { --line:var(--bs-border-color); --cream:var(--bs-tertiary-bg); }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial,sans-serif; background:var(--cream); color:var(--bs-body-color); }
        .top-strip { height:7px; background:var(--bs-primary); }
        .header { min-height:70px; background:#fff; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; padding:0 24px; gap:12px; }
        .header-title { font-size:20px; font-weight:800; }
        .user-area { display:flex; align-items:center; gap:12px; text-align:right; }
        .avatar { width:40px; height:40px; border-radius:50%; background:var(--bs-primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .content { padding:24px; max-width:1100px; margin:0 auto; }
        .hero { background:#fff; border:1px solid var(--line); border-radius:16px; padding:28px; margin-bottom:24px; text-align:center; }
        .hero h1 { font-size:28px; font-weight:900; margin:0 0 6px; }
        .hero p { margin:0; color:var(--bs-secondary-color); }
        .group-title { font-size:13px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:var(--bs-secondary-color); margin:28px 0 14px; text-align:center; }
        .cards { display:flex; flex-wrap:wrap; justify-content:center; gap:18px; }
        .official { width:200px; background:#fff; border:1px solid var(--line); border-radius:16px; padding:22px 14px; text-align:center; }
        .official .pic { width:112px; height:112px; border-radius:50%; margin:0 auto 14px; object-fit:cover; border:3px solid var(--bs-primary-bg-subtle); display:block; }
        .official .ph { display:flex; align-items:center; justify-content:center; background:var(--bs-primary-bg-subtle); color:var(--bs-primary-text-emphasis); font-size:34px; font-weight:800; }
        .official .nm { font-weight:800; font-size:16px; line-height:1.25; }
        .official .pos { margin-top:6px; font-size:13px; color:var(--bs-primary); font-weight:700; }
        .footer-note { text-align:center; color:var(--bs-secondary-color); font-size:13px; margin:34px 0 10px; }
        .login-link { font-size:14px; }
    </style>
</head>
<body>

<div class="top-strip"></div>

<script>window.SK_PUBLIC = <?= is_limited_viewer() ? 'true' : 'false' ?>;</script>
<script src="sidebar.js?v=4"></script>

<main class="main page-content">

<header class="header">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
            <div class="header-title">SK Officials</div>
            <small>KK Youth Profiling System</small>
        </div>
    </div>
    <div class="user-area">
        <?php if (current_user()): ?>
            <div><div style="font-size:15px;font-weight:bold;"><?= e(uname()) ?></div><small><?= e(strtoupper(urole())) ?></small></div>
            <div class="avatar"><?= e(uinit()) ?></div>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline-primary btn-sm login-link"><i class="bi bi-box-arrow-in-right me-1"></i>Official login</a>
        <?php endif; ?>
    </div>
</header>

<div class="content">

    <section class="hero">
        <img src="images/sk-logo.jpg" alt="SK Barangay Sikat logo" width="72" height="72" class="mb-2" style="border-radius:50%;object-fit:cover">
        <h1>Sangguniang Kabataan Officials</h1>
        <p>Barangay Sikat, Alfonso, Cavite &bull; Your youth leaders for FY 2026&ndash;2027</p>
    </section>

    <?php if (!$officials): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-people fs-1 d-block mb-2"></i>
            The list of SK officials has not been posted yet. Please check back soon.
        </div>
    <?php endif; ?>

    <?php foreach ($groups as $pos => $list): ?>
        <div class="group-title"><?= e($pos) ?><?= count($list) > 1 ? 's' : '' ?></div>
        <div class="cards">
            <?php foreach ($list as $o): ?>
                <article class="official">
                    <?php if ($o['photo']): ?>
                        <img class="pic" src="<?= e($o['photo']) ?>" alt="Photo of <?= e($o['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="pic ph" aria-hidden="true"><?= e($o['initials']) ?></div>
                    <?php endif; ?>
                    <div class="nm"><?= e($o['name']) ?></div>
                    <div class="pos"><?= e($o['position']) ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="footer-note">Sangguniang Kabataan of Barangay Sikat &bull; Alfonso, Cavite</div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>