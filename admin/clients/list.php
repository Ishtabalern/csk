<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

// Fetch all clients
$result = $conn->query("SELECT * FROM clients ORDER BY created_at DESC");
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        select, input[type="date"], button { margin: 5px; padding: 5px 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        td.left { text-align: left; }
        .add-client{ 
            padding: 15px;
            border-radius: 6px;
            background-color: #00AF7E;
            color: #FFF;
            font-weight: bold;
            cursor: pointer;
            align-self: center;
            text-decoration:none;
        }
    </style>
</head>
<body>

    <?php if (isset($_GET['msg'])): ?>
        <p style="color: green;"><?= htmlspecialchars($_GET['msg']) ?></p>
    <?php endif; ?>

    <h1 style="color:#1ABC9C">📁 Client List</h1>

    <a class="add-client" href="add.php">➕ Add New Client</a>
    <br><br>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['contact_person']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                        <td style="display: flex; align-items: center; justify-content: space-evenly;">
                            <a href="edit.php?id=<?= $row['id'] ?>" style="background-color: #00AF7E; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;">✏️ Edit</a> |
                            <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this client?')" style="background-color: rgb(169, 40, 1); color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;">❌ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No clients found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    <a href="../../admin_dashboard.php" style="text-decoration:none; background:#007bff; color:white; padding:8px 12px; border-radius:5px;">⬅️ Back to Admin Dashboard</a>


</body>
</html>
