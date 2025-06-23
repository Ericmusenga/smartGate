<?php
// API Index - Entry Point
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry/Exit API - University of Rwanda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .endpoint-card {
            transition: transform 0.2s;
        }
        .endpoint-card:hover {
            transform: translateY(-5px);
        }
        .method-get { border-left: 4px solid #28a745; }
        .method-post { border-left: 4px solid #007bff; }
        .method-put { border-left: 4px solid #ffc107; }
        .method-delete { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h2><i class="fas fa-university"></i> University of Rwanda</h2>
                        <h4>Entry/Exit Management API</h4>
                        <p class="mb-0">Digital Gate Management System</p>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle"></i> About</h5>
                                <p>This API provides endpoints for managing student entry and exit activities using RFID cards and manual logging systems.</p>
                                <ul>
                                    <li>RFID card processing</li>
                                    <li>Manual entry/exit logging</li>
                                    <li>Real-time status monitoring</li>
                                    <li>Comprehensive reporting</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-key"></i> Authentication</h5>
                                <p>All API endpoints require authentication using API keys:</p>
                                <div class="alert alert-info">
                                    <strong>Header:</strong> X-API-Key: your_api_key<br>
                                    <strong>Valid Keys:</strong> gate_system_2024, security_api_key, admin_api_key
                                </div>
                            </div>
                        </div>

                        <h5><i class="fas fa-list"></i> Available Endpoints</h5>
                        
                        <div class="row">
                            <!-- Process RFID Card -->
                            <div class="col-md-6 mb-3">
                                <div class="card endpoint-card method-post">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-primary">POST</span>
                                            Process RFID Card
                                        </h6>
                                        <p class="card-text">Process RFID card scanning and automatically determine entry/exit.</p>
                                        <code>/entry_exit/process.php</code>
                                        <div class="mt-2">
                                            <a href="entry_exit/test_interface.html" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-play"></i> Test Interface
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Entry/Exit -->
                            <div class="col-md-6 mb-3">
                                <div class="card endpoint-card method-post">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-primary">POST</span>
                                            Manual Entry/Exit
                                        </h6>
                                        <p class="card-text">Manual logging for security officers without RFID cards.</p>
                                        <code>/entry_exit/manual.php</code>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Information -->
                            <div class="col-md-6 mb-3">
                                <div class="card endpoint-card method-get">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-success">GET</span>
                                            Student Information
                                        </h6>
                                        <p class="card-text">Get comprehensive student information by RFID card number.</p>
                                        <code>/entry_exit/student_info.php</code>
                                    </div>
                                </div>
                            </div>

                            <!-- Entry/Exit Logs -->
                            <div class="col-md-6 mb-3">
                                <div class="card endpoint-card method-get">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-success">GET</span>
                                            Entry/Exit Logs
                                        </h6>
                                        <p class="card-text">Retrieve entry/exit logs with filtering and pagination.</p>
                                        <code>/entry_exit/logs.php</code>
                                    </div>
                                </div>
                            </div>

                            <!-- Campus Status -->
                            <div class="col-md-6 mb-3">
                                <div class="card endpoint-card method-get">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-success">GET</span>
                                            Campus Status
                                        </h6>
                                        <p class="card-text">Get real-time campus status and occupancy information.</p>
                                        <code>/entry_exit/status.php</code>
                                    </div>
                                </div>
                            </div>

                            <!-- Test API -->
                            <div class="col-md-6 mb-3">
                                <div class="card endpoint-card method-get">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-success">GET</span>
                                            Test API
                                        </h6>
                                        <p class="card-text">Simple endpoint to test if the API is working correctly.</p>
                                        <code>/test.php</code>
                                        <div class="mt-2">
                                            <a href="test.php" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i> Test Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-tools"></i> Tools & Examples</h5>
                                <div class="list-group">
                                    <a href="entry_exit/test_interface.html" class="list-group-item list-group-item-action">
                                        <i class="fas fa-desktop"></i> Test Interface
                                    </a>
                                    <a href="entry_exit/rfid_reader_example.html" class="list-group-item list-group-item-action">
                                        <i class="fas fa-credit-card"></i> RFID Reader Integration
                                    </a>
                                    <a href="test_api.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-vial"></i> API Test Script
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-book"></i> Documentation</h5>
                                <div class="list-group">
                                    <a href="README.md" class="list-group-item list-group-item-action">
                                        <i class="fas fa-file-alt"></i> API Documentation
                                    </a>
                                    <a href="settings.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-cog"></i> API Settings
                                    </a>
                                    <a href="../database/schema.sql" class="list-group-item list-group-item-action">
                                        <i class="fas fa-database"></i> Database Schema
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-4">
                            <h6><i class="fas fa-exclamation-triangle"></i> Important Notes</h6>
                            <ul class="mb-0">
                                <li>All API requests must include a valid API key in the X-API-Key header</li>
                                <li>Rate limiting is enabled to prevent abuse</li>
                                <li>All timestamps are in UTC format</li>
                                <li>For production use, ensure HTTPS is enabled</li>
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <p class="text-muted">
                                <i class="fas fa-code"></i> 
                                API Version 1.0.0 | 
                                <i class="fas fa-calendar"></i> 
                                Last Updated: <?php echo date('Y-m-d H:i:s'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 