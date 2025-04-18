<?php
session_start();
include 'includes/db.php'; // your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check user
    $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: employee_dashboard.php");
            }
            exit;
        }
    }

    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="styles/login.css">
</head>
<body>
<!-- Simple login form -->
<div class="center-container">
    <div class="login-form">
        <form method="POST" class="form">
            <img src="imgs/csk_logo.png" alt="" class="logo">
            <div class="title">Welcome,<br><span>please sign in to continue</span></div>
            <input type="text" class="input" name="username" placeholder="Username" required />
            <input type="password" class="input" name="password" placeholder="Password" required />
            <button type="submit" class="button-confirm">Login</button>
            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        </form>
    </div>
</div>
</body>
</html>