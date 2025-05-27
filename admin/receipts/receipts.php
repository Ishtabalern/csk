<?php
    session_start();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit;
    }

    include '../../includes/db.php';

    $filter_client = $_GET['client_id'] ?? "";
    $filter_employee = $_GET['employee_id'] ?? "";

    // Fetch filter options
    $clients = $conn->query("SELECT id, name FROM clients");
    $employees = $conn->query("SELECT id, full_name FROM users WHERE role = 'employee'");

    // Fetch receipts with filters
    $query = "SELECT r.*, c.name as client_name, u.full_name as employee_name
            FROM receipts r
            JOIN clients c ON r.client_id = c.id
            JOIN users u ON r.uploaded_by = u.id
            WHERE 1";

    $params = [];
    $types = "";

    if ($filter_client) {
        $query .= " AND r.client_id = ?";
        $params[] = $filter_client;
        $types .= "i";
    }
    if ($filter_employee) {
        $query .= " AND r.uploaded_by = ?";
        $params[] = $filter_employee;
        $types .= "i";
    }

    $query .= " ORDER BY r.created_at DESC";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
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
    <link rel="stylesheet" href="../../partials/topbar.css">
    <link rel="stylesheet" href="../../styles/admin_receipts/receipts.css">
</head>
<body>

<div class="topbar-container">
    <div class="header">
        <img src="../../imgs/csk_logo.png" alt="">
        <h1 style="color:#1ABC9C">All Receipts</h1>
    </div>
    
    <div class="btn">
        <a href="../../admin_dashboard.php">← Back to Admin Dashboard</a>
    </div>
</div>

<div class="container">

    <!-- Filters -->
    <form method="GET">
        <label>Client:</label>
        <select name="client_id" onchange="this.form.submit()">
            <option value="">All Clients</option>
            <?php while ($c = $clients->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= ($filter_client == $c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        &nbsp;

        <label>Employee:</label>
        <select name="employee_id" onchange="this.form.submit()">
            <option value="">All Employees</option>
            <?php while ($e = $employees->fetch_assoc()): ?>
                <option value="<?= $e['id'] ?>" <?= ($filter_employee == $e['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['full_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <div class="table-container">
  
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Employee</th>
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
                            <td><?= htmlspecialchars($row['employee_name']) ?></td>
                            <td><?= htmlspecialchars($row['vendor']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>₱<?= number_format($row['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['payment_method']) ?></td>
                            <td><?= $row['receipt_date'] ?></td>
                            <td style="text-align: center; vertical-align: middle;">
                                <?php if ($row['image_path']): ?>
                                    <a href="../<?= $row['image_path'] ?>" target="_blank">
                                        <img src="../<?= $row['image_path'] ?>" width="80" height="80" style="object-fit:cover;" alt="receipt">
                                    </a>
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
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

