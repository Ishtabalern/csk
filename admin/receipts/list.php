<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../includes/db.php';

// Get all receipts
$result = $conn->query("SELECT * FROM receipts ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt List</title>
    <link rel="stylesheet" href="../../partials/topbar.css">
    <link rel="stylesheet" href="../../styles/admin_receipts/list.css">
    <style>
        
    </style>
</head>
<body>
    <div class="topbar-container">
        <div class="header">
            <img src="../../imgs/csk_logo.png" alt="">
            <h1 style="color: #0B440F">Receipt List</h1>
        </div>
       
        <div class="btn">
            <a href="../../admin_dashboard.php">Back to Admin Dashboard</a>
        </div>
    </div>

    <div class="container">
        <br>
        <a class="add-client" href="add.php">➕ Add New Receipt</a>
        <br><br>

        <div class="table-container">
     
            <table border="1" cellpadding="8" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Receipt Date</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Receipt Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['client_id']) ?></td>
                                <td><?= htmlspecialchars($row['receipt_date']) ?></td>
                                <td><?= htmlspecialchars($row['vendor']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['amount']) ?></td>
                                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                                <td><a href="../<?= htmlspecialchars($row['image_path']) ?>" target="_blank">View</a></td>
                                <td style="display: flex; align-items: center; justify-content: space-evenly;">
                                    <a href="edit.php?id=<?= $row['id'] ?>" style="background-color: #00AF7E; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;">✏️ Edit</a> |
                                    <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this client?')" style="background-color: rgb(169, 40, 1); color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px;">❌ Delete</a>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No receipts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>

    </div>
    
</body>
</html>

