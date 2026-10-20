<?php require_once __DIR__ . '/config.php'; require_viewer(); ?>
<!DOCTYPE html>
<html lang="en"<?= is_public() ? ' class="is-public"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Youth Registration</title>
 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">
 
    <style>
        :root {
            --kk-primary: var(--bs-primary);
            --kk-primary-dark: var(--bs-primary-text-emphasis);
            --kk-primary-soft: rgba(var(--bs-primary-rgb),.12);
            --black: var(--bs-body-color);
            --dark: var(--bs-primary-text-emphasis);
            --cream: var(--bs-tertiary-bg);
            --line: var(--bs-border-color);
            --muted: var(--bs-secondary-color);
            --white: #ffffff;
        }
 
        * {
            box-sizing: border-box;
        }
 
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--cream);
            color: var(--black);
        }
 
        .top-strip {
            height: 7px;
            background:var(--bs-primary);
        }
 
        .main {
            min-height: 100vh;
        }
 
        .header {
            height: 70px;
            background: white;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 900;
        }
 
        .header-title {
            font-weight: 700;
            font-size: 20px;
        }
 
        .header small {
            color: var(--muted);
            font-size: 14px;
        }
 
        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }
 
        .avatar {
            width: 38px;
            height: 38px;
            background:var(--bs-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 15px;
        }
 
        .content {
            padding: 30px;
        }
 
        .hero {
            background:var(--bs-primary);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 25px;
        }
 
        .hero h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 6px;
        }
 
        .hero p {
            margin: 0;
            color: var(--bs-primary-text-emphasis);
            font-size: 16px;
        }
 
        .card-box {
            background: white;
            border: 1px solid var(--line);
            border-radius: 14px;
            margin-bottom: 22px;
            overflow: hidden;
        }
 
        .card-header {
            background:var(--bs-primary-text-emphasis);
            color: white;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: bold;
        }
 
        .card-header i {
            color: var(--kk-primary);
            margin-right: 8px;
        }
 
        .card-body {
            padding: 22px;
        }
 
        .form-label {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 6px;
        }
 
        .form-control,
        .form-select {
            height: 42px;
            border: 1px solid var(--bs-border-color);
            border-radius: 7px;
            font-size: 15px;
        }
 
        .form-control:focus,
        .form-select:focus {
            border-color: var(--kk-primary-dark);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .2);
        }
 
        .required {
            color: var(--bs-danger);
        }
 
        .info-box {
            background: rgba(var(--bs-primary-rgb),.08);
            border: 1px solid var(--bs-border-color);
            border-left: 4px solid var(--kk-primary);
            padding: 14px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--bs-secondary-color);
            margin-bottom: 20px;
        }
 
        .summary {
            background:var(--bs-primary-text-emphasis);
            color: white;
            border-radius: 14px;
            padding: 22px;
            position: sticky;
            top: 95px;
        }
 
        .summary h5 {
            font-size: 18px;
            margin-bottom: 20px;
        }
 
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(var(--bs-white-rgb), .18);
            font-size: 14px;
        }
 
        .summary-item span:first-child {
            color: rgba(var(--bs-white-rgb), .78);
        }
 
        .summary-item span:last-child {
            font-weight: bold;
        }
 
        .action-bar {
            background: white;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 25px;
        }
 
        .btn-black {
            background:var(--bs-primary-text-emphasis);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
        }
 
        .btn-black:hover {
            background: var(--bs-primary);
            color: white;
        }
 
        .btn-primary {
            background:var(--bs-primary);
            color: var(--black);
            border: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
        }
 
        .btn-primary:hover {
            background: var(--kk-primary-dark);
        }
 
        .btn-outline {
            background: white;
            color: var(--black);
            border: 1px solid var(--bs-border-color);
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
        }
 
        .check-area {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
 
        .check-item {
            border: 1px solid var(--bs-border-color);
            padding: 9px 12px;
            border-radius: 7px;
            font-size: 14px;
            background: #fff;
        }
 
        .check-item input {
            margin-right: 6px;
        }
 
        .footer-note {
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            padding: 10px 0 25px;
        }
 
        @media (max-width: 991px) {
 
            .header {
                padding: 0 18px;
            }
 
            .content {
                padding: 18px;
            }
 
            .summary {
                position: static;
                margin-top: 20px;
            }
        }
 
        @media (max-width: 576px) {
            .hero h1 {
                font-size: 25px;
            }
 
            .action-bar {
                flex-direction: column;
            }
 
            .action-bar button {
                width: 100%;
            }
        }
    
        /* ---- small additions for the revised form (everything else above is the original style) ---- */
        .group-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            margin: 4px 0 8px;
        }
 
        .form-text {
            font-size: 13px;
        }
 
        #toastBox {
            position: fixed;
            top: 85px;
            right: 20px;
            z-index: 2000;
            max-width: 360px;
        }
 
        .thumb {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }

        /* ---- 10 Centers of Participation: grid layout ---- */
        #centerPrograms {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 10px;
            align-items: start;
        }

        #centerPrograms .center-item {
            margin: 0;
        }

        #centerPrograms .check-item {
            display: flex;
            align-items: center;
            width: 100%;
        }

        #centerPrograms .center-subtype {
            height: 38px;
            font-size: 14px;
        }
    </style>
<?php include __DIR__ . '/init.php'; ?>
</head>
 
<body>
 
<div class="top-strip"></div>
 
<script src="sidebar.js?v=4"></script>
 
