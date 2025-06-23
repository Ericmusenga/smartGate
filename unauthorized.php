<?php
require_once 'config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Unauthorized Access";
include 'includes/header.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card mt-5">
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h2 class="card-title text-danger">Access Denied</h2>
                            <p class="card-text">
                                You do not have permission to access this page. 
                                Please contact your administrator if you believe this is an error.
                            </p>
                            
                            <?php if (is_logged_in()): ?>
                                <div class="mt-4">
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="fas fa-home"></i> Go to Dashboard
                                    </a>
                                    <a href="logout.php" class="btn btn-secondary">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="mt-4">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </a>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-home"></i> Go Home
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title">What happened?</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-info-circle text-info"></i> You may not have the required role to access this page</li>
                                <li><i class="fas fa-info-circle text-info"></i> Your session may have expired</li>
                                <li><i class="fas fa-info-circle text-info"></i> The page may require specific permissions</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 