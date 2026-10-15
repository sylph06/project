<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Reports</title>

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
            background:white;
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
            background:white;
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
            background:white;
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

        .summary-grid {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:12px;
            margin-bottom:14px;
        }

        .summary-card {
            background:#fff;
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
            background:#fff;
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
            background:#fff;
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
            .entry-row.three { grid-template-columns:1fr; }
            .pdf-section { flex-direction:column; align-items:stretch; }
            .pdf-btn { width:100%; }
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
        <div class="header-title">Reports</div>
        <small>KK Youth Profiling System</small>
    </div></div>

    <div class="user-area">
        <div>
            <div style="font-size:15px;font-weight:bold;"><?= e(uname()) ?></div>
            <small><?= e(strtoupper(urole())) ?></small>
        </div>
        <div class="avatar"><?= e(uinit()) ?></div>
    </div>
</header>

<div class="content">

<section class="hero">
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
            <p>Monitor youth participation and involvement in programs.</p>
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
                <option>Budget & Expense Report</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" style="font-size:14px;font-weight:bold;">Start Date</label>
            <input type="date" class="form-control">
        </div>

        <div class="col-md-3">
            <label class="form-label" style="font-size:14px;font-weight:bold;">End Date</label>
            <input type="date" class="form-control">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn-report w-100" onclick="generateFilteredReport()">Generate</button>
        </div>
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
                        Edit the budget and expense records before generating the final PDF report.
                    </div>
                </div>
                <div class="fy-badge" id="fyBadge">FY 2026-2027</div>
            </div>

            <div class="fund-box">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <div>
                        <div style="font-size:17px;font-weight:800;">Set Barangay Youth Fund</div>
                        <div style="font-size:13px;color:var(--bs-secondary-color);margin-top:5px;">
                            Enter the total budget available for the fiscal year.
                        </div>
                    </div>
                    <i class="bi bi-stack" style="font-size:26px;"></i>
                </div>

                <div class="fund-grid">
                    <div>
                        <label class="field-label">Fiscal Year</label>
                        <input id="budgetFY" class="budget-input" value="2026-2027">
                    </div>

                    <div>
                        <label class="field-label">Total Budget (₱)</label>
                        <input id="budgetAmount" type="number" min="0" step="0.01" class="budget-input" value="0">
                    </div>

                    <button class="save-budget-btn" onclick="saveBudget()">
                        <i class="bi bi-floppy-fill me-1"></i> Save Budget
                    </button>
                </div>
            </div>

            <div class="summary-grid">

                <div class="summary-card">
                    <div class="summary-label">Total Budget</div>
                    <div class="summary-value" id="summaryBudget">₱0.00</div>
                    <div class="summary-note">Approved/available budget</div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">Total Spent</div>
                    <div class="summary-value spent-value" id="summarySpent">₱0.00</div>
                    <div class="summary-note">All recorded expenses</div>
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

            <div class="entry-grid">

                <div class="entry-card">
                    <h4>Record Money Spent</h4>

                    <div class="entry-row">
                        <div>
                            <label class="field-label">Date</label>
                            <input type="date" id="expenseDate" class="budget-input">
                        </div>

                        <div>
                            <label class="field-label">Program / Expense Title</label>
                            <input id="expenseTitle" class="budget-input" placeholder="e.g. Basketball Equipment">
                        </div>
                    </div>

                    <div class="entry-row three">
                        <div>
                            <label class="field-label">Category</label>
                            <select id="expenseCategory" class="budget-select">
                                <option value="">Select category</option>
                                <option>Sports & Recreation</option>
                                <option>Education</option>
                                <option>Health</option>
                                <option>Livelihood</option>
                                <option>Events & Programs</option>
                                <option>Supplies & Equipment</option>
                                <option>Food & Refreshments</option>
                                <option>Transportation</option>
                                <option>Training & Seminars</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="field-label">Amount (₱)</label>
                            <input type="number" id="expenseAmount" min="0" step="0.01" class="budget-input" placeholder="0.00">
                        </div>

                        <div>
                            <label class="field-label">Paid To / Recipient</label>
                            <input id="expenseRecipient" class="budget-input" placeholder="Person / supplier / organization">
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Where did the money go? / Description</label>
                        <textarea id="expenseDescription" class="budget-textarea" placeholder="Explain what the money was used for."></textarea>
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
                    <div id="categoryBreakdown" class="where-empty">
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
                                <th>PROGRAM / EXPENSE</th>
                                <th>CATEGORY</th>
                                <th>PAID TO</th>
                                <th>AMOUNT</th>
                                <th>DESCRIPTION</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="expenseTableBody">
                            <tr>
                                <td colspan="7" class="empty-row">
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

<script>
const BUDGET_KEY = 'skTransparencyBudget';
const EXPENSE_KEY = 'skTransparencyExpenses';

let budgetData = {
    fy: '2026-2027',
    amount: 0
};

let expenseData = [];
let editingExpenseIndex = -1;

function formatPeso(value) {
    return '₱' + Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits:2,
        maximumFractionDigits:2
    });
}

