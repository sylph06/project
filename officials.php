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
if (is_limited_viewer()) {
    header('Location: index.php#officials');
    exit;
}

$POSITIONS = ['SK Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Kagawad'];

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
$roles = app_json('skRoles');
$officialProfiles = app_json('skOfficialProfiles');

$today = date('Y-m-d');
$officials = [];
$seen = [];
$seenPositions = [];
foreach ($roles as $r) {
    if (!is_array($r)) continue;
    $kid = (string)($r['kkId'] ?? '');
    if (!isset($members[$kid]) || isset($seen[$kid])) continue;
    $m = $members[$kid];
    if (($r['status'] ?? '') !== 'Active' || ($m['status'] ?? '') === 'Archived') continue;
    if (!empty($r['termStart']) && $r['termStart'] > $today) continue;
    if (!empty($r['termEnd']) && $r['termEnd'] < $today) continue;           // term already ended
    $position = ($r['position'] ?? '') === 'SK Chairman' ? 'SK Chairperson' : ($r['position'] ?? '');
    if (!in_array($position, $POSITIONS, true)) continue;
    if ($position !== 'SK Kagawad' && isset($seenPositions[$position])) continue;
    $name = trim(($m['firstName'] ?? '') . ' ' . (!empty($m['middleName']) ? mb_substr($m['middleName'], 0, 1) . '. ' : '') . ($m['lastName'] ?? '') . (!empty($m['suffix']) ? ' ' . $m['suffix'] : ''));
    if ($name === '' || in_array(strtolower($name), ['chairperson', 'sk chairperson', 'sk chairman', 'secretary', 'sk secretary'], true)) continue;
    $seen[$kid] = 1;
    $profile = $officialProfiles[(string)($r['id'] ?? '')] ?? [];
    if (!is_array($profile)) $profile = [];
    $photoId = (string)($profile['photoId'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $photoId)) $photoId = '';
    $photo = $photoId !== '' ? 'official_public_file.php?type=photo&id=' . rawurlencode($photoId) : (string)($m['photo'] ?? '');
    if ($photoId === '' && !preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=]+$#', $photo)) $photo = '';
    $parts = preg_split('/\s+/', $name);
    $ini = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    $saln = is_array($profile['saln'] ?? null) ? $profile['saln'] : [];
    $publicSalnId = (string)($saln['publicId'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $publicSalnId)) $publicSalnId = '';
    $officials[] = [
                    'roleId' => (string)($r['id'] ?? ''),
                    'position' => $position, 'name' => $name, 'photo' => $photo, 'initials' => $ini,
                    'bio' => mb_substr(trim((string)($profile['bio'] ?? '')), 0, 1000),
                    'monthlyHonorarium' => isset($profile['monthlyHonorarium']) && is_numeric($profile['monthlyHonorarium'])
                        ? max(0, (float)$profile['monthlyHonorarium']) : null,
                    'salnId' => $publicSalnId,
                    'salnYear' => preg_match('/^\d{4}$/', (string)($saln['year'] ?? '')) ? (string)$saln['year'] : '',
                    'order' => array_search($position, $POSITIONS, true)];
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
    if ($name === '' || in_array(strtolower($name), ['chairperson', 'sk chairperson', 'sk chairman', 'secretary', 'sk secretary'], true)) continue;
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
if ($officials && !isset($seenPositions['SK Chairperson'])) {
    $officials[] = [
        'roleId' => '',
        'position' => 'SK Chairperson',
        'name' => 'Vacant',
        'photo' => '',
        'initials' => '',
        'bio' => '',
        'monthlyHonorarium' => null,
        'salnId' => '',
        'salnYear' => '',
        'order' => array_search('SK Chairperson', $POSITIONS, true),
        'vacant' => true,
    ];
}
$selfRole = null;
$selfProfile = [];
$selfSaln = [];
$selfUser = current_user();
if ($selfUser && can_write_app_data() && !empty($selfUser['legacy_id'])) {
    foreach ($roles as $role) {
        $roleId = (string)($role['id'] ?? '');
        if ((string)($role['kkId'] ?? '') !== (string)$selfUser['legacy_id']
            || ($role['status'] ?? '') !== 'Active'
            || (!empty($role['termStart']) && $role['termStart'] > $today)
            || (!empty($role['termEnd']) && $role['termEnd'] < $today)) continue;
        $selfRole = $role;
        $selfProfile = is_array($officialProfiles[$roleId] ?? null) ? $officialProfiles[$roleId] : [];
        $selfSaln = is_array($selfProfile['saln'] ?? null) ? $selfProfile['saln'] : [];
        break;
    }
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=33" rel="stylesheet">
    <script src="js/theme.js"></script>

    <style>
        :root {
            --mem-bg: var(--ui-bg, #f8fafc);
            --mem-surface: var(--ui-paper, #ffffff);
            --mem-surface-alt: var(--ui-surface, #f1f5f9);
            --mem-ink: var(--ui-ink, #1e293b);
            --mem-muted: var(--ui-ink-muted, #64748b);
            --mem-brand: var(--ui-brand, #1e40af);
            --mem-brand-soft: rgba(30,64,175,.1);
            --mem-brand-rgb: 30,64,175;
            --mem-brand-text-emphasis: var(--ui-brand-text-emphasis, rgba(30,64,175,.75));
            --mem-radius: var(--ui-radius, 12px);
            --mem-line: var(--ui-line, #e2e8f0);
        }

        html[data-bs-theme="dark"] {
            --mem-bg: #111827;
            --mem-surface: #1f2937;
            --mem-surface-alt: #374151;
            --mem-ink: #f9fafb;
            --mem-muted: #9ca3af;
            --mem-brand: #3b82f6;
            --mem-brand-soft: rgba(59,130,246,.1);
            --mem-brand-rgb: 59,130,246;
            --mem-brand-text-emphasis: rgba(59,130,246,.8);
            --mem-line: #374151;
        }

        * { box-sizing:border-box; }

        body.officials-page {
            margin:0;
            font-family:Arial,sans-serif;
            background:var(--mem-bg)!important;
            color:var(--mem-ink)!important;
        }

        .top-strip {
            height:7px;
            background:var(--mem-brand)!important;
        }

        .header {
            min-height:70px;
            background:var(--mem-surface)!important;
            border-bottom:1px solid var(--mem-line)!important;
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:0 24px;
            gap:12px;
        }

        .header-title {
            font-size:20px;
            font-weight:800;
            color:var(--mem-ink)!important;
        }

        .user-area { display:flex; align-items:center; gap:12px; text-align:right; }

        .avatar {
            width:40px;
            height:40px;
            border-radius:50%;
            background:var(--mem-brand)!important;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
        }

        .content { padding:24px; max-width:1100px; margin:0 auto; }

        .hero {
            background:var(--mem-surface)!important;
            border:1px solid var(--mem-line)!important;
            border-radius:8px;
            padding:24px;
            margin-bottom:24px;
            text-align:center;
        }

        .hero h1 { font-size:28px; font-weight:900; margin:0 0 6px; color:var(--mem-ink)!important; }

        .hero p { margin:0; color:var(--mem-muted)!important; }

        .officials-hierarchy {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            grid-template-areas:
                "chairperson chairperson"
                "secretary treasurer"
                "kagawads kagawads";
            gap:24px 18px;
        }

        .official-group { min-width:0; }
        .official-group-sk-chairperson { grid-area:chairperson; }
        .official-group-sk-secretary { grid-area:secretary; }
        .official-group-sk-treasurer { grid-area:treasurer; }
        .official-group-sk-kagawad { grid-area:kagawads; }

        .group-title {
            font-size:13px;
            font-weight:800;
            letter-spacing:1px;
            text-transform:uppercase;
            color:var(--mem-muted)!important;
            margin:0 0 14px;
            text-align:center;
        }

        .cards { display:flex; flex-wrap:wrap; justify-content:center; align-items:stretch; gap:20px; }

        .official {
            flex:0 1 232px;
            max-width:100%;
            background:var(--mem-surface)!important;
            border:1px solid var(--mem-line)!important;
            border-radius:18px;
            padding:0;
            text-align:center;
            overflow:hidden;
            box-shadow:0 8px 24px rgba(27,65,125,.07);
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .official-profile-trigger {
            display:flex;
            min-height:274px;
            width:100%;
            height:100%;
            flex-direction:column;
            align-items:center;
            padding:24px 18px 18px;
            border:0;
            border-radius:inherit;
            background:linear-gradient(180deg,var(--mem-surface-alt) 0%,var(--mem-surface) 42%);
            color:inherit;
            font:inherit;
            text-align:center;
            cursor:pointer;
        }

        .official-profile-trigger:focus-visible {
            outline:3px solid var(--mem-brand);
            outline-offset:-4px;
        }

        .official:hover,
        .official:focus-within {
            transform:translateY(-4px);
            box-shadow:0 16px 34px rgba(27,65,125,.16);
            border-color:var(--mem-brand);
        }

        .official .pic {
            width:116px;
            height:116px;
            flex:none;
            border-radius:50%;
            margin:0 auto 18px;
            object-fit:cover;
            border:4px solid var(--mem-surface)!important;
            display:block;
            box-shadow:0 0 0 2px var(--mem-brand-soft),0 8px 18px rgba(23,35,60,.12);
        }

        .official .ph {
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(145deg,var(--mem-brand-soft),var(--mem-surface))!important;
            color:var(--mem-brand)!important;
            font-size:32px;
            font-weight:850;
            border-radius:50%;
            width:116px;
            height:116px;
            margin:0 auto 18px;
        }

        .official .nm {
            display:flex;
            min-height:2.6em;
            align-items:center;
            justify-content:center;
            font-weight:800;
            font-size:16px;
            line-height:1.3;
            color:var(--mem-ink)!important;
        }

        .official .pos {
            display:inline-flex;
            align-items:center;
            margin-top:10px;
            padding:5px 11px;
            border:1px solid var(--mem-brand-soft);
            border-radius:999px;
            background:var(--mem-brand-soft);
            color:var(--mem-brand)!important;
            font-size:11px;
            line-height:1.25;
            font-weight:750;
        }

        .official .profile-hint {
            display:flex;
            align-items:center;
            justify-content:space-between;
            width:100%;
            margin-top:auto;
            padding-top:18px;
            color:var(--mem-muted)!important;
            font-size:12px;
            font-weight:700;
        }

        .official .profile-hint .saln-state {
            color:var(--mem-brand)!important;
            font-size:11px;
        }

        .official .profile-hint .view-label {
            display:inline-flex;
            align-items:center;
            gap:6px;
            color:var(--mem-ink)!important;
        }

        .official:hover .profile-hint .view-label,
        .official:focus-within .profile-hint .view-label {
            color:var(--mem-brand)!important;
        }

        .footer-note { text-align:center; color:var(--mem-muted)!important; font-size:13px; margin:34px 0 10px; }
        .login-link { font-size:14px; }

        @media (max-width:575.98px) {
            .official { flex-basis:min(100%,280px); }

            .officials-hierarchy {
                grid-template-columns:minmax(0,1fr);
                grid-template-areas:
                    "chairperson"
                    "secretary"
                    "treasurer"
                    "kagawads";
                gap:20px;
            }
        }

        @media (prefers-reduced-motion:reduce) {
            .official { transition:none; }
        }
    </style>
    <link href="css/site-theme.css?v=2" rel="stylesheet">
</head>
<body class="officials-page">

<div class="top-strip"></div>

<script>
window.SK_PUBLIC = <?= is_limited_viewer() ? 'true' : 'false' ?>;
window.SK_MEMBER_PORTAL = <?= !empty($selfUser['legacy_id']) ? 'true' : 'false' ?>;
</script>
<script src="js/sidebar.js?v=20"></script>

<main class="main page-content">

<header class="header">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
            <div class="header-title">SK Officials</div>
            <small>KK Youth Profiling System</small>
        </div>
    </div>
    <div class="header-account-area">
        <?php if (current_user()): ?>
            <div class="header-avatar"><?= e(uinit()) ?></div>
            <div class="d-none d-md-block header-identity">
                <div class="header-identity-name"><?= e(uname()) ?></div>
                <div class="header-identity-role"><?= e(strtoupper(urole())) ?></div>
            </div>
            <?php if ($selfRole): ?>
                <a href="member-portal.php" class="btn btn-sm btn-outline-primary">My member profile</a>
                <a href="#myOfficialProfile" class="btn btn-sm btn-outline-primary">My official profile</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-sm header-logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
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

    <div class="officials-hierarchy">
        <?php foreach ($groups as $pos => $list): ?>
            <?php $groupClass = 'official-group-' . strtolower(str_replace(' ', '-', $pos)); ?>
            <section class="official-group <?= e($groupClass) ?>">
                <h2 class="group-title"><?= e($pos) ?><?= count($list) > 1 ? 's' : '' ?></h2>
                <div class="cards">
                    <?php foreach ($list as $o): ?>
                        <article class="official<?= !empty($o['vacant']) ? ' official-vacant' : '' ?>">
                            <?php if (!empty($o['vacant'])): ?>
                                <div class="official-vacant-card">
                                    <span class="official-vacant-icon"><i class="bi bi-person-dash" aria-hidden="true"></i></span>
                                    <strong class="official-vacant-title">Vacant</strong>
                                    <span class="official-vacant-position"><?= e($o['position']) ?></span>
                                    <span class="official-vacant-note">No official is currently assigned.</span>
                                </div>
                            <?php else: ?>
                            <button
                                type="button"
                                class="official-profile-trigger"
                                data-official-name="<?= e($o['name']) ?>"
                                data-official-position="<?= e($o['position']) ?>"
                                data-official-photo="<?= e($o['photo']) ?>"
                                data-official-initials="<?= e($o['initials']) ?>"
                                data-official-bio="<?= e($o['bio']) ?>"
                                data-official-honorarium="<?= $o['monthlyHonorarium'] !== null ? e(number_format($o['monthlyHonorarium'], 2, '.', '')) : '' ?>"
                                data-official-saln-id="<?= e($o['salnId']) ?>"
                                data-official-saln-year="<?= e($o['salnYear']) ?>"
                                aria-label="View <?= e($o['position']) ?> <?= e($o['name']) ?> profile and public SALN"
                            >
                                <?php if ($o['photo']): ?>
                                    <img class="pic" src="<?= e($o['photo']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <div class="pic ph" aria-hidden="true"><?= e($o['initials']) ?></div>
                                <?php endif; ?>
                                <div class="nm"><?= e($o['name']) ?></div>
                                <div class="pos"><?= e($o['position']) ?></div>
                                <div class="profile-hint">
                                    <span class="saln-state">
                                        <i class="bi <?= $o['salnId'] !== '' ? 'bi-file-earmark-check' : 'bi-file-earmark' ?> me-1" aria-hidden="true"></i>
                                        <?= $o['salnId'] !== '' ? 'SALN posted' : 'Profile' ?>
                                    </span>
                                    <span class="view-label">View details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                                </div>
                            </button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <?php if ($selfUser && can_write_app_data()): ?>
        <section class="hero mt-4" id="myOfficialProfile">
            <h2 class="h4 fw-bold">My official profile</h2>
            <?php if (!$selfRole): ?>
                <p class="mb-0">Your account is not linked to an active SK member profile. Ask the SK Chairperson or Secretary to link it before uploading your profile or SALN.</p>
            <?php else: ?>
                <?php
                    $selfOriginalId = (string)($selfSaln['originalId'] ?? '');
                    if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $selfOriginalId)) $selfOriginalId = '';
                    $selfPublicId = (string)($selfSaln['publicId'] ?? '');
                    if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$/', $selfPublicId)) $selfPublicId = '';
                    $selfSalnYear = preg_match('/^\d{4}$/', (string)($selfSaln['year'] ?? '')) ? (string)$selfSaln['year'] : '';
                ?>
                <p>Update your public profile. To publish a SALN, upload both copies: the original stays private and your redacted copy is made public.</p>
                <form id="officialProfileForm" class="text-start" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="officialBio">Public bio</label>
                        <textarea class="form-control" id="officialBio" name="bio" rows="3" maxlength="1000"><?= e((string)($selfProfile['bio'] ?? '')) ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="officialHonorarium">Monthly SK honorarium (₱)</label>
                            <input class="form-control" type="number" id="officialHonorarium" name="monthlyHonorarium" min="0" max="10000000" step="0.01"
                                value="<?= isset($selfProfile['monthlyHonorarium']) && is_numeric($selfProfile['monthlyHonorarium']) ? e((string)$selfProfile['monthlyHonorarium']) : '' ?>">
                            <div class="form-text">Leave blank until the verified amount is available.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="officialPhoto">Profile photo</label>
                            <input class="form-control" type="file" id="officialPhoto" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <div class="form-text">JPEG, PNG, or WebP; up to 3 MB.</div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-2 fw-bold">SALN publication</div>
                    <?php if ($selfOriginalId !== ''): ?>
                        <p class="small">
                            Current original: <a href="api/official-files.php?action=saln_original_view&amp;id=<?= e(rawurlencode($selfOriginalId)) ?>">Download private original</a>
                            <?php if ($selfPublicId !== ''): ?> · <a href="official_public_file.php?type=saln&amp;id=<?= e(rawurlencode($selfPublicId)) ?>" target="_blank" rel="noopener noreferrer">View published copy</a><?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p class="small">No SALN has been uploaded for your profile.</p>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="salnYear">SALN year</label>
                            <input class="form-control" type="number" id="salnYear" name="salnYear" min="1900" max="2100" value="<?= e($selfSalnYear !== '' ? $selfSalnYear : date('Y')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="salnOriginal">Private original</label>
                            <input class="form-control" type="file" id="salnOriginal" name="salnOriginal" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="salnPublic">Redacted public copy</label>
                            <input class="form-control" type="file" id="salnPublic" name="salnPublic" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                    <div class="form-text mt-2">Upload both SALN files together. PDF, JPEG, PNG, or WebP; up to 8 MB each. Anyone can access the redacted copy.</div>
                    <button class="btn btn-primary mt-3" type="submit">Save profile and files</button>
                    <div id="officialProfileStatus" class="small mt-3" role="status" aria-live="polite"></div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="footer-note">Sangguniang Kabataan of Barangay Sikat &bull; Alfonso, Cavite</div>
</div>

</main>

<div class="modal fade official-profile-modal" id="officialProfileModal" tabindex="-1" aria-labelledby="officialProfileModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="official-profile-modal-kicker">SK OFFICIAL PROFILE</div>
                    <h2 class="modal-title" id="officialProfileModalTitle">Official profile</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close profile"></button>
            </div>
            <div class="modal-body">
                <div class="official-profile-layout">
                    <section class="official-profile-summary" aria-label="Official details">
                        <div class="official-profile-identity">
                            <img id="officialProfilePhoto" class="official-profile-photo" alt="" hidden>
                            <div id="officialProfileInitials" class="avatar official-profile-initials" aria-hidden="true" hidden></div>
                            <div class="official-profile-identity-copy">
                                <div id="officialProfilePosition" class="official-profile-position"></div>
                                <div id="officialProfileHonorarium" class="official-profile-honorarium"></div>
                            </div>
                        </div>
                        <section class="official-profile-bio" aria-labelledby="officialProfileBioTitle">
                            <h3 id="officialProfileBioTitle"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> About</h3>
                            <p id="officialProfileBio" class="official-profile-bio-copy"></p>
                        </section>
                    </section>
                    <section class="official-profile-saln" aria-labelledby="officialProfileSalnTitle">
                        <div class="official-profile-saln-heading">
                            <div>
                                <div class="official-profile-modal-kicker">PUBLIC DISCLOSURE</div>
                                <h3 id="officialProfileSalnTitle">Statement of Assets, Liabilities and Net Worth</h3>
                            </div>
                            <a id="officialProfileSalnOpen" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" hidden>Open SALN</a>
                        </div>
                        <div class="official-profile-saln-viewer">
                            <p id="officialProfileNoSaln" class="official-profile-no-saln" hidden>
                                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                <span>No public SALN has been posted for this official.</span>
                            </p>
                            <img id="officialProfileSalnImage" class="official-profile-saln-image" alt="" hidden>
                            <iframe id="officialProfileSalnPdf" class="official-profile-saln-pdf" title="Public SALN document" hidden></iframe>
                        </div>
                    </section>
                </div>
            </div>
            <div class="modal-footer">
                <span class="official-profile-footer-note">Public profile information</span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(function () {
    var modalElement = document.getElementById('officialProfileModal');
    var modal = new bootstrap.Modal(modalElement);
    var profilePhoto = document.getElementById('officialProfilePhoto');
    var profileInitials = document.getElementById('officialProfileInitials');
    var salnImage = document.getElementById('officialProfileSalnImage');
    var salnPdf = document.getElementById('officialProfileSalnPdf');
    var salnOpen = document.getElementById('officialProfileSalnOpen');
    var noSaln = document.getElementById('officialProfileNoSaln');

    document.querySelectorAll('.official-profile-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var data = trigger.dataset;
            var name = data.officialName || 'SK Official';
            var photo = data.officialPhoto || '';
            var salnId = data.officialSalnId || '';
            var salnUrl = salnId
                ? 'official_public_file.php?type=saln&id=' + encodeURIComponent(salnId)
                : '';

            document.getElementById('officialProfileModalTitle').textContent = name;
            document.getElementById('officialProfilePosition').textContent = data.officialPosition || '';
            document.getElementById('officialProfileBio').textContent = data.officialBio || 'No profile bio has been posted.';
            document.getElementById('officialProfileHonorarium').textContent = data.officialHonorarium
                ? 'Monthly honorarium: ₱' + Number(data.officialHonorarium).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })
                : '';
            profilePhoto.src = '';
            profilePhoto.alt = '';
            profilePhoto.hidden = !photo;
            profileInitials.textContent = data.officialInitials || '';
            profileInitials.hidden = !!photo;
            if (photo) {
                profilePhoto.src = photo;
                profilePhoto.alt = 'Photo of ' + name;
            }

            salnImage.removeAttribute('src');
            salnImage.alt = '';
            salnImage.hidden = true;
            salnPdf.removeAttribute('src');
            salnPdf.hidden = true;
            salnOpen.href = salnUrl || '#';
            salnOpen.textContent = data.officialSalnYear
                ? 'Open ' + data.officialSalnYear + ' SALN'
                : 'Open SALN';
            salnOpen.hidden = !salnUrl;
            noSaln.hidden = !!salnUrl;
            if (salnUrl) {
                if (/\.pdf$/i.test(salnId)) {
                    salnPdf.src = salnUrl;
                    salnPdf.title = (data.officialSalnYear || 'Public') + ' SALN for ' + name;
                    salnPdf.hidden = false;
                } else {
                    salnImage.src = salnUrl;
                    salnImage.alt = (data.officialSalnYear || 'Public') + ' SALN for ' + name;
                    salnImage.hidden = false;
                }
            }

            modal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        profilePhoto.removeAttribute('src');
        salnImage.removeAttribute('src');
        salnPdf.removeAttribute('src');
    });
})();
</script>
<?php if ($selfRole): ?>
<script>
document.getElementById('officialProfileForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const form = event.currentTarget;
    const status = document.getElementById('officialProfileStatus');
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    status.textContent = 'Saving profile and SALN files…';
    try {
        const response = await fetch('api/official-files.php?action=profile_files', {
            method: 'POST',
            headers: { 'X-CSRF-Token': <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?> },
            body: new FormData(form)
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'Could not save your profile.');
        status.textContent = 'Profile saved. Reloading…';
        window.location.reload();
    } catch (error) {
        status.textContent = error.message || 'Could not save your profile.';
        button.disabled = false;
    }
});
</script>
<?php endif; ?>
</body>
</html>