<main class="main page-content">
 
    <header class="header">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div>
            <div class="header-title">Youth Registration</div>
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
            <h1>KK Youth Profiling</h1>
            <p><?= is_public() ? 'Fill in the form to register as a KK youth member. An SK officer will review your registration.' : 'Register a new youth member into the Katipunan ng Kabataan database.' ?></p>
        </section>
 
        <div class="info-box">
            <i class="bi bi-info-circle-fill me-1"></i>
            Please make sure that all information provided is complete and accurate before submitting the registration.
            Only youth aged 15 to 30 years old can be registered.
        </div>
 
        <div id="formMessage"></div>
 
        <form id="registrationForm" novalidate>
 
            <div class="row">
 
                <div class="col-lg-12">
 
                    <!-- PERSONAL INFORMATION (name, birth date, age, gender) -->
                    <div class="card-box">
 
                        <div class="card-header">
                            <i class="bi bi-person-fill"></i>
                            Personal Information
                        </div>
 
                        <div class="card-body">
 
                            <div class="row g-3">
 
                                <div class="col-12 admin-only">
                                    <label class="form-label">Profile Picture <span class="text-muted fw-normal">(optional)</span></label>
 
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
 
                                        <img id="photoPreview"
                                             alt="Profile preview"
                                             class="rounded-circle border object-fit-cover d-none"
                                             style="width:72px;height:72px;">
 
                                        <span id="photoPlaceholder"
                                              class="d-inline-flex align-items-center justify-content-center rounded-circle border bg-light text-muted"
                                              style="width:72px;height:72px;font-size:1.8rem;">
                                            <i class="bi bi-person"></i>
                                        </span>
 
                                        <input type="file" class="form-control" id="photo" accept="image/*" style="max-width:320px;">
 
                                        <button type="button" class="btn btn-sm btn-outline-dark d-none" id="removePhotoBtn">
                                            <i class="bi bi-x-circle"></i> Remove
                                        </button>
                                    </div>
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="firstName">
                                        First Name <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="firstName" maxlength="60">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="middleName">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" maxlength="60">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="lastName">
                                        Last Name <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="lastName" maxlength="60">
                                </div>
 
                                <div class="col-md-3">
                                    <label class="form-label" for="suffix">Suffix</label>
                                    <select id="suffix" class="form-select">
                                        <option value="">None</option>
                                        <option>Jr.</option>
                                        <option>Sr.</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                        <option>V</option>
                                        <option>VI</option>
                                        <option>VII</option>
                                        <option>VIII</option>
                                        <option>IX</option>
                                        <option>X</option>
                                        <option>XI</option>
                                        <option>XII</option>
                                        <option>XIII</option>
                                        <option>XIV</option>
                                        <option>XV</option>
                                        <option>XVI</option>
                                        <option>XVII</option>
                                        <option>XVIII</option>
                                        <option>XIX</option>
                                        <option>XX</option>
                                    </select>
                                </div>
 
                                <div class="col-md-3">
                                    <label class="form-label" for="birthDate">
                                        Date of Birth <span class="required">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="birthDate">
                                </div>
 
                                <div class="col-md-2">
                                    <label class="form-label" for="age">Age</label>
                                    <input type="text" class="form-control" id="age" readonly tabindex="-1" placeholder="" aria-readonly="true">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="gender">
                                        Gender <span class="required">*</span>
                                    </label>
                                    <select id="gender" class="form-select" data-other="box_genderOther" data-other-value="Others">
                                        <option value="">Select</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                        <option>Transgender Male</option>
                                        <option>Transgender Female</option>
                                        <option>Non-binary</option>
                                        <option>Others</option>
                                    </select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_genderOther">
                                    <label class="form-label" for="genderOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="genderOther" class="form-control" maxlength="40" placeholder="Self-described gender">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="civilStatus">Civil Status</label>
                                    <select id="civilStatus" class="form-select">
                                        <option value="">Select</option>
                                        <option>Single</option>
                                        <option>Married</option>
                                        <option>Widowed</option>
                                        <option>Separated</option>
                                        <option>Live-in</option>
                                    </select>
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="religion">Religion</label>
                                    <select id="religion" class="form-select" data-other="box_religionOther" data-other-value="Other">
                                        <option value="">Select</option>
                                        <option>Born Again</option>
                                        <option>Catholic</option>
                                        <option>Christian</option>
                                        <option>Iglesia ni Cristo</option>
                                        <option>Islam</option>
                                        <option>Protestant</option>
                                        <option>Other</option>
                                    </select>
                                </div>
 
                                <div class="col-md-4 d-none" id="box_religionOther">
                                    <label class="form-label" for="religionOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="religionOther" class="form-control" maxlength="60" placeholder="Religion">
                                </div>
 
                            </div>
 
                        </div>
                    </div>
 
                    <!-- ADDRESS -->
                    <div class="card-box">
 
                        <div class="card-header">
                            <i class="bi bi-geo-alt-fill"></i>
                            Address Information
                        </div>
 
                        <div class="card-body">
 
                            <div class="row g-3">
 
                                <div class="col-md-4">
                                    <label class="form-label" for="houseNo">House Number</label>
                                    <input id="houseNo" type="text" class="form-control" inputmode="numeric" maxlength="6" placeholder="Numbers only">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="street">Street</label>
                                    <input id="street" type="text" class="form-control" maxlength="80">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="purok">
                                        Purok / Sitio <span class="required">*</span>
                                    </label>
                                    <select id="purok" class="form-select">
                                        <option value="">Select Purok</option>
                                        <option>Purok 1</option>
                                        <option>Purok 2</option>
                                        <option>Purok 3</option>
                                        <option>Purok 4</option>
                                        <option>Purok 5</option>
                                        <option>Purok 6</option>
                                        <option>Purok 7</option>
                                    </select>
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="barangay">Barangay</label>
                                    <input id="barangay" type="text" class="form-control" value="Sikat" readonly>
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="city">City / Municipality</label>
                                    <input id="city" type="text" class="form-control" value="Alfonso" readonly>
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="province">Province</label>
                                    <input id="province" type="text" class="form-control" value="Cavite" readonly>
                                </div>
 
                            </div>
 
                        </div>
                    </div>
 
                    <!-- CONTACT -->
                    <div class="card-box">
 
                        <div class="card-header">
                            <i class="bi bi-telephone-fill"></i>
                            Contact Information
                        </div>
 
                        <div class="card-body">
 
                            <div class="row g-3">
 
                                <div class="col-md-4">
                                    <label class="form-label" for="mobile">
                                        Mobile Number <span class="required">*</span>
                                    </label>
                                    <input id="mobile" type="tel" class="form-control" inputmode="numeric" placeholder="09XXXXXXXXX" maxlength="11" autocomplete="tel">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="email">Email Address</label>
                                    <input id="email" type="email" class="form-control" placeholder="example@email.com" maxlength="100">
                                </div>
 
                                <div class="col-md-4">
                                    <label class="form-label" for="facebook">Facebook Account <span class="text-muted fw-normal">(optional)</span></label>
                                    <input id="facebook" type="text" class="form-control" placeholder="Facebook name or link" maxlength="120">
                                </div>
 
                            </div>
 
                        </div>
                    </div>
 
                    <!-- EDUCATION & EMPLOYMENT (driven by classification) -->
                    <div class="card-box">
 
                        <div class="card-header">
                            <i class="bi bi-mortarboard-fill"></i>
                            Education & Employment
                        </div>
 
                        <div class="card-body">
 
                            <div class="row g-3">
 
                                <div class="col-md-6">
                                    <label class="form-label" for="classification">
                                        Youth Classification <span class="required">*</span>
                                    </label>
                                    <select id="classification" class="form-select" data-other="box_classificationOther" data-other-value="Other">
                                        <option value="">Select Classification</option>
                                        <option>In-School Youth</option>
                                        <option>Out-of-School Youth</option>
                                        <option>Working Youth</option>
                                        <option>Unemployed Youth</option>
                                        <option>Other</option>
                                    </select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_classificationOther">
                                    <label class="form-label" for="classificationOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="classificationOther" class="form-control" maxlength="60" placeholder="Classification">
                                </div>
 
                                <div class="col-12" id="classHint">
                                    <div class="form-text">Select a youth classification to show the related school, employment and training fields.</div>
                                </div>
 
                                <div class="col-md-6">
                                    <label class="form-label" for="educationLevel">
                                        Educational Attainment <span class="required">*</span>
                                    </label>
                                    <select id="educationLevel" class="form-select" data-other="box_educationOther" data-other-value="Other"></select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_educationOther">
                                    <label class="form-label" for="educationOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="educationOther" class="form-control" maxlength="60" placeholder="Educational attainment">
                                </div>
 
                                <div class="col-md-6">
                                    <label class="form-label" for="educationStatus">
                                        Education Status <span class="required">*</span>
                                    </label>
                                    <select id="educationStatus" class="form-select" data-other="box_educationStatusOther" data-other-value="Other"></select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_educationStatusOther">
                                    <label class="form-label" for="educationStatusOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="educationStatusOther" class="form-control" maxlength="60" placeholder="Education status">
                                </div>
 
                                <div class="col-md-6 d-none" id="box_school">
                                    <label class="form-label" for="school">
                                        School / Institution <span class="required d-none" id="star_school">*</span>
                                    </label>
                                    <select id="school" class="form-select" data-other="box_schoolOther" data-other-value="Others"></select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_schoolOther">
                                    <label class="form-label" for="schoolOther">School / Institution Name <span class="required">*</span></label>
                                    <input type="text" id="schoolOther" class="form-control" maxlength="100" placeholder="Name of school / institution">
                                </div>
 
                                <div class="col-md-6 d-none" id="box_employment">
                                    <label class="form-label" for="employment">
                                        Employment Status <span class="required d-none" id="star_employment">*</span>
                                    </label>
                                    <select id="employment" class="form-select" data-other="box_employmentOther" data-other-value="Other"></select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_employmentOther">
                                    <label class="form-label" for="employmentOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="employmentOther" class="form-control" maxlength="60" placeholder="Employment status">
                                </div>

                                <div class="col-md-6 d-none" id="box_salaryIncome">
                                    <label class="form-label" for="salaryIncome">Monthly Salary / Income (₱)</label>
                                    <select id="salaryIncome" class="form-select"></select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_occupation">
                                    <label class="form-label" for="occupation">
                                        Occupation / Job <span class="required d-none" id="star_occupation">*</span>
                                    </label>
                                    <select id="occupation" class="form-select" data-other="box_occupationOther" data-other-value="Other"></select>
                                </div>

                                <div class="col-md-6 d-none" id="box_occupationOther">
                                    <label class="form-label" for="occupationOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="occupationOther" class="form-control" maxlength="60" placeholder="Occupation / job">
                                </div>
 
                                <div class="col-md-6 d-none" id="box_training">
                                    <label class="form-label" for="training">
                                        Training / Skills Program <span class="required d-none" id="star_training">*</span>
                                    </label>
                                    <select id="training" class="form-select" data-other="box_trainingOther" data-other-value="Other"></select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_trainingOther">
                                    <label class="form-label" for="trainingOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="trainingOther" class="form-control" maxlength="80" placeholder="Training / skills program">
                                </div>
 
                            </div>
 
                        </div>
                    </div>
 
                    <!-- YOUTH INFORMATION -->
                    <div class="card-box">
 
                        <div class="card-header">
                            <i class="bi bi-people-fill"></i>
                            Youth Information
                        </div>
 
                        <div class="card-body">
 
                            <div class="row g-3">
 
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Person with Disability (PWD) <span class="required">*</span>
                                    </label>
                                    <div class="check-area" id="pwdGroup">
                                        <label class="check-item"><input type="radio" name="pwd" value="Yes"> Yes</label>
                                        <label class="check-item"><input type="radio" name="pwd" value="No"> No</label>
                                    </div>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_disability">
                                    <label class="form-label" for="disabilityType">
                                        Disability Type <span class="required">*</span>
                                    </label>
                                    <select id="disabilityType" class="form-select" data-other="box_disabilityOther" data-other-value="Other">
                                        <option value="">Select</option>
                                        <option>Visual</option>
                                        <option>Hearing</option>
                                        <option>Physical</option>
                                        <option>Speech</option>
                                        <option>Intellectual</option>
                                        <option>Psychosocial</option>
                                        <option>Multiple Disabilities</option>
                                        <option>Other</option>
                                    </select>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_disabilityOther">
                                    <label class="form-label" for="disabilityOther">Please specify <span class="required">*</span></label>
                                    <input type="text" id="disabilityOther" class="form-control" maxlength="60" placeholder="Type of disability">
                                </div>
 
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Registered SK Voter <span class="required">*</span>
                                    </label>
                                    <div class="check-area" id="voterGroup">
                                        <label class="check-item"><input type="radio" name="skVoter" value="Yes"> Yes</label>
                                        <label class="check-item"><input type="radio" name="skVoter" value="No"> No</label>
                                    </div>
                                </div>
 
                                <div class="col-md-6 d-none" id="box_voter">
                                    <label class="form-label" for="voterNo">Precinct Voter Number <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" id="voterNo" class="form-control" inputmode="numeric" placeholder="Numbers only" maxlength="30">
                                </div>
 
                                <div class="col-12">
                                    <label class="form-label">Program Interests</label>
 
                                    <div class="group-title">10 Centers of Participation</div>
                                    <div class="check-area mb-3" id="centerPrograms"></div>
 
                                    <div class="group-title">Other</div>
                                    <div class="check-area">
                                        <label class="check-item"><input type="checkbox" class="interest-box" id="interestOtherChk" value="Other"> Other</label>
                                    </div>
 
                                    <div class="mt-2 d-none" id="box_interestOther">
                                        <label class="form-label" for="interestOther">Please specify <span class="required">*</span></label>
                                        <input type="text" id="interestOther" class="form-control" maxlength="80" placeholder="Other program interest">
                                    </div>
                                </div>
 
                                <div class="col-12">
                                    <label class="form-label" for="notes">Additional Notes</label>
                                    <textarea id="notes" class="form-control" rows="4" style="height:auto;"></textarea>
                                </div>
 
                            </div>
 
                        </div>
                    </div>
 
                    <div class="action-bar">
 
                        <button type="button" class="btn-outline" onclick="window.location.href='dashboard.php'">
                            <i class="bi bi-arrow-left"></i>
                            Cancel
                        </button>
 
                        <button type="reset" class="btn-black" id="clearBtn">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Clear
                        </button>
 
                        <button type="button" class="btn-outline d-none" id="cancelEditBtn">
                            <i class="bi bi-x-circle"></i>
                            Cancel Edit
                        </button>
 
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-person-plus-fill"></i>
                            <span id="submitLabel"
                                  data-normal="Register Youth"
                                  data-edit="Submit Changes">Register Youth</span>
                        </button>
 
                    </div>
 
                </div>
 
            </div>
 
        </form>
 
        <!-- PUBLIC REGISTRATIONS (PENDING REVIEW) -->
        <div class="card-box admin-only">

            <div class="card-header">
                <i class="bi bi-inbox-fill"></i>
                Public Registrations (Pending Review)
                <small class="text-muted fw-normal ms-2" id="pubCount">0 pending</small>
            </div>

            <div class="table-responsive" style="max-height:420px;overflow:auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Purok / Sitio</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="pubBody"></tbody>
                </table>
            </div>

        </div>

        <!-- RECENTLY REGISTERED MEMBERS -->
        <div class="card-box admin-only">
 
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people-fill"></i>
                    Recently Registered Youth
                    <small class="text-muted fw-normal ms-2" id="recentCount">0 active youth</small>
                </span>
 
                <span class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light d-none" id="toggleAllBtn">Show All</button>
                    <a href="members.php" class="btn btn-sm btn-outline-light">View All</a>
                </span>
            </div>
 
            <div class="table-responsive">
 
                <table class="table table-hover align-middle mb-0">
 
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Purok / Sitio</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
 
                    <tbody id="recentMembersBody"></tbody>
 
                </table>
 
            </div>
 
        </div>
 
        <!-- EDIT REQUESTS -->
        <div class="card-box admin-only">
 
            <div class="card-header">
                <i class="bi bi-pencil-square"></i>
                Profile Edit Requests
                <small class="text-muted fw-normal ms-2" id="reqCount">0 pending</small>
            </div>
 
            <div class="table-responsive" style="max-height:420px;overflow:auto;">
 
                <table class="table table-hover align-middle mb-0">
 
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Requested On</th>
                            <th>Fields Changed</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
 
                    <tbody id="requestsBody"></tbody>
 
                </table>
 
            </div>
 
        </div>
 
        <!-- ARCHIVED RECORDS -->
        <div class="card-box admin-only">
 
            <div class="card-header">
                <i class="bi bi-archive-fill"></i>
                Archived Records
                <small class="text-muted fw-normal ms-2" id="archiveCount">0 archived</small>
            </div>
 
            <div class="table-responsive">
 
                <table class="table table-hover align-middle mb-0">
 
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Archived On</th>
                            <th>Reason</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
 
                    <tbody id="archiveBody"></tbody>
 
                </table>
 
            </div>
 
        </div>
 
        <div class="footer-note">
            KK Youth Profiling System &nbsp;•&nbsp; SK Barangay Sikat, Alfonso, Cavite &nbsp;•&nbsp; FY 2026–2027
        </div>
 
    </div>
 