function escapeReportHtml(value) {
    return String(value ?? '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#052c65;');
}

function loadBudgetData() {
    try {
        const savedBudget = JSON.parse(DB.getItem(BUDGET_KEY) || 'null');
        const savedExpenses = JSON.parse(DB.getItem(EXPENSE_KEY) || '[]');

        if (savedBudget && typeof savedBudget === 'object') {
            budgetData = {
                fy: savedBudget.fy || '2026-2027',
                amount: Number(savedBudget.amount || 0)
            };
        }

        expenseData = Array.isArray(savedExpenses) ? savedExpenses : [];
    } catch(error) {
        budgetData = { fy:'2026-2027', amount:0 };
        expenseData = [];
    }
}

function saveBudget() {
    const fy = document.getElementById('budgetFY').value.trim() || '2026-2027';
    const amount = Math.max(0, Number(document.getElementById('budgetAmount').value || 0));

    budgetData = { fy, amount };

    DB.setItem(BUDGET_KEY, JSON.stringify(budgetData));

    updateBudgetInterface();

    alert('Budget saved successfully.');
}

function updateBudgetInterface() {
    document.getElementById('budgetFY').value = budgetData.fy;
    document.getElementById('budgetAmount').value = budgetData.amount;
    document.getElementById('fyBadge').textContent = 'FY ' + budgetData.fy;

    const totalBudget = Number(budgetData.amount || 0);
    const totalSpent = expenseData.reduce((sum, expense) => {
        return sum + Number(expense.amount || 0);
    }, 0);

    const remaining = totalBudget - totalSpent;
    const utilization = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;
    const progress = Math.min(100, Math.max(0, utilization));

    document.getElementById('summaryBudget').textContent = formatPeso(totalBudget);
    document.getElementById('summarySpent').textContent = formatPeso(totalSpent);
    document.getElementById('summaryRemaining').textContent = formatPeso(remaining);
    document.getElementById('summaryUtilization').textContent =
        (totalBudget > 0 ? utilization.toFixed(1) : '0.0') + '%';

    document.getElementById('summaryProgress').style.width = progress + '%';

    renderCategoryBreakdown(totalSpent);
    renderExpenseTable();
}

function addOrUpdateExpense() {
    const date = document.getElementById('expenseDate').value;
    const title = document.getElementById('expenseTitle').value.trim();
    const category = document.getElementById('expenseCategory').value;
    const amount = Number(document.getElementById('expenseAmount').value || 0);
    const recipient = document.getElementById('expenseRecipient').value.trim();
    const description = document.getElementById('expenseDescription').value.trim();

    if (!date || !title || !category || amount <= 0 || !recipient || !description) {
        alert('Please complete all expense fields.');
        return;
    }

    const expense = {
        date,
        title,
        category,
        amount,
        recipient,
        description
    };

    if (editingExpenseIndex >= 0) {
        expenseData[editingExpenseIndex] = expense;
    } else {
        expenseData.push(expense);
    }

    DB.setItem(EXPENSE_KEY, JSON.stringify(expenseData));

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
    document.getElementById('expenseCategory').value = expense.category || '';
    document.getElementById('expenseAmount').value = expense.amount || '';
    document.getElementById('expenseRecipient').value = expense.recipient || '';
    document.getElementById('expenseDescription').value = expense.description || '';

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

    if (!confirm('Delete this expense record?')) return;

    expenseData.splice(index, 1);
    DB.setItem(EXPENSE_KEY, JSON.stringify(expenseData));

    if (editingExpenseIndex === index) {
        cancelExpenseEdit();
    }

    updateBudgetInterface();
}

function clearExpenseForm() {
    document.getElementById('expenseDate').value = '';
    document.getElementById('expenseTitle').value = '';
    document.getElementById('expenseCategory').value = '';
    document.getElementById('expenseAmount').value = '';
    document.getElementById('expenseRecipient').value = '';
    document.getElementById('expenseDescription').value = '';
}

function renderCategoryBreakdown(totalSpent) {
    const container = document.getElementById('categoryBreakdown');

    if (!expenseData.length) {
        container.className = 'where-empty';
        container.innerHTML = 'No spending recorded yet.';
        return;
    }

    const totals = {};

    expenseData.forEach(expense => {
        const category = expense.category || 'Other';
        totals[category] = (totals[category] || 0) + Number(expense.amount || 0);
    });

    const entries = Object.entries(totals).sort((a,b) => b[1] - a[1]);

    container.className = '';
    container.innerHTML = entries.map(([category, amount]) => {
        const percent = totalSpent > 0 ? (amount / totalSpent) * 100 : 0;

        return `
            <div class="category-item">
                <div class="category-head">
                    <span>${escapeReportHtml(category)}</span>
                    <span>${formatPeso(amount)}</span>
                </div>
                <div class="category-bar">
                    <span style="width:${Math.min(100, percent)}%"></span>
                </div>
            </div>
        `;
    }).join('');
}

