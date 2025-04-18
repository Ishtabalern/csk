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

<?php if (isset($_GET['msg'])): ?>
    <p style="color: green;"><?= htmlspecialchars($_GET['msg']) ?></p>
<?php endif; ?>


<h2>📁 Client List</h2>

<a href="add.php">➕ Add New Client</a>
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
                    <td>
                        <a href="edit.php?id=<?= $row['id'] ?>">✏️ Edit</a> |
                        <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this client?')">❌ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No clients found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<br>
<a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>