</main>
 
<div id="toastBox"></div>
 
<!-- VIEW DETAILS -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2" style="color:var(--bs-primary-border-subtle);"></i>Youth Details</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsBody"></div>
            <div class="modal-footer" id="detailsFooter"></div>
        </div>
    </div>
</div>
 
<!-- PUBLIC REGISTRATION REVIEW -->
<div class="modal fade" id="pubModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-inbox-fill me-2" style="color:var(--bs-primary-border-subtle);"></i>Public Registration</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="pubBodyDetail"></div>
                <div id="pubRemarksBox" class="mt-3">
                    <label class="form-label small fw-bold" for="pubRemarks">Remarks (required when rejecting)</label>
                    <textarea id="pubRemarks" class="form-control" rows="2" maxlength="200"></textarea>
                </div>
            </div>
            <div class="modal-footer" id="pubFooter"></div>
        </div>
    </div>
</div>

<!-- ARCHIVE -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-archive-fill me-2" style="color:var(--bs-primary-border-subtle);"></i>Archive Youth Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-3" id="archiveText"></p>
                <div class="mb-3">
                    <label class="form-label" for="archiveReason">Reason <span class="required">*</span></label>
                    <select id="archiveReason" class="form-select">
                        <option value="">Select reason</option>
                        <option>Moved out of the barangay</option>
                        <option>No longer interested / requested by the youth</option>
                        <option>Duplicate record</option>
                        <option>Incorrect / invalid record</option>
                        <option>Other</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="archiveRemarks">Remarks <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="archiveRemarks" class="form-control" rows="2" style="height:auto;"></textarea>
                </div>
                <div class="form-text mt-2">Archived records are kept and can be restored from the Archived Records section. They are never deleted.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-black" id="confirmArchiveBtn"><i class="bi bi-archive"></i> Archive</button>
            </div>
        </div>
    </div>
</div>
 
<!-- REVIEW EDIT REQUEST -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2" style="color:var(--bs-primary-border-subtle);"></i>Edit Request</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reviewBody"></div>
            <div class="modal-footer" id="reviewFooter">
                <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-black" id="rejectBtn"><i class="bi bi-x-circle"></i> Reject Changes</button>
                <button type="button" class="btn-primary" id="approveBtn"><i class="bi bi-check-circle"></i> Approve Changes</button>
            </div>
        </div>
    </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="app-alerts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- members.js is no longer needed by this page: all registration logic is below -->
