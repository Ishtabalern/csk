<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php
if (!isset($page)) {
  $page = ''; // or a default value like 'home'
}
?>
<div class="navbar">
        <div class="company-logo">
        <?php if ($page === 'sidebar'): ?>
            <img src="../imgs/csk_logo.png" alt="">
        <?php endif; ?>
        <?php if ($page === 'all_receipts'): ?>
            <img src="../imgs/csk_logo.png" alt="">
        <?php endif; ?>
        <?php if ($page === 'view_reports'): ?>
            <img src="../imgs/csk_logo.png" alt="">
        <?php endif; ?>
        <?php if ($page === 'view_reports_admin'): ?>
            <img src="../../imgs/csk_logo.png" alt="">
        <?php endif; ?>
            <img src="imgs/csk_logo.png" alt="">
        </div>

        <?php if ($_SESSION['role'] === 'employee' && $page === 'employee_dashboard'): ?>
        <div class="navtabs">
            <h1 class="title-tabs">Employee Dashboard</h1>
            <h2 class="title-tabs"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
        </div>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'employee' && $page === '1employee_dashboard'): ?>
        <div class="title">
            <h1>Employee Dashboard</h1>
        </div>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'employee' && $page === '1employee_dashboard'): ?>
        <div class="title">
            <h1>Employee Dashboard</h1>
        </div>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'employee' && $page === '1employee_dashboard'): ?>
        <div class="title">
            <h1>Employee Dashboard</h1>
        </div>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'employee' && $page === '1employee_dashboard'): ?>
        <div class="title">
            <h1>Employee Dashboard</h1>
        </div>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'employee' && $page === '1employee_dashboard'): ?>
        <div class="title">
            <h1>Employee Dashboard</h1>
        </div>
        <?php endif; ?>


</div>
    
</body>
</html>