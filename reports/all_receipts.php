<?php
session_start();
require_once '../includes/db.php';

// Optional: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch filter options
$clients = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");
$vendors = $conn->query("SELECT DISTINCT vendor FROM receipts");
$categories = $conn->query("SELECT DISTINCT category FROM receipts");
$payment_methods = $conn->query("SELECT DISTINCT payment_method FROM receipts");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Receipts Report</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../partials/sidebar.css">
    <style>
        body { font-family: Arial; padding: 20px; }
        h2 { margin-bottom: 20px; }
        select, input[type="date"] { padding: 5px; margin-right: 10px; }
        table { width: 100%; margin-top: 20px; }
        .main {margin-left: 250px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        }
    </style>
</head>
<body>
    <?php
     $page = 'all_receipts';
     include '../partials/sidebar.php'; ?>
    <?php
    $dashboard_link = ($_SESSION['role'] === 'admin') ? '../admin_dashboard.php' : '../employee_dashboard.php';
    ?>

<div class="main">
    <h2 style="color: #0B440F">📄 All Receipts Report</h2>
    <a href="<?= $dashboard_link ?>" style="text-decoration:none; background:#007bff; color:white; padding:8px 12px; border-radius:5px;">
        ⬅️ Back to Dashboard
    </a>
    <br><br>
    <form method="GET">
        <label>Client:</label>
        <select name="client_id">
            <option value="">All</option>
            <?php while ($row = $clients->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($_GET['client_id'] ?? '') == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Vendor:</label>
        <select name="vendor">
            <option value="">All</option>
            <?php while ($row = $vendors->fetch_assoc()): ?>
                <option value="<?= $row['vendor'] ?>" <?= ($_GET['vendor'] ?? '') == $row['vendor'] ? 'selected' : '' ?>><?= $row['vendor'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Category:</label>
        <select name="category">
            <option value="">All</option>
            <?php while ($row = $categories->fetch_assoc()): ?>
                <option value="<?= $row['category'] ?>" <?= ($_GET['category'] ?? '') == $row['category'] ? 'selected' : '' ?>><?= $row['category'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Payment:</label>
        <select name="payment_method">
            <option value="">All</option>
            <?php while ($row = $payment_methods->fetch_assoc()): ?>
                <option value="<?= $row['payment_method'] ?>" <?= ($_GET['payment_method'] ?? '') == $row['payment_method'] ? 'selected' : '' ?>><?= $row['payment_method'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>From:</label>
        <input type="date" name="from_date" value="<?= $_GET['from_date'] ?? '' ?>">

        <label>To:</label>
        <input type="date" name="to_date" value="<?= $_GET['to_date'] ?? '' ?>">

        <button type="submit">🔍 Filter</button>
    </form>

    <br>

    <div id="all_receipts_table">
        <table id="receiptTable" class="display">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Vendor</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Uploaded By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build the WHERE clause
                $where = [];
                if (!empty($_GET['client_id'])) {
                    $client_id = intval($_GET['client_id']);
                    $where[] = "r.client_id = $client_id";
                }
                if (!empty($_GET['vendor'])) {
                    $vendor = $conn->real_escape_string($_GET['vendor']);
                    $where[] = "r.vendor = '$vendor'";
                }
                if (!empty($_GET['category'])) {
                    $category = $conn->real_escape_string($_GET['category']);
                    $where[] = "r.category = '$category'";
                }
                if (!empty($_GET['payment_method'])) {
                    $pm = $conn->real_escape_string($_GET['payment_method']);
                    $where[] = "r.payment_method = '$pm'";
                }
                if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
                    $from = $_GET['from_date'];
                    $to = $_GET['to_date'];
                    $where[] = "r.receipt_date BETWEEN '$from' AND '$to'";
                }

                $filterQuery = "SELECT r.*, c.name AS client_name, u.username 
                                FROM receipts r
                                JOIN clients c ON r.client_id = c.id
                                LEFT JOIN users u ON r.uploaded_by = u.id";

                if ($where) {
                    $filterQuery .= " WHERE " . implode(" AND ", $where);
                }

                $filterQuery .= " ORDER BY r.receipt_date DESC";
                $result = $conn->query($filterQuery);

                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $row['receipt_date'] ?></td>
                        <td><?= $row['client_name'] ?></td>
                        <td><?= $row['vendor'] ?></td>
                        <td><?= $row['category'] ?></td>
                        <td>₱<?= number_format($row['amount'], 2) ?></td>
                        <td><?= $row['payment_method'] ?></td>
                        <td><?= $row['username'] ?? 'N/A' ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <br>
</div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#receiptTable').DataTable();
        });
    </script>
</body>
</html>
