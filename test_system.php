<?php
require_once 'config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "System Test";
include 'includes/header.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">System Test</div>
            <div class="page-subtitle">Verify system components and functionality</div>
        </div>

        <div class="row">
            <!-- Database Connection Test -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-database"></i> Database Connection</span>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $pdo = get_pdo();
                            $pdo->query("SELECT 1");
                            echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Database connection successful</div>';
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Session Test -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-user"></i> Session Management</span>
                    </div>
                    <div class="card-body">
                        <?php if (is_logged_in()): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> User logged in: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No user logged in
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- File Permissions Test -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-file"></i> File Permissions</span>
                    </div>
                    <div class="card-body">
                        <?php
                        $test_dirs = ['backups', 'logs', 'uploads'];
                        $all_good = true;
                        
                        foreach ($test_dirs as $dir) {
                            $path = $dir . '/';
                            if (!is_dir($path)) {
                                if (mkdir($path, 0755, true)) {
                                    echo '<div class="text-success"><i class="fas fa-check-circle"></i> Created directory: ' . $dir . '</div>';
                                } else {
                                    echo '<div class="text-danger"><i class="fas fa-times-circle"></i> Failed to create directory: ' . $dir . '</div>';
                                    $all_good = false;
                                }
                            } else {
                                if (is_writable($path)) {
                                    echo '<div class="text-success"><i class="fas fa-check-circle"></i> Directory writable: ' . $dir . '</div>';
                                } else {
                                    echo '<div class="text-danger"><i class="fas fa-times-circle"></i> Directory not writable: ' . $dir . '</div>';
                                    $all_good = false;
                                }
                            }
                        }
                        
                        if ($all_good) {
                            echo '<div class="alert alert-success mt-2"><i class="fas fa-check-circle"></i> All file permissions are correct</div>';
                        } else {
                            echo '<div class="alert alert-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Some file permissions need attention</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- PHP Extensions Test -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-cogs"></i> PHP Extensions</span>
                    </div>
                    <div class="card-body">
                        <?php
                        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
                        $all_extensions = true;
                        
                        foreach ($required_extensions as $ext) {
                            if (extension_loaded($ext)) {
                                echo '<div class="text-success"><i class="fas fa-check-circle"></i> ' . $ext . ' extension loaded</div>';
                            } else {
                                echo '<div class="text-danger"><i class="fas fa-times-circle"></i> ' . $ext . ' extension missing</div>';
                                $all_extensions = false;
                            }
                        }
                        
                        if ($all_extensions) {
                            echo '<div class="alert alert-success mt-2"><i class="fas fa-check-circle"></i> All required extensions are loaded</div>';
                        } else {
                            echo '<div class="alert alert-danger mt-2"><i class="fas fa-times-circle"></i> Some required extensions are missing</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- API Test -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-plug"></i> API Endpoints</span>
                    </div>
                    <div class="card-body">
                        <div id="apiTestResults">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Testing APIs...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fas fa-info-circle"></i> System Information</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server Software:</strong></td>
                                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Document Root:</strong></td>
                                        <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Current Directory:</strong></td>
                                        <td><?php echo getcwd(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Memory Limit:</strong></td>
                                        <td><?php echo ini_get('memory_limit'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Max Execution Time:</strong></td>
                                        <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-tools"></i> Quick Actions</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" onclick="runFullTest()">
                        <i class="fas fa-play"></i> Run Full Test
                    </button>
                    <button class="btn btn-info" onclick="testDatabase()">
                        <i class="fas fa-database"></i> Test Database
                    </button>
                    <button class="btn btn-success" onclick="testAPIs()">
                        <i class="fas fa-plug"></i> Test APIs
                    </button>
                    <button class="btn btn-warning" onclick="clearCache()">
                        <i class="fas fa-broom"></i> Clear Cache
                    </button>
                    <a href="setup_database.php" class="btn btn-secondary">
                        <i class="fas fa-database"></i> Setup Database
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    testAPIs();
});

function testAPIs() {
    const apiEndpoints = [
        { name: 'Students API', url: 'api/students.php?action=list' },
        { name: 'Devices API', url: 'api/devices.php?action=list' },
        { name: 'Reports API', url: 'api/reports.php?action=quick_stats' }
    ];
    
    const resultsDiv = document.getElementById('apiTestResults');
    resultsDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Testing APIs...</span></div>';
    
    let completed = 0;
    let results = [];
    
    apiEndpoints.forEach(endpoint => {
        fetch(endpoint.url)
            .then(response => response.json())
            .then(data => {
                results.push({
                    name: endpoint.name,
                    status: data.success ? 'success' : 'error',
                    message: data.message || 'Unknown error'
                });
                completed++;
                
                if (completed === apiEndpoints.length) {
                    displayAPIResults(results);
                }
            })
            .catch(error => {
                results.push({
                    name: endpoint.name,
                    status: 'error',
                    message: 'Network error: ' + error.message
                });
                completed++;
                
                if (completed === apiEndpoints.length) {
                    displayAPIResults(results);
                }
            });
    });
}

function displayAPIResults(results) {
    const resultsDiv = document.getElementById('apiTestResults');
    let html = '';
    
    results.forEach(result => {
        const icon = result.status === 'success' ? 'check-circle' : 'times-circle';
        const color = result.status === 'success' ? 'success' : 'danger';
        
        html += `<div class="text-${color}"><i class="fas fa-${icon}"></i> ${result.name}: ${result.message}</div>`;
    });
    
    const allSuccess = results.every(r => r.status === 'success');
    if (allSuccess) {
        html += '<div class="alert alert-success mt-2"><i class="fas fa-check-circle"></i> All APIs are working correctly</div>';
    } else {
        html += '<div class="alert alert-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Some APIs have issues</div>';
    }
    
    resultsDiv.innerHTML = html;
}

function runFullTest() {
    // Reload the page to run all tests
    location.reload();
}

function testDatabase() {
    fetch('test_database.php')
        .then(response => response.text())
        .then(data => {
            alert('Database test completed. Check the console for details.');
            console.log('Database test result:', data);
        })
        .catch(error => {
            alert('Database test failed: ' + error.message);
        });
}

function clearCache() {
    if (confirm('Are you sure you want to clear the cache?')) {
        fetch('api/maintenance.php?action=run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tasks: ['clean_sessions']
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully!');
            } else {
                alert('Error clearing cache: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error clearing cache: ' + error.message);
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?> 