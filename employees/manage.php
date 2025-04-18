<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../includes/db.php';

// Handle add employee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = 'employee';

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $password, $role, $full_name, $email);
    $stmt->execute();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'employee'");
}

// Fetch employees
$employees = $conn->query("SELECT * FROM users WHERE role = 'employee'");
?>

<h2>Manage Employees</h2>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required />
    <input type="password" name="password" placeholder="Password" required />
    <input type="text" name="full_name" placeholder="Full Name" />
    <input type="email" name="email" placeholder="Email" />
    <button type="submit">Add Employee</button>
</form>

<hr>

<table border="1" cellpadding="8">
    <tr>
        <th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Action</th>
    </tr>
    <?php while ($row = $employees->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this employee?')">Delete</a></td>
    </tr>
    <?php endwhile; ?>
</table>

<a href="../admin_dashboard.php">Back to Dashboard</a>
