<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

$stmt = $conn->prepare("SELECT * FROM scanned_receipts ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scanned Receipts</title>
    <link rel="stylesheet" href="view.css">
    <link rel="stylesheet" href="../partials/topbar.css">
</head>
<body>

<div class="topbar-container">
    <div class="header">
        <img src="../imgs/csk_logo.png" alt="">
        <h1>Scanned Receipts</h1>
    </div>
    <div class="btn">
        <a href="scanned_upload.php">Upload Scanned Receipt</a>
        <a href="../employee_dashboard.php">← Back to Dashboard</a>
    </div>
</div>

<br>

<div class="receipts-container">
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Vendor</th>
                <th>Category</th>
                <th>Total</th>
                <th>Method</th>
                <th>Date</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['vendor']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td>₱<?= number_format($row['amount'], 2) ?></td>
                        <td>Cash</td>
                        <td><?= $row['receipt_date'] ?></td>
                        <td style="text-align: center;">
                            <?php if ($row['image_path']): ?>
                                <a href="<?= $row['image_path'] ?>" target="_blank">
                                    <img src="<?= $row['image_path'] ?>" width="80" height="80" style="object-fit:cover;">
                                </a>
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                            <a href="scanned_edit.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No scanned receipts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
