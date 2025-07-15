<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Entry/Exit Management - Security Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .sidebar { position: fixed; left: 0; top: 70px; bottom: 0; width: 250px; background: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.07); z-index: 999; transition: transform 0.3s; }
        .sidebar.closed { transform: translateX(-100%); }
        .sidebar-menu { padding: 2rem 0; }
        .menu-section { margin-bottom: 2rem; }
        .menu-section h3 { color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; padding: 0 2rem 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #eee; }
        .menu-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; color: #2c3e50; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: #f4f6fb; border-left-color: #3498db; color: #3498db; }
        .menu-item i { font-size: 1.2rem; width: 20px; text-align: center; }
        
        .header { position: fixed; top: 0; left: 0; right: 0; background: #667eea; color: #fff; z-index: 1000; display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
        .header .sidebar-toggle { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; margin-right: 1rem; }
        .header .user-info { display: flex; align-items: center; gap: 1rem; }
        .header .user-avatar { width: 35px; height: 35px; border-radius: 50%; background: #764ba2; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        
        .main-content { margin-left: 250px; margin-top: 70px; padding: 2rem 1rem 4rem 1rem; min-height: calc(100vh - 70px); transition: margin-left 0.3s; background: rgb(8, 78, 147); }
        .sidebar.closed ~ .main-content { margin-left: 0; }
        
        .page-header { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; }
        .page-title { font-size: 2.2rem; font-weight: 700; color: #2c3e50; margin: 0; }
        .page-subtitle { color: #7f8c8d; font-size: 1.1rem; margin: 5px 0 0 0; }
        
        .tabs { display: flex; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 30px; overflow: hidden; }
        .tab { flex: 1; padding: 20px; background: #f8f9fa; border: none; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; color: #6c757d; }
        .tab.active { background: #28a745; color: #fff; }
        .tab:last-child.active { background: #dc3545; }
        .tab i { margin-right: 8px; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .form-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; }
        .form-section { margin-bottom: 30px; }
        .form-section h3 { color: #2c3e50; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: #2c3e50; margin-bottom: 8px; }
        .form-control { border: 2px solid #e9ecef; border-radius: 8px; padding: 12px; font-size: 1rem; transition: all 0.2s; }
        .form-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        
        .visitor-search { margin-bottom: 20px; }
        .search-input { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1rem; }
        .search-results { max-height: 200px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 8px; margin-top: 10px; background: #fff; }
        .search-result-item { padding: 12px; border-bottom: 1px solid #e9ecef; cursor: pointer; transition: background 0.2s; }
        .search-result-item:hover { background: #f8f9fa; }
        .search-result-item:last-child { border-bottom: none; }
        
        .equipment-section { margin-bottom: 20px; }
        .equipment-item { display: flex; align-items: center; gap: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
        .equipment-item input[type="text"] { flex: 1; }
        .equipment-item button { background: #dc3545; color: #fff; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 1rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-1px); }
        .btn-success { background: #28a745; color: #fff; }
        .btn-success:hover { background: #1e7e34; transform: translateY(-1px); }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; transform: translateY(-1px); }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-secondary:hover { background: #545b62; transform: translateY(-1px); }
        
        .table-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); padding: 30px; margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        .table tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; }
            .sidebar { transform: translateX(-100%); }
            .tabs { flex-direction: column; }
            .tab { text-align: center; }
        }
    </style>
</head>
<body>
    <header class="header">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div style="font-size: 1.3rem; font-weight: bold; letter-spacing: 1px;">Visitor Entry/Exit Management</div>
        <div class="user-info">
            <div class="user-avatar">S</div>
            <span>Security Officer</span>
            <button onclick="logout()" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.9rem;">Logout</button>
        </div>
    </header>
    
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <div class="menu-section">
                <h3>Security</h3>
                <a href="/Capstone_project/pages/dashboard_security.php" class="menu-item"><i class="fas fa-shield-alt"></i> Dashboard</a>
                <a href="/Capstone_project/pages/visitor_form.php" class="menu-item"><i class="fas fa-user-plus"></i> Register Visitor</a>
                <a href="/Capstone_project/pages/visitors.php" class="menu-item"><i class="fas fa-users"></i> Manage Visitors</a>
                <a href="/Capstone_project/pages/logs.php" class="menu-item"><i class="fas fa-clipboard-list"></i> Student Entry/Exit Logs</a>
                <a href="/Capstone_project/pages/visitor_logs.php" class="menu-item active"><i class="fas fa-address-book"></i> Visitor Entry/Exit Logs</a>
            </div>
            <div class="menu-section">
                <h3>Account</h3>
                <a href="/Capstone_project/change_password.php" class="menu-item"><i class="fas fa-key"></i> Change Password</a>
                <a href="/Capstone_project/logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Visitor Entry/Exit Management</h1>
            <p class="page-subtitle">Manage visitor access and track entry/exit activities</p>
        </div>
        
        <div class="tabs">
            <button type="button" class="tab active" onclick="switchTab('entry', event)">
                <i class="fas fa-sign-in-alt"></i> Entry
            </button>
            <button type="button" class="tab" onclick="switchTab('exit', event)">
                <i class="fas fa-sign-out-alt"></i> Exit
            </button>
        </div>
        
        <!-- Entry Tab Content -->
        <div id="entry-tab" class="tab-content active">
            <div class="form-container">
                <div class="form-section">
                    <h3><i class="fas fa-user-plus"></i> Visitor Entry Form</h3>
                    <form action="save_vistor_entry.php" method="post">
                        <div class="form-group">
                            <label>Visitor Name</label>
                            <select id="entry-department" class="form-control" name="vname">
                                <option value="">Select Name</option>
                                <?php
                                $conn = new mysqli('localhost', 'root', '', 'gate_management_system');
                                if ($conn->connect_error) {
                                    die("Connection failed: " . $conn->connect_error);
                                }
                                $result = $conn->query("SELECT * FROM vistor");
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['visitor_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No visitors found</option>';
                                }
                                $conn->close();
                                ?>

                            </select>

                        </div>
                        <div class="form-group">
                            <label>Purpose of Visit</label>
                            <input type="text" id="entry-purpose" name="purpose" class="form-control" placeholder="Enter purpose of visit" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select id="entry-department" class="form-control" name="dept" required>
                                <option value="">Select Department</option>
                                <option value="IT">IT</option>
                                <option value="Administration">Administration</option>
                            </select>
                        </div>
                        <div class="form-section">
                            <h3><i class="fas fa-laptop"></i> Equipment Brought</h3>
                            <div id="entry-equipment-list" class="equipment-section">
                                <div class="equipment-item">
                                    <input type="text" placeholder="Equipment name (e.g., Laptop, Mobile Phone)" name="equipment[]" class="form-control">
                                    <button type="button" onclick="removeEquipment(this)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" onclick="addEquipment('entry')" class="btn btn-secondary">
                                <i class="fas fa-plus"></i> Add Equipment
                            </button>
                        </div>
                        <div class="form-section">
                            <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                            <textarea id="entry-notes" class="form-control" rows="3" name="notes" placeholder="Any additional notes or observations..."></textarea>
                        </div>
                        <div class="form-section">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Record Entry
                            </button>
                            <button type="button" onclick="resetForm('entry')" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Entry Logs Table -->
            <div class="table-container">
                <h3><i class="fas fa-list"></i> Today's Entry Logs</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Name</th>
                            <th>ID Number</th>
                            <th>Department</th>
                            <th>Purpose</th>
                            <th>Equipment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="entry-logs-table">
                        <?php
                        $conn=new mysqli("localhost","root","","gate_management_system");
                        $sql="SELECT * from entry_visitor e,vistor v where e.visitor_id=v.id";
                        $result=$conn->query($sql);
                        while ($row=$result->fetch_assoc())
                         {?>
                              <tr>
                            <td><?php echo $row['entry_time'];?></td>
                            <td><?php echo $row['visitor_name'];?></td>
                            <td><?php echo $row['id_number'];?></td>
                            <td><?php echo $row['department']?></td>
                            <td><?php echo $row['purpose'];?></td>
                            <td><?php echo $row['equipment'];?></td>
                            <td><span class="badge badge-success">Inside</span></td>
                        </tr>
                     <?php   }
                        ?>
                    </tbody>
                </table>
            </div>
        </div> 
        
        <!-- Exit Tab Content -->
        <div id="exit-tab" class="tab-content">
            <div class="form-container">
                <form action="save_vistor_exit.php" method="post" id="exit-form">
                    <div class="form-section">
                        <h3><i class="fas fa-sign-out-alt"></i> Visitor Exit Form</h3>
                        <div class="form-group">
                            <label>Visitor Name</label>
                            <select id="entry-department" class="form-control" name="vname">
                                <option value="">Select Name</option>
                                <?php
                                $conn = new mysqli('localhost', 'root', '', 'gate_management_system');
                                if ($conn->connect_error) {
                                    die("Connection failed: " . $conn->connect_error);
                                }
                                $result = $conn->query("SELECT * FROM vistor");
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['visitor_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No visitors found</option>';
                                }
                                $conn->close();
                                ?>

                            </select>

                        </div>
                    </div>
                    <div class="form-section">
                        <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                        <textarea id="exit-notes" class="form-control" rows="3" name="notes" placeholder="Any additional notes or observations..." required></textarea>
                    </div>
                    <div class="form-section">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-sign-out-alt"></i> Record Exit
                        </button>
                        <button type="button" onclick="resetForm('exit')" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Exit Logs Table -->
            <div class="table-container">
                <h3><i class="fas fa-list"></i> Today's Exit Logs</h3>
                <table class="table">
                    <thead>
                        <tr>
                           
                            <th>Exit Time</th>
                            <th>Name</th>
                            <th>ID Number</th>
                           
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="entry-logs-table">
                        <?php
                        $conn=new mysqli("localhost","root","","gate_management_system");
                        $sql="SELECT * from exit_visitor e,vistor v where e.visitor_id=v.id";
                        $result=$conn->query($sql);
                        while ($row=$result->fetch_assoc())
                         {?>
                              <tr>
                            <td><?php echo $row['exit_time'];?></td>
                            <td><?php echo $row['visitor_name'];?></td>
                            <td><?php echo $row['id_number'];?></td>
                            <td><span class="badge badge-success">OutSide</span></td>
                        </tr>
                     <?php   }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('closed');
        }

        function switchTab(tabName, event) {
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Activate the correct tab button
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }

            // Activate the correct tab content
            const tabContent = document.getElementById(tabName + '-tab');
            if (tabContent) {
                tabContent.classList.add('active');
            }

            // Clear any alerts when switching tabs
            clearAlerts();
        }

        function addEquipment(type) {
            const equipmentList = document.getElementById(`${type}-equipment-list`);
            const newItem = document.createElement('div');
            newItem.className = 'equipment-item';
            newItem.innerHTML = `
                <input type="text" placeholder="Equipment name (e.g., Laptop, Mobile Phone)" name="equipment[]" class="form-control">
                <button type="button" onclick="removeEquipment(this)" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            equipmentList.appendChild(newItem);
        }

        function removeEquipment(button) {
            button.parentElement.remove();
        }

        function resetForm(type) {
            if (type === 'entry') {
                document.getElementById('entry-visitor-name').value = '';
                document.getElementById('entry-purpose').value = '';
                document.getElementById('entry-department').value = '';
                document.getElementById('entry-notes').value = '';
                
                // Reset equipment list
                const equipmentList = document.getElementById('entry-equipment-list');
                equipmentList.innerHTML = `
                    <div class="equipment-item">
                        <input type="text" placeholder="Equipment name (e.g., Laptop, Mobile Phone)" name="equipment[]" class="form-control">
                        <button type="button" onclick="removeEquipment(this)" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            } else if (type === 'exit') {
                document.getElementById('exit-visitor-name').value = '';
                document.getElementById('exit-notes').value = '';
            }
            
            clearAlerts();
        }

        function showAlert(message, type) {
            clearAlerts();
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            // Insert alert at the top of the active tab content
            const activeTab = document.querySelector('.tab-content.active');
            activeTab.insertBefore(alertDiv, activeTab.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        function clearAlerts() {
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '/Capstone_project/logout.php';
            }
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            clearAlerts();
            
            // Add form submission handler for better UX
            document.getElementById('exit-form').addEventListener('submit', function(e) {
                const visitorName = document.getElementById('exit-visitor-name').value;
                const notes = document.getElementById('exit-notes').value.trim();
                
                if (!visitorName || !notes) {
                    e.preventDefault();
                    showAlert('Please fill in all required fields.', 'danger');
                    return false;
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            });
        });
    </script>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
    </footer>
</body>
</html>