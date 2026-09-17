<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="css/design.css">
</head>
<body >
  <nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand">SK Youth Profiling System</span>
    <div>
      <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
      <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </nav>

  <div class="container mt-4">
    <h3>Dashboard</h3>
    <div class="row mt-3">
      <div class="col-md-3 mb-3">
        <div class="card text-center p-3">
          <h5>KK Profiling</h5>
          <a href="members.php" class="btn btn-primary btn-sm mt-2">Manage Members</a>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center p-3">
          <h5>Attendance</h5>
          <a href="attendance.php" class="btn btn-primary btn-sm mt-2">Manage Attendance</a>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center p-3">
          <h5>Programs</h5>
          <a href="programs.php" class="btn btn-primary btn-sm mt-2">Manage Programs</a>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center p-3">
          <h5>Reports</h5>
          <a href="reports.php" class="btn btn-primary btn-sm mt-2">View Reports</a>
        </div>
      </div>
    </div>
  </div>

  <script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>