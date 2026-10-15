<?php require_once __DIR__ . '/config.php'; if (current_user()) { header('Location: dashboard.php'); exit; } ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KK Youth Profiling System | Official Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        /* Uses only Bootstrap's own colours, same as the rest of the system */
        html{ font-size:17px; }

        body{
            min-height:100vh;
            background:var(--bs-tertiary-bg);
            color:var(--bs-body-color);
        }

        .top-strip{
            height:6px;
            background:var(--bs-primary);
        }

        .login-shell{
            min-height:calc(100vh - 6px);
        }

        /* ---------- LEFT: logo panel ---------- */
        .brand-side{
            position:relative;
            overflow:hidden;
            color:#fff;
            background:linear-gradient(145deg, var(--bs-primary) 0%, var(--bs-primary-text-emphasis) 100%);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:48px;
        }

        .brand-side::before,
        .brand-side::after{
            content:"";
            position:absolute;
            border-radius:50%;
            background:rgba(255,255,255,.08);
        }
        .brand-side::before{ width:420px; height:420px; top:-140px; right:-140px; }
        .brand-side::after{ width:300px; height:300px; bottom:-110px; left:-90px; }

        .brand-inner{
            position:relative;
            z-index:1;
            max-width:440px;
            text-align:center;
        }

        .logo-big{
            width:230px;
            height:230px;
            object-fit:cover;
            border-radius:32px;
            border:4px solid rgba(255,255,255,.55);
            box-shadow:0 22px 50px rgba(0,0,0,.35);
            animation:floaty 5s ease-in-out infinite;
            transition:transform .3s ease;
            cursor:pointer;
        }
        .logo-big:hover{ transform:scale(1.05) rotate(-2deg); }

        @keyframes floaty{
            0%,100%{ translate:0 0; }
            50%{ translate:0 -10px; }
        }

        .brand-inner h2{ font-weight:800; margin-top:28px; }
        .brand-inner p{ opacity:.9; }

        .feature{
            display:flex;
            align-items:center;
            gap:12px;
            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.2);
            border-radius:14px;
            padding:10px 14px;
            text-align:left;
            font-size:.9rem;
            margin-top:10px;
        }
        .feature i{ font-size:1.2rem; }

        /* ---------- RIGHT: form ---------- */
        .form-side{
            display:flex;
            align-items:center;
            justify-content:center;
            padding:32px 20px;
        }

        .login-card{
            width:100%;
            max-width:430px;
            background:#fff;
            border:1px solid var(--bs-border-color);
            border-radius:24px;
            box-shadow:0 14px 34px rgba(20,20,20,.07);
            padding:34px 30px;
        }

        .logo-small{
            width:84px;
            height:84px;
            object-fit:cover;
            border-radius:22px;
            box-shadow:0 8px 20px rgba(20,20,20,.18);
        }

        .login-card .form-control{
            border-color:var(--bs-border-color);
            padding:.7rem .9rem;
        }
        .login-card .input-group-text{
            background:#fff;
            border-color:var(--bs-border-color);
            color:var(--bs-secondary-color);
        }
        .login-card .form-control:focus{
            border-color:var(--bs-primary);
            box-shadow:0 0 0 3px rgba(var(--bs-primary-rgb), .25);
        }
        .input-group:focus-within .input-group-text{
            border-color:var(--bs-primary);
            color:var(--bs-primary);
        }
        .input-group .form-control{ border-left:0; }
        .input-group .toggle-pass{
            border:1px solid var(--bs-border-color);
            border-left:0;
            background:#fff;
            color:var(--bs-secondary-color);
        }
        .input-group .toggle-pass:hover{ color:var(--bs-primary); }

        .btn-login{
            background:var(--bs-primary);
            border-color:var(--bs-primary);
            color:#fff;
            font-weight:700;
            padding:.75rem 1rem;
            border-radius:14px;
            transition:.2s ease;
        }
        .btn-login:hover,
        .btn-login:focus{
            background:var(--bs-primary-text-emphasis);
            border-color:var(--bs-primary-text-emphasis);
            color:#fff;
            transform:translateY(-1px);
        }
        .btn-login:disabled{
            background:var(--bs-primary);
            border-color:var(--bs-primary);
            color:#fff;
            opacity:.75;
        }

        .shake{ animation:shake .45s; }
        @keyframes shake{
            0%,100%{ transform:translateX(0); }
            20%,60%{ transform:translateX(-9px); }
            40%,80%{ transform:translateX(9px); }
        }


        .link-soft{
            color:var(--bs-primary);
            text-decoration:none;
            font-weight:600;
        }
        .link-soft:hover{ text-decoration:underline; }
    </style>
