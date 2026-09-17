<?php
require "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $full_name, $email, $password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error = "Registration failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.css">
  <link rel="stylesheet" href="css/design.css">
</head>
<body class="bg-success">
  <div class="container mt-5">
    <h2>Register</h2>
    <?php if (!empty($error)) echo "<p class='text-danger'>$error</p>"; ?>
    <form method="POST">
      <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Register</button>
        <a href="index.php" class="btn btn-outline-secondary">Back to Login</a>
      </div>
    </form>
  </div>
  <script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>