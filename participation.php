<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Participation</title>

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
        <div class="header-title">Participation</div>
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

        <h1>Youth Participation</h1>

        <p>
            Monitor youth involvement and participation in SK activities and programs.
        </p>

    </section>

    <div class="row g-3">

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-people"></i>
                <div class="number">124</div>
                <div class="label">Active Youth</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-person-check"></i>
                <div class="number">86</div>
                <div class="label">Participating Youth</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-trophy"></i>
                <div class="number">8</div>
                <div class="label">Highly Active</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat">
                <i class="bi bi-calendar-event"></i>
                <div class="number">12</div>
                <div class="label">Programs Joined</div>
            </div>
        </div>

    </div>

    <div class="card-box">

        <div class="card-header">
            <i class="bi bi-person-check me-2"></i>
            Youth Participation Records
        </div>

        <div class="card-body">

            <div class="row g-2 mb-4">

                <div class="col-md-5">
                    <input type="text"
                           id="search"
                           class="form-control"
                           placeholder="Search youth member...">
                </div>

                <div class="col-md-4">

                    <select class="form-select">

                        <option>All Activities</option>
                        <option>Barangay Basketball League</option>
                        <option>Youth Study Assistance</option>
                        <option>Youth Health Awareness</option>
                        <option>Youth Clean-Up Drive</option>

                    </select>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Member ID</th>
                            <th>Full Name</th>
                            <th>Programs Joined</th>
                            <th>Participation</th>
                            <th>Last Activity</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody id="participationTable">

                        <tr>
                            <td>KK-2026-014</td>
                            <td><strong>Maria Clara Santos</strong></td>
                            <td>8</td>
                            <td style="min-width:180px;">
                                <div class="progress">
                                    <div class="progress-bar" style="width:90%;"></div>
                                </div>
                                <small>90%</small>
                            </td>
                            <td>Sep 18, 2026</td>
                            <td><span class="badge bg-dark">Active</span></td>
                        </tr>

                        <tr>
                            <td>KK-2026-013</td>
                            <td><strong>Juan Miguel Dela Cruz</strong></td>
                            <td>6</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" style="width:75%;"></div>
                                </div>
                                <small>75%</small>
                            </td>
                            <td>Sep 15, 2026</td>
                            <td><span class="badge bg-dark">Active</span></td>
                        </tr>

                        <tr>
                            <td>KK-2026-012</td>
                            <td><strong>Angelica Reyes</strong></td>
                            <td>3</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" style="width:40%;"></div>
                                </div>
                                <small>40%</small>
                            </td>
                            <td>Sep 10, 2026</td>
                            <td><span class="badge bg-secondary">Low</span></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="footer-note">
        KK Youth Profiling System • SK Barangay Sikat, Alfonso, Cavite • FY 2026–2027
    </div>

</div>

</main>

<script>

document.getElementById("search").addEventListener("input", function() {

    const value = this.value.toLowerCase();

    document.querySelectorAll("#participationTable tr").forEach(row => {

        row.style.display =
            row.innerText.toLowerCase().includes(value)
            ? ""
            : "none";

    });

});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>