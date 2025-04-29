<?php
include_once '../includes/db.php';

// Get list of clients for the dropdown
$clients = mysqli_query($conn, "SELECT id, name FROM clients");

// Determine selected client
$selectedClientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../partials/sidebar.css">
    <link rel="stylesheet" href="../styles/reports/category_summary.css">
</head>
<body>
 <!--   <?php include '../partials/sidebar.php'; ?> -->

<div class="main-content p-4">
    <h1 class="mb-4">Category Summary Report</h1>
    
    <div class="filter-container">
        <form class="filter mb-4 row g-3" method="GET">
            <div class="section">
                <div class="input col-md-4">
                    <label for="client_id" class="form-label">Select Client:</label>
                    <select name="client_id" id="client_id" class="form-control">
                        <option value="0">-- All Clients --</option>
                        <?php mysqli_data_seek($clients, 0); while ($client = mysqli_fetch_assoc($clients)) : ?>
                            <option value="<?= $client['id'] ?>" <?= $client['id'] == $selectedClientId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input col-md-3">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                        value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
                </div>

                <div class="input col-md-3">
                    <label for="end_date" class="form-label">End Date:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                        value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
                </div>   
            </div>     

            <div class="btn ol-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    
        
    </div>

    <div class="category-container">
        <div class="table">
            <table class="table table-bordered table-striped" id="categorySummary" >
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conditions = [];

                    if ($selectedClientId > 0) {
                        $conditions[] = "client_id = $selectedClientId";
                    }
                    
                    if (!empty($_GET['start_date'])) {
                        $startDate = $_GET['start_date'];
                        $conditions[] = "receipt_date >= '$startDate'";
                    }
                    
                    if (!empty($_GET['end_date'])) {
                        $endDate = $_GET['end_date'];
                        $conditions[] = "receipt_date <= '$endDate'";
                    }
                    
                    $whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";
                    
                    $query = "
                        SELECT category, SUM(amount) AS total 
                        FROM receipts 
                        $whereClause
                        GROUP BY category
                    ";

                    $result = mysqli_query($conn, $query);

                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['category']) . "</td>
                                <td>₱" . number_format($row['total'], 2) . "</td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        
    </div>
 
</div>


<div class="btn ol-md-2 d-flex align-items-end">
    <a href="../employee_dashboard.php">← Back to Employee Dashboard</a>
</div>

<!-- DataTables JS -->
<script>
    $(document).ready(function () {
        $('#categorySummary').DataTable();
    });
</script>

</body>
</html>