function renderExpenseTable() {
    const tbody = document.getElementById('expenseTableBody');
    const count = document.getElementById('expenseCount');

    count.textContent = expenseData.length + (expenseData.length === 1 ? ' Expense' : ' Expenses');

    if (!expenseData.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-row">
                    No expenses recorded yet. Add an expense above to show where the money went.
                </td>
            </tr>
        `;
        return;
    }

    const sorted = expenseData.map((expense,index) => ({
        ...expense,
        originalIndex:index
    })).sort((a,b) => String(b.date).localeCompare(String(a.date)));

    tbody.innerHTML = sorted.map(expense => `
        <tr>
            <td>${escapeReportHtml(expense.date)}</td>
            <td>${escapeReportHtml(expense.title)}</td>
            <td>${escapeReportHtml(expense.category)}</td>
            <td>${escapeReportHtml(expense.recipient)}</td>
            <td>${formatPeso(expense.amount)}</td>
            <td>${escapeReportHtml(expense.description)}</td>
            <td>
                <button class="table-action edit-btn" onclick="editExpense(${expense.originalIndex})">
                    <i class="bi bi-pencil-fill"></i> Edit
                </button>
                <button class="table-action delete-btn" onclick="deleteExpense(${expense.originalIndex})">
                    <i class="bi bi-trash-fill"></i> Delete
                </button>
            </td>
        </tr>
    `).join('');
}

function generateBudgetReport() {
    loadBudgetData();
    updateBudgetInterface();

    document.getElementById('budgetModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

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
    const fy = document.getElementById('budgetFY').value.trim() || '2026-2027';
    const totalBudget = Number(document.getElementById('budgetAmount').value || 0);

    /* Save the current edited budget before generating */
    budgetData = { fy, amount: totalBudget };
    DB.setItem(BUDGET_KEY, JSON.stringify(budgetData));

    const totalSpent = expenseData.reduce((sum,e) => sum + Number(e.amount || 0),0);
    const remaining = totalBudget - totalSpent;
    const utilization = totalBudget > 0 ? (totalSpent / totalBudget) * 100 : 0;

    const categoryTotals = {};

    expenseData.forEach(expense => {
        const category = expense.category || 'Other';
        categoryTotals[category] =
            (categoryTotals[category] || 0) + Number(expense.amount || 0);
    });

    const categoryRows = Object.entries(categoryTotals)
        .sort((a,b) => b[1] - a[1])
        .map(([category,amount]) => `
            <tr>
                <td>${escapeReportHtml(category)}</td>
                <td class="right">${formatPeso(amount)}</td>
            </tr>
        `).join('');

    const expenseRows = expenseData.length
        ? expenseData
            .slice()
            .sort((a,b) => String(b.date).localeCompare(String(a.date)))
            .map(expense => `
                <tr>
                    <td>${escapeReportHtml(expense.date)}</td>
                    <td>${escapeReportHtml(expense.title)}</td>
                    <td>${escapeReportHtml(expense.category)}</td>
                    <td>${escapeReportHtml(expense.recipient)}</td>
                    <td class="right">${formatPeso(expense.amount)}</td>
                    <td>${escapeReportHtml(expense.description)}</td>
                </tr>
            `).join('')
        : `<tr><td colspan="6" class="center">No expenses recorded.</td></tr>`;

    const reportWindow = window.open('', '_blank', 'width=1200,height=800');

    if (!reportWindow) {
        alert('Please allow pop-ups in your browser to generate the PDF-ready report.');
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
                .remaining { color:#198754; }

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

                <h2>Spending Breakdown by Category</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${categoryRows || `<tr><td colspan="2" class="center">No spending recorded.</td></tr>`}
                    </tbody>
                </table>

                <h2>Complete Expense Records</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Program / Expense</th>
                            <th>Category</th>
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

function generateFilteredReport() {
    const selected = document.getElementById('filterReportType').value;

    if (selected === 'Budget & Expense Report') {
        generateBudgetReport();
        return;
    }

    generateReport(selected);
}

function generateReport(reportName) {
    if (reportName === 'Budget & Expense Report') {
        generateBudgetReport();
        return;
    }

    alert(
        reportName +
        '\n\nReport generation selected.\n\n' +
        'This report is ready for connection to the PHP and MySQL database.'
    );
}

loadBudgetData();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>