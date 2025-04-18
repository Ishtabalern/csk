<?php
include 'includes/db.php';

$username = "admin";
$password = "admin123"; // change this later
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = "admin";
$full_name = "Main Admin";
$email = "admin@example.com";

// Check if already exists
$check = $conn->prepare("SELECT * FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "Admin user already exists.";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashedPassword, $role, $full_name, $email);

    if ($stmt->execute()) {
        echo "Admin user created successfully.";
    } else {
        echo "Error creating admin user.";
    }
}
?>