</head>
<body>

<div class="top-strip"></div>

<div class="container-fluid p-0">
    <div class="row g-0 login-shell">

        <!-- ================= LEFT: LOGO PANEL ================= -->
        <div class="col-lg-6 brand-side d-none d-lg-flex">
            <div class="brand-inner">
                <img src="images/sk-logo.jpg" alt="SK Barangay Sikat logo" class="logo-big" id="bigLogo" title="Click me!">

                <h2>Sangguniang Kabataan</h2>
                <p class="mb-4">Barangay Sikat &bull; Alfonso, Cavite<br>KK Youth Profiling System</p>

                <div class="feature"><i class="bi bi-people-fill"></i> Manage KK youth records and registration</div>
                <div class="feature"><i class="bi bi-megaphone-fill"></i> Post announcements and upcoming events</div>
                <div class="feature"><i class="bi bi-graph-up-arrow"></i> Track participation and print reports</div>
            </div>
        </div>

        <!-- ================= RIGHT: LOGIN FORM ================= -->
        <div class="col-lg-6 form-side">
            <div class="login-card" id="loginCard">

                <div class="text-center mb-4">
                    <img src="images/sk-logo.jpg" alt="SK Barangay Sikat logo" class="logo-small mb-3">
                    <h1 class="h4 fw-bold mb-1">Official Login</h1>
                    <div class="text-muted small">Sign in to the SK Barangay Sikat dashboard</div>
                </div>

                <div class="alert alert-danger d-none py-2 small" id="loginError" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="loginErrorText"></span>
                </div>

                <div class="alert alert-success d-none py-2 small" id="loginOk" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <span id="loginOkText"></span>
                </div>

                <form id="loginForm" novalidate>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold small">Username</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="username" placeholder="Enter your username"
                                   autocomplete="username" required>
                            <div class="invalid-feedback">Please enter your username.</div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label fw-semibold small">Password</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password" placeholder="Enter your password"
                                   autocomplete="current-password" required>
                            <button type="button" class="btn toggle-pass" id="togglePass" aria-label="Show or hide password">
                                <i class="bi bi-eye-fill" id="togglePassIcon"></i>
                            </button>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                        <div class="text-warning-emphasis small mt-1 d-none" id="capsWarn">
                            <i class="bi bi-capslock-fill me-1"></i>Caps Lock is on
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label small" for="rememberMe">Remember me</label>
                        </div>
                        <a href="#" class="link-soft small" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-login w-100" id="loginBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="loginSpinner" aria-hidden="true"></span>
                        <span id="loginBtnText"><i class="bi bi-box-arrow-in-right me-2"></i>Login</span>
                    </button>
                </form>

                <div class="d-flex align-items-center gap-2 my-4 text-secondary small">
                    <hr class="flex-grow-1 m-0"><span>or</span><hr class="flex-grow-1 m-0">
                </div>

                <a href="public.php" class="btn btn-outline-primary w-100" id="publicBtn" style="padding:.7rem .9rem;">
                    <i class="bi bi-globe2 me-2"></i>View as Public
                </a>
                <div class="text-center text-secondary small mt-2">
                    No password needed. Public view is limited to SK Officials and Announcements.
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ================= FORGOT PASSWORD ================= -->
<div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="forgotTitle"><i class="bi bi-key-fill me-2"></i>Forgot password?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Please contact the <b>SK Chairman</b> or the <b>SK Secretary</b> at the Barangay Hall to reset your password.</p>
                <p class="text-muted small mb-0">For security, passwords can only be reset by an SK officer.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-login" data-bs-dismiss="modal">Okay</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* =========================================================
   LOGIN PAGE
   - officer accounts live in the MySQL "users" table
   - the password is checked by api.php (PHP session)
   - after a good login the page opens dashboard.php
   - only the "Remember me" username stays in the browser ("skRememberUser")
   ========================================================= */
