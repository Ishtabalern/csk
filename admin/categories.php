<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Handle Add Category form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $debit_account_id = $_POST['debit_account_id'];
    $credit_account_id = $_POST['credit_account_id'];

    $stmt = $conn->prepare("INSERT INTO categories (client_id, name, type, debit_account_id, credit_account_id, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issii", $client_id, $name, $type, $debit_account_id, $credit_account_id);
    $stmt->execute();
}

// Fetch categories with account names
$categories = $conn->query("
    SELECT c.id, c.name, c.type, 
           da.name AS debit_account, ca.name AS credit_account, cl.name AS client
    FROM categories c
    LEFT JOIN accounts da ON c.debit_account_id = da.id
    LEFT JOIN accounts ca ON c.credit_account_id = ca.id
    LEFT JOIN clients cl ON c.client_id = cl.id
    ORDER BY c.created_at DESC
");

// Fetch accounts
$accounts = $conn->query("SELECT id, name FROM accounts ORDER BY name ASC");

// Fetch clients
$clients = $conn->query("SELECT id, name FROM clients");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../partials/topbar.css">
    <style>
          * {
            margin: 0;
            padding: 0;
            list-style: none;
            text-decoration: none;
            box-sizing: border-box;
            scroll-behavior: smooth;
            font-family: Arial, sans-serif;
        }
        .container{padding: 20px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f0f0f0; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; padding: 20px; margin: 100px auto; width: 500px; border-radius: 5px; }
        .modal.active { display: block; }
        button { padding: 10px 20px; }
    </style>
</head>
<body>

    <div class="topbar-container">
        <div class="header">
            <img src="../../imgs/csk_logo.png" alt="">
            <h1 style="color:#1ABC9C">Category Management</h1>
        </div>
       
        <div class="btn">
            <a href="../admin_dashboard.php">← Back to Admin Dashboard</a>
        </div>
    </div>

    <div class="container">

        <button onclick="document.getElementById('addModal').classList.add('active')">Add Category</button>

        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Category Name</th>
                    <th>Type</th>
                    <th>Debit Account</th>
                    <th>Credit Account</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['client']) ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= ucfirst($cat['type']) ?></td>
                        <td><?= htmlspecialchars($cat['debit_account']) ?></td>
                        <td><?= htmlspecialchars($cat['credit_account']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Add Modal -->
        <div class="modal" id="addModal">
            <div class="modal-content">
                <h2>Add New Category</h2>
                <form method="POST">
                    <label>Client:</label>
                    <select name="client_id" required>
                        <option value="">Select Client</option>
                        <?php $clients->data_seek(0); while ($c = $clients->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select><br><br>

                    <label>Category Name:</label><br>
                    <input type="text" name="name" required><br><br>

                    <label>Type:</label><br>
                    <select name="type" required>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                        <option value="withdrawal">Withdrawal</option>
                    </select><br><br>

                    <label>Debit Account:</label><br>
                    <select name="debit_account_id" required>
                        <option value="">Select Debit Account</option>
                        <?php $accounts->data_seek(0); while ($a = $accounts->fetch_assoc()): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                        <?php endwhile; ?>
                    </select><br><br>

                    <label>Credit Account:</label><br>
                    <select name="credit_account_id" required>
                        <option value="">Select Credit Account</option>
                        <?php $accounts->data_seek(0); while ($a = $accounts->fetch_assoc()): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                        <?php endwhile; ?>
                    </select><br><br>

                    <button type="submit">Save</button>
                    <button type="button" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
                </form>
            </div>
        </div>

    </div>
</body>
</html>
