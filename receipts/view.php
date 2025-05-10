<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

$employee_id = $_SESSION['user_id'];
$filter_client = isset($_GET['client_id']) ? $_GET['client_id'] : "";

// Fetch all clients for the filter dropdown
$clients = $conn->query("SELECT id, name FROM clients");

// Fetch receipts with optional client filter
if ($filter_client) {
    $stmt = $conn->prepare("SELECT r.*, c.name as client_name
                            FROM receipts r
                            JOIN clients c ON r.client_id = c.id
                            WHERE r.uploaded_by = ? AND r.client_id = ?
                            ORDER BY r.created_at DESC");
    $stmt->bind_param("ii", $employee_id, $filter_client);
} else {
    $stmt = $conn->prepare("SELECT r.*, c.name as client_name
                            FROM receipts r
                            JOIN clients c ON r.client_id = c.id
                            WHERE r.uploaded_by = ?
                            ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $employee_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="view.css">
    <link rel="stylesheet" href="../partials/topbar.css">
</head>
<body>
   
    <div class="topbar-container">
        <div class="header">
            <img src="../imgs/csk_logo.png" alt="">
            <h1>My Uploaded Receipts</h1>
        </div>
       
        <div class="btn">
            <a href="add.php">Upload New Receipt</a>
            <a href="../employee_dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
   

    <!-- Filter Form -->
    <div class="filter-container">
        <form class="filter" method="GET">
            <div class="section">
                <div class="input">
                    <label for="client_id">Filter by Client:</label>
                    <select name="client_id" onchange="this.form.submit()">
                        <option value="">All Clients</option>
                        <?php while ($client = $clients->fetch_assoc()): ?>
                            <option value="<?= $client['id'] ?>" <?= ($client['id'] == $filter_client) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>         
            </div>            
        </form>
    </div>
    

    <br>

    <div class="receipts-container">
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Vendor</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['client_name']) ?></td>
                            <td><?= htmlspecialchars($row['vendor']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>₱<?= number_format($row['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['payment_method']) ?></td>
                            <td><?= $row['receipt_date'] ?></td>
                            <td style="text-align: center; vertical-align: middle;">
                                <?php if ($row['image_path']): ?>
                                    <a href="<?= $row['image_path'] ?>" target="_blank">
                                        <img src="<?= $row['image_path'] ?>" width="80" height="80" style="object-fit:cover;" alt="receipt">
                                    </a>
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No receipts found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

 
  

</body>
</html>
