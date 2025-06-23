<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/Capstone_project/assets/css/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/Capstone_project/assets/js/main.js" defer></script>
</head>
<body>
    <header class="header">
        <button class="sidebar-toggle" aria-label="Toggle Sidebar" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="logo">
            <img src="/Capstone_project/assets/images/logo.png" alt="UR Logo" onerror="this.style.display='none'">
            <div>
                <h1><?php echo APP_NAME; ?></h1>
                <div class="subtitle">UR College of Education, Rukara Campus</div>
            </div>
        </div>
        <nav class="header-nav">
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    if (isset($_SESSION['first_name'])) {
                        echo strtoupper(substr($_SESSION['first_name'], 0, 1));
                    } else {
                        echo 'U';
                    }
                    ?>
                </div>
                <span>
                    <?php 
                    if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                        echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                    } else {
                        echo 'User';
                    }
                    ?>
                    <small>(<?php echo isset($_SESSION['user_type']) ? ucfirst($_SESSION['user_type']) : 'User'; ?>)</small>
                </span>
                <a href="/Capstone_project/logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
    </header>

</body>
</html> 