(function () {
    "use strict";

    var NEXT_PAGE = "dashboard.php";
    var MAX_TRIES = 5;          // wrong tries before a short lock (the SERVER enforces this: api.php)
    var LOCK_SECONDS = 20;

    var $ = function (id) { return document.getElementById(id); };
    var tries = 0;
    var locked = false;

    /* remembered username */
    try {
        var saved = localStorage.getItem("skRememberUser");
        if (saved) { $("username").value = saved; $("rememberMe").checked = true; $("password").focus(); }
        else { $("username").focus(); }
    } catch (e) { $("username").focus(); }

    /* ---------- show / hide password ---------- */
    $("togglePass").addEventListener("click", function () {
        var p = $("password");
        var show = p.type === "password";
        p.type = show ? "text" : "password";
        $("togglePassIcon").className = show ? "bi bi-eye-slash-fill" : "bi bi-eye-fill";
        p.focus();
    });

    /* ---------- caps lock warning ---------- */
    function checkCaps(e) {
        if (!e.getModifierState) return;
        $("capsWarn").classList.toggle("d-none", !e.getModifierState("CapsLock"));
    }
    $("password").addEventListener("keydown", checkCaps);
    $("password").addEventListener("keyup", checkCaps);
    $("password").addEventListener("blur", function () { $("capsWarn").classList.add("d-none"); });

    /* ---------- clicking the logo = little spin ---------- */
    $("bigLogo").addEventListener("click", function () {
        var img = this;
        img.animate([{ transform: "rotate(0deg)" }, { transform: "rotate(360deg)" }], { duration: 700, easing: "ease-in-out" });
    });

    /* ---------- messages ---------- */
    function hideMessages() {
        $("loginError").classList.add("d-none");
        $("loginOk").classList.add("d-none");
    }
    function showError(text) {
        $("loginOk").classList.add("d-none");
        $("loginErrorText").textContent = text;
        $("loginError").classList.remove("d-none");
        var card = $("loginCard");
        card.classList.remove("shake");
        void card.offsetWidth;          // restart the animation
        card.classList.add("shake");
    }

    ["username", "password"].forEach(function (id) {
        $(id).addEventListener("input", function () { this.classList.remove("is-invalid"); if (!locked) hideMessages(); });
    });

    /* ---------- lock for a few seconds after too many wrong tries ---------- */
    function lockLogin(secs) {
        locked = true;
        $("loginBtn").disabled = true;
        var left = Math.max(1, parseInt(secs, 10) || LOCK_SECONDS);
        showError("Too many wrong tries. Please wait " + left + " seconds.");
        var timer = setInterval(function () {
            left--;
            if (left <= 0) {
                clearInterval(timer);
                locked = false;
                tries = 0;
                $("loginBtn").disabled = false;
                hideMessages();
            } else {
                $("loginErrorText").textContent = "Too many wrong tries. Please wait " + left + " seconds.";
            }
        }, 1000);
    }

    function setLoading(on) {
        $("loginSpinner").classList.toggle("d-none", !on);
        $("loginBtnText").textContent = on ? "Signing in..." : "Login";
        if (!on) $("loginBtnText").innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Login';
        $("loginBtn").disabled = on;
    }

    /* ---------- check the login on the server (api.php) ---------- */
    function postLogin(user, pass, done) {
        fetch("api.php?action=login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username: user, password: pass })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) { done(res && res.ok ? res.user : null, res || {}); })
        .catch(function () { done(null, { error: "Could not reach the server. Please try again." }); });
    }

    /* ---------- submit ---------- */
    $("loginForm").addEventListener("submit", function (e) {
        e.preventDefault();
        if (locked) return;
        hideMessages();

        var user = $("username").value.trim();
        var pass = $("password").value;

        $("username").classList.toggle("is-invalid", user === "");
        $("password").classList.toggle("is-invalid", pass === "");
        if (user === "" || pass === "") {
            showError("Please fill in your username and password.");
            return;
        }

        setLoading(true);

        postLogin(user, pass, function (found, res) {

            if (!found) {
                setLoading(false);
                $("password").value = "";
                if (res.retryAfter) { lockLogin(res.retryAfter); return; }       // locked by the server
                if (!res.error || res.triesLeft === undefined) {
                    $("password").focus();
                    showError(res.error || "Wrong username or password.");
                    return;
                }
                $("password").focus();
                showError("Wrong username or password. " + res.triesLeft + (res.triesLeft === 1 ? " try left." : " tries left."));
                return;
            }

            try {
                if ($("rememberMe").checked) localStorage.setItem("skRememberUser", found.username);
                else localStorage.removeItem("skRememberUser");
            } catch (err) {}

            $("loginOkText").textContent = "Welcome, " + found.name + "! Opening the dashboard...";
            $("loginOk").classList.remove("d-none");
            $("loginBtnText").innerHTML = '<i class="bi bi-check-lg me-2"></i>Success';
            setTimeout(function () {
                location.href = found.must_change_password ? "change-password.php" : NEXT_PAGE;
            }, 900);
        });
    });
})();
</script>

</body>
</html>