<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

// Handle adding new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $client_id = $_POST['client_id'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $debit_account_id = $_POST['debit_account_id'];
    $credit_account_id = $_POST['credit_account_id'];

    $stmt = $conn->prepare("INSERT INTO categories (client_id, name, type, debit_account_id, credit_account_id, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issii", $client_id, $name, $type, $debit_account_id, $credit_account_id);
    $stmt->execute();
    
}
if (isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $debit_account_id = $_POST['debit_account_id'];
    $credit_account_id = $_POST['credit_account_id'];

    $update_stmt = $conn->prepare("UPDATE categories SET name = ?, type = ?, debit_account_id = ?, credit_account_id = ? WHERE id = ?");
    $update_stmt->bind_param("ssiii", $name, $type, $debit_account_id, $credit_account_id, $id);
    
    if ($update_stmt->execute()) {
        echo "<p>Category updated successfully.</p>";
        echo "<script>window.location='manage_categories.php';</script>";
        exit;
    } else {
        echo "<p>Error updating category: " . $update_stmt->error . "</p>";
    }
}

// Fetch all categories
$categories = $conn->query("SELECT c.*, cl.name AS client_name FROM categories c JOIN clients cl ON c.client_id = cl.id");

// Fetch accounts for dropdown
$accounts = $conn->query("SELECT id, name FROM accounts");
$accounts_arr = [];
while ($row = $accounts->fetch_assoc()) {
    $accounts_arr[$row['id']] = $row['name'];
}

// Fetch clients
$clients = $conn->query("SELECT id, name FROM clients");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Management</title>
    <style>
        table, th, td { border: 1px solid #ccc; border-collapse: collapse; padding: 8px; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h2>Category Management</h2>

    <form method="POST">
        <h3>Add New Category</h3>
        <label>Client:</label>
        <select name="client_id" required>
            <?php while ($client = $clients->fetch_assoc()): ?>
                <option value="<?= $client['id'] ?>"><?= $client['name'] ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Name:</label>
        <input type="text" name="name" required><br><br>

        <label>Type:</label>
        <select name="type" required>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select><br><br>

        <label>Debit Account ID:</label>
        <select name="debit_account_id" required>
            <?php foreach ($accounts_arr as $id => $name): ?>
                <option value="<?= $id ?>"><?= $name ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Credit Account ID:</label>
        <select name="credit_account_id" required>
            <?php foreach ($accounts_arr as $id => $name): ?>
                <option value="<?= $id ?>"><?= $name ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit" name="add_category">Add Category</button>
    </form>

    <h3>Existing Categories</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Name</th>
                <th>Type</th>
                <th>Debit Account</th>
                <th>Credit Account</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $categories->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['client_name'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= ucfirst($row['type']) ?></td>
                    <td><?= $accounts_arr[$row['debit_account_id']] ?? 'N/A' ?></td>
                    <td><?= $accounts_arr[$row['credit_account_id']] ?? 'N/A' ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                    <a href="manage_categories.php?edit_id=<?= $row['id'] ?>">Edit</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if (isset($_GET['edit_id'])): 
        $edit_id = intval($_GET['edit_id']);
        $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $edit_stmt->bind_param("i", $edit_id);
        $edit_stmt->execute();
        $edit_result = $edit_stmt->get_result();
        $edit_data = $edit_result->fetch_assoc();
    ?>
    <h3>Edit Category</h3>
    <form method="POST" action="manage_categories.php">
        <input type="hidden" name="category_id" value="<?= $edit_data['id'] ?>">
        <label>Name:</label>
        <input type="text" name="name" value="<?= $edit_data['name'] ?>" required><br>

        <label>Type:</label>
        <select name="type" required>
            <option value="income" <?= $edit_data['type'] === 'income' ? 'selected' : '' ?>>Income</option>
            <option value="expense" <?= $edit_data['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
        </select><br>

        <label>Debit Account ID:</label>
        <input type="number" name="debit_account_id" value="<?= $edit_data['debit_account_id'] ?>" required><br>

        <label>Credit Account ID:</label>
        <input type="number" name="credit_account_id" value="<?= $edit_data['credit_account_id'] ?>" required><br>

        <button type="submit" name="update_category">Update Category</button>
    </form>
    <hr>
    <?php endif; ?>

</body>
</html>
