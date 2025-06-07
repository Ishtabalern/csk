<?php
session_start();
require_once '../../includes/db.php';

// Optional: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reports</title>
    <link rel="stylesheet" href="../../partials/sidebar.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fafafa;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: #222;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 80px auto 40px;
            text-align: center;
        }
        h2 {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            font-weight: 600;
            font-size: 70px;
            color: #111;
            margin-top: -10px;
            margin-bottom: 50px;
            letter-spacing: 0.05em;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, auto);
            grid-template-rows: repeat(2, 1fr); /* Four rows */
            gap: 50px;
            justify-items: center;
        }
        a.report-card:nth-child(5) {
            grid-column: span 2; /* Makes the bottom card span across both columns */
            justify-self: center; /* Centers it */
        }

        a.report-card {
            text-decoration: none;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.07);
            width: 220px;
            height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #062335;
            transition: box-shadow 0.3s ease, color 0.3s ease, transform 0.3s ease;
            cursor: pointer;
            user-select: none;
        }
        a.report-card:hover,
        a.report-card:focus {
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            color: #0B440F;
            transform: translateY(-4px);
            outline: none;
        }
        svg.icon {
            width: 40px;
            height: 40px;
            margin-bottom: 12px;
            fill: #888;
            transition: fill 0.3s ease;
        }
        a.report-card:hover svg.icon,
        a.report-card:focus svg.icon {
            fill: #0B440F;
        }
        span.label {
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 0.03em;
        }
        @media (max-width: 480px) {
            .container {
                margin: 100px 10px 40px;
            }
            a.report-card {
                width: 100px;
                height: 100px;
            }
            svg.icon {
                width: 28px;
                height: 28px;
                margin-bottom: 8px;
            }
            span.label {
                font-size: 0.9rem;
            }
            .grid {
                grid-template-columns: repeat(2, 1fr); /* Adjust for smaller screens */
            }
        }
    </style>
</head>
<body>
    <?php
        $page = 'view_reports_admin';
        include '../../partials/sidebar.php'; 
    ?>
    <div class="container">
        <h2>Reports</h2>
        <div class="grid">
            <a class="report-card" href="category_summary.php" title="Category Summary Report">
                <span style="font-size: 75px; margin-bottom: 10px">📃</span>
                <span class="label">Category Summary</span>
            </a>
            <a class="report-card" href="sales_expense.php" title="Sales Vs Expense Report">
                <span style="font-size: 75px; margin-bottom: 10px">📊</span>
                <span class="label">Sales Vs Expense</span>
            </a>
            <a class="report-card" href="../../reports/trial_balance.php" title="Trial Balance Report">
                <span style="font-size: 75px; margin-bottom: 10px">⚖️</span>
                <span class="label">Trial Balance</span>
            </a>
            <a class="report-card" href="../../reports/income_statement.php" title="Income Statement Report">
                <span style="font-size: 75px; margin-bottom: 10px">💵</span>
                <span class="label">Income Statement</span>
            </a>
            <a class="report-card" href="../../reports/owners_equity.php" title="Owner's Equity Report">
                <span style="font-size: 75px; margin-bottom: 10px">🏦</span>
                <span class="label">Owner's Equity</span>
            </a>
            <a class="report-card" href="../../reports/cash_flow.php" title="Statement of Cash Flow Report" style="margin-left: -300px;">
                <span style="font-size: 75px; margin-bottom: 10px">💸</span>
                <span class="label">Statement of Cash Flow</span>
            </a>
            <a class="report-card" href="../../reports/balance_sheet.php" title="Balance Sheet Report" style="margin-left: -300px;">
                <span style="font-size: 75px; margin-bottom: 10px">🧾</span>
                <span class="label">Balance Sheet</span>
            </a>
        </div>
    </div>
</body>
</html>