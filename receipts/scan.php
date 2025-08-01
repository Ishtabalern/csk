<?php
session_start();
if ($_SESSION['role'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';


$stmt = $conn->prepare("SELECT * FROM scanned_receipts ORDER BY confidence_score ASC");
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0; top: 0;
            width: 100%; height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            box-shadow: 0 0 10px #333;
        }
        .modal input, .modal select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
        }
        .modal-buttons {
            text-align: right;
            margin-top: 10px;
        }
        .buttons{
            display: flex;
            flex-direction: row;
            margin-top: 20px;
            margin-left: 750px;
        }
        .buttons button{
            background-color: white;
            border-radius: 10px;
            border-width: 1px;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
        }
        .buttons button:active{
               transform: scale(0.96);
        }
        .buttons button:hover{
            background-color:#0B440F;
            color: #fff;
        }
        .scan-btn{
            padding: 10px;
            width: 200px;
            height: 50px;
        }
        .upload-btn{
            padding: 10px;
            width: 200px;
            height: 50px;
            margin-right: 50px;
        }
        .low-quality {
            background-color: rgba(255, 0, 0, 0.2) !important; /* Light red */
        }

        .good-quality {
            background-color: rgba(255, 255, 0, 0.2) !important; /* Light yellow */
        }

        .very-good-quality {
            background-color: rgba(0, 255, 0, 0.2) !important; /* Light green */
        }
        .excellent-quality {
            background-color: #ccf6e4; /* light greenish-blue */
        }
        
        #uploadModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        #rawTextModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        #rawTextModal .modal-content {
            background: #fff;
            padding: 20px;
            width: 500px; /* Fixed width */
            height: 300px; /* Fixed height */
            margin: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden; /* Prevent stretching */
        }

        #rawTextContent {
            max-height: 500px; /* Limited height inside modal */
            overflow-y: auto; /* Enables scrolling */
            white-space: pre-wrap;
            word-wrap: break-word;
            padding: 10px;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }
        .close-btn {
            background-color: #ff4d4d; /* Red for emphasis */
            margin-top: 10px;
            margin-left: 200px;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease-in-out;
        }

        .close-btn:hover {
            background-color: #cc0000; /* Darker red on hover */
        }

    </style>
</head>
<body>

<div class="topbar-container">
    <div class="header">
        <img src="../imgs/csk_logo.png" alt="">
        <h1>Scanned Receipts</h1>
    </div>
    <div class="btn">
        <a href="../employee_dashboard.php">← Back to Dashboard</a>
    </div>
</div>

<!-- Upload Button -->
<div class="buttons">
    <button id="openUploadModal" class="upload-btn">📤 Upload All to Receipts</button>
    <button class="scan-btn">Scan on raspberry</button>
</div>

<!-- Modal -->
<div id="uploadModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background-color:rgba(0,0,0,0.5);">
    <div style="background:#fff; padding:20px; max-width:400px; margin:100px auto; border-radius:10px;">
        <form method="POST" action="../process/upload_scanned_to_receipts.php">
            <label for="client_id">Select Client:</label>
            <select name="client_id" id="client_id" required>
                <option value="">-- Choose Client --</option>
                <?php
                    $clients = $conn->query("SELECT id, name FROM clients");
                    while ($client = $clients->fetch_assoc()):
                ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <br><br>
            <button type="submit" onclick="return confirm('Upload all scanned receipts to this client?')">Upload All</button>
            <button type="button" onclick="document.getElementById('uploadModal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('openUploadModal').onclick = () => {
        document.getElementById('uploadModal').style.display = 'block';
    };
</script>


<br>

<!-- Raw Text Modal -->
<div id="rawTextModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background-color:rgba(0,0,0,0.5);">
    <div style="background:#fff; padding:20px; max-width:500px; margin:100px auto; border-radius:10px;">
        <h3 style="margin-bottom: 10px; margin-left:180px; font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;">RAW TEXT</h3>
        <pre id="rawTextContent" style="white-space: pre-wrap; word-wrap: break-word; font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;"></pre>
        <button onclick="document.getElementById('rawTextModal').style.display='none'" class="close-btn"><i class="fas fa-times"></i> Close</button>
    </div>
</div>

