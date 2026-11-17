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
    $seen[$kid] = 1;
    $name = trim(($m['firstName'] ?? '') . ' ' . (!empty($m['middleName']) ? mb_substr($m['middleName'], 0, 1) . '. ' : '') . ($m['lastName'] ?? '') . (!empty($m['suffix']) ? ' ' . $m['suffix'] : ''));
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
    <link href="sidebar.css?v=31" rel="stylesheet">
    <script src="theme.js"></script>

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
        .hero { background:#fff; border:1px solid var(--line); border-radius:8px; padding:24px; margin-bottom:24px; text-align:center; }
        .hero h1 { font-size:28px; font-weight:900; margin:0 0 6px; }
        .hero p { margin:0; color:var(--bs-secondary-color); }
        .group-title { font-size:13px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:var(--bs-secondary-color); margin:28px 0 14px; text-align:center; }
        .cards { display:flex; flex-wrap:wrap; justify-content:center; gap:18px; }
        .official { width:200px; background:#fff; border:1px solid var(--line); border-radius:8px; padding:20px 14px; text-align:center; }
        .official .pic { width:112px; height:112px; border-radius:50%; margin:0 auto 14px; object-fit:cover; border:3px solid var(--bs-primary-bg-subtle); display:block; }
        .official .ph { display:flex; align-items:center; justify-content:center; background:var(--bs-primary-bg-subtle); color:var(--bs-primary-text-emphasis); font-size:34px; font-weight:800; }
        .official .nm { font-weight:800; font-size:16px; line-height:1.25; }
        .official .pos { margin-top:6px; font-size:13px; color:var(--bs-primary); font-weight:700; }
        .official .bio { margin-top:10px; font-size:13px; color:var(--bs-secondary-color); }
        .official .honorarium { margin-top:10px; font-size:13px; font-weight:700; }
        .official .saln-link { display:inline-block; margin-top:8px; font-size:12px; }
        .footer-note { text-align:center; color:var(--bs-secondary-color); font-size:13px; margin:34px 0 10px; }
        .login-link { font-size:14px; }
        html.dark-mode body,
        html.dark-mode .hero,
        html.dark-mode .official { background:var(--bs-body-bg); color:var(--bs-body-color); }
        html.dark-mode .header { background:var(--bs-body-bg); color:var(--bs-body-color); }
        html.dark-mode .header small,
        html.dark-mode .hero p,
        html.dark-mode .footer-note { color:var(--bs-secondary-color); }
        html.dark-mode .hero,
        html.dark-mode .official { border-color:var(--bs-border-color); }
    </style>
</head>
<body>

<div class="top-strip"></div>

<script>
window.SK_PUBLIC = <?= is_limited_viewer() ? 'true' : 'false' ?>;
window.SK_MEMBER_PORTAL = <?= !empty($selfUser['legacy_id']) ? 'true' : 'false' ?>;
</script>
<script src="sidebar.js?v=15"></script>

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
            <a href="announcements.php" class="header-notification" aria-label="Notifications">
                <i class="bi bi-bell"></i><span class="header-notification-count">3</span>
            </a>
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
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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