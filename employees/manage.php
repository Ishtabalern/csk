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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../styles/employee/manage_employee.css">
        <link rel="stylesheet" href="../partials/topbar.css">
</head>
<body>

    <div class="topbar-container">
        <div class="header">
            <img src="../../imgs/csk_logo.png" alt="">
            <h1 style="color: #0B440F">Manage Employees</h1>
        </div>
       
        <div class="btn">
            <a href="../admin_dashboard.php">Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        
        <div class="manage-container">
            <form class="manage-employee" method="POST">
                <div class="section">

                    <div class="input">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Username" required />
                    </div>

                    <div class="input">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Password" required />
                    </div>

                    <div class="input">
                        <label>Full name</label>
                        <input type="text" name="full_name" placeholder="Full Name" />
                    </div>

                    <div class="input">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Email" />
                    </div>

                </div>
                        
                <button type="submit">Add Employee</button>
            </form>
        </div>
        
        <hr style="margin: 40px;">
    
        <div class="manageTable-container">
            <table border="1" cellpadding="8">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Action</th>
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
        </div>
    
    </div>



</body>
</html>



