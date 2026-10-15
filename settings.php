<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Settings</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">

    <style>
        :root {
            --kk-primary:var(--bs-primary);
            --black:var(--bs-body-color);
            --cream:var(--bs-tertiary-bg);
            --line:var(--bs-border-color);
            --muted:var(--bs-secondary-color);
        }

        * { box-sizing:border-box; }

        body {
            margin:0;
            font-family:Arial,sans-serif;
            background:var(--cream);
            color:var(--bs-body-color);
        }

        .top-strip {
            height:7px;
            background:var(--bs-primary);
        }

        .header {
            height:70px;
            background:white;
            border-bottom:1px solid var(--line);
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:0 30px;
        }

        .header-title {
            font-weight:bold;
            font-size:20px;
        }

        .header small {
            color:var(--muted);
            font-size:14px;
        }

        .user-area {
            display:flex;
            align-items:center;
            gap:10px;
        }

        .avatar {
            width:38px;
            height:38px;
            background:var(--bs-primary);
            border-radius:50%;
            display:flex;
            justify-content:center;
            align-items:center;
            font-weight:bold;
        }

        .content {
            padding:30px;
        }

        .hero {
            background:var(--bs-primary);
            border-radius:16px;
            padding:28px;
            margin-bottom:25px;
        }

        .hero h1 {
            font-size:32px;
            font-weight:800;
        }

        .hero p {
            color:var(--bs-primary-text-emphasis);
            font-size:16px;
            margin:0;
        }

        .card-box {
            background:white;
            border:1px solid var(--line);
            border-radius:14px;
            margin-bottom:22px;
            overflow:hidden;
        }

        .card-header {
            padding:17px 20px;
            border-bottom:1px solid var(--line);
            font-weight:bold;
        }

        .card-body {
            padding:20px;
        }

        .stat {
            background:white;
            border:1px solid var(--line);
            border-radius:13px;
            padding:20px;
        }

        .stat i {
            display:flex;
            width:42px;
            height:42px;
            background:var(--bs-primary);
            border-radius:9px;
            align-items:center;
            justify-content:center;
            font-size:23px;
            margin-bottom:12px;
        }

        .number {
            font-size:29px;
            font-weight:800;
        }

        .label {
            color:var(--muted);
            font-size:14px;
        }

        .table {
            font-size:14px;
        }

        .table th {
            background:var(--bs-primary-text-emphasis);
            color:white;
            font-size:13px;
            padding:13px;
        }

        .table td {
            padding:13px;
            vertical-align:middle;
        }

        .progress {
            height:8px;
            background:var(--bs-border-color);
        }

        .progress-bar {
            background:var(--bs-primary);
        }

        .footer-note {
            text-align:center;
            color:var(--muted);
            font-size:13px;
            padding:25px;
        }

        @media(max-width:991px) {
            .content { padding:18px; }
        }
    </style>
<?php include __DIR__ . '/init.php'; ?>
</head>

<body>

<div class="top-strip"></div>

<script src="sidebar.js?v=3"></script>

<main class="main page-content">

<header class="header">

    <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
        <div class="header-title">Settings</div>
        <small>KK Youth Profiling System</small>
    </div></div>

    <div class="user-area">

        <div>
            <div style="font-size:15px;font-weight:bold;">
                <?= e(uname()) ?>
            </div>
            <small><?= e(strtoupper(urole())) ?></small>
        </div>

        <div class="avatar"><?= e(uinit()) ?></div>

    </div>

</header>

<div class="content">

    <section class="hero">
        <h1>Settings</h1>
        <p><?= is_public() ? 'Choose how the system looks for you. You are viewing as a public viewer.' : 'Your account and display settings.' ?></p>
    </section>

    <div id="msg"></div>

    <div class="row g-3">

        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-person-circle me-2"></i>Account</div>
                <div class="card-body">
                    <?php if (current_user()): $u = current_user(); ?>
                        <div class="mb-1"><span class="label">Name</span><div class="fw-bold"><?= e($u['name']) ?></div></div>
                        <div class="mb-1"><span class="label">Position</span><div class="fw-bold"><?= e($u['role']) ?></div></div>
                        <div class="mb-3"><span class="label">Username</span><div class="fw-bold"><?= e($u['username']) ?></div></div>
                        <a href="logout.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                    <?php else: ?>
                        <p class="mb-2">You are using the system as a <b>Public Viewer</b> (view only). You can read the pages and send a KK registration, but you cannot change any records.</p>
                        <a href="registration.php" class="btn btn-primary btn-sm me-1"><i class="bi bi-person-plus-fill me-1"></i>KK Youth Registration</a>
                        <a href="logout.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Official Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-type me-2"></i>Display</div>
                <div class="card-body">
                    <label class="form-label fw-bold" for="textSize">Text size</label>
                    <select id="textSize" class="form-select mb-2">
                        <option value="small">Small</option>
                        <option value="normal">Normal</option>
                        <option value="large">Large</option>
                    </select>
                    <div class="label">Saved on this device only. The page reloads to apply it.</div>
                </div>
            </div>
        </div>

        <?php if (current_user()): ?>
        <div class="col-lg-6">
            <div class="card-box">
                <div class="card-header"><i class="bi bi-key-fill me-2"></i>Change Password</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label" for="curPass">Current password</label>
                        <input type="password" id="curPass" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="newPass">New password <small class="text-muted">(at least 6 characters)</small></label>
                        <input type="password" id="newPass" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="newPass2">Confirm new password</label>
                        <input type="password" id="newPass2" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="button" class="btn btn-primary" id="savePassBtn"><i class="bi bi-check-circle me-1"></i>Save password</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="footer-note">
        KK Youth Profiling System • SK Barangay Sikat, Alfonso, Cavite • FY 2026–2027
    </div>

</div>

</main>

<script>
function note(type, text) {
    var d = document.getElementById("msg");
    d.innerHTML = '<div class="alert alert-' + type + ' small">' + text.replace(/[<>&]/g, "") + '</div>';
    window.scrollTo({ top: 0, behavior: "smooth" });
}

/* text size (saved in this browser; sidebar.js applies it on every page) */
var sel = document.getElementById("textSize");
try { sel.value = localStorage.getItem("kkTextSize") || "normal"; } catch (e) {}
sel.addEventListener("change", function () {
    try { localStorage.setItem("kkTextSize", sel.value); } catch (e) {}
    location.reload();
});

/* change password (officers only) */
var btn = document.getElementById("savePassBtn");
if (btn) btn.addEventListener("click", function () {
    var cur = document.getElementById("curPass").value;
    var n1 = document.getElementById("newPass").value;
    var n2 = document.getElementById("newPass2").value;
    if (!cur || !n1) { note("danger", "Please fill in your current and new password."); return; }
    if (n1.length < 6) { note("danger", "The new password must be at least 6 characters."); return; }
    if (n1 !== n2) { note("danger", "The new passwords do not match."); return; }
    fetch("api.php?action=password", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ current: cur, "new": n1 })
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.ok) {
            note("success", "Your password was changed.");
            ["curPass", "newPass", "newPass2"].forEach(function (id) { document.getElementById(id).value = ""; });
        } else note("danger", res.error || "Could not change the password.");
    })
    .catch(function () { note("danger", "Could not reach the server."); });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>