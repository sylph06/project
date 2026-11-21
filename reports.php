<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Reports</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link href="css/sidebar.css?v=31" rel="stylesheet">

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
            background:var(--bs-body-bg);
            border-bottom:1px solid var(--line);
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:0 30px;
        }

        .header-title { font-size:20px; font-weight:bold; }
        .header small { color:var(--muted); font-size:14px; }

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

        .content { padding:30px; }

        .hero {
            background:var(--bs-primary);
            border-radius:16px;
            padding:28px;
            margin-bottom:25px;
        }

        .hero h1 {
            font-size:32px;
            font-weight:800;
            margin-bottom:6px;
        }

        .hero p {
            margin:0;
            color:var(--bs-primary-text-emphasis);
            font-size:16px;
        }

        .report-card {
            background:var(--bs-body-bg);
            border:1px solid var(--line);
            border-radius:14px;
            padding:22px;
            height:100%;
        }

        .report-icon {
            width:48px;
            height:48px;
            background:var(--bs-primary);
            border-radius:10px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:25px;
            margin-bottom:15px;
        }

        .report-card h5 {
            font-size:17px;
            font-weight:bold;
        }

        .report-card p {
            font-size:14px;
            color:var(--muted);
            min-height:38px;
        }

        .btn-report {
            background:var(--bs-primary-text-emphasis);
            color:white;
            border:none;
            border-radius:7px;
            padding:9px 15px;
            font-size:14px;
            font-weight:bold;
        }

        .btn-report:hover {
            background:var(--bs-body-color);
            color:white;
        }

        .filter-box {
            background:var(--bs-body-bg);
            border:1px solid var(--line);
            border-radius:14px;
            padding:20px;
            margin-top:25px;
        }

        .form-control,
        .form-select {
            height:42px;
            font-size:15px;
            border-radius:7px;
        }

        .footer-note {
            text-align:center;
            color:var(--muted);
            font-size:13px;
            padding:25px;
        }

        /* Budget report modal */
        .budget-modal {
            position:fixed;
            inset:0;
            z-index:2000;
            background:rgba(0,0,0,.58);
            display:none;
            overflow-y:auto;
            padding:25px;
        }

        .budget-modal.show { display:block; }

        .budget-modal-box {
            width:min(1450px,100%);
            margin:0 auto;
            background:var(--bs-tertiary-bg);
            border-radius:12px;
            overflow:hidden;
            box-shadow:0 20px 60px rgba(0,0,0,.3);
        }

        .budget-modal-header {
            background:var(--bs-body-bg);
            padding:18px 24px;
            border-bottom:1px solid var(--bs-border-color);
            display:flex;
            align-items:center;
            justify-content:space-between;
            position:sticky;
            top:0;
            z-index:5;
        }

        .budget-modal-header h3 {
            margin:0;
            font-size:23px;
            font-weight:800;
        }

        .budget-modal-header p {
            margin:3px 0 0;
            color:var(--bs-secondary-color);
            font-size:14px;
        }

        .close-budget {
            border:0;
            background:var(--bs-primary-text-emphasis);
            color:white;
            width:38px;
            height:38px;
            border-radius:8px;
            font-size:23px;
        }

        .budget-body { padding:20px; }

        .budget-heading {
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:10px;
        }

        .budget-heading h4 {
            margin:0;
            font-size:17px;
            font-weight:800;
        }

        .fy-badge {
            background:rgba(var(--bs-primary-rgb),.08);
            color:var(--bs-primary);
            padding:8px 13px;
            border-radius:9px;
            font-size:13px;
            font-weight:bold;
        }

        .fund-box {
            background:rgba(var(--bs-primary-rgb),.08);
            border:1px solid var(--bs-primary-border-subtle);
            border-radius:12px;
            padding:18px;
            margin-bottom:14px;
        }

        .fund-grid {
            display:grid;
            grid-template-columns:1fr 1.25fr .85fr;
            gap:10px;
            align-items:end;
        }

        .field-label {
            display:block;
            font-size:14px;
            font-weight:bold;
            margin-bottom:6px;
        }

        .budget-input,
        .budget-select,
        .budget-textarea {
            width:100%;
            border:1px solid var(--bs-border-color);
            background:var(--bs-tertiary-bg);
            border-radius:8px;
            padding:10px 12px;
            font-size:15px;
            outline:none;
        }

        .budget-input:focus,
        .budget-select:focus,
        .budget-textarea:focus {
            border-color:var(--bs-primary);
            box-shadow:0 0 0 2px rgba(var(--bs-primary-rgb),.2);
        }

        .save-budget-btn,
        .add-expense-btn,
        .pdf-btn {
            border:0;
            background:var(--bs-body-color);
            color:#fff;
            border-radius:8px;
            padding:10px 15px;
            font-size:14px;
            font-weight:bold;
            width:100%;
        }

        .save-budget-btn:hover,
        .add-expense-btn:hover,
        .pdf-btn:hover {
            background:var(--bs-body-color);
        }

        html[data-bs-theme="dark"] .save-budget-btn,
        html[data-bs-theme="dark"] .add-expense-btn,
        html[data-bs-theme="dark"] .pdf-btn {
            background:var(--bs-primary)!important;
            border-color:var(--bs-primary)!important;
            color:var(--bs-white)!important;
        }

        html[data-bs-theme="dark"] .save-budget-btn:hover,
        html[data-bs-theme="dark"] .add-expense-btn:hover,
        html[data-bs-theme="dark"] .pdf-btn:hover {
            background:var(--bs-primary-text-emphasis)!important;
            border-color:var(--bs-primary-text-emphasis)!important;
            color:#071a33!important;
        }

        .summary-grid {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:12px;
            margin-bottom:14px;
        }

        .summary-card {
            background:var(--bs-body-bg);
            border:1px solid var(--bs-border-color);
            border-radius:11px;
            padding:16px;
        }

        .summary-label {
            color:var(--bs-secondary-color);
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:.3px;
            margin-bottom:6px;
        }

        .summary-value {
            font-size:24px;
            font-weight:800;
        }

        .spent-value { color:var(--bs-danger); }
        .balance-value { color:var(--bs-success); }

        .summary-note {
            color:var(--bs-secondary-color);
            font-size:12px;
            margin-top:5px;
        }

        .progress-wrap {
            height:8px;
            background:var(--bs-border-color);
            border-radius:10px;
            overflow:hidden;
            margin-top:8px;
        }

        .progress-fill {
            height:100%;
            width:0%;
            background:var(--bs-primary);
            transition:width .2s ease;
        }

        .entry-grid {
            display:grid;
            grid-template-columns:1.3fr 1fr;
            gap:14px;
            margin-bottom:14px;
        }

        .entry-card,
        .where-card,
        .records-card {
            background:var(--bs-body-bg);
            border:1px solid var(--bs-border-color);
            border-radius:11px;
            padding:16px;
        }

        .entry-card h4,
        .where-card h4,
        .records-card h4 {
            font-size:17px;
            font-weight:800;
            margin:0 0 18px;
        }

        .entry-row {
            display:grid;
            grid-template-columns:1fr 1.6fr;
            gap:10px;
            margin-bottom:12px;
        }

        .entry-row.three {
            grid-template-columns:1.2fr .75fr 1.2fr;
        }

        .entry-row.four {
            grid-template-columns:1.2fr .75fr .75fr 1.2fr;
        }

        .budget-textarea {
            min-height:70px;
            resize:vertical;
        }

        .add-expense-btn {
            width:auto;
            margin-top:12px;
            padding:10px 16px;
        }

        .where-empty {
            min-height:190px;
            display:flex;
            justify-content:center;
            align-items:center;
            color:var(--bs-secondary-color);
            font-size:14px;
            text-align:center;
        }

        .category-item {
            margin-bottom:13px;
        }

        .category-head {
            display:flex;
            justify-content:space-between;
            gap:10px;
            font-size:13px;
            font-weight:bold;
            margin-bottom:5px;
        }

        .category-bar {
            height:8px;
            background:var(--bs-border-color);
            border-radius:10px;
            overflow:hidden;
        }

        .category-bar span {
            display:block;
            height:100%;
            background:var(--bs-primary);
        }

        .records-card { overflow:hidden; }

        .records-top {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:12px;
        }

        .expense-count {
            background:rgba(var(--bs-primary-rgb),.08);
            color:var(--bs-primary);
            padding:8px 12px;
            border-radius:8px;
            font-size:13px;
            font-weight:bold;
        }

        .table-scroll { overflow-x:auto; }

        .expense-table {
            width:100%;
            border-collapse:collapse;
            min-width:950px;
            font-size:13px;
        }

        .expense-table th {
            background:var(--bs-primary-border-subtle);
            padding:10px 9px;
            text-align:left;
            font-size:12px;
            white-space:nowrap;
        }

        .expense-table td {
            border-bottom:1px solid var(--bs-border-color);
            padding:9px;
            vertical-align:top;
        }

        .empty-row {
            text-align:center;
            color:var(--bs-secondary-color);
            padding:25px !important;
        }

        .table-action {
            border:0;
            border-radius:6px;
            padding:5px 8px;
            font-size:12px;
            font-weight:bold;
            margin:2px;
        }

        .edit-btn { background:var(--bs-primary-text-emphasis); color:#fff; }
        .delete-btn { background:var(--bs-danger-bg-subtle); color:var(--bs-danger); }

        .pdf-section {
            margin-top:14px;
            background:var(--bs-body-bg);
            border:1px solid var(--bs-border-color);
            border-radius:11px;
            padding:16px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:15px;
        }

        .pdf-section h4 {
            margin:0 0 4px;
            font-size:17px;
            font-weight:800;
        }

        .pdf-section p {
            margin:0;
            color:var(--bs-secondary-color);
            font-size:13px;
        }

        .pdf-btn {
            width:auto;
            min-width:210px;
            background:var(--bs-primary-text-emphasis);
        }

        .pdf-btn i { margin-right:5px; }

        @media(max-width:991px) {
            .content { padding:18px; }
            .summary-grid { grid-template-columns:repeat(2,1fr); }
            .entry-grid { grid-template-columns:1fr; }
            .fund-grid { grid-template-columns:1fr; }
        }

        @media(max-width:600px) {
            .header { padding:0 15px; }
            .content { padding:12px; }
            .summary-grid { grid-template-columns:1fr; }
            .budget-modal { padding:8px; }
            .budget-body { padding:10px; }
            .entry-row,
            .entry-row.three,
            .entry-row.four { grid-template-columns:1fr; }
            .pdf-section { flex-direction:column; align-items:stretch; }
            .pdf-btn { width:100%; }
        }
    </style>
<?php include __DIR__ . '/init.php'; ?>
</head>

<body>

<div class="top-strip"></div>

<script src="js/theme.js"></script>
<script src="js/sidebar.js?v=15"></script>

<main class="main page-content">

<header class="header">
    <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
        <div class="header-title">Reports</div>
        <small>KK Youth Profiling System</small>
    </div></div>

    <div class="header-account-area">
        <a href="announcements.php" class="header-notification" aria-label="Notifications">
            <i class="bi bi-bell"></i><span class="header-notification-count">3</span>
        </a>
        <div class="header-avatar"><?= e(uinit()) ?></div>
        <div class="d-none d-md-block header-identity">
            <div class="header-identity-name"><?= e(uname()) ?></div>
            <div class="header-identity-role"><?= e(strtoupper(urole())) ?></div>
        </div>
        <a href="<?= is_public() ? 'login.php' : 'logout.php' ?>" class="btn btn-sm header-logout">
            <i class="bi bi-box-arrow-right me-1"></i>
            <?= is_public() ? 'Official Login' : 'Logout' ?>
        </a>
    </div>
</header>

<div class="content">

<section class="hero hero-primary">
    <h1>Reports & Analytics</h1>
    <p>Generate reports for KK youth profiling, attendance, participation, and programs.</p>
</section>

<div class="row g-3">

    <div class="col-md-4">
        <div class="report-card">
            <div class="report-icon"><i class="bi bi-people"></i></div>
            <h5>KK Member Report</h5>
            <p>View registered youth members and their basic profile information.</p>
            <button class="btn-report" onclick="generateReport('KK Member Report')">
                <i class="bi bi-file-earmark-text me-1"></i> Generate
            </button>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card">
            <div class="report-icon"><i class="bi bi-calendar-check"></i></div>
            <h5>Attendance Report</h5>
            <p>View attendance records from different SK activities and programs.</p>
            <button class="btn-report" onclick="generateReport('Attendance Report')">
                <i class="bi bi-file-earmark-text me-1"></i> Generate
            </button>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card">
            <div class="report-icon"><i class="bi bi-person-check"></i></div>
            <h5>Participation Report</h5>
            <p>Review confirmed attendance by activity, age group, and Purok.</p>
            <button class="btn-report" onclick="generateReport('Participation Report')">
                <i class="bi bi-file-earmark-text me-1"></i> Generate
            </button>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card">
            <div class="report-icon"><i class="bi bi-diagram-3"></i></div>
            <h5>Programs Report</h5>
            <p>View activities and programs conducted by the SK.</p>
            <button class="btn-report" onclick="generateReport('Programs Report')">
                <i class="bi bi-file-earmark-text me-1"></i> Generate
            </button>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card">
            <div class="report-icon"><i class="bi bi-graph-up"></i></div>
            <h5>Annual Summary</h5>
            <p>Generate a summary of youth programs, participation, and activities.</p>
            <button class="btn-report" onclick="generateReport('Annual Summary')">
                <i class="bi bi-file-earmark-text me-1"></i> Generate
            </button>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card">
            <div class="report-icon"><i class="bi bi-cash-stack"></i></div>
            <h5>Budget & Expense Report</h5>
            <p>View the SK budget, expenses, remaining balance, utilization, and spending breakdown.</p>
            <button class="btn-report" onclick="generateBudgetReport()">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate
            </button>
        </div>
    </div>

</div>

<div class="filter-box">
    <h5 style="font-size:17px;font-weight:bold;">Report Filters</h5>

    <div class="row g-3 mt-1">
        <div class="col-md-4">
            <label class="form-label" style="font-size:14px;font-weight:bold;">Report Type</label>
            <select class="form-select" id="filterReportType">
                <option>KK Member Report</option>
                <option>Attendance Report</option>
                <option>Participation Report</option>
                <option>Programs Report</option>
                <option>Annual Summary</option>
                <option>Budget & Expense Report</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" style="font-size:14px;font-weight:bold;">Start Date</label>
            <input type="date" class="form-control" id="filterStartDate">
        </div>

        <div class="col-md-3">
            <label class="form-label" style="font-size:14px;font-weight:bold;">End Date</label>
            <input type="date" class="form-control" id="filterEndDate">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn-report w-100" onclick="generateFilteredReport()">Generate</button>
        </div>
    </div>
    <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" id="includeMinutes">
        <label class="form-check-label" for="includeMinutes">Include minutes in the SK meeting attendance report</label>
    </div>
</div>

<div class="footer-note">
    KK Youth Profiling System • SK Barangay Sikat, Alfonso, Cavite • FY 2026–2027
</div>

</div>
</main>

<!-- EDITABLE BUDGET & EXPENSE INTERFACE -->
<div class="budget-modal" id="budgetModal">
    <div class="budget-modal-box">

        <div class="budget-modal-header">
            <div>
                <h3>Transparency Hub - Budget & Expenses</h3>
                <p>Record the SK budget and show where the money was spent.</p>
            </div>
            <button class="close-budget" onclick="closeBudgetReport()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="budget-body">

            <div class="budget-heading">
                <div>
                    <h4>Transparency Hub - Budget & Expenses</h4>
                    <div style="font-size:13px;color:var(--bs-secondary-color);margin-top:3px;">
                    Each folder holds that year's project budgets and expenses. New expenses are saved to the selected folder.
                    </div>
                </div>
                <div class="fy-badge" id="fyBadge">FY 2026-2027</div>
            </div>

            <div class="fund-box">
                <div class="fund-grid">
                    <div>
                        <label class="field-label" for="budgetYearSelect">Year folder</label>
                        <select id="budgetYearSelect" class="budget-select"></select>
                    </div>
                    <div>
                        <label class="field-label" for="budgetFY">Create fiscal year folder</label>
                        <input id="budgetFY" class="budget-input" value="2026-2027" placeholder="2026-2027">
                    </div>
                    <button class="save-budget-btn" onclick="createBudgetYear()">
                        <i class="bi bi-folder-plus me-1"></i> Open / Create Year
                    </button>
                </div>
            </div>

            <div class="summary-grid">

                <div class="summary-card">
                    <div class="summary-label">Total Budget</div>
                    <div class="summary-value" id="summaryBudget">₱0.00</div>
                    <div class="summary-note">Sum of project budgets in this year</div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Total Spent</div>
                    <div class="summary-value spent-value" id="summarySpent">₱0.00</div>
                    <div class="summary-note">Expenses in selected fiscal year</div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Remaining Balance</div>
                    <div class="summary-value balance-value" id="summaryRemaining">₱0.00</div>
                    <div class="summary-note">Budget less expenses</div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Utilization</div>
                    <div class="summary-value" id="summaryUtilization">0%</div>
                    <div class="progress-wrap">
                        <div class="progress-fill" id="summaryProgress"></div>
                    </div>
                </div>

            </div>

            <div class="records-card mb-3">
                <h4>Projects in this fiscal year</h4>
                <p class="small text-muted">Each entry is a project budget linked to one Center of Participation and committee.</p>
                <div class="entry-card mb-3" id="projectEditor">
                    <h4 id="projectEditorTitle">Add a project budget</h4>
                    <div class="entry-row">
                        <div>
                            <label class="field-label" for="projectName">Project name</label>
                            <input id="projectName" class="budget-input" maxlength="160" placeholder="e.g. Leadership Training">
                        </div>
                        <div>
                            <label class="field-label" for="projectAmount">Project budget (₱)</label>
                            <input id="projectAmount" type="number" min="0.01" step="0.01" class="budget-input" placeholder="1087000">
                        </div>
                    </div>
                    <div class="entry-row four">
                        <div>
                            <label class="field-label" for="projectType">Budget plan</label>
                            <select id="projectType" class="budget-select">
                                <option value="Annual">Annual budget</option>
                                <option value="Supplemental">Supplemental budget</option>
                                <option value="Continuing">Continuing fund</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="projectCop">Center of Participation</label>
                            <select id="projectCop" class="budget-select"></select>
                        </div>
                        <div>
                            <label class="field-label" for="projectCommittee">Committee</label>
                            <select id="projectCommittee" class="budget-select"></select>
                        </div>
                        <div>
                            <label class="field-label">Committee chairperson</label>
                            <div id="projectChairperson" class="budget-input d-flex align-items-center" aria-live="polite">Not assigned</div>
                        </div>
                    </div>
                    <div>
                        <label class="field-label" for="projectDescription">Project details (optional)</label>
                        <textarea id="projectDescription" class="budget-textarea" maxlength="1000" placeholder="Purpose, planned outputs, or notes"></textarea>
                    </div>
                    <button class="add-expense-btn mt-3" id="projectSubmitBtn" onclick="addOrUpdateProject()">
                        <i class="bi bi-plus-lg me-1"></i> Add Project Budget
                    </button>
                    <button class="btn btn-sm btn-light ms-1 d-none" id="cancelProjectEditBtn" onclick="cancelProjectEdit()">Cancel Edit</button>
                </div>
                <div class="table-scroll">
                    <table class="expense-table">
                        <thead>
                            <tr><th>Project</th><th>Budget plan</th><th>Center of Participation</th><th>Committee</th><th>Committee chairperson</th><th>Budget (₱)</th><th>Spent (₱)</th><th>Remaining (₱)</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="projectTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="entry-grid">

                <div class="entry-card">
                    <h4>Record Money Spent <span class="text-muted fs-6">(<span id="expenseFiscalYearLabel">FY 2026-2027</span>)</span></h4>

                    <div class="entry-row">
                        <div>
                            <label class="field-label">Date</label>
                            <input type="date" id="expenseDate" class="budget-input">
                        </div>

                        <div>
                            <label class="field-label" for="expenseTitle">Expense title</label>
                            <input id="expenseTitle" class="budget-input" placeholder="e.g. Basketball Equipment">
                        </div>
                    </div>

                    <div class="entry-row four">
                        <div>
                            <label class="field-label" for="expenseProject">Project</label>
                            <select id="expenseProject" class="budget-select"></select>
                        </div>

                        <div>
                            <label class="field-label">Center of Participation</label>
                            <div id="expenseCopLabel" class="budget-input d-flex align-items-center">Select a project</div>
                        </div>

                        <div>
                            <label class="field-label">Amount (₱)</label>
                            <input type="number" id="expenseAmount" min="0" step="0.01" class="budget-input" placeholder="0.00">
                        </div>

                        <div>
                            <label class="field-label">Supplier / Recipient (shown publicly)</label>
                            <input id="expenseRecipient" class="budget-input" placeholder="Person / supplier / organization">
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Where did the money go? / Description</label>
                        <textarea id="expenseDescription" class="budget-textarea" placeholder="Explain what the money was used for."></textarea>
                    </div>

                    <div class="mt-3">
                        <label class="field-label" for="expenseReceipt">Internal receipt (PDF, JPEG, PNG, or WebP; max 8 MB)</label>
                        <input type="file" id="expenseReceipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        <div id="expenseReceiptStatus" class="small text-muted mt-1" aria-live="polite">Receipts are visible only to signed-in SK officials.</div>
                    </div>

                    <button class="add-expense-btn" id="expenseSubmitBtn" onclick="addOrUpdateExpense()">
                        <i class="bi bi-plus-lg me-1"></i> Add Expense
                    </button>

                    <button class="btn btn-sm btn-light ms-1 d-none" id="cancelEditBtn" onclick="cancelExpenseEdit()">
                        Cancel Edit
                    </button>
                </div>

                <div class="where-card">
                    <h4>Where the Money Went <i class="bi bi-pie-chart-fill float-end"></i></h4>
                    <div id="projectBreakdown" class="where-empty">
                        No spending recorded yet.
                    </div>
                </div>

            </div>

            <div class="records-card">
                <div class="records-top">
                    <div>
                        <h4 style="margin-bottom:4px;">Expense Records</h4>
                        <div style="font-size:13px;color:var(--bs-secondary-color);">Every recorded expense appears here for public accountability.</div>
                    </div>
                    <div class="expense-count" id="expenseCount">0 Expenses</div>
                </div>

                <div class="table-scroll">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th>DATE</th>
                                <th>PROJECT / EXPENSE</th>
                                <th>COMMITTEE</th>
                                <th>COP</th>
                                <th>SUPPLIER / RECIPIENT</th>
                                <th>AMOUNT</th>
                                <th>DESCRIPTION</th>
                                <th>INTERNAL RECEIPT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="expenseTableBody">
                            <tr>
                                <td colspan="9" class="empty-row">
                                    No expenses recorded yet. Add an expense above to show where the money went.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FINAL PDF BUTTON AT THE VERY BOTTOM -->
            <div class="pdf-section">
                <div>
                    <h4>Generate Final Budget Report</h4>
                    <p>Review and edit the information above first. The latest saved information will be included in the printable PDF report.</p>
                </div>

                <button class="pdf-btn" onclick="generateBudgetPDF()">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    Generate Report / PDF
                </button>
            </div>

        </div>
    </div>
</div>

<script src="vendor/dompurify/purify.min.js"></script>
<script src="js/minutes-render.js"></script>
<script>
const BUDGET_KEY = 'skTransparencyBudget';
const EXPENSE_KEY = 'skTransparencyExpenses';
const COP_CATEGORIES = ['Health', 'Education', 'Economic Empowerment', 'Social Inclusion and Equity',
    'Peace-Building and Security', 'Governance', 'Active Citizenship', 'Environment', 'Global Mobility', 'Agriculture'];
const DEFAULT_COMMITTEES = [
    'Committee on Good Governance and Active Citizenship',
    'Committee on Environment & Disaster Risk Resiliency',
    'Committee on Anti-Drug Abuse and Social Protection',
    'Committee on Education and Culture',
    'Committee on Health and Sanitation',
    'Committee on Youth and Sports Development',
    'Committee on Budget and Appropriation'
];
let committees = DEFAULT_COMMITTEES.slice();
let committeeChairs = {};
let officialsById = {};
let budgetData = { fy:'2026-2027', projects:[] };
let budgetsByYear = {};
let expenseData = [];
let editingExpenseIndex = -1;
let editingProjectId = null;

function formatPeso(value) {
    return '₱' + Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits:2,
        maximumFractionDigits:2
    });
}

