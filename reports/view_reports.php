<?php
session_start();
require_once '../includes/db.php';

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
    <link rel="stylesheet" href="../partials/sidebar.css" />
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
            width: 95%;
            max-width: 900px;
            margin: 120px auto 60px;
            text-align: center;
        }
        h2 {
            font-weight: 600;
            font-size: 2.25rem;
            color: #111;
            margin-bottom: 40px;
            letter-spacing: 0.05em;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 24px;
            justify-items: center;
        }
        a.report-card {
            text-decoration: none;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.07);
            width: 140px;
            height: 140px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #333;
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
            font-size: 1rem;
            font-weight: 500;
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
        }
    </style>
</head>
<body>
    <?php
        $page = 'view_reports';
        include '../partials/sidebar.php'; 
    ?>
    <div class="container">
        <h2>Reports</h2>
        <div class="grid">
            <a class="report-card" href="category_summary.php" title="Category Summary Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M2 20c0-3 5-5 10-5s10 2 10 5v1H2v-1z"/>
                </svg>
                <span class="label">Category Summary</span>
            </a>
            <a class="report-card" href="sales_expense.php" title="Sales Vs Expense Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M4 17h16v2H4zM4 14h8v2H4zM4 11h5v2H4z"/>
                </svg>
                <span class="label">Sales Vs Expense</span>
            </a>
            <a class="report-card" href="trial_balance.php" title="Trial Balance Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <rect x="3" y="4" width="18" height="2" rx="1"/>
                    <path d="M6 8h12v1H6zM6 12h9v1H6zM6 16h7v1H6z"/>
                </svg>
                <span class="label">Trial Balance</span>
            </a>
            <a class="report-card" href="income_statement.php" title="Income Statement Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 3l7 6v12H5V9l7-6z" fill="none" stroke="#000" stroke-width="2"/>
                </svg>
                <span class="label">Income Statement</span>
            </a>
            <a class="report-card" href="owners_equity.php" title="Owner's Equity Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="10" stroke="#000" stroke-width="1" fill="none"/>
                    <path d="M8 12h8M12 8v8" stroke="#000" stroke-width="1" />
                </svg>
                <span class="label">Owner's Equity</span>
            </a>
            <a class="report-card" href="cash_flow.php" title="Statement of Cash Flow Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M3 11h18v2H3v-2zm6-6v13l4-7-4-6z"/>
                </svg>
                <span class="label">Statement of Cash Flow</span>
            </a>
            <a class="report-card" href="balance_sheet.php" title="Balance Sheet Report">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
                </svg>
                <span class="label">Balance Sheet</span>
            </a>
        </div>
    </div>
</body>
</html>

