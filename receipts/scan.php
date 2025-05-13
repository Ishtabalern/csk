<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Handle manual confirmation of a receipt
if (isset($_POST['confirm'])) {
    $id = intval($_POST['id']);
    $client_id = intval($_POST['client_id']);
    $receipt_date = $_POST['receipt_date'];
    $vendor = $_POST['vendor'];
    $category = $_POST['category'];
    $amount = floatval($_POST['amount']);
    $image_path = $_POST['image_path'];

    $stmt = $conn->prepare("INSERT INTO receipts (client_id, receipt_date, vendor, category, amount, img_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssds", $client_id, $receipt_date, $vendor, $category, $amount, $image_path);
    $stmt->execute();
    $stmt->close();

    // Use prepared statement to delete
    $deleteStmt = $conn->prepare("DELETE FROM scanned_receipts WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
    $deleteStmt->close();

    echo "<script>alert('Receipt confirmed and moved to receipts table!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Record Expense</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="styles/scan.css">
    <link rel="stylesheet" href="styles/sidebar.css">
</head>
<body>

<div class="dashboard">
    <div class="top-bar">
        <h1>Record Expense</h1>
        <div class="user-controls">
            <a href="functions/logout.php"><button class="logout-btn">Log out</button></a>
        </div>
    </div>

    <div class="main-content">
        <div class="receipt-table">
            <div class="data-table">
                <form id="clientForm" method="POST">
                    <select id="clientSelect" name="client" class="client-dropdown" style="margin-bottom: 10px;">
                        <option value="">-- Select Client --</option>
                        <?php
                        $clients = $conn->query("SELECT id, name FROM clients ORDER BY name ASC");
                        while ($row = $clients->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </form>

                <table id="recordsTable" class="display">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Receipt Image</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = $conn->query("SELECT id, receipt_date, vendor, category, amount, image_path FROM scanned_receipts");
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr data-id='{$row["id"]}'>";
                            echo "<td><input type='checkbox' class='row-check'></td>";
                            echo "<td contenteditable='true' class='editable' data-field='date'>{$row["receipt_date"]}</td>";
                            echo "<td contenteditable='true' class='editable' data-field='vendor'>" . htmlspecialchars($row["vendor"]) . "</td>";
                            echo "<td contenteditable='true' class='editable' data-field='category'>" . htmlspecialchars($row["category"]) . "</td>";
                            echo "<td contenteditable='true' class='editable' data-field='amount'>₱" . number_format($row["amount"], 2) . "</td>";
                            echo "<td><img src='" . htmlspecialchars($row["image_path"]) . "' alt='receipt' style='max-height:100px;'></td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <button type="button" id="saveEdits" class="btn save-btn">Save All Edits</button>
                <button type="button" id="deleteSelected" class="btn" style="background-color: #aaa;">Delete Selected</button>
            </div>
        </div>

        <div class="scan">
            <h2>Do you have an image of your receipt? Click here to automatically record your expenses!</h2>
            <div class="scan-options">
                <button type="button" id="saveChanges" class="btn save-btn">💾 Save Changes</button>
                <button type="button" id="confirmReceipts" class="btn" style="background-color: #27ae60; color: white;">✔ Confirm Selected Receipts</button>
                <button type="button" id="deleteSelected" class="btn" style="background-color: #aaa;">🗑 Delete Selected</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function () {
    $('#recordsTable').DataTable();

    function parseAmount(text) {
        return parseFloat(text.replace(/[^\d.-]/g, '')) || 0;
    }

    function collectEditedRows() {
        let editedRows = [];
        $('#recordsTable tbody tr').each(function () {
            const row = $(this);
            const id = row.data('id');
            const receipt_date = row.find('[data-field="receipt_date"]').text().trim();
            const vendor = row.find('[data-field="vendor"]').text().trim();
            const category = row.find('[data-field="category"]').text().trim();
            const amount = parseAmount(row.find('[data-field="amount"]').text());
            editedRows.push({ id, date, vendor, category, amount });
        });
        return editedRows;
    }

    $('#saveChanges').click(function () {
        const rows = collectEditedRows();

        $.ajax({
            url: '../process/save_scanned_edits.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ receipts: rows }),
            success: function (res) {
                alert("Changes saved successfully!");
            },
            error: function () {
                alert("Failed to save changes.");
            }
        });
    });

    $('#deleteSelected').click(function () {
        const selectedIds = [];
        $('#recordsTable tbody tr').each(function () {
            if ($(this).find('.row-check').is(':checked')) {
                selectedIds.push($(this).data('id'));
            }
        });

        if (selectedIds.length === 0) {
            alert("No receipts selected for deletion.");
            return;
        }

        if (!confirm("Are you sure you want to delete selected receipts?")) return;

        $.ajax({
            url: '../process/delete_scanned_receipts.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ ids: selectedIds }),
            success: function () {
                alert("Receipts deleted.");
                location.reload();
            },
            error: function () {
                alert("Failed to delete receipts.");
            }
        });
    });

    // Raspberry Pi trigger
    $('.scan-btn').click(() => {
        fetch('http://raspberrypi:5000/run-script', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Script ran successfully:\n' + data.output : 'Error:\n' + data.error);
        })
        .catch(error => {
            alert('Connection error:\n' + error);
        });
    });
});
</script>
</body>
</html>