function budgetTypeLabel(value) {
    if (value === 'Supplemental') return 'Supplemental budget';
    if (value === 'Continuing') return 'Continuing fund';
    return 'Annual budget';
}

function escapeReportHtml(value) {
    return String(value ?? '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

function storedArray(key) {
    const raw = DB.getItem(key);
    if (raw === null) return [];
    const value = JSON.parse(raw);
    if (!Array.isArray(value)) throw new Error('Saved ' + key + ' data is not a list.');
    return value;
}

function storedObject(key) {
    const raw = DB.getItem(key);
    if (raw === null) return {};
    const value = JSON.parse(raw);
    if (!value || typeof value !== 'object' || Array.isArray(value)) throw new Error('Saved ' + key + ' data is invalid.');
    return value;
}

function legacyProject(year, amount) {
    return {
        id:'legacy-' + year.replace(/[^0-9]/g, ''),
        name:'Legacy budget — add project breakdown',
        type:'Annual',
        amount:Number(amount || 0),
        cop:'',
        committee:'',
        description:'Imported year-wide budget. Edit or replace this entry with individual project budgets.',
        legacy:true
    };
}

function normalizeYearBudget(year, value) {
    const projects = Array.isArray(value && value.projects) ? value.projects.map(function (project) {
        if (!project || typeof project !== 'object') return null;
        return {
            id:String(project.id || ''),
            name:String(project.name || ''),
            type:['Annual', 'Supplemental', 'Continuing'].includes(project.type) ? project.type : 'Annual',
            amount:Number(project.amount || 0),
            cop:String(project.cop || ''),
            committee:String(project.committee || ''),
            description:String(project.description || ''),
            legacy:project.legacy === true
        };
    }).filter(Boolean) : [];
    if (!projects.length && Number(value && value.amount || 0) > 0) {
        projects.push(legacyProject(year, value.amount));
    }
    return { fy:year, projects:projects };
}

function loadBudgetData() {
    budgetData = { fy:'2026-2027', projects:[] };
    budgetsByYear = {};
    try {
        const savedBudget = JSON.parse(DB.getItem(BUDGET_KEY) || 'null');
        const savedExpenses = JSON.parse(DB.getItem(EXPENSE_KEY) || '[]');
        const savedYears = savedBudget && savedBudget.budgets && typeof savedBudget.budgets === 'object'
            ? savedBudget.budgets : {};
        Object.keys(savedYears).forEach(function (year) {
            if (/^\d{4}-\d{4}$/.test(year)) budgetsByYear[year] = normalizeYearBudget(year, savedYears[year]);
        }
        );
        const legacyYear = String(savedBudget && savedBudget.fy || '2026-2027');
        if (/^\d{4}-\d{4}$/.test(legacyYear) && !budgetsByYear[legacyYear]) {
            budgetsByYear[legacyYear] = normalizeYearBudget(legacyYear, savedBudget || {});
        }
        if (!Object.keys(budgetsByYear).length) budgetsByYear['2026-2027'] = { fy:'2026-2027', projects:[] };
        const selectedYear = savedBudget && savedBudget.fy && budgetsByYear[savedBudget.fy]
            ? savedBudget.fy : Object.keys(budgetsByYear).sort().pop();
        budgetData = budgetsByYear[selectedYear];
        expenseData = Array.isArray(savedExpenses) ? savedExpenses.filter(expense => expense && typeof expense === 'object').map(function (expense) {
            const year = String(expense.fiscalYear || selectedYear);
            if (!budgetsByYear[year]) budgetsByYear[year] = { fy:year, projects:[] };
            const projects = budgetsByYear[year].projects;
            if (!expense.projectId) {
                const legacy = projects.find(project => project.legacy);
                if (legacy) expense.projectId = legacy.id;
            }
            expense.fiscalYear = year;
            return expense;
        }) : [];
        budgetData = budgetsByYear[selectedYear];
        const savedCommittees = storedArray('skCommittees');
        committees = Array.from(new Set(DEFAULT_COMMITTEES.concat(savedCommittees.filter(name => typeof name === 'string'))));
        committeeChairs = storedObject('skCommitteeChairs');
        const roles = storedArray('skRoles');
        const members = storedArray('kkMembers');
        const memberById = {};
        members.forEach(member => { if (member && member.id != null) memberById[String(member.id)] = member; });
        officialsById = {};
        const currentDate = new Date();
        const today = [
            currentDate.getFullYear(),
            String(currentDate.getMonth() + 1).padStart(2, '0'),
            String(currentDate.getDate()).padStart(2, '0')
        ].join('-');
        roles.forEach(role => {
            if (!role || role.status !== 'Active'
                || (role.termStart && role.termStart > today)
                || (role.termEnd && role.termEnd < today)) return;
            const member = memberById[String(role.kkId)];
            if (!member || (member.status || 'Active') !== 'Active') return;
            officialsById[String(role.id)] = {
                name:[member.firstName, member.middleName, member.lastName, member.suffix].filter(Boolean).join(' ').trim(),
                position:String(role.position || '')
            };
        });
        renderProjectCommitteeOptions();
        renderBudgetYearOptions();
    } catch(error) {
        budgetData = { fy:'2026-2027', projects:[] };
        budgetsByYear = { '2026-2027': budgetData };
        expenseData = [];
        if (window.AppAlerts) AppAlerts.error('Could not load the saved budget and expense records.');
        else console.error('Could not load the saved budget and expense records.', error);
    }
}

function renderBudgetYearOptions() {
    const select = document.getElementById('budgetYearSelect');
    const years = Object.keys(budgetsByYear);
    if (years.indexOf(budgetData.fy) < 0) years.push(budgetData.fy);
    years.sort();
    select.innerHTML = years.map(year => '<option value="' + escapeReportHtml(year) + '">' + escapeReportHtml(year) + '</option>').join('');
    select.value = budgetData.fy;
}

function expensesForSelectedYear() {
    return expenseData.filter(expense => String(expense && expense.fiscalYear || '') === String(budgetData.fy));
}

function saveBudgetStore() {
    budgetsByYear[budgetData.fy] = { fy:budgetData.fy, projects:budgetData.projects };
    DB.setItem(BUDGET_KEY, JSON.stringify({
        fy: budgetData.fy,
        budgets: budgetsByYear
    }));
}

function renderProjectCommitteeOptions() {
    const copSelect = document.getElementById('projectCop');
    const committeeSelect = document.getElementById('projectCommittee');
    const copValue = copSelect.value, committeeValue = committeeSelect.value;
    copSelect.innerHTML = '<option value="">Select a Center of Participation</option>' +
        COP_CATEGORIES.map(cop => '<option value="' + escapeReportHtml(cop) + '">' + escapeReportHtml(cop) + '</option>').join('');
    committeeSelect.innerHTML = '<option value="">Select a committee</option>' +
        committees.map(name => '<option value="' + escapeReportHtml(name) + '">' + escapeReportHtml(name) + '</option>').join('');
    if (COP_CATEGORIES.includes(copValue)) copSelect.value = copValue;
    if (committees.includes(committeeValue)) committeeSelect.value = committeeValue;
    updateProjectChairperson();
}

function updateProjectChairperson() {
    const roleId = committeeChairs[document.getElementById('projectCommittee').value];
    const official = roleId ? officialsById[String(roleId)] : null;
    document.getElementById('projectChairperson').textContent = official
        ? official.name + ' — ' + official.position : 'Not assigned';
}

function renderExpenseProjectOptions(selectedId) {
    const select = document.getElementById('expenseProject');
    select.innerHTML = '<option value="">Select a project budget</option>' + budgetData.projects.map(project =>
        '<option value="' + escapeReportHtml(project.id) + '">' + escapeReportHtml(project.name) +
        ' — ' + escapeReportHtml(project.cop || 'COP not set') + '</option>'
    ).join('');
    select.value = selectedId || '';
    updateExpenseCopLabel();
}

function updateExpenseCopLabel() {
    const project = budgetData.projects.find(item => item.id === document.getElementById('expenseProject').value);
    document.getElementById('expenseCopLabel').textContent = project
        ? project.cop || 'COP not set' : 'Select a project';
}

function createBudgetYear() {
    const fy = document.getElementById('budgetFY').value.trim();
    if (!/^\d{4}-\d{4}$/.test(fy)) {
        AppAlerts.warning('Enter a fiscal year in YYYY-YYYY format.');
        return;
    }
    const previous = budgetData;
    if (!budgetsByYear[fy]) budgetsByYear[fy] = { fy:fy, projects:[] };
    budgetData = budgetsByYear[fy];
    try {
        saveBudgetStore();
        updateBudgetInterface();
        AppAlerts.success('Fiscal-year folder opened.');
    } catch (error) {
        budgetData = previous;
        AppAlerts.error(error.message || 'Could not create the fiscal-year folder.');
    }
}

function newProjectId() {
    return 'project-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
}

function addOrUpdateProject() {
    const name = document.getElementById('projectName').value.trim();
    const amount = Number(document.getElementById('projectAmount').value);
    const cop = document.getElementById('projectCop').value;
    const committee = document.getElementById('projectCommittee').value;
    if (!name || name.length > 160 || !Number.isFinite(amount) || amount <= 0
        || !COP_CATEGORIES.includes(cop) || !committees.includes(committee)) {
        AppAlerts.warning('Enter a project name, a positive project budget, a COP, and a committee.');
        return;
    }
    const project = {
        id:editingProjectId || newProjectId(),
        name:name,
        type:document.getElementById('projectType').value,
        amount:amount,
        cop:cop,
        committee:committee,
        description:document.getElementById('projectDescription').value.trim()
    };
    const previous = budgetData.projects.slice();
    const index = budgetData.projects.findIndex(item => item.id === editingProjectId);
    if (index >= 0) budgetData.projects[index] = project;
    else budgetData.projects.push(project);
    try {
        saveBudgetStore();
        cancelProjectEdit();
        updateBudgetInterface();
        AppAlerts.success(index >= 0 ? 'Project budget updated.' : 'Project budget added.');
    } catch (error) {
        budgetData.projects = previous;
        AppAlerts.error(error.message || 'Could not save the project budget.');
        updateBudgetInterface();
    }
}

function editProject(id) {
    const project = budgetData.projects.find(item => item.id === id);
    if (!project) return;
    editingProjectId = id;
    document.getElementById('projectName').value = project.name;
    document.getElementById('projectAmount').value = project.amount;
    document.getElementById('projectType').value = project.type;
    document.getElementById('projectCop').value = project.cop;
    document.getElementById('projectCommittee').value = project.committee;
    document.getElementById('projectDescription').value = project.description;
    document.getElementById('projectEditorTitle').textContent = 'Edit project budget';
    document.getElementById('projectSubmitBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i> Update Project Budget';
    document.getElementById('cancelProjectEditBtn').classList.remove('d-none');
    updateProjectChairperson();
    document.getElementById('projectEditor').scrollIntoView({ behavior:'smooth', block:'start' });
}

function cancelProjectEdit() {
    editingProjectId = null;
    document.getElementById('projectName').value = '';
    document.getElementById('projectAmount').value = '';
    document.getElementById('projectType').value = 'Annual';
    document.getElementById('projectCop').value = '';
    document.getElementById('projectCommittee').value = '';
    document.getElementById('projectDescription').value = '';
    document.getElementById('projectEditorTitle').textContent = 'Add a project budget';
    document.getElementById('projectSubmitBtn').innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add Project Budget';
    document.getElementById('cancelProjectEditBtn').classList.add('d-none');
    updateProjectChairperson();
}

function deleteProject(id) {
    if (expenseData.some(expense => expense.projectId === id)) {
        AppAlerts.warning('This project has expense records. Reassign or delete those expenses before removing the project.');
        return;
    }
    AppAlerts.confirm('Delete this project budget?', 'Delete project?', 'Delete').then(function (confirmed) {
        if (!confirmed) return;
        const previous = budgetData.projects.slice();
        budgetData.projects = budgetData.projects.filter(project => project.id !== id);
        try {
            saveBudgetStore();
            if (editingProjectId === id) cancelProjectEdit();
            updateBudgetInterface();
        } catch (error) {
            budgetData.projects = previous;
            AppAlerts.error(error.message || 'Could not delete the project budget.');
        }
    });
}

function projectById(id) {
    return budgetData.projects.find(project => project.id === id);
}

function renderProjectTable() {
    const expenses = expensesForSelectedYear();
    const body = document.getElementById('projectTableBody');
    if (!budgetData.projects.length) {
        body.innerHTML = '<tr><td colspan="9" class="empty-row">No project budgets in this year folder. Add each project with its own budget.</td></tr>';
        return;
    }
    body.innerHTML = budgetData.projects.map(project => {
        const spent = expenses.filter(expense => expense.projectId === project.id)
            .reduce((sum, expense) => sum + Number(expense.amount || 0), 0);
        const chairId = committeeChairs[project.committee];
        const chair = chairId ? officialsById[String(chairId)] : null;
        return '<tr><td><strong>' + escapeReportHtml(project.name) + '</strong>' +
            (project.legacy ? '<div class="small text-warning">Legacy total — review and split into projects</div>' : '') +
            (project.description ? '<div class="small text-muted">' + escapeReportHtml(project.description) + '</div>' : '') +
            '</td><td>' + escapeReportHtml(budgetTypeLabel(project.type)) + '</td><td>' + escapeReportHtml(project.cop || 'Not set') +
            '</td><td>' + escapeReportHtml(project.committee || 'Not set') + '</td><td>' +
            escapeReportHtml(chair ? chair.name : 'Not assigned') + '</td><td>' + formatPeso(project.amount) +
            '</td><td>' + formatPeso(spent) + '</td><td>' + formatPeso(project.amount - spent) + '</td><td>' +
            '<button class="table-action edit-btn" data-project-edit="' + escapeReportHtml(project.id) + '">Edit</button> ' +
            '<button class="table-action delete-btn" data-project-delete="' + escapeReportHtml(project.id) + '">Delete</button></td></tr>';
    }).join('');
}

function updateBudgetInterface() {
    renderBudgetYearOptions();
    document.getElementById('budgetFY').value = budgetData.fy;
    document.getElementById('fyBadge').textContent = 'FY ' + budgetData.fy;
    document.getElementById('expenseFiscalYearLabel').textContent = 'FY ' + budgetData.fy;
    const selectedExpenses = expensesForSelectedYear();
    const totalBudget = budgetData.projects.reduce((sum, project) => sum + Number(project.amount || 0), 0);
    const totalSpent = selectedExpenses.reduce((sum, expense) => sum + Number(expense.amount || 0), 0);
    const utilization = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;
    document.getElementById('summaryBudget').textContent = formatPeso(totalBudget);
    document.getElementById('summarySpent').textContent = formatPeso(totalSpent);
    document.getElementById('summaryRemaining').textContent = formatPeso(totalBudget - totalSpent);
    document.getElementById('summaryUtilization').textContent = (totalBudget > 0 ? utilization.toFixed(1) : '0.0') + '%';
    document.getElementById('summaryProgress').style.width = Math.min(100, Math.max(0, utilization)) + '%';
    renderExpenseProjectOptions(document.getElementById('expenseProject').value);
    renderProjectTable();
    renderProjectBreakdown(totalSpent, selectedExpenses);
    renderExpenseTable(selectedExpenses);
}

document.getElementById('projectTableBody').addEventListener('click', function (event) {
    const edit = event.target.closest('[data-project-edit]');
    const remove = event.target.closest('[data-project-delete]');
    if (edit) editProject(edit.dataset.projectEdit);
    if (remove) deleteProject(remove.dataset.projectDelete);
});

async function addOrUpdateExpense() {
    const date = document.getElementById('expenseDate').value;
    const title = document.getElementById('expenseTitle').value.trim();
    const project = projectById(document.getElementById('expenseProject').value);
    const amount = Number(document.getElementById('expenseAmount').value || 0);
    const recipient = document.getElementById('expenseRecipient').value.trim();
    const description = document.getElementById('expenseDescription').value.trim();
    const receiptFile = document.getElementById('expenseReceipt').files[0] || null;

    if (!date || !title || !project || !Number.isFinite(amount) || amount <= 0 || !recipient || !description) {
        AppAlerts.warning('Complete the expense details and select a project in this fiscal year.');
        return;
    }

    const targetIndex = editingExpenseIndex >= 0 ? editingExpenseIndex : expenseData.length;
    const priorExpense = editingExpenseIndex >= 0 ? expenseData[editingExpenseIndex] : null;
    const expense = Object.assign({}, priorExpense || {}, {
        fiscalYear: budgetData.fy,
        projectId: project.id,
        date,
        title,
        committee: project.committee,
        cop: project.cop,
        amount,
        recipient,
        description
    });

    const previousExpenses = expenseData.slice();
    if (editingExpenseIndex >= 0) {
        expenseData[targetIndex] = expense;
    } else {
        expenseData.push(expense);
    }

    try {
        DB.setItem(EXPENSE_KEY, JSON.stringify(expenseData));
    } catch (error) {
        expenseData = previousExpenses;
        AppAlerts.error(error.message || 'Could not save the expense.');
        updateBudgetInterface();
        return;
    }

    if (receiptFile) {
        try {
            const result = await DB.uploadExpenseReceipt(targetIndex, receiptFile);
            expenseData = JSON.parse(result.value);
        } catch (error) {
            editingExpenseIndex = targetIndex;
            document.getElementById('expenseSubmitBtn').innerHTML =
                '<i class="bi bi-check-lg me-1"></i> Update Expense';
            document.getElementById('cancelEditBtn').classList.remove('d-none');
            document.getElementById('expenseReceiptStatus').textContent =
                'Expense saved, but the receipt was not uploaded: ' + (error.message || 'try again.');
            AppAlerts.error('The expense was saved, but its receipt was not uploaded. Edit the expense to retry.');
            updateBudgetInterface();
            return;
        }
    }

    clearExpenseForm();
    editingExpenseIndex = -1;

    document.getElementById('expenseSubmitBtn').innerHTML =
        '<i class="bi bi-plus-lg me-1"></i> Add Expense';

    document.getElementById('cancelEditBtn').classList.add('d-none');

    updateBudgetInterface();
}

function editExpense(index) {
    const expense = expenseData[index];
    if (!expense) return;

    editingExpenseIndex = index;

    document.getElementById('expenseDate').value = expense.date || '';
    document.getElementById('expenseTitle').value = expense.title || '';
    document.getElementById('expenseProject').value = expense.projectId || '';
    updateExpenseCopLabel();
    document.getElementById('expenseAmount').value = expense.amount || '';
    document.getElementById('expenseRecipient').value = expense.recipient || '';
    document.getElementById('expenseDescription').value = expense.description || '';
    document.getElementById('expenseReceipt').value = '';
    document.getElementById('expenseReceiptStatus').innerHTML = expense.receiptId
        ? 'Receipt on file. <a target="_blank" rel="noopener noreferrer" href="api/official-files.php?action=receipt_view&amp;id=' +
            encodeURIComponent(expense.receiptId) + '">Download internal receipt</a>. Choose a file to replace it.'
        : 'No receipt attached. Uploading one keeps it internal to SK officials.';

    document.getElementById('expenseSubmitBtn').innerHTML =
        '<i class="bi bi-check-lg me-1"></i> Update Expense';

    document.getElementById('cancelEditBtn').classList.remove('d-none');

    document.querySelector('.entry-card').scrollIntoView({
        behavior:'smooth',
        block:'start'
    });
}

function cancelExpenseEdit() {
    editingExpenseIndex = -1;
    clearExpenseForm();

    document.getElementById('expenseSubmitBtn').innerHTML =
        '<i class="bi bi-plus-lg me-1"></i> Add Expense';

    document.getElementById('cancelEditBtn').classList.add('d-none');
}

function deleteExpense(index) {
    if (!expenseData[index]) return;

    AppAlerts.confirm('Delete this expense record?', 'Delete expense?', 'Delete')
        .then(function (confirmed) {
            if (!confirmed) return;
            const previousExpenses = expenseData.slice();
            expenseData.splice(index, 1);
            try {
                DB.setItem(EXPENSE_KEY, JSON.stringify(expenseData));
            } catch (error) {
                expenseData = previousExpenses;
                AppAlerts.error(error.message || 'Could not delete the expense.');
                updateBudgetInterface();
                return;
            }

            if (editingExpenseIndex === index) {
                cancelExpenseEdit();
            }

            updateBudgetInterface();
        });
}

function clearExpenseForm() {
    document.getElementById('expenseDate').value = '';
    document.getElementById('expenseTitle').value = '';
    document.getElementById('expenseProject').value = '';
    updateExpenseCopLabel();
    document.getElementById('expenseAmount').value = '';
    document.getElementById('expenseRecipient').value = '';
    document.getElementById('expenseDescription').value = '';
    document.getElementById('expenseReceipt').value = '';
    document.getElementById('expenseReceiptStatus').textContent =
        'Receipts are visible only to signed-in SK officials.';
}

function renderProjectBreakdown(totalSpent, selectedExpenses) {
    const container = document.getElementById('projectBreakdown');

    if (!selectedExpenses.length) {
        container.className = 'where-empty';
        container.innerHTML = 'No spending recorded yet.';
        return;
    }

    const totals = {};
    selectedExpenses.forEach(expense => {
        const project = projectById(expense.projectId);
        const name = project ? project.name : 'Unlinked legacy expense';
        totals[name] = (totals[name] || 0) + Number(expense.amount || 0);
    });
    const entries = Object.entries(totals).sort((a,b) => b[1] - a[1]);

    container.className = '';
    container.innerHTML = entries.map(([name, amount]) => {
        const percent = totalSpent > 0 ? (amount / totalSpent) * 100 : 0;

        return `
            <div class="category-item">
                <div class="category-head">
                    <span>${escapeReportHtml(name)}</span>
                    <span>${formatPeso(amount)}</span>
                </div>
                <div class="category-bar">
                    <span style="width:${Math.min(100, percent)}%"></span>
                </div>
            </div>
        `;
    }).join('');
}

function renderExpenseTable(selectedExpenses) {
    const tbody = document.getElementById('expenseTableBody');
    const count = document.getElementById('expenseCount');

    count.textContent = selectedExpenses.length + (selectedExpenses.length === 1 ? ' Expense' : ' Expenses');

    if (!selectedExpenses.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-row">
                    No expenses recorded yet. Add an expense above to show where the money went.
                </td>
            </tr>
        `;
        return;
    }

    const sorted = selectedExpenses.map(expense => ({
        ...expense,
        originalIndex: expenseData.indexOf(expense)
    })).sort((a,b) => String(b.date).localeCompare(String(a.date)));

    tbody.innerHTML = sorted.map(expense => {
        const project = projectById(expense.projectId);
        return `
        <tr>
            <td>${escapeReportHtml(expense.date)}</td>
            <td>${escapeReportHtml(expense.title)}</td>
            <td>${escapeReportHtml(project ? project.committee : expense.committee || '')}</td>
            <td>${escapeReportHtml(project ? project.cop || 'Unallocated' : expense.cop || 'Unallocated')}</td>
            <td>${escapeReportHtml(expense.recipient)}</td>
            <td>${formatPeso(expense.amount)}</td>
            <td>${escapeReportHtml(expense.description)}</td>
            <td>${expense.receiptId
                ? '<a target="_blank" rel="noopener noreferrer" href="api/official-files.php?action=receipt_view&amp;id=' +
                    encodeURIComponent(expense.receiptId) + '">Download receipt</a>'
                : '—'}</td>
            <td>
                <button class="table-action edit-btn" onclick="editExpense(${expense.originalIndex})">
                    <i class="bi bi-pencil-fill"></i> Edit
                </button>
                <button class="table-action delete-btn" onclick="deleteExpense(${expense.originalIndex})">
                    <i class="bi bi-trash-fill"></i> Delete
                </button>
            </td>
        </tr>
    `;
    }).join('');
}

function generateBudgetReport() {
    loadBudgetData();
    cancelProjectEdit();
    updateBudgetInterface();

    document.getElementById('budgetModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

document.getElementById('budgetYearSelect').addEventListener('change', function () {
    const selected = this.value;
    if (!budgetsByYear[selected]) return;
    const previous = budgetData;
    budgetData = budgetsByYear[selected];
    try {
        saveBudgetStore();
    } catch (error) {
        budgetData = previous;
        this.value = previous.fy;
        AppAlerts.error(error.message || 'Could not open the selected fiscal-year folder.');
        return;
    }
    editingExpenseIndex = -1;
    cancelExpenseEdit();
    cancelProjectEdit();
    updateBudgetInterface();
});
document.getElementById('projectCommittee').addEventListener('change', updateProjectChairperson);
document.getElementById('expenseProject').addEventListener('change', updateExpenseCopLabel);

function closeBudgetReport() {
    document.getElementById('budgetModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('budgetModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeBudgetReport();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' &&
        document.getElementById('budgetModal').classList.contains('show')) {
        closeBudgetReport();
    }
});

function generateBudgetPDF() {
    const fy = budgetData.fy;
    const totalBudget = budgetData.projects.reduce((sum, project) => sum + Number(project.amount || 0), 0);
    const selectedExpenses = expensesForSelectedYear();
    const totalSpent = selectedExpenses.reduce((sum,e) => sum + Number(e.amount || 0),0);
    const remaining = totalBudget - totalSpent;
    const utilization = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;
    const projectRows = budgetData.projects.map(project => {
        const spent = selectedExpenses.filter(expense => expense.projectId === project.id)
            .reduce((sum, expense) => sum + Number(expense.amount || 0), 0);
        const chairId = committeeChairs[project.committee];
        const chair = chairId ? officialsById[String(chairId)] : null;
        return '<tr><td>' + escapeReportHtml(project.name) + '</td><td>' + escapeReportHtml(budgetTypeLabel(project.type)) +
            '</td><td>' + escapeReportHtml(project.cop || 'Not set') + '</td><td>' +
            escapeReportHtml(project.committee || 'Not set') + '</td><td>' +
            escapeReportHtml(chair ? chair.name : 'Not assigned') + '</td><td class="right">' +
            formatPeso(project.amount) + '</td><td class="right">' + formatPeso(spent) +
            '</td><td class="right">' + formatPeso(project.amount - spent) + '</td></tr>';
    }).join('');

    const expenseRows = selectedExpenses.length
        ? selectedExpenses
            .slice()
            .sort((a,b) => String(b.date).localeCompare(String(a.date)))
            .map(expense => {
                const project = projectById(expense.projectId);
                return `
                <tr>
                    <td>${escapeReportHtml(expense.date)}</td>
                    <td>${escapeReportHtml(expense.title)}</td>
                    <td>${escapeReportHtml(project ? project.committee : expense.committee || '')}</td>
                    <td>${escapeReportHtml(project ? project.cop || 'Unallocated' : expense.cop || 'Unallocated')}</td>
                    <td>${escapeReportHtml(expense.recipient)}</td>
                    <td class="right">${formatPeso(expense.amount)}</td>
                    <td>${escapeReportHtml(expense.description)}</td>
                </tr>
            `;
            }).join('')
        : `<tr><td colspan="7" class="center">No expenses recorded.</td></tr>`;

    const reportWindow = window.open('', '_blank', 'width=1200,height=800');

    if (!reportWindow) {
        AppAlerts.warning('Please allow pop-ups in your browser to generate the PDF-ready report.');
        return;
    }

    reportWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Budget & Expense Report - ${escapeReportHtml(fy)}</title>
            <style>
                * { box-sizing:border-box; }

                body {
                    font-family:Arial,sans-serif;
                    margin:0;
                    color:#212529;
                    background:#fff;
                }

                .top-strip {
                    height:9px;
                    background:#0d6efd;
                }

                .page {
                    padding:32px;
                }

                .header {
                    border-bottom:2px solid #212529;
                    padding-bottom:16px;
                    margin-bottom:20px;
                }

                .header h1 {
                    margin:0;
                    font-size:30px;
                }

                .header p {
                    margin:5px 0 0;
                    color:#6c757d;
                    font-size:14px;
                }

                .fy {
                    margin-top:8px;
                    font-weight:bold;
                    font-size:15px;
                }

                .summary {
                    display:grid;
                    grid-template-columns:repeat(4,1fr);
                    gap:12px;
                    margin-bottom:24px;
                }

                .summary-box {
                    border:1px solid #dee2e6;
                    border-radius:9px;
                    padding:14px;
                }

                .label {
                    color:#6c757d;
                    font-size:12px;
                    text-transform:uppercase;
                    margin-bottom:6px;
                }

                .value {
                    font-size:22px;
                    font-weight:bold;
                }

                .spent { color:#dc3545; }
                .remaining { color:#0d6efd; }

                h2 {
                    font-size:18px;
                    margin:24px 0 9px;
                    border-bottom:3px solid #9ec5fe;
                    padding-bottom:6px;
                }

                table {
                    width:100%;
                    border-collapse:collapse;
                    font-size:12px;
                }

                th {
                    background:#0d6efd;
                    padding:8px;
                    text-align:left;
                }

                td {
                    border-bottom:1px solid #dee2e6;
                    padding:8px;
                    vertical-align:top;
                }

                .right { text-align:right; }
                .center { text-align:center; }

                .utilization {
                    margin-top:8px;
                    height:8px;
                    background:#dee2e6;
                    border-radius:10px;
                    overflow:hidden;
                }

                .utilization span {
                    display:block;
                    height:100%;
                    background:#0d6efd;
                    width:${Math.min(100,Math.max(0,utilization))}%;
                }

                .footer {
                    margin-top:35px;
                    padding-top:12px;
                    border-top:1px solid #dee2e6;
                    text-align:center;
                    color:#6c757d;
                    font-size:12px;
                }

                .print-btn {
                    position:fixed;
                    top:20px;
                    right:20px;
                    border:0;
                    background:#052c65;
                    color:#fff;
                    border-radius:7px;
                    padding:10px 15px;
                    font-weight:bold;
                    cursor:pointer;
                }

                @media print {
                    .print-btn { display:none; }
                    .page { padding:15px; }
                    @page { size:A4 landscape; margin:10mm; }
                }
            </style>
        </head>

        <body>
            <div class="top-strip"></div>

            ${window.SK_PUBLIC ? '' : `<button class="print-btn no-print" onclick="window.print()">
                Print / Save as PDF
            </button>`}

            <div class="page">

                <div class="header">
                    <h1>Budget & Expense Report</h1>
                    <p>KK Youth Profiling System | SK Barangay Sikat</p>
                    <div class="fy">Fiscal Year: ${escapeReportHtml(fy)}</div>
                </div>

                <div class="summary">
                    <div class="summary-box">
                        <div class="label">Total Budget</div>
                        <div class="value">${formatPeso(totalBudget)}</div>
                    </div>

                    <div class="summary-box">
                        <div class="label">Total Spent</div>
                        <div class="value spent">${formatPeso(totalSpent)}</div>
                    </div>

                    <div class="summary-box">
                        <div class="label">Remaining Balance</div>
                        <div class="value remaining">${formatPeso(remaining)}</div>
                    </div>

                    <div class="summary-box">
                        <div class="label">Utilization</div>
                        <div class="value">${totalBudget > 0 ? utilization.toFixed(1) : '0.0'}%</div>
                        <div class="utilization">
                            <span></span>
                        </div>
                    </div>
                </div>

                <h2>Project Budgets</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Project</th><th>Plan</th><th>COP</th><th>Committee</th><th>Chairperson</th>
                            <th style="text-align:right;">Budget</th><th style="text-align:right;">Spent</th>
                            <th style="text-align:right;">Remaining</th>
                        </tr>
                    </thead>
                    <tbody>${projectRows || `<tr><td colspan="8" class="center">No project budgets recorded.</td></tr>`}</tbody>
                </table>

                <h2>Complete Expense Records</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Program / Expense</th>
                            <th>Committee</th>
                            <th>COP</th>
                            <th>Paid To / Recipient</th>
                            <th style="text-align:right;">Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${expenseRows}
                    </tbody>
                </table>

                <div class="footer">
                    Generated from the SK Barangay Budget & Transparency records.
                    Fiscal Year ${escapeReportHtml(fy)}.
                </div>

            </div>
        </body>
        </html>
    `);

    reportWindow.document.close();
}

function reportArray(key) {
    const raw = DB.getItem(key);
    if (raw === null) return [];
    const data = JSON.parse(raw);
    if (!Array.isArray(data)) throw new Error('Saved ' + key + ' data is not a list.');
    return data;
}

function reportObject(key) {
    const raw = DB.getItem(key);
    if (raw === null) return {};
    const data = JSON.parse(raw);
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
        throw new Error('Saved ' + key + ' data is invalid.');
    }
    return data;
}

function reportDateInRange(value, startDate, endDate) {
    const date = String(value || '').slice(0, 10);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return !startDate && !endDate;
    return (!startDate || date >= startDate) && (!endDate || date <= endDate);
}

function reportMemberName(member) {
    return [member.firstName, member.middleName, member.lastName, member.suffix]
        .filter(Boolean).join(' ').trim() || 'Unnamed member';
}

function reportMemberAge(member) {
    if (!member.birthDate) return '';
    const birth = new Date(String(member.birthDate).slice(0, 10) + 'T00:00:00');
    if (Number.isNaN(birth.getTime())) return '';
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    if (today.getMonth() < birth.getMonth() ||
        (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age--;
    return age >= 0 && age <= 120 ? String(age) : '';
}

function reportMemberAgeAt(member, eventDate, fallbackAge) {
    const birthValue = member && member.birthDate ? String(member.birthDate).slice(0, 10) : '';
    const eventValue = String(eventDate || '').slice(0, 10);
    const birthMatch = birthValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    const eventMatch = eventValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (birthMatch && eventMatch) {
        const birth = new Date(Date.UTC(Number(birthMatch[1]), Number(birthMatch[2]) - 1, Number(birthMatch[3])));
        const event = new Date(Date.UTC(Number(eventMatch[1]), Number(eventMatch[2]) - 1, Number(eventMatch[3])));
        const validBirth = birth.getUTCFullYear() === Number(birthMatch[1]) &&
            birth.getUTCMonth() === Number(birthMatch[2]) - 1 &&
            birth.getUTCDate() === Number(birthMatch[3]);
        const validEvent = event.getUTCFullYear() === Number(eventMatch[1]) &&
            event.getUTCMonth() === Number(eventMatch[2]) - 1 &&
            event.getUTCDate() === Number(eventMatch[3]);
        if (validBirth && validEvent) {
            let age = event.getUTCFullYear() - birth.getUTCFullYear();
            if (event.getUTCMonth() < birth.getUTCMonth() ||
                (event.getUTCMonth() === birth.getUTCMonth() && event.getUTCDate() < birth.getUTCDate())) age--;
            if (age >= 0 && age <= 120) return age;
        }
    }
    if (fallbackAge === null || fallbackAge === undefined || fallbackAge === '') return null;
    const age = Number(fallbackAge);
    return Number.isInteger(age) && age >= 1 && age <= 120 ? age : null;
}

function reportAgeGroup(age) {
    if (age === null) return 'Unknown';
    if (age >= 15 && age <= 17) return '15-17';
    if (age >= 18 && age <= 20) return '18-20';
    if (age >= 21 && age <= 24) return '21-24';
    if (age >= 25 && age <= 30) return '25-30';
    return 'Outside ages 15-30';
}

function reportDateLabel(startDate, endDate) {
    if (startDate && endDate) return startDate + ' to ' + endDate;
    if (startDate) return 'From ' + startDate;
    if (endDate) return 'Through ' + endDate;
    return 'All dates';
}

function openDataReport(title, subtitle, columns, rows, notes, metrics, sectionsHtml) {
    const reportWindow = window.open('', '_blank', 'width=1200,height=800');
    if (!reportWindow) {
        AppAlerts.warning('Please allow pop-ups in your browser to generate a printable report.');
        return;
    }

    const metricHtml = (metrics || []).map(function (metric) {
        return '<div class="metric"><span>' + escapeReportHtml(metric.label) +
            '</span><strong>' + escapeReportHtml(metric.value) + '</strong></div>';
    }).join('');
    const headerHtml = columns.map(function (column) {
        return '<th>' + escapeReportHtml(column) + '</th>';
    }).join('');
    const rowHtml = rows.length
        ? rows.map(function (row) {
            return '<tr>' + row.map(function (cell) {
                return '<td>' + escapeReportHtml(cell) + '</td>';
            }).join('') + '</tr>';
        }).join('')
        : '<tr><td class="empty" colspan="' + Math.max(columns.length, 1) + '">No records match this report and date range.</td></tr>';
    const notesHtml = (notes || []).map(function (note) {
        return '<p class="note">' + escapeReportHtml(note) + '</p>';
    }).join('');
    const extraHtml = sectionsHtml || '';

    reportWindow.document.write('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>' + escapeReportHtml(title) + '</title><style>' +
        '*{box-sizing:border-box}body{font:14px Arial,sans-serif;color:#212529;margin:0;background:#fff}' +
        '.top{height:8px;background:#0d6efd}.page{padding:32px;max-width:1200px;margin:auto}' +
        'h1{font-size:28px;margin:0 0 6px}.subtitle,.meta{color:#6c757d}.meta{margin-top:6px;font-size:12px}' +
        '.metrics{display:flex;flex-wrap:wrap;gap:12px;margin:22px 0}.metric{flex:1 1 170px;border:1px solid #dee2e6;border-radius:8px;padding:14px}' +
        '.metric span{display:block;color:#6c757d;font-size:12px;margin-bottom:8px}.metric strong{font-size:19px}' +
        'table{border-collapse:collapse;width:100%;font-size:12px}th{background:#0d6efd;color:#fff;text-align:left}' +
        'th,td{padding:8px;border:1px solid #dee2e6;vertical-align:top}.empty{text-align:center;color:#6c757d;padding:20px}' +
        '.note{padding:10px 12px;margin:12px 0 0;background:#f1f3f5;border-left:3px solid #0d6efd;color:#495057}' +
        '.minutes-section{page-break-inside:avoid;margin-top:24px}.minutes-section h2{font-size:18px;margin-bottom:8px}' +
        '.minutes-content p{margin:0 0 6px}.minutes-content ul,.minutes-content ol{margin:4px 0 8px;padding-left:22px}' +
        '.minutes-content img{display:none!important}' +
        '.footer{margin-top:24px;padding-top:10px;border-top:1px solid #dee2e6;color:#6c757d;font-size:11px;text-align:center}' +
        '.print{position:fixed;right:20px;top:16px;border:0;border-radius:6px;background:#052c65;color:#fff;padding:10px 14px;font-weight:bold;cursor:pointer}' +
        '@media print{.print{display:none}.page{padding:12px}@page{size:A4 landscape;margin:10mm}}' +
        '</style></head><body><div class="top"></div><button class="print" onclick="window.print()">Print / Save as PDF</button>' +
        '<main class="page"><h1>' + escapeReportHtml(title) + '</h1><div class="subtitle">KK Youth Profiling System | SK Barangay Sikat</div>' +
        '<div class="meta">' + escapeReportHtml(subtitle) + ' · Generated ' + escapeReportHtml(new Date().toLocaleString()) + '</div>' +
        (metricHtml ? '<section class="metrics">' + metricHtml + '</section>' : '') +
        '<table><thead><tr>' + headerHtml + '</tr></thead><tbody>' + rowHtml + '</tbody></table>' +
        notesHtml + extraHtml + '<div class="footer">Prepared from records currently saved in the KK Youth Profiling System.</div>' +
        '</main></body></html>');
    reportWindow.document.close();
}

function reportBreakdownSection(title, columns, rows) {
    const headerHtml = columns.map(function (column) {
        return '<th>' + escapeReportHtml(column) + '</th>';
    }).join('');
    const bodyHtml = rows.length
        ? rows.map(function (row) {
            return '<tr>' + row.map(function (cell) {
                return '<td>' + escapeReportHtml(cell) + '</td>';
            }).join('') + '</tr>';
        }).join('')
        : '<tr><td class="empty" colspan="' + Math.max(columns.length, 1) + '">No confirmed attendance records match this report and date range.</td></tr>';
    return '<section class="minutes-section"><h2>' + escapeReportHtml(title) + '</h2><table><thead><tr>' +
        headerHtml + '</tr></thead><tbody>' + bodyHtml + '</tbody></table></section>';
}

function buildMemberReport(startDate, endDate) {
    const members = reportArray('kkMembers').filter(function (member) {
        return member && typeof member === 'object' &&
            reportDateInRange(member.createdAt || member.created_at, startDate, endDate);
    });
    const active = members.filter(function (member) { return member.status === 'Active'; }).length;
    const rows = members.map(function (member) {
        return [
            member.memberNo || member.id || '',
            reportMemberName(member),
            reportMemberAge(member),
            member.gender || '',
            member.purok || '',
            member.status || 'Unknown'
        ];
    });
    const notes = [];
    if (startDate || endDate) {
        notes.push('The date range filters by member registration date. Records without a registration date are omitted when a date range is set.');
    } else {
        notes.push('Age is calculated from birth date; the birth date and contact information are not included.');
    }
    openDataReport('KK Member Report', reportDateLabel(startDate, endDate),
        ['Member ID', 'Name', 'Age', 'Gender', 'Purok', 'Status'], rows, notes,
        [{ label: 'Members in report', value: String(members.length) }, { label: 'Active members', value: String(active) }]);
}

function buildAttendanceReport(startDate, endDate, includeMinutes) {
    const meetings = reportArray('skMeetings');
    const attendance = reportObject('skAttendance');
    const members = reportArray('kkMembers');
    const memberById = {};
    members.forEach(function (member) {
        if (member && member.id != null) memberById[String(member.id)] = member;
    });
    const rows = [];
    const minutesSections = [];
    let present = 0, late = 0, excused = 0, absent = 0, unknown = 0;
    meetings.filter(function (meeting) {
        return meeting && reportDateInRange(meeting.startDate || meeting.date, startDate, endDate);
    }).forEach(function (meeting) {
        if (includeMinutes && meeting.minutes) {
            minutesSections.push(
                '<section class="minutes-section"><h2>' + escapeReportHtml(meeting.title || 'Untitled meeting') + '</h2>' +
                '<p class="note">Minutes status: ' + escapeReportHtml(meeting.minutesStatus === 'Approved' ? 'Approved' : 'Draft') + '</p>' +
                '<div class="minutes-content">' + window.SKMinutes.sanitize(meeting.minutes) + '</div></section>'
            );
        }
        const records = attendance[meeting.id] || {};
        Object.keys(records).forEach(function (memberId) {
            const record = records[memberId] || {};
            const status = String(record.status || 'Unknown');
            if (status === 'Present') present++;
            else if (status === 'Late') late++;
            else if (status === 'Excused') excused++;
            else if (status === 'Absent') absent++;
            else unknown++;
            const member = memberById[String(memberId)];
            rows.push([
                meeting.startDate || meeting.date || '',
                meeting.title || 'Untitled meeting',
                member ? reportMemberName(member) : 'Removed member',
                member && (member.memberNo || member.id) || memberId,
                status,
                record.remarks || ''
            ]);
        });
    });
    rows.sort(function (a, b) {
        return String(a[0]).localeCompare(String(b[0])) || String(a[1]).localeCompare(String(b[1]));
    });
    const total = present + late + excused + absent + unknown;
    const reportNotes = ['This report uses attendance recorded for SK meetings. It is not youth program/event attendance.'];
    if (includeMinutes && !minutesSections.length) reportNotes.push('No meeting minutes are available for the selected date range.');
    openDataReport('Attendance Report', reportDateLabel(startDate, endDate),
        ['Date', 'SK meeting', 'Member', 'Member ID', 'Status', 'Remarks'], rows,
        reportNotes,
        [
            { label: 'Attendance records', value: String(total) },
            { label: 'Present', value: String(present) },
            { label: 'Late', value: String(late) },
            { label: 'Excused', value: String(excused) },
            { label: 'Absent', value: String(absent) },
            { label: 'Unknown status', value: String(unknown) }
        ],
        minutesSections.join(''));
}

function buildParticipationReport(startDate, endDate) {
    const announcements = reportArray('skAnnouncements');
    const eventTitles = {};
    const eventDates = {};
    announcements.forEach(function (event) {
        if (event && event.id != null) {
            eventTitles[String(event.id)] = event.title || 'Untitled event';
            eventDates[String(event.id)] = event.date || '';
        }
    });
    const selectedEventIds = {};
    Object.keys(eventDates).forEach(function (eventId) {
        if (reportDateInRange(eventDates[eventId], startDate, endDate)) selectedEventIds[eventId] = true;
    });
    const signups = reportArray('skEventRegistrations').filter(function (signup) {
        return signup && selectedEventIds[String(signup.eventId || '')];
    });
    const walkins = reportArray('skEventAttendance').filter(function (record) {
        return record && selectedEventIds[String(record.eventId || '')];
    });
    const memberById = {};
    reportArray('kkMembers').forEach(function (member) {
        if (member && member.id != null) memberById[String(member.id)] = member;
    });
    const totals = {};
    const linkedAttendees = {};
    const attendedSignupMemberEvents = {};
    const walkinMemberEvents = {};
    const activityBreakdowns = {};
    const ageGroups = ['15-17', '18-20', '21-24', '25-30', 'Outside ages 15-30', 'Unknown'];
    function recordDemographicAttendance(eventId, title, member, fallbackAge, fallbackPurok) {
        const key = eventId || title;
        if (!activityBreakdowns[key]) {
            activityBreakdowns[key] = { title: title, ages: {}, puroks: {} };
        }
        const age = reportMemberAgeAt(member, eventDates[eventId] || '', fallbackAge);
        const ageGroup = reportAgeGroup(age);
        const memberPurok = member && String(member.purok || '').trim();
        const purok = memberPurok || String(fallbackPurok || '').trim() || 'Not recorded';
        activityBreakdowns[key].ages[ageGroup] = (activityBreakdowns[key].ages[ageGroup] || 0) + 1;
        activityBreakdowns[key].puroks[purok] = (activityBreakdowns[key].puroks[purok] || 0) + 1;
    }
    signups.forEach(function (signup) {
        const eventId = String(signup.eventId || '');
        const title = signup.eventTitle || eventTitles[eventId] || 'Unknown event';
        const key = eventId || title;
        if (!totals[key]) totals[key] = { title: title, signups: 0, attended: 0, absent: 0, unmarked: 0, members: {} };
        totals[key].signups++;
        const status = signup.attendanceStatus || 'Not recorded';
        if (status === 'Attended') {
            totals[key].attended++;
            if (signup.memberId) {
                totals[key].members[String(signup.memberId)] = true;
                linkedAttendees[String(signup.memberId)] = true;
                attendedSignupMemberEvents[eventId + ':' + String(signup.memberId)] = true;
            }
            const memberId = String(signup.memberId || '');
            recordDemographicAttendance(
                eventId,
                title,
                memberById[memberId] || null,
                signup.age,
                signup.purok
            );
        } else if (status === 'Did not attend') totals[key].absent++;
        else totals[key].unmarked++;
    });
    walkins.forEach(function (record) {
        const eventId = String(record.eventId || '');
        const memberId = String(record.memberId || '');
        const memberEventKey = eventId + ':' + memberId;
        if (memberId && (attendedSignupMemberEvents[memberEventKey] || walkinMemberEvents[memberEventKey])) return;
        if (memberId) walkinMemberEvents[memberEventKey] = true;
        const title = record.eventTitle || eventTitles[eventId] || 'Unknown event';
        const key = eventId || title;
        if (!totals[key]) totals[key] = { title: title, signups: 0, attended: 0, absent: 0, unmarked: 0, members: {} };
        totals[key].attended++;
        if (memberId) {
            totals[key].members[memberId] = true;
            linkedAttendees[memberId] = true;
        }
        recordDemographicAttendance(
            eventId,
            title,
            memberById[memberId] || null,
            null,
            ''
        );
    });
    const rows = Object.keys(totals).map(function (key) {
        return [
            totals[key].title,
            String(totals[key].signups),
            String(totals[key].attended),
            String(totals[key].absent),
            String(totals[key].unmarked),
            String(Object.keys(totals[key].members).length)
        ];
    }).sort(function (a, b) {
        return Number(b[2]) - Number(a[2]) || Number(b[1]) - Number(a[1]) || a[0].localeCompare(b[0]);
    });
    const activityKeys = Object.keys(activityBreakdowns).sort(function (a, b) {
        return activityBreakdowns[a].title.localeCompare(activityBreakdowns[b].title);
    });
    const ageBreakdownRows = activityKeys.map(function (key) {
        const activity = activityBreakdowns[key];
        const counts = ageGroups.map(function (group) { return activity.ages[group] || 0; });
        return [activity.title].concat(
            counts.map(String),
            [String(counts.reduce(function (sum, count) { return sum + count; }, 0))]
        );
    });
    const puroks = [];
    activityKeys.forEach(function (key) {
        Object.keys(activityBreakdowns[key].puroks).forEach(function (purok) {
            if (puroks.indexOf(purok) === -1) puroks.push(purok);
        });
    });
    puroks.sort(function (a, b) {
        if (a === 'Not recorded') return 1;
        if (b === 'Not recorded') return -1;
        return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
    });
    const purokBreakdownRows = activityKeys.map(function (key) {
        const activity = activityBreakdowns[key];
        const counts = puroks.map(function (purok) { return activity.puroks[purok] || 0; });
        return [activity.title].concat(
            counts.map(String),
            [String(counts.reduce(function (sum, count) { return sum + count; }, 0))]
        );
    });
    const breakdownSections = reportBreakdownSection(
        'Confirmed attendance by age group and activity',
        ['Activity'].concat(ageGroups, ['Total']),
        ageBreakdownRows
    ) + reportBreakdownSection(
        'Confirmed attendance by Purok and activity',
        ['Activity'].concat(puroks, ['Total']),
        purokBreakdownRows
    );
    openDataReport('Participation Report', reportDateLabel(startDate, endDate),
        ['Event / program', 'Sign-ups', 'Attended', 'Did not attend', 'Not recorded', 'Linked KK members attended'], rows,
        [
            'Sign-up totals and confirmed attendance are tracked separately. Unmarked sign-ups are not counted as attendees; walk-in attendance is included.',
            'The date filter applies to the scheduled event date. All sign-ups and attendance records belonging to events in the selected range are included.',
            'A walk-in is counted unless the same linked member already has a sign-up marked Attended for that event. A prior sign-up marked Did not attend does not suppress a recorded walk-in. Records linked to deleted or unknown events are not included because their event date cannot be verified.',
            'Age groups use a linked member’s age on the scheduled event date when the birth date is available; otherwise, the captured sign-up age is used when available. Purok uses the linked member profile when available, otherwise the captured sign-up value. Missing or out-of-range values are shown separately.'
        ],
        [
            { label: 'Sign-ups in range', value: String(signups.length) },
            { label: 'Confirmed attendance records', value: String(rows.reduce(function (total, row) { return total + Number(row[2]); }, 0)) },
            { label: 'Linked KK members attended', value: String(Object.keys(linkedAttendees).length) },
            { label: 'Events with attendance or sign-ups', value: String(rows.length) }
        ],
        breakdownSections);
}

function buildProgramsReport(startDate, endDate) {
    const events = reportArray('skAnnouncements').filter(function (event) {
        return event && reportDateInRange(event.date, startDate, endDate);
    }).sort(function (a, b) {
        return String(a.date || '').localeCompare(String(b.date || ''));
    });
    const signupTotals = {};
    const attendanceTotals = {};
    const attendedSignupMemberEvents = {};
    reportArray('skEventRegistrations').forEach(function (signup) {
        if (signup && signup.eventId != null) {
            const id = String(signup.eventId);
            signupTotals[id] = (signupTotals[id] || 0) + 1;
            if (signup.attendanceStatus === 'Attended') {
                attendanceTotals[id] = (attendanceTotals[id] || 0) + 1;
                if (signup.memberId) attendedSignupMemberEvents[id + ':' + String(signup.memberId)] = true;
            }
        }
    });
    const walkinMemberEvents = {};
    reportArray('skEventAttendance').forEach(function (record) {
        if (!record || record.eventId == null) return;
        const id = String(record.eventId);
        const memberId = String(record.memberId || '');
        const memberEventKey = id + ':' + memberId;
        if (memberId && (attendedSignupMemberEvents[memberEventKey] || walkinMemberEvents[memberEventKey])) return;
        if (memberId) walkinMemberEvents[memberEventKey] = true;
        attendanceTotals[id] = (attendanceTotals[id] || 0) + 1;
    });
    const now = new Date();
    const today = now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0');
    const rows = events.map(function (event) {
        return [
            event.date || '',
            event.title || 'Untitled activity',
            event.category || '',
            event.venue || '',
            event.publishState || 'Draft',
            event.cancelled ? 'Cancelled' : (event.date && event.date < today ? 'Past' : 'Scheduled'),
            String(signupTotals[String(event.id)] || 0),
            String(attendanceTotals[String(event.id)] || 0)
        ];
    });
    openDataReport('Programs Report', reportDateLabel(startDate, endDate),
        ['Date', 'Program / activity', 'Category', 'Venue', 'Publication', 'Status', 'Sign-ups', 'Attended'], rows,
        ['Program data comes from SK announcements. The date filter selects programs by their scheduled date; sign-ups and attendance totals are then limited to those programs. Attendance includes sign-ups marked Attended and walk-in KK members.'],
        [{ label: 'Programs in report', value: String(events.length) }]);
}

function buildAnnualSummary(startDate, endDate) {
    const members = reportArray('kkMembers');
    const announcements = reportArray('skAnnouncements').filter(function (event) {
        return event && reportDateInRange(event.date, startDate, endDate);
    });
    const eventIds = {};
    announcements.forEach(function (event) {
        if (event && event.id != null) eventIds[String(event.id)] = true;
    });
    const meetings = reportArray('skMeetings').filter(function (meeting) {
        return meeting && reportDateInRange(meeting.startDate || meeting.date, startDate, endDate);
    });
    const signups = reportArray('skEventRegistrations').filter(function (signup) {
        return signup && eventIds[String(signup.eventId || '')];
    });
    const confirmedEventSignups = reportArray('skEventRegistrations').filter(function (signup) {
        return signup && signup.attendanceStatus === 'Attended' &&
            eventIds[String(signup.eventId || '')];
    });
    const attendedSignupMemberEvents = {};
    reportArray('skEventRegistrations').forEach(function (signup) {
        if (signup && signup.eventId != null && signup.memberId && signup.attendanceStatus === 'Attended') {
            attendedSignupMemberEvents[String(signup.eventId) + ':' + String(signup.memberId)] = true;
        }
    });
    const walkinMemberEvents = {};
    const walkins = reportArray('skEventAttendance').filter(function (record) {
        if (!record || !eventIds[String(record.eventId || '')]) return false;
        const memberEventKey = String(record.eventId || '') + ':' + String(record.memberId || '');
        if (!record.memberId) return true;
        if (attendedSignupMemberEvents[memberEventKey] || walkinMemberEvents[memberEventKey]) return false;
        walkinMemberEvents[memberEventKey] = true;
        return true;
    });
    const attendance = reportObject('skAttendance');
    const meetingIds = {};
    meetings.forEach(function (meeting) { meetingIds[String(meeting.id)] = true; });
    let attendanceRecords = 0, attended = 0, absent = 0, excused = 0;
    Object.keys(attendance).forEach(function (meetingId) {
        if (!meetingIds[meetingId] || !attendance[meetingId] ||
            typeof attendance[meetingId] !== 'object') return;
        Object.keys(attendance[meetingId]).forEach(function (memberId) {
            const record = attendance[meetingId][memberId] || {};
            attendanceRecords++;
            if (record.status === 'Present' || record.status === 'Late') attended++;
            else if (record.status === 'Absent') absent++;
            else if (record.status === 'Excused') excused++;
        });
    });
    const activeMembers = members.filter(function (member) {
        return member && member.status === 'Active';
    }).length;
    const publishedPrograms = announcements.filter(function (event) {
        return event.publishState === 'Published';
    }).length;
    const rows = [
        ['KK members (current total)', String(members.filter(Boolean).length)],
        ['Active KK members (current)', String(activeMembers)],
        ['Programs / announcements in date range', String(announcements.length)],
        ['Published programs in date range', String(publishedPrograms)],
        ['SK meetings in date range', String(meetings.length)],
        ['SK meeting attendance records', String(attendanceRecords)],
        ['Present or late', String(attended)],
        ['Excused', String(excused)],
        ['Absent', String(absent)],
        ['Event sign-ups submitted in date range', String(signups.length)],
        ['Confirmed event attendances (including walk-ins)', String(confirmedEventSignups.length + walkins.length)],
        ['Linked KK members with confirmed attendance', String(new Set(
            confirmedEventSignups.filter(function (signup) { return !!signup.memberId; }).map(function (signup) { return String(signup.memberId); })
                .concat(walkins.filter(function (record) { return !!record.memberId; }).map(function (record) { return String(record.memberId); }))
        ).size)]
    ];
    openDataReport('Annual Summary', reportDateLabel(startDate, endDate),
        ['Measure', 'Total'], rows,
        [
            'Member totals are current totals and are not limited by the selected date range; they describe the membership state when this report is generated.',
            'SK meeting attendance and event attendance are reported separately. Event sign-ups are not confirmed attendance.',
            'The selected date range filters events by scheduled event date. Sign-ups and attendance for those events are included regardless of when registration or attendance was recorded.'
        ]);
}

function generateFilteredReport() {
    const selected = document.getElementById('filterReportType').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    if (startDate && endDate && startDate > endDate) {
        AppAlerts.warning('The start date must be on or before the end date.');
        return;
    }
    if (selected === 'Budget & Expense Report') {
        generateBudgetReport();
        return;
    }
    generateReport(selected, startDate, endDate);
}

function generateReport(reportName, startDate, endDate) {
    if (reportName === 'Budget & Expense Report') {
        generateBudgetReport();
        return;
    }
    startDate = startDate || '';
    endDate = endDate || '';
    try {
        if (reportName === 'KK Member Report') buildMemberReport(startDate, endDate);
        else if (reportName === 'Attendance Report') buildAttendanceReport(
            startDate,
            endDate,
            document.getElementById('includeMinutes').checked
        );
        else if (reportName === 'Participation Report') buildParticipationReport(startDate, endDate);
        else if (reportName === 'Programs Report') buildProgramsReport(startDate, endDate);
        else if (reportName === 'Annual Summary') buildAnnualSummary(startDate, endDate);
        else throw new Error('This report type is not supported.');
    } catch (error) {
        AppAlerts.error('Could not generate ' + reportName + ': ' + error.message);
    }
}

const reportTypeSelect = document.getElementById('filterReportType');
const includeMinutesCheckbox = document.getElementById('includeMinutes');
function updateMinutesReportOption() {
    includeMinutesCheckbox.disabled = reportTypeSelect.value !== 'Attendance Report';
    if (includeMinutesCheckbox.disabled) includeMinutesCheckbox.checked = false;
}
reportTypeSelect.addEventListener('change', updateMinutesReportOption);
updateMinutesReportOption();

loadBudgetData();
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/app-alerts.js?v=2"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>