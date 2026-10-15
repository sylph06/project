<?php
require_once __DIR__ . '/config.php';
$user = current_user();
if (!$user) {
    header('Location: login.php');
    exit;
}
if (empty($user['must_change_password'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password | KK Youth Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:520px">
    <section class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold">Change your temporary password</h1>
            <p class="text-muted">Choose a new password to continue to the system.</p>
            <div id="message" class="alert d-none" role="alert"></div>
            <form id="passwordForm">
                <label class="form-label" for="current">Temporary or current password</label>
                <input class="form-control mb-3" id="current" type="password" autocomplete="current-password" required>
                <label class="form-label" for="newPassword">New password</label>
                <input class="form-control mb-3" id="newPassword" type="password" minlength="8" maxlength="100" autocomplete="new-password" required>
                <label class="form-label" for="confirmPassword">Confirm new password</label>
                <input class="form-control mb-3" id="confirmPassword" type="password" minlength="8" maxlength="100" autocomplete="new-password" required>
                <button class="btn btn-primary w-100" type="submit">Save password</button>
            </form>
        </div>
    </section>
</main>
<script>
(function () {
    var form = document.getElementById('passwordForm');
    var message = document.getElementById('message');
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var current = document.getElementById('current').value;
        var next = document.getElementById('newPassword').value;
        if (next.length < 8 || next !== document.getElementById('confirmPassword').value) {
            message.className = 'alert alert-danger';
            message.textContent = 'Use at least 8 characters and enter the same new password twice.';
            return;
        }
        fetch('api.php?action=password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ current: current, new: next })
        }).then(function (response) { return response.json(); }).then(function (result) {
            if (!result.ok) throw new Error(result.error || 'Could not update your password.');
            message.className = 'alert alert-success';
            message.textContent = 'Password changed. Opening the dashboard…';
            setTimeout(function () { location.href = 'dashboard.php'; }, 700);
        }).catch(function (error) {
            message.className = 'alert alert-danger';
            message.textContent = error.message;
        });
    });
})();
</script>
</body>
</html>