<script>
(function () {
    'use strict';
 
    /* =====================================================
       SETTINGS  (easy to edit)
       ===================================================== */
    var STORAGE_KEY   = 'kkMembers';        // saved youth records
    var REQUEST_KEY   = 'kkEditRequests';   // edit requests (previous + requested data = audit trail)
    var MIN_AGE = 15, MAX_AGE = 30;
    var BARANGAY = 'Sikat', CITY = 'Alfonso', PROVINCE = 'Cavite';
    var OFFICER = window.SK_OFFICER || 'SK Officer';
    var PUBLIC_MODE = !!window.SK_PUBLIC;      // public viewers can only SUBMIT a registration for approval
    var AUTO_ARCHIVE_REASON = 'Automatically archived \u2013 exceeded youth age eligibility.';
 
    /* ---------- SCHOOL LIST: add / remove schools here, "Others" is added automatically ---------- */
    var SCHOOLS = [
        'Adventist University of the Philippines',
        'AMA College - Cavite',
        'Asian Institute of Science and Technology (AISAT)',
        'Cavite Institute',
        'Cavite National High School',
        'Cavite State University - Alfonso Campus',
        'Cavite State University - Bacoor Campus',
        'Cavite State University - Carmona Campus',
        'Cavite State University - Cavite City Campus',
        'Cavite State University - General Trias Campus',
        'Cavite State University - Imus Campus',
        'Cavite State University - Main Campus, Indang',
        'Cavite State University - Naic Campus',
        'Cavite State University - Rosario Campus',
        'Cavite State University - Silang Campus',
        'Cavite State University - Tanza Campus',
        'Cavite State University - Trece Martires City Campus',
        'City College of Tagaytay',
        'De La Salle Medical and Health Sciences Institute',
        'De La Salle University - Dasmariñas',
        'Far Eastern University - Silang',
        'Imus Institute of Science and Technology',
        'Kolehiyo ng Lungsod ng Dasmariñas (KLD)',
        'Lyceum of the Philippines University - Cavite',
        'National College of Science and Technology (NCST)',
        'Olivarez College - Tagaytay',
        'Philippine Christian University - Dasmariñas Campus',
        'Philippine National Police Academy (PNPA)',
        'PNTC Colleges',
        'Rogationist College',
        'San Sebastian College - Recoletos de Cavite',
        'St. Dominic College of Asia',
        'St. Scholastica’s College Westgrove',
        'STI College - Bacoor',
        'STI College - Dasmariñas',
        'STI College - Imus',
        'STI College - Rosario',
        'STI College - Tagaytay',
        'Tagaytay City Science National High School',
        'Technological University of the Philippines - Cavite',
        'Unida Christian Colleges',
        'University of Perpetual Help System - GMA Campus',
    ];
 
    /* ---------- Dropdown lists ---------- */
    var EDU_LEVELS = ['Out-of-school', 'Elementary', 'Junior High School', 'Senior High School',
        'Alternative Learning System', 'Vocational / Technical', 'College', 'Graduate', 'College Graduate', 'Other'];
    var EDU_STATUS = ['Currently Enrolled', 'Graduated', 'Temporarily Not Studying', 'Dropped Out', 'Other'];
    var EMPLOYMENT = ['Employed', 'Self-Employed', 'Freelance / Gig Worker', 'Unemployed', 'Student',
        'Not in Education, Employment or Training', 'Other'];
    var SALARY_INCOME_RANGES = ['No income', 'Below ₱5,000', '₱5,000–₱9,999', '₱10,000–₱14,999',
        '₱15,000–₱19,999', '₱20,000–₱29,999', '₱30,000–₱39,999', '₱40,000–₱49,999', '₱50,000 or more'];
    var OCCUPATIONS = ['Student', 'Employee (Private Company)', 'Government Employee', 'Self-Employed / Business Owner', 'Teacher', 'Police',
        'Vendor / Sari-sari Store', 'Construction Worker', 'Driver / Tricycle', 'Factory / Production Worker', 'Call Center / BPO',
        'Service Crew / Sales', 'Freelancer / Online Worker', 'Household / Homemaker', 'Unemployed', 'Other'];
    var TRAININGS = ['No training yet', 'TESDA / Vocational Course', 'Livelihood Training', 'Leadership Training',
        'Computer / IT Training', 'Seminar / Workshop', 'Other'];
 
    var CENTERS = ['Health', 'Education', 'Economic Empowerment', 'Social Inclusion and Equity',
        'Peace-Building and Security', 'Governance', 'Active Citizenship', 'Environment',
        'Global Mobility', 'Agriculture'];
    var CENTER_CLASSIFICATIONS = {
        'Health': ['Primary Health Care', 'Maternal and Child Health', 'Mental Health and Wellness', 'Nutrition and Food Security',
            'Disease Prevention and Screening', 'Health Education and Advocacy', 'Other'],
        'Education': ['Literacy and Basic Education', 'Formal School Support', 'Skills and Vocational Training',
            'Youth Leadership and Learning', 'Digital and Career Readiness', 'Access to Education Programs', 'Other'],
        'Economic Empowerment': ['Livelihood and Small Business Support', 'Job Placement and Employment', 'Financial Literacy',
            'Cooperative Development', 'Agricultural Enterprise Support', 'Youth Entrepreneurship', 'Other'],
        'Social Inclusion and Equity': ['Gender and Equality Programs', 'Persons with Disabilities Inclusion', 'Indigenous and Minority Support',
            'Community Welfare', 'Anti-Discrimination Advocacy', 'Access to Social Services', 'Other'],
        'Peace-Building and Security': ['Conflict Resolution', 'Community Safety', 'DRRM and Disaster Readiness',
            'Civic Protection', 'Youth Peace Advocacy', 'Security Awareness', 'Other'],
        'Governance': ['Local Governance Awareness', 'Citizen Participation', 'Public Policy Engagement', 'Leadership Development',
            'Transparency and Accountability', 'Youth Governance Training', 'Other'],
        'Active Citizenship': ['Volunteerism and Service Learning', 'Community Mobilization', 'Local Advocacy',
            'Civic Education', 'Participation in Local Programs', 'Community Engagement', 'Other'],
        'Environment': ['Waste Management and Recycling', 'Climate and Resilience', 'Tree Planting and Greening',
            'Water and Sanitation', 'Clean-up Drives', 'Environmental Education', 'Other'],
        'Global Mobility': ['Cultural Exchange', 'Study and Scholarship Opportunities', 'International Networking',
            'Migration and Mobility Awareness', 'Youth Exchange Programs', 'Global Citizenship', 'Other'],
        'Agriculture': ['Organic Farming', 'Urban Gardening', 'Agri-business and Market Support', 'Livestock and Poultry',
            'Agricultural Skills Training', 'Food Production and Sustainability', 'Other']
    };
 
    /* ---------- Classification rules: school / employment / occupation / training = 'req' | 'show' | 'hide' ---------- */
    var RULES = {
        'In-School Youth': {
            school: 'req', employment: 'hide', occupation: 'hide', training: 'hide',
            autoEmployment: 'Student',
            levelExclude: ['Out-of-school', 'College Graduate'], statusOnly: ['Currently Enrolled']
        },
        'Out-of-School Youth': {
            school: 'hide', employment: 'show', occupation: 'show', training: 'show'
        },
        'Working Youth': {
            school: 'hide', employment: 'req', occupation: 'req', training: 'show',
            empOnly: ['Employed', 'Self-Employed', 'Freelance / Gig Worker', 'Other']
        },
        'Unemployed Youth': {
            school: 'hide', employment: 'hide', occupation: 'hide', training: 'show',
            autoEmployment: 'Unemployed'
        },
        'Youth Not in Education, Employment, or Training': {
            school: 'hide', employment: 'hide', occupation: 'hide', training: 'req',
            autoEmployment: 'Not in Education, Employment or Training'
        },
        'Other': {
            school: 'show', employment: 'show', occupation: 'show', training: 'show', statusAll: true
        }
    };
 
    /* ---------- Fields: [id, label] (used for saving, details and edit-request comparison) ---------- */
    var FIELDS = [
        ['firstName', 'First Name'], ['middleName', 'Middle Name'], ['lastName', 'Last Name'], ['suffix', 'Suffix'],
        ['birthDate', 'Date of Birth'], ['gender', 'Gender'], ['genderOther', 'Gender (self-described)'],
        ['civilStatus', 'Civil Status'], ['religion', 'Religion'], ['religionOther', 'Religion (specified)'],
        ['houseNo', 'House Number'], ['street', 'Street'], ['purok', 'Purok / Sitio'],
        ['mobile', 'Mobile Number'], ['email', 'Email Address'], ['facebook', 'Facebook Account'],
        ['classification', 'Youth Classification'], ['classificationOther', 'Classification (specified)'],
        ['educationLevel', 'Educational Attainment'], ['educationOther', 'Educational Attainment (specified)'],
        ['educationStatus', 'Education Status'], ['educationStatusOther', 'Education Status (specified)'],
        ['school', 'School / Institution'], ['schoolOther', 'School / Institution (specified)'],
        ['employment', 'Employment Status'], ['employmentOther', 'Employment Status (specified)'],
        ['salaryIncome', 'Monthly Salary / Income (₱)'],
        ['occupation', 'Occupation / Job'], ['occupationOther', 'Occupation (specified)'], ['training', 'Training / Skills Program'], ['trainingOther', 'Training (specified)'],
        ['pwd', 'PWD'], ['disabilityType', 'Disability Type'], ['disabilityOther', 'Disability (specified)'],
        ['skVoter', 'Registered SK Voter'], ['voterNo', 'Precinct Voter Number'],
        ['interests', 'Program Interests'], ['interestOther', 'Program Interest (specified)'],
        ['notes', 'Additional Notes'], ['photo', 'Profile Picture']
    ];
    var INPUT_IDS = FIELDS.map(function (f) { return f[0]; }).filter(function (k) {
        return ['pwd', 'skVoter', 'interests', 'photo'].indexOf(k) === -1;
    });
    var LABELS = {};
    FIELDS.forEach(function (f) { LABELS[f[0]] = f[1]; });
 
    /* =====================================================
       HELPERS
       ===================================================== */
    function $(id) { return document.getElementById(id); }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function nowISO() { return new Date().toISOString(); }
    function fmtDate(iso) {
        if (!iso) return '\u2014';
        var d = new Date(iso.length === 10 ? iso + 'T00:00:00' : iso);
        return isNaN(d) ? '\u2014' : d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    function calcAge(iso) {
        var b = new Date(iso + 'T00:00:00');
        if (isNaN(b)) return NaN;
        var t = new Date();
        var a = t.getFullYear() - b.getFullYear();
        var m = t.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && t.getDate() < b.getDate())) a--;
        return a;
    }
    function fullName(m) {
        return [m.firstName, m.middleName, m.lastName, m.suffix].filter(Boolean).join(' ');
    }
    function norm(v) {
        if (Array.isArray(v)) return v.slice().sort().join('|');
        return v == null ? '' : String(v);
    }
    function show(id, on) { $(id).classList.toggle('d-none', !on); }
    function isShown(el) { return !el.closest('.d-none'); }
 
    function notify(type, text) {
        var box = $('toastBox');
        var div = document.createElement('div');
        div.className = 'alert alert-' + type + ' shadow small';
        div.textContent = text;
        box.appendChild(div);
        setTimeout(function () { div.remove(); }, 5000);
    }
 
    /* =====================================================
       STORAGE
       ===================================================== */
    var members = [];
    var requests = [];
    var editingId = null;
    var photoData = '';
    var showAll = false;
    var currentRequestId = null;
    var archiveTargetId = null;
 
    function normalizeMember(m) {
        if (!m.id) m.id = 'm' + Date.now() + Math.random().toString(36).slice(2, 7);
        if (!m.gender && m.sex) m.gender = m.sex;
        if (m.status !== 'Archived') m.status = 'Active';        // no "Pending Verification" anymore
        if (typeof m.interests === 'string') m.interests = m.interests ? m.interests.split(',').map(function (s) { return s.trim(); }) : [];
        if (!Array.isArray(m.interests)) m.interests = [];
        if (!Array.isArray(m.history)) m.history = [];
        if (!m.createdAt) m.createdAt = nowISO();
        return m;
    }
    function loadAll() {
        try {
            var a = JSON.parse(DB.getItem(STORAGE_KEY) || '[]');
            members = Array.isArray(a) ? a.map(normalizeMember) : [];
        } catch (e) { members = []; }
        try {
            var r = JSON.parse(DB.getItem(REQUEST_KEY) || '[]');
            requests = Array.isArray(r) ? r : [];
        } catch (e) { requests = []; }
    }
    function saveAll() {
        try {
            if (window.USE_MEMBERS_TABLE) DB.saveMembers(members);
            else DB.setItem(STORAGE_KEY, JSON.stringify(members));
            DB.setItem(REQUEST_KEY, JSON.stringify(requests));
            return true;
        } catch (e) {
            notify('danger', e.message || 'Could not save. Please try again.');
            return false;
        }
    }
    function findMember(id) { return members.filter(function (m) { return m.id === id; })[0]; }
    function findRequest(id) { return requests.filter(function (r) { return r.id === id; })[0]; }
    function pendingFor(id) {
        return requests.filter(function (r) { return r.memberId === id && r.status === 'Edit Request'; })[0];
    }
    function nextMemberNo() {
        var y = new Date().getFullYear(), max = 0;
        members.forEach(function (m) {
            var x = new RegExp('^KK-' + y + '-(\\d+)$').exec(m.memberNo || '');
            if (x) max = Math.max(max, parseInt(x[1], 10));
        });
        return 'KK-' + y + '-' + String(max + 1).padStart(3, '0');
    }
 
    /* =====================================================
       FORM: dropdown lists, show/hide rules
       ===================================================== */
    function setOptions(sel, list, placeholder) {
        var prev = sel.value;
        sel.innerHTML = '';
        var o = document.createElement('option');
        o.value = ''; o.textContent = placeholder;
        sel.appendChild(o);
        list.forEach(function (t) {
            var op = document.createElement('option');
            op.value = t; op.textContent = t;
            sel.appendChild(op);
        });
        if (list.indexOf(prev) !== -1) sel.value = prev;
        else if (list.length === 1) sel.value = list[0];
    }
 
    function currentRule() { return RULES[$('classification').value] || null; }

    function refreshSalaryIncomeVisibility() {
        var classification = $('classification').value;
        var occupation = $('occupation').value;
        var hasOccupation = !!occupation && (occupation !== 'Other' || !!$('occupationOther').value.trim());
        show('box_salaryIncome', classification === 'Working Youth'
            || (classification === 'Out-of-School Youth' && hasOccupation));
    }

    function applyRules() {
        var r = currentRule();
        var level = EDU_LEVELS.filter(function (l) { return !(r && r.levelExclude && r.levelExclude.indexOf(l) !== -1); });
        var status = r && r.statusOnly ? r.statusOnly
            : (r && r.statusAll ? EDU_STATUS : EDU_STATUS.filter(function (s) { return s !== 'Currently Enrolled'; }));
        setOptions($('educationLevel'), level, 'Select');
        setOptions($('educationStatus'), status, 'Select');
        setOptions($('employment'), r && r.empOnly ? r.empOnly : EMPLOYMENT, 'Select');

        ['school', 'employment', 'occupation', 'training'].forEach(function (k) {
            var mode = r ? r[k] : 'hide';
            show('box_' + k, mode !== 'hide');
            $('star_' + k).classList.toggle('d-none', mode !== 'req');
        });
        show('classHint', !r);
        refreshOthers();
        refreshSalaryIncomeVisibility();
    }
 
    function refreshBoxes() {
        var pwd = document.querySelector('input[name="pwd"]:checked');
        var vot = document.querySelector('input[name="skVoter"]:checked');
        show('box_disability', !!pwd && pwd.value === 'Yes');
        show('box_voter', !!vot && vot.value === 'Yes');
        show('box_interestOther', $('interestOtherChk').checked);
        refreshOthers();
    }
 
    // every select with data-other shows its extra text box when the trigger value is chosen
    function refreshOthers() {
        document.querySelectorAll('select[data-other]').forEach(function (sel) {
            var box = $(sel.dataset.other);
            box.classList.toggle('d-none', !(isShown(sel) && sel.value === sel.dataset.otherValue));
        });
    }
 
    function refreshAge() {
        var v = $('birthDate').value;
        var a = v ? calcAge(v) : NaN;
        $('age').value = (isNaN(a) || a < 0) ? '' : a;
    }
 
    /* =====================================================
       VALIDATION
       ===================================================== */
    function clearError(el) {
        el.classList.remove('is-invalid');
        var n = el.nextElementSibling;
        if (n && n.classList.contains('js-error')) n.remove();
    }
    function setError(el, msg) {
        clearError(el);
        el.classList.add('is-invalid');
        var d = document.createElement('div');
        d.className = 'invalid-feedback d-block js-error';
        d.textContent = msg;
        el.insertAdjacentElement('afterend', d);
    }
    function clearAllErrors() {
        document.querySelectorAll('#registrationForm .js-error').forEach(function (e) { e.remove(); });
        document.querySelectorAll('#registrationForm .is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); });
    }
 
    function dobMessage(v) {
        var b = new Date(v + 'T00:00:00');
        if (isNaN(b)) return 'Please enter a valid date of birth.';
        if (b > new Date()) return 'Date of birth cannot be in the future.';
        var a = calcAge(v);
        if (a < MIN_AGE) return 'Registration not allowed: the applicant is only ' + a + ' year' + (a === 1 ? '' : 's') +
            ' old. Only youth aged ' + MIN_AGE + ' to ' + MAX_AGE + ' may register.';
        if (a > MAX_AGE) return 'Registration not allowed: the applicant is ' + a +
            ' years old, above the youth age limit (' + MIN_AGE + ' to ' + MAX_AGE + ').';
        return '';
    }
 
    function validate() {
        var errs = [];
        function bad(el, msg) { setError(el, msg); errs.push(el); }
        function need(id, label) {
            var el = $(id);
            if (isShown(el) && !el.value.trim()) bad(el, label + ' is required.');
        }
        var r = currentRule();
 
        need('firstName', 'First name');
        need('lastName', 'Last name');
 
        var dob = $('birthDate');
        if (!dob.value) bad(dob, 'Date of birth is required.');
        else { var m = dobMessage(dob.value); if (m) bad(dob, m); }
 
        need('gender', 'Gender');
        need('genderOther', 'Please specify your gender');
        need('religionOther', 'Please specify the religion');
 
        var house = $('houseNo');
        if (house.value && !/^\d+$/.test(house.value)) bad(house, 'House number must contain numbers only.');
 
        need('purok', 'Purok / Sitio');
 
        var mob = $('mobile');
        if (!mob.value.trim()) bad(mob, 'Mobile number is required.');
        else if (!/^09\d{9}$/.test(mob.value.trim())) bad(mob, 'Mobile number must be 11 digits, numbers only, starting with 09 (e.g. 09171234567).');
 
        var vno = $('voterNo');
        if (isShown(vno) && vno.value.trim() && !/^\d+$/.test(vno.value.trim())) bad(vno, 'Voter number must contain numbers only.');
 
        if ($('interestOtherChk').checked) need('interestOther', 'Please specify the other program interest');
 
        var em = $('email');
        if (em.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim())) bad(em, 'Enter a valid email address.');
 
        need('classification', 'Youth classification');
        need('classificationOther', 'Please specify the classification');
 
        need('educationLevel', 'Educational attainment');
        need('educationOther', 'Please specify the educational attainment');
        need('educationStatus', 'Education status');
        need('educationStatusOther', 'Please specify the education status');
 
        if (r && r.school === 'req') need('school', 'School / institution');
        need('schoolOther', 'School / institution name');
        if (r && r.employment === 'req') need('employment', 'Employment status');
        need('employmentOther', 'Please specify the employment status');
        if (r && r.occupation === 'req') need('occupation', 'Occupation / job');
        need('occupationOther', 'Please specify the occupation / job');
        if (r && r.training === 'req') need('training', 'Training / skills program');
        need('trainingOther', 'Please specify the training');
 
        var pwd = document.querySelector('input[name="pwd"]:checked');
        if (!pwd) bad($('pwdGroup'), 'Please select Yes or No.');
        else if (pwd.value === 'Yes') {
            need('disabilityType', 'Disability type');
            need('disabilityOther', 'Please specify the disability');
        }
        if (!document.querySelector('input[name="skVoter"]:checked')) bad($('voterGroup'), 'Please select Yes or No.');

        document.querySelectorAll('.center-item').forEach(function (item) {
            var cb = item.querySelector('.center-interest');
            var sel = item.querySelector('.center-subtype');
            if (!cb || !sel) return;
            if (cb.checked && !sel.value) {
                setError(sel, 'Please select a classification for ' + cb.value + '.');
                errs.push(sel);
            } else if (cb.checked && sel.value === cb.value + ' | Other') {
                var other = item.querySelector('.center-other');
                if (other && !other.value.trim()) {
                    setError(other, 'Please specify the ' + cb.value + ' classification.');
                    errs.push(other);
                }
            }
        });

        if (errs.length) {
            var first = errs[0];
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (first.focus && first.tagName !== 'DIV') first.focus({ preventScroll: true });
            return false;
        }
        return true;
    }
 
    /* =====================================================
       READ / WRITE THE FORM
       ===================================================== */
    function getFormData() {
        var d = {}, r = currentRule();
        INPUT_IDS.forEach(function (k) {
            var el = $(k);
            d[k] = (el && isShown(el)) ? el.value.trim() : '';      // hidden fields are never saved
        });
        if (!isShown($('employment')) && r && r.autoEmployment) d.employment = r.autoEmployment;
        var pwd = document.querySelector('input[name="pwd"]:checked');
        var vot = document.querySelector('input[name="skVoter"]:checked');
        d.pwd = pwd ? pwd.value : '';
        d.skVoter = vot ? vot.value : '';
        d.interests = [];
        document.querySelectorAll('.interest-box:checked').forEach(function (c) {
            if (c.classList.contains('center-interest')) return;
            d.interests.push(c.value);
        });
        document.querySelectorAll('.center-item').forEach(function (item) {
            var cb = item.querySelector('.center-interest');
            var sel = item.querySelector('.center-subtype');
            if (!cb || !cb.checked) return;
            if (!sel || !sel.value) {
                d.interests.push(cb.value);
                return;
            }
            var other = item.querySelector('.center-other');
            d.interests.push(sel.value === cb.value + ' | Other'
                ? sel.value + ': ' + (other ? other.value.trim() : '')
                : sel.value);
        });
        d.photo = photoData;
        return d;
    }
 
    function setPhoto(data) {
        photoData = data || '';
        $('photoPreview').src = photoData;
        show('photoPreview', !!photoData);
        show('photoPlaceholder', !photoData);
        show('removePhotoBtn', !!photoData);
    }
 
    function fillForm(m) {
        $('classification').value = m.classification || '';
        applyRules();
        INPUT_IDS.forEach(function (k) { if (k !== 'classification') $(k).value = m[k] || ''; });
        if (m.salaryIncome && SALARY_INCOME_RANGES.indexOf(m.salaryIncome) === -1) {
            var previousIncome = document.createElement('option');
            previousIncome.value = m.salaryIncome;
            previousIncome.textContent = 'Previously recorded: ₱' + m.salaryIncome;
            $('salaryIncome').appendChild(previousIncome);
            $('salaryIncome').value = m.salaryIncome;
        }
        // older records saved occupation as free text: show it under "Other"
        if (m.occupation && OCCUPATIONS.indexOf(m.occupation) === -1) { $('occupation').value = 'Other'; $('occupationOther').value = m.occupation; }
        // older records may hold spaces / +63 in number fields: show them as digits only
        ['houseNo', 'mobile', 'voterNo'].forEach(function (k) { $(k).value = ($(k).value || '').replace(/\D/g, ''); });
        if (/^639\d{9}$/.test($('mobile').value)) $('mobile').value = '0' + $('mobile').value.slice(2);
        var p = document.querySelector('input[name="pwd"][value="' + m.pwd + '"]');
        var v = document.querySelector('input[name="skVoter"][value="' + m.skVoter + '"]');
        if (p) p.checked = true;
        if (v) v.checked = true;
        document.querySelectorAll('.interest-box').forEach(function (c) {
            c.checked = (m.interests || []).indexOf(c.value) !== -1;
        });
        document.querySelectorAll('.center-item').forEach(function (item) {
            var cb = item.querySelector('.center-interest');
            var sel = item.querySelector('.center-subtype');
            var other = item.querySelector('.center-other');
            if (!cb || !sel) return;
            var center = cb.value;
            var found = null;
            var otherText = '';
            (m.interests || []).forEach(function (value) {
                if (typeof value === 'string' && value.indexOf(center + ' | ') === 0) {
                    var otherPrefix = center + ' | Other: ';
                    if (value.indexOf(otherPrefix) === 0) {
                        found = center + ' | Other';
                        otherText = value.slice(otherPrefix.length);
                    } else {
                        found = value;
                    }
                }
            });
            cb.checked = !!((m.interests || []).indexOf(center) !== -1 || found);
            sel.value = found || '';
            if (other) other.value = otherText;
        });
        setPhoto(m.photo);
        refreshBoxes();
        refreshCenterTypeVisibility();
        applyRules();
        refreshAge();
    }
 
    function resetUI() {
        setPhoto('');
        clearAllErrors();
        $('photo').value = '';
        $('barangay').value = BARANGAY; $('city').value = CITY; $('province').value = PROVINCE;
        applyRules();
        refreshBoxes();
        refreshCenterTypeVisibility();
        refreshAge();
    }
 
    function setEditMode(on, m) {
        var label = $('submitLabel');
        label.textContent = on ? label.dataset.edit : label.dataset.normal;
        show('cancelEditBtn', on);
        show('clearBtn', !on);
        $('formMessage').innerHTML = on
            ? '<div class="alert alert-warning small"><i class="bi bi-pencil-square me-1"></i>Editing <strong>' + esc(fullName(m)) +
              '</strong> (' + esc(m.memberNo || '') + '). Changes will <strong>not</strong> replace the official record right away \u2013 ' +
              'they are sent as an edit request for the SK officer to approve.</div>'
            : '';
    }
 
    function exitEdit() {
        editingId = null;
        $('registrationForm').reset();
        setEditMode(false);
        resetUI();
    }
 
    /* =====================================================
       ACTIONS
       ===================================================== */
    function hasDuplicate(d, exceptId) {
        return members.some(function (m) {
            return m.id !== exceptId &&
                (m.firstName || '').toLowerCase() === d.firstName.toLowerCase() &&
                (m.lastName || '').toLowerCase() === d.lastName.toLowerCase() &&
                m.birthDate === d.birthDate;
        });
    }
 
    // Public viewer: the form is sent to the server as a PENDING registration (no profile picture).
    function submitPublic(d) {
        delete d.photo;
        try {
            var res = DB.register(d);
            $('registrationForm').reset();
            resetUI();
            $('formMessage').innerHTML = '<div class="alert alert-success small"><i class="bi bi-check-circle-fill me-1"></i>' +
                'Thank you! Your registration was submitted' + (res.refNo ? ' (reference <strong>' + esc(res.refNo) + '</strong>)' : '') +
                ' and is waiting for SK officer approval.</div>';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            notify('danger', err.message || 'Could not submit your registration.');
        }
    }

    function registerYouth(d) {
        if (hasDuplicate(d, null)) {
            notify('danger', 'This youth is already registered (same name and date of birth). Check the active and archived lists.');
            return;
        }
        var rec = d;
        rec.id = 'm' + Date.now();
        rec.memberNo = nextMemberNo();
        rec.barangay = BARANGAY; rec.city = CITY; rec.province = PROVINCE;
        rec.status = 'Active';
        rec.createdAt = nowISO();
        rec.history = [{ at: nowISO(), action: 'Registered', by: OFFICER, note: 'Profile created as Active.' }];
        members.push(rec);
        if (!saveAll()) { members.pop(); return; }
        notify('success', fullName(rec) + ' was registered (' + rec.memberNo + ').');
        $('registrationForm').reset();
        resetUI();
        renderAll();
    }
 
    function submitEdit(d) {
        var m = findMember(editingId);
        if (!m || m.status === 'Archived') {
            notify('danger', 'This profile is archived and can no longer be edited.');
            exitEdit(); renderAll();
            return;
        }
        if (pendingFor(m.id)) {
            notify('warning', 'This profile already has an edit request waiting for approval. Approve or reject it first.');
            return;
        }
        if (hasDuplicate(d, m.id)) {
            notify('danger', 'Another youth already has the same name and date of birth.');
            return;
        }
        var changes = {}, previous = {}, n = 0;
        FIELDS.forEach(function (f) {
            var k = f[0];
            var before = (k === 'gender' && !m.gender) ? m.sex : m[k];
            if (norm(before) !== norm(d[k])) {
                changes[k] = d[k];
                previous[k] = before == null ? '' : before;
                n++;
            }
        });
        if (!n) { notify('info', 'No changes were made to the profile.'); return; }
        var rq = {
            id: 'r' + Date.now(), memberId: m.id, memberNo: m.memberNo, memberName: fullName(m),
            requestedAt: nowISO(), requestedBy: OFFICER, status: 'Edit Request',
            previous: previous, changes: changes, reviewedAt: null, reviewedBy: '', remarks: ''
        };
        requests.push(rq);
        m.history.push({ at: nowISO(), action: 'Edit requested', by: OFFICER, note: n + ' field(s) changed, waiting for approval.' });
        if (!saveAll()) { requests.pop(); m.history.pop(); return; }
        notify('success', 'Edit request submitted. The official profile will update once it is approved.');
        exitEdit();
        renderAll();
    }
 
    function startEdit(id) {
        var m = findMember(id);
        if (!m) return;
        if (m.status === 'Archived') { notify('warning', 'Restore this record first before editing it.'); return; }
        if (pendingFor(id)) { notify('warning', 'This profile already has an edit request waiting for approval.'); return; }
        editingId = id;
        $('registrationForm').reset();
        fillForm(m);
        setEditMode(true, m);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
 
    function archiveMember(m, reason, auto) {
        m.status = 'Archived';
        m.archiveReason = reason;
        m.archivedAt = nowISO();
        m.archivedBy = auto ? 'System' : OFFICER;
        m.archiveType = auto ? 'auto' : 'manual';
        m.history.push({ at: nowISO(), action: auto ? 'Auto-archived' : 'Archived', by: m.archivedBy, note: reason });
        requests.forEach(function (r) {                       // a pending edit can no longer be approved
            if (r.memberId === m.id && r.status === 'Edit Request') {
                r.status = 'Rejected'; r.reviewedAt = nowISO(); r.reviewedBy = 'System';
                r.remarks = 'Profile was archived.';
            }
        });
        if (editingId === m.id) exitEdit();
    }
 
    function restoreMember(id) {
        var m = findMember(id);
        if (!m) return;
        var age = m.birthDate ? calcAge(m.birthDate) : NaN;
        if (!isNaN(age) && age > MAX_AGE) {
            notify('danger', 'Cannot restore: this youth is now ' + age + ' years old, above the youth age limit.');
            return;
        }
        AppAlerts.confirm('Restore ' + fullName(m) + ' to the active youth list?', 'Restore archived member?', 'Restore')
            .then(function (confirmed) {
                if (!confirmed) return;
                m.status = 'Active';
                m.restoredAt = nowISO();
                m.archiveReason = ''; m.archivedAt = null; m.archivedBy = ''; m.archiveType = '';
                m.history.push({ at: nowISO(), action: 'Restored', by: OFFICER, note: 'Moved back to the active list.' });
                if (saveAll()) {
                    notify('success', fullName(m) + ' was restored.');
                    hideModal('detailsModal');
                    renderAll();
                }
            });
    }
 
    function openArchive(id) {
        var m = findMember(id);
        if (!m) return;
        archiveTargetId = id;
        $('archiveText').innerHTML = 'You are about to archive <strong>' + esc(fullName(m)) + '</strong> (' + esc(m.memberNo || '') + '). ' +
            'The youth will be removed from the active list.';
        $('archiveReason').value = '';
        $('archiveRemarks').value = '';
        clearError($('archiveReason'));
        hideModal('detailsModal');
        modal('archiveModal').show();
    }
 
    function confirmArchive() {
        var m = findMember(archiveTargetId);
        var sel = $('archiveReason');
        if (!m) return;
        if (!sel.value) { setError(sel, 'Please choose a reason.'); return; }
        var reason = sel.value + ($('archiveRemarks').value.trim() ? ' \u2013 ' + $('archiveRemarks').value.trim() : '');
        archiveMember(m, reason, false);
        if (saveAll()) {
            hideModal('archiveModal');
            notify('success', fullName(m) + ' was archived.');
            renderAll();
        }
    }
 
    // automatic age monitoring: archive anyone above the age limit
    function runAgeCheck() {
        var n = 0;
        members.forEach(function (m) {
            if (m.status !== 'Archived' && m.birthDate && calcAge(m.birthDate) > MAX_AGE) {
                archiveMember(m, AUTO_ARCHIVE_REASON, true);
                n++;
            }
        });
        if (n) {
            saveAll();
            renderAll();
            notify('info', n + ' profile' + (n > 1 ? 's were' : ' was') + ' automatically archived \u2013 exceeded youth age eligibility.');
        }
    }
 
    function reviewRequest(approve) {
        var rq = findRequest(currentRequestId);
        if (!rq || rq.status !== 'Edit Request') return;
        var m = findMember(rq.memberId);
        var remarks = ($('reviewRemarks') ? $('reviewRemarks').value.trim() : '');
        if (approve) {
            if (!m || m.status === 'Archived') { notify('danger', 'This profile is archived, so the changes cannot be applied.'); return; }
            Object.keys(rq.changes).forEach(function (k) { m[k] = rq.changes[k]; });
            m.updatedAt = nowISO();
            m.history.push({ at: nowISO(), action: 'Edit approved', by: OFFICER,
                note: Object.keys(rq.changes).map(function (k) { return LABELS[k]; }).join(', ') });
            rq.status = 'Approved';
        } else {
            rq.status = 'Rejected';
            if (m) m.history.push({ at: nowISO(), action: 'Edit rejected', by: OFFICER, note: remarks || 'Changes were not approved.' });
        }
        rq.reviewedAt = nowISO(); rq.reviewedBy = OFFICER; rq.remarks = remarks;
        if (!saveAll()) return;
        hideModal('reviewModal');
        notify('success', approve ? 'Changes approved and the profile was updated.' : 'Changes rejected. The profile was left unchanged.');
        runAgeCheck();
        renderAll();
    }
 
    /* =====================================================
       RENDER
       ===================================================== */
    function modal(id) { return bootstrap.Modal.getOrCreateInstance($(id)); }
    function hideModal(id) { modal(id).hide(); }
 
    function avatar(m, size) {
        var st = size ? ' style="width:' + size + 'px;height:' + size + 'px;font-size:' + Math.round(size / 3) + 'px;"' : '';
        if (m.photo) return '<img src="' + esc(m.photo) + '" alt="" class="rounded-circle border object-fit-cover thumb"' + st + '>';
        var ini = ((m.firstName || '?').charAt(0) + (m.lastName || '').charAt(0)).toUpperCase();
        return '<span class="avatar thumb"' + st + '>' + esc(ini) + '</span>';
    }
    function statusBadge(m) {
        if (m.status === 'Archived') return '<span class="badge bg-secondary">Archived</span>';
        if (pendingFor(m.id)) return '<span class="badge bg-warning text-dark">Edit Request</span>';
        return '<span class="badge bg-dark">Active</span>';
    }
    function memberCell(m) {
        return '<div class="d-flex align-items-center gap-2">' + avatar(m) +
            '<div><div class="fw-bold">' + esc(fullName(m)) + '</div><small class="text-muted">' + esc(m.memberNo || '') + '</small></div></div>';
    }
    function btn(act, id, label, cls) {
        return '<button type="button" class="btn btn-sm ' + cls + '" data-act="' + act + '" data-id="' + esc(id) + '">' + label + '</button>';
    }
    function byDesc(key) { return function (a, b) { return String(b[key] || '').localeCompare(String(a[key] || '')); }; }
 
    function renderRecent() {
        var active = members.filter(function (m) { return m.status !== 'Archived'; }).sort(byDesc('createdAt'));
        var list = showAll ? active : active.slice(0, 5);
        $('recentCount').textContent = active.length + ' active youth';
        show('toggleAllBtn', active.length > 5);
        $('toggleAllBtn').textContent = showAll ? 'Show Recent' : 'Show All';
        $('recentMembersBody').innerHTML = list.length ? list.map(function (m) {
            return '<tr><td>' + memberCell(m) + '</td><td>' + esc(m.purok || '\u2014') + '</td><td>' + statusBadge(m) + '</td>' +
                '<td class="text-end"><div class="d-flex justify-content-end gap-1 flex-wrap">' +
                btn('view', m.id, 'View Details', 'btn-outline-dark') +
                btn('edit', m.id, 'Edit Profile', 'btn-outline-dark') +
                btn('archive', m.id, 'Archive', 'btn-dark') +
                '</div></td></tr>';
        }).join('') : '<tr><td colspan="4" class="text-center text-muted py-4">No youth registered yet.</td></tr>';
    }
 
    function reqBadge(s) {
        var c = s === 'Approved' ? 'bg-success' : s === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark';
        return '<span class="badge ' + c + '">' + esc(s) + '</span>';
    }
 
    function renderRequests() {
        var list = requests.slice().sort(byDesc('requestedAt'));
        var pending = list.filter(function (r) { return r.status === 'Edit Request'; }).length;
        $('reqCount').textContent = pending + ' pending';
        $('requestsBody').innerHTML = list.length ? list.map(function (r) {
            var m = findMember(r.memberId);
            return '<tr><td><div class="fw-bold">' + esc(m ? fullName(m) : r.memberName) + '</div><small class="text-muted">' + esc(r.memberNo || '') + '</small></td>' +
                '<td>' + fmtDate(r.requestedAt) + '</td><td>' + Object.keys(r.changes).length + '</td><td>' + reqBadge(r.status) + '</td>' +
                '<td class="text-end">' + btn('review', r.id, 'View Details', 'btn-outline-dark') + '</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-center text-muted py-4">No edit requests yet.</td></tr>';
    }
 
    function renderArchive() {
        var list = members.filter(function (m) { return m.status === 'Archived'; }).sort(byDesc('archivedAt'));
        $('archiveCount').textContent = list.length + ' archived';
        $('archiveBody').innerHTML = list.length ? list.map(function (m) {
            return '<tr><td>' + memberCell(m) + '</td><td>' + fmtDate(m.archivedAt) + '</td><td>' + esc(m.archiveReason || '\u2014') + '</td>' +
                '<td class="text-end"><div class="d-flex justify-content-end gap-1 flex-wrap">' +
                btn('view', m.id, 'View Details', 'btn-outline-dark') +
                btn('restore', m.id, 'Restore', 'btn-dark') +
                '</div></td></tr>';
        }).join('') : '<tr><td colspan="4" class="text-center text-muted py-4">No archived records.</td></tr>';
    }
 
    function renderAll() { renderRecent(); renderRequests(); renderArchive(); renderPending(); }

    /* =====================================================
       PUBLIC REGISTRATIONS  (submitted from public-register.html)
       Public users only write to PENDING_KEY. Only an official can approve
       a request, which is the only way it becomes a real member record.
       ===================================================== */
    var PENDING_KEY = 'kkPendingRegistrations';
    var pendingRegs = [];
    var PUB_FIELDS = FIELDS.filter(function (f) { return ['firstName', 'middleName', 'lastName', 'suffix', 'interests', 'photo'].indexOf(f[0]) === -1; });
    function loadPending() {
        try { var v = JSON.parse(DB.getItem(PENDING_KEY)); pendingRegs = Array.isArray(v) ? v : []; }
        catch (e) { pendingRegs = []; }
    }
    function savePending() {
        try { DB.setItem(PENDING_KEY, JSON.stringify(pendingRegs)); return true; }
        catch (e) { notify('danger', 'Could not save. Browser storage may be full.'); return false; }
    }
    function pubBadge(st) {
        var c = st === 'Approved' ? 'success' : st === 'Rejected' ? 'danger' : 'warning text-dark';
        return '<span class="badge bg-' + c + '">' + esc(st) + '</span>';
    }
    function renderPending() {
        loadPending();
        var list = pendingRegs.slice().sort(function (a, b) {
            return (a.status === 'Pending' ? 0 : 1) - (b.status === 'Pending' ? 0 : 1) || String(b.submittedAt || '').localeCompare(String(a.submittedAt || ''));
        });
        $('pubCount').textContent = list.filter(function (r) { return r.status === 'Pending'; }).length + ' pending';
        $('pubBody').innerHTML = list.length ? list.map(function (r) {
            return '<tr><td><div class="fw-bold">' + esc(fullName(r)) + '</div><small class="text-muted">' + esc(r.refNo || '') + '</small></td>' +
                '<td>' + esc(r.purok || '\u2014') + '</td><td>' + fmtDate(r.submittedAt) + '</td><td>' + pubBadge(r.status) + '</td>' +
                '<td class="text-end">' + btn('pubview', r.id, 'Review', 'btn-outline-dark') + '</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-center text-muted py-4">No public registrations yet.</td></tr>';
    }
    function showPending(id) {
        loadPending();
        var r = pendingRegs.filter(function (x) { return x.id === id; })[0];
        if (!r) return;
        var rows = PUB_FIELDS.filter(function (f) { return r[f[0]]; }).map(function (f) {
            var v = f[0] === 'birthDate' ? fmtDate(r.birthDate) + ' (age ' + calcAge(r.birthDate) + ')' : r[f[0]];
            return '<div class="col-md-6"><div class="text-muted" style="font-size:13px;">' + esc(f[1]) + '</div><div class="small fw-bold">' + esc(v) + '</div></div>';
        }).join('');
        $('pubBodyDetail').innerHTML = '<h5 class="fw-bold mb-0">' + esc(fullName(r)) + '</h5><small class="text-muted">' + esc(r.refNo || '') + ' \u00b7 submitted ' + fmtDate(r.submittedAt) + '</small> ' + pubBadge(r.status) +
            '<div class="row g-3 mt-1">' + rows + '</div>' +
            (r.status !== 'Pending' ? '<div class="alert alert-secondary small mt-3 mb-0">' + esc(r.status) + ' on ' + fmtDate(r.reviewedAt) + (r.remarks ? ' \u2013 ' + esc(r.remarks) : '') + '</div>' : '');
        $('pubRemarks').value = '';
        show('pubRemarksBox', r.status === 'Pending');
        $('pubFooter').innerHTML = (r.status === 'Pending'
            ? btn('pubreject', r.id, '<i class="bi bi-x-circle"></i> Reject', 'btn-outline-dark') + btn('pubapprove', r.id, '<i class="bi bi-check-circle"></i> Approve & Register', 'btn-dark')
            : '') + '<button type="button" class="btn-outline" data-bs-dismiss="modal">Close</button>';
        modal('pubModal').show();
    }
    function approvePending(id) {
        loadAll(); loadPending();
        var r = pendingRegs.filter(function (x) { return x.id === id; })[0];
        if (!r || r.status !== 'Pending') { notify('warning', 'This registration was already reviewed.'); renderPending(); return; }
        var dm = dobMessage(r.birthDate);
        if (dm) { notify('danger', dm); return; }
        if (hasDuplicate(r, null)) { notify('danger', 'This youth is already registered (same name and date of birth). Reject this request instead.'); return; }
        var rec = {};
        INPUT_IDS.concat(['pwd', 'skVoter']).forEach(function (k) { rec[k] = r[k] || ''; });
        rec.interests = Array.isArray(r.interests) ? r.interests.slice() : []; rec.photo = '';
        rec.id = 'm' + Date.now();
        rec.memberNo = nextMemberNo();
        rec.barangay = BARANGAY; rec.city = CITY; rec.province = PROVINCE;
        rec.status = 'Active';
        rec.createdAt = nowISO();
        rec.history = [{ at: nowISO(), action: 'Registered', by: OFFICER, note: 'Approved from public registration ' + (r.refNo || '') + '.' }];
        members.push(rec);
        if (!saveAll()) { members.pop(); return; }
        r.status = 'Approved'; r.reviewedAt = nowISO(); r.reviewedBy = OFFICER; r.memberId = rec.id; r.memberNo = rec.memberNo;
        savePending();
        hideModal('pubModal');
        notify('success', fullName(rec) + ' was approved and registered (' + rec.memberNo + ').');
        renderAll(); renderPending();
    }
    function rejectPending(id) {
        var remarks = $('pubRemarks').value.trim();
        if (!remarks) { notify('warning', 'Please write a short reason (remarks) before rejecting.'); $('pubRemarks').focus(); return; }
        loadPending();
        var r = pendingRegs.filter(function (x) { return x.id === id; })[0];
        if (!r || r.status !== 'Pending') { notify('warning', 'This registration was already reviewed.'); renderPending(); return; }
        r.status = 'Rejected'; r.reviewedAt = nowISO(); r.reviewedBy = OFFICER; r.remarks = remarks;
        savePending();
        hideModal('pubModal');
        notify('success', 'Registration rejected.');
        renderPending();
    }

 
    function valueHtml(k, v) {
        if (k === 'photo') return v ? '<img src="' + esc(v) + '" alt="" class="rounded border object-fit-cover" style="width:44px;height:44px;">' : '\u2014';
        if (k === 'birthDate') return esc(fmtDate(v));
        if (Array.isArray(v)) return v.length ? esc(v.join(', ')) : '\u2014';
        return v ? esc(v) : '\u2014';
    }
 
    function showDetails(id) {
        var m = findMember(id);
        if (!m) return;
        var age = m.birthDate ? calcAge(m.birthDate) : '';
        var rows = FIELDS.filter(function (f) {
            var v = m[f[0]];
            return f[0] !== 'photo' && !(v == null || v === '' || (Array.isArray(v) && !v.length));
        }).map(function (f) {
            return '<div class="col-md-6"><div class="text-muted" style="font-size:13px;">' + esc(f[1]) + '</div><div class="small fw-bold">' + valueHtml(f[0], m[f[0]]) + '</div></div>';
        }).join('');
        var pend = pendingFor(id);
        var hist = m.history.slice().reverse().map(function (h) {
            return '<li class="small"><strong>' + esc(h.action) + '</strong> \u2013 ' + fmtDate(h.at) + ' <span class="text-muted">(' + esc(h.by) + ')</span>' +
                (h.note ? '<br><span class="text-muted">' + esc(h.note) + '</span>' : '') + '</li>';
        }).join('');
        $('detailsBody').innerHTML =
            '<div class="d-flex align-items-center gap-3 mb-3">' + avatar(m, 64) +
            '<div><h5 class="mb-0 fw-bold">' + esc(fullName(m)) + '</h5><small class="text-muted">' + esc(m.memberNo || '') + '</small> ' + statusBadge(m) + '</div></div>' +
            (m.status === 'Archived' ? '<div class="alert alert-secondary small">' + esc(m.archiveReason || '') + '</div>' : '') +
            (pend ? '<div class="alert alert-warning small">An edit request is waiting for approval.</div>' : '') +
            '<div class="row g-3 mb-3"><div class="col-md-6"><div class="text-muted" style="font-size:13px;">Age</div><div class="small fw-bold">' + esc(age) + '</div></div>' +
            '<div class="col-md-6"><div class="text-muted" style="font-size:13px;">Barangay</div><div class="small fw-bold">' + esc(m.barangay || BARANGAY) + ', ' + esc(m.city || CITY) + ', ' + esc(m.province || PROVINCE) + '</div></div>' +
            rows + '</div>' +
            '<div class="group-title">Record History</div><ul class="ps-3 mb-0">' + (hist || '<li class="small text-muted">No history.</li>') + '</ul>';
        $('detailsFooter').innerHTML = (m.status === 'Archived'
            ? btn('restore', m.id, '<i class="bi bi-arrow-counterclockwise"></i> Restore', 'btn-dark')
            : btn('edit', m.id, '<i class="bi bi-pencil"></i> Edit Profile', 'btn-outline-dark') +
              btn('archive', m.id, '<i class="bi bi-archive"></i> Archive', 'btn-dark')) +
            '<button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>';
        modal('detailsModal').show();
    }
 
    function showReview(id) {
        var rq = findRequest(id);
        if (!rq) return;
        currentRequestId = id;
        var m = findMember(rq.memberId);
        var body = Object.keys(rq.changes).map(function (k) {
            return '<tr><td class="fw-bold">' + esc(LABELS[k] || k) + '</td><td>' + valueHtml(k, rq.previous[k]) + '</td><td>' + valueHtml(k, rq.changes[k]) + '</td></tr>';
        }).join('');
        var pending = rq.status === 'Edit Request';
        $('reviewBody').innerHTML =
            '<div class="d-flex justify-content-between align-items-start mb-3"><div><h6 class="fw-bold mb-0">' + esc(m ? fullName(m) : rq.memberName) + '</h6>' +
            '<small class="text-muted">' + esc(rq.memberNo || '') + ' \u2022 requested ' + fmtDate(rq.requestedAt) + ' by ' + esc(rq.requestedBy) + '</small></div>' + reqBadge(rq.status) + '</div>' +
            '<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Field</th><th>Current (official)</th><th>Requested</th></tr></thead><tbody>' + body + '</tbody></table></div>' +
            (pending
                ? '<label class="form-label" for="reviewRemarks">Remarks <span class="text-muted fw-normal">(optional)</span></label><textarea id="reviewRemarks" class="form-control" rows="2" style="height:auto;"></textarea>'
                : '<div class="small text-muted">Reviewed ' + fmtDate(rq.reviewedAt) + ' by ' + esc(rq.reviewedBy) + (rq.remarks ? ' \u2013 ' + esc(rq.remarks) : '') + '</div>');
        show('approveBtn', pending);
        show('rejectBtn', pending);
        modal('reviewModal').show();
    }
 
    /* =====================================================
       PROFILE PICTURE (optional, resized so storage stays small)
       ===================================================== */
    $('photo').addEventListener('change', function () {
        var f = this.files[0];
        if (!f) return;
        if (!/^image\//.test(f.type)) { setError(this, 'Please choose an image file.'); this.value = ''; return; }
        var reader = new FileReader();
        reader.onload = function () {
            var img = new Image();
            img.onload = function () {
                var max = 320, s = Math.min(1, max / Math.max(img.width, img.height));
                var c = document.createElement('canvas');
                c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                setPhoto(c.toDataURL('image/jpeg', 0.85));
            };
            img.onerror = function () { setError($('photo'), 'This image could not be read.'); };
            img.src = reader.result;
        };
        reader.readAsDataURL(f);
    });
    $('removePhotoBtn').addEventListener('click', function () { setPhoto(''); $('photo').value = ''; });
 
    /* =====================================================
       EVENTS
       ===================================================== */
    var form = $('registrationForm');
 
    function refreshCenterTypeVisibility() {
        document.querySelectorAll('.center-item').forEach(function (item) {
            var cb = item.querySelector('.center-interest');
            var wrap = item.querySelector('.center-subtype-wrap');
            var sel = item.querySelector('.center-subtype');
            var otherWrap = item.querySelector('.center-other-wrap');
            var other = item.querySelector('.center-other');
            if (!cb || !wrap || !sel) return;
            wrap.classList.toggle('d-none', !cb.checked);
            if (!cb.checked) {
                sel.value = '';
                if (other) other.value = '';
            }
            if (otherWrap) otherWrap.classList.toggle('d-none', !cb.checked || sel.value !== cb.value + ' | Other');
        });
    }

    function handleFieldChange(e) {
        var t = e.target;
        clearError(t);
        var g = t.closest('.check-area');
        if (g) clearError(g);
        if (t.id === 'birthDate') {
            refreshAge();
            if (t.value) { var msg = dobMessage(t.value); if (msg) setError(t, msg); }
        }
        if (t.id === 'classification') applyRules();
        if (t.id === 'occupation' || t.id === 'occupationOther') applyRules();
        if (t.name === 'pwd' || t.name === 'skVoter' || t.id === 'interestOtherChk') refreshBoxes();
        if (t.matches && t.matches('select[data-other]')) refreshOthers();
        if (t.matches && (t.matches('.center-interest') || t.matches('.center-subtype'))) refreshCenterTypeVisibility();
    }
    form.addEventListener('input', handleFieldChange);
    form.addEventListener('change', handleFieldChange);
 
    // number-only fields: letters, spaces and symbols are removed as they are typed or pasted
    ['houseNo', 'mobile', 'voterNo'].forEach(function (id) {
        $(id).addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); });
    });
 
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearAllErrors();
        if (!validate()) return;
        var d = getFormData();
        if (PUBLIC_MODE) submitPublic(d);
        else if (editingId) submitEdit(d); else registerYouth(d);
    });
 
    form.addEventListener('reset', function () { setTimeout(function () { if (!editingId) resetUI(); }, 0); });
    $('cancelEditBtn').addEventListener('click', function () { exitEdit(); });
    $('toggleAllBtn').addEventListener('click', function () { showAll = !showAll; renderRecent(); });
    $('confirmArchiveBtn').addEventListener('click', confirmArchive);
    $('approveBtn').addEventListener('click', function () { reviewRequest(true); });
    $('rejectBtn').addEventListener('click', function () { reviewRequest(false); });
    $('archiveReason').addEventListener('change', function () { clearError(this); });
 
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b) return;
        var id = b.dataset.id;
        if (b.dataset.act === 'view') showDetails(id);
        else if (b.dataset.act === 'edit') { hideModal('detailsModal'); startEdit(id); }
        else if (b.dataset.act === 'archive') openArchive(id);
        else if (b.dataset.act === 'restore') restoreMember(id);
        else if (b.dataset.act === 'review') showReview(id);
        else if (b.dataset.act === 'pubview') showPending(id);
        else if (b.dataset.act === 'pubapprove') approvePending(id);
        else if (b.dataset.act === 'pubreject') rejectPending(id);
    });
 
    // another tab changed the data
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY || e.key === REQUEST_KEY || e.key === PENDING_KEY) { loadAll(); renderAll(); }
    });
 
    /* =====================================================
       START
       ===================================================== */
    function checkboxes(boxId, list) {
        $(boxId).innerHTML = list.map(function (t) {
            return '<label class="check-item"><input type="checkbox" class="interest-box" value="' + esc(t) + '"> ' + esc(t) + '</label>';
        }).join('');
    }
    function renderCenterClassifications() {
        var box = $('centerPrograms');
        box.innerHTML = CENTERS.map(function (center) {
            var options = (CENTER_CLASSIFICATIONS[center] || []).map(function (type) {
                return '<option value="' + esc(center + ' | ' + type) + '">' + esc(type) + '</option>';
            }).join('');
            var otherId = 'centerOther' + CENTERS.indexOf(center);
            return '<div class="center-item">' +
                '<label class="check-item"><input type="checkbox" class="interest-box center-interest" value="' + esc(center) + '"> ' + esc(center) + '</label>' +
                '<div class="center-subtype-wrap mt-2 d-none">' +
                '<select class="form-select center-subtype" data-center="' + esc(center) + '">' +
                '<option value="">Select ' + esc(center) + ' classification</option>' + options +
                '</select>' +
                '<div class="center-other-wrap mt-2 d-none">' +
                '<label class="form-label" for="' + otherId + '">Please specify the ' + esc(center) + ' classification</label>' +
                '<input type="text" id="' + otherId + '" class="form-control center-other" maxlength="80" placeholder="Type your classification">' +
                '</div>' +
                '</div>' +
                '</div>';
        }).join('');
        refreshCenterTypeVisibility();
    }
    renderCenterClassifications();
 
    setOptions($('school'), SCHOOLS.concat(['Others']), 'Select school / institution');
    setOptions($('training'), TRAININGS, 'Select');
    setOptions($('occupation'), OCCUPATIONS, 'Select');
    setOptions($('salaryIncome'), SALARY_INCOME_RANGES, 'Select monthly salary / income');
 
    $('barangay').value = BARANGAY; $('city').value = CITY; $('province').value = PROVINCE;
 
    applyRules();
    refreshBoxes();
    if (!PUBLIC_MODE) {
        loadAll();
        saveAll();                 // keeps older saved records in the new format
        renderAll();
        runAgeCheck();
        // opened from Member List > Edit (registration.php?edit=ID)
        (function () {
            var editId = new URLSearchParams(location.search).get('edit');
            if (editId) startEdit(editId);
        })();
        setInterval(runAgeCheck, 60 * 60 * 1000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { loadAll(); runAgeCheck(); renderAll(); } });
    }
 
})();
</script>
 
</body>
</html>