<div class="receipts-container">
    <table id="receiptTable" border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Vendor</th>
                <th>Category</th>
                <th>Total</th>
                <th>Payment Method</th>
                <th>Date</th>
                <th>Image</th>
                <th>Confidence Score</th>
                <th>Quality</th>
                <th>Raw Text</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-id="<?= $row['id'] ?>" data-vendor="<?= htmlspecialchars($row['vendor']) ?>" data-category="<?= htmlspecialchars($row['category']) ?>" data-amount="<?= $row['amount'] ?>" data-date="<?= $row['receipt_date'] ?>" data-payment_method="<?= htmlspecialchars($row['payment_method']) ?>" class="<?php 
                        if ($row['quality_flag'] === 'Low') echo 'low-quality'; 
                        elseif ($row['quality_flag'] === 'Good') echo 'good-quality'; 
                        elseif ($row['quality_flag'] === 'Very Good') echo 'very-good-quality';
                        elseif ($row['quality_flag'] === 'Excellent - Edited') echo 'excellent-quality'; 
                    ?>">
                        <td><?= htmlspecialchars($row['vendor']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td>₱<?= number_format($row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['payment_method']) ?></td>
                        <td><?= date("m-d-Y", strtotime($row['receipt_date'])) ?></td> <!-- Display formatted -->
                        <td style="text-align: center;">
                            <?php if ($row['image_path']): ?>
                                <a href="<?= $row['image_path'] ?>" target="_blank">
                                    <img src="<?= $row['image_path'] ?>" width="80" height="80" style="object-fit:cover;">
                                </a>
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                
                        <td><?= htmlspecialchars($row['confidence_score']) ?></td>
                        <td><?= htmlspecialchars($row['quality_flag']) ?></td>
                        <td>
                            <button class="view-btn" onclick="openRawTextModal(`<?= htmlspecialchars($row['raw_text']) ?>`)">View Raw Text</button>
                        </td>
                        <td style="text-align: center; vertical-align: middle;"><button class="edit-btn">Edit</button></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No scanned receipts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Receipt</h3>
        <input type="hidden" id="edit-id">
        <label>Vendor</label>
        <input type="text" id="edit-vendor">
        <label>Category</label>
        <input type="text" id="edit-category">
        <label>Total</label>
        <input type="number" step="0.01" id="edit-amount">
        <label>Payment Method</label>
        <input type="text" id="edit-payment_method">
        <label>Date</label>
        <input type="date" id="edit-date">
        <div class="modal-buttons">
            <button onclick="saveChanges()">Save</button>
            <button onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    
    // Scan button triggers script on Raspberry Pi
            $('.scan-btn').click(() => {
                fetch('http://192.168.1.12:5000/run-script', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Script ran successfully:\n' + data.output);
                    } else {
                        alert('Error running script:\n' + data.error);
                    }
                })
                .catch(error => {
                    alert('Failed to connect to the server:\n' + error);
                });
            });
    
    const modal = document.getElementById('editModal');
    let currentRow = null;

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentRow = this.closest('tr');
            const id = currentRow.dataset.id;
            const vendor = currentRow.dataset.vendor;
            const category = currentRow.dataset.category;
            const amount = currentRow.dataset.amount;
            const payment_method = currentRow.dataset.payment_method;
            const date = currentRow.dataset.date;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-vendor').value = currentRow.dataset.vendor;
            document.getElementById('edit-category').value = currentRow.dataset.category;
            document.getElementById('edit-amount').value = currentRow.dataset.amount;
            document.getElementById('edit-payment_method').value = currentRow.dataset.payment_method;
            document.getElementById('edit-date').value = currentRow.dataset.date;
            modal.style.display = 'block';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
    }

        function saveChanges() {
        const id = document.getElementById('edit-id').value;
        const vendor = document.getElementById('edit-vendor').value;
        const category = document.getElementById('edit-category').value;
        const amount = document.getElementById('edit-amount').value;
        const payment_method = document.getElementById('edit-payment_method').value;
        const date = document.getElementById('edit-date').value;

        fetch('../process/scanned_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&vendor=${encodeURIComponent(vendor)}&category=${encodeURIComponent(category)}&amount=${amount}&payment_method=${encodeURIComponent(payment_method)}&date=${date}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                currentRow.dataset.vendor = vendor;
                currentRow.dataset.category = category;
                currentRow.dataset.amount = amount;
                currentRow.dataset.payment_method = payment_method;
                currentRow.dataset.date = date;

                currentRow.children[0].textContent = vendor;
                currentRow.children[1].textContent = category;
                currentRow.children[2].textContent = `₱${parseFloat(amount).toFixed(2)}`;
                currentRow.children[3].textContent = payment_method;
                currentRow.children[4].textContent = date;
                currentRow.children[7].textContent = data.quality;

                // Reset and reapply quality color
                currentRow.classList.remove('low-quality', 'good-quality', 'very-good-quality', 'excellent-quality');
                if (data.quality === "Excellent - Edited") {
                    currentRow.classList.add('excellent-quality');
                }

                alert("Update successful!");
                closeModal();
            } else {
                alert("Failed to save changes.");
            }
        })
        .catch(error => {
            alert("Error saving changes: " + error);
        });
    }

    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    }
    
    $(document).ready(function () {
    $('#receiptTable').DataTable({
        "order": [[6, "asc"]],
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50, 100],
        "language": {
            "search": "Search Receipts:",
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": "No matching receipts found",
            "info": "Showing _START_ to _END_ of _TOTAL_ receipts",
            "infoEmpty": "No receipts available",
            "infoFiltered": "(filtered from _MAX_ total receipts)"
        },
        "createdRow": function (row, data, dataIndex) {
            var quality = (data[7] || "").trim().toLowerCase();
            if (quality === "low") {
                $(row).addClass('low-quality');
            } else if (quality === "good") {
                $(row).addClass('good-quality');
            } else if (quality === "very good") {
                $(row).addClass('very-good-quality');
            } else if (quality === "excellent - edited") {
                $(row).addClass('excellent-quality');
            }
        }
    });
});

function openRawTextModal(rawText) {
    document.getElementById('rawTextContent').textContent = rawText;
    document.getElementById('rawTextModal').style.display = 'block';
}
</script>

</body>
</html>
