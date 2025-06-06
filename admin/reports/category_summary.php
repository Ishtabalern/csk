<?php
include_once '../../includes/db.php';

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
    <link rel="stylesheet" href="../../partials/sidebar.css">
    <link rel="stylesheet" href="../../partials/topbar.css">
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


        h1{
        color: #1ABC9C;
        padding: 20px;
        }

        /* filter form */
        .filter-container {
        display: flex;
        justify-content: center; /* ➡ Center horizontally yung form */
        margin-top: 20px; /* ➡ Small space above the form */
        }

        .filter {
        background-color: #fff;
        padding: 20px 0px;
        border: 1px solid rgb(208, 208, 208);
        border-radius: 7px;
        display: flex;
        flex-direction: column;
        align-items: center; /* ➡ Center all content inside the form */
        gap: 20px;
        max-width: 100%;
        width: 700px;
        }

        .filter .section {
        display: flex;
        flex-wrap: wrap; /* ➡ Para responsive, wrap if needed */
        justify-content: center; /* ➡ Center the inputs */
        gap: 30px;
        }

        .section .input {
        display: flex;
        flex-direction: column;
        gap: 10px;
        }

        .input label {
        font-size: 1rem;
        font-weight: bolder;
        }

        .input select,
        .input input[type="date"] {
        padding: 10px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 6px;
        background-color: white;
        cursor: pointer;
        width: 250px; /* ➡ fixed width para same lahat ng input */
        }

        .btn button {
        width: 200px;
        padding: 15px 0;
        border-radius: 6px;
        background-color: #00AF7E;
        color: #FFF;
        font-weight: bold;
        border: 1px solid #c3c3c3;
        cursor: pointer;
        transition: transform 0.3s ease;
        transform: scale(0.95);
        margin-top: 10px;
        align-self: center; /* ➡ Button is centered inside form */
        }

        .btn button:active {
        transform: scale(0.9);
        }



        /* table */
        .category-container{
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #ddd;
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-width: 100%;
        width: 700px;
        margin: auto;
        margin-top: 20px;
        }
        
        .category-container table{ 
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .category-container th, .category-container td { 
        padding: 15px !important;
        text-align: left;
        border: 1px solid #ddd;
        font-size: 1rem;
        }
        
        .category-container th{
        background-color: #f2f2f2;
        font-weight: bold;
        color: #434343;
        }
        
        .category-container tbody tr:hover{
        background-color: #f2f2f2;
        }


    </style>
</head>
<body>
 <!--   <?php include '../../partials/sidebar.php'; ?> -->

    <div class="topbar-container">
        <div class="header">
            <img src="../../imgs/csk_logo.png" alt="">
            <h1 style="color: #0B440F">Category Summary Report</h1>
        </div>
       
        <div class="btn">
            <?php
            $dashboard_link = ($_SESSION['role'] === 'admin') ? '../reports/view_reports.php' : 'view_reports.php';
        ?>
        <a href="<?= $dashboard_link ?>">
             Reports
        </a>
            <a href="../../admin_dashboard.php"> Back to Admin Dashboard</a>
        </div>
    </div>

    <div class="main-content p-4" style="padding:20px;">

        <div class="filter-container">
            <form class="filter mb-4 row g-3" method="GET" >
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

                <div class="btn col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    
        <div class="category-container">
            <div class="table">
                <table id="categorySummary" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="background-color: #00AF7E; color:#fff;">Category</th>
                            <th style="background-color: #00AF7E; color:#fff;">Total Amount</th>
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




<!-- DataTables JS -->
<script>
    $(document).ready(function () {
        $('#categorySummary').DataTable();
    });
</script>

</body>
</html>