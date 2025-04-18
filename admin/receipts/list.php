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

<h2>📁 Receipt List</h2>

<a href="add.php">➕ Add New Receipt</a>
<br><br>

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
                    <td><a href="<?= htmlspecialchars($row['image_path']) ?>" target="_blank">View</a></td>
                    <td>
                        <a href="edit.php?id=<?= $row['id'] ?>">✏️ Edit</a> |
                        <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this receipt?')">❌ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No receipts found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>
