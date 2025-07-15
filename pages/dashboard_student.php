<?php
require_once '../config/config.php';

$conn = new mysqli("localhost", "root", "", "gate_management_system");

// Check connection
if ($conn->connect_error) {
    die("Error of database Connection: " . $conn->connect_error);
} 
$lender_id=$_SESSION['user_id'];
$sql="SELECT * from computer_lending where lender_id=$lender_id";
$result=$conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <center>Gate Management System - Student Dashboard</center></title>
      <link rel="stylesheet" href="../fontawesome/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background-color: #2196F3;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header-info {
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #1976D2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #d32f2f;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        .sidebar {
            width: 250px;
            background-color: white;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-title {
            color: #666;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .sidebar-item:hover {
            background-color: #f5f5f5;
        }

        .sidebar-item.active {
            background-color: #e3f2fd;
            color: #2196F3;
            border-left-color: #2196F3;
        }

        .sidebar-item-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f5f5f5;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.computer { background-color: #2196F3; }
        .stat-icon.share { background-color: #4CAF50; }
        .stat-icon.borrow { background-color: #FF9800; }
        .stat-icon.card { background-color: #9C27B0; }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stat-content p {
            color: #666;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            background: white;
            border: 2px solid #2196F3;
            color: #2196F3;
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            font-weight: 600;
        }

        .action-btn:hover {
            background-color: #2196F3;
            color: white;
        }

        .content-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover { 
            background-color: #f9f9f9;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-available {
            background-color: #e8f5e8;
            color: #2e7d32;
        }

        .status-borrowed {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-overdue {
            background-color: #ffebee;
            color: #c62828;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
        }

        .btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #1976D2;
        }

        .btn-secondary {
            background-color: #666;
        }

        .btn-secondary:hover {
            background-color: #555;
        }

        .btn-danger {
            background-color: #f44336;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        .hidden {
            display: none !important;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem;
            border-radius: 4px;
            color: white;
            font-weight: 600;
            z-index: 1001;
            transform: translateX(100%);
            transition: transform 0.3s;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background-color: #4CAF50;
        }

        .notification.error {
            background-color: #f44336;
        }

        .notification.info {
            background-color: #2196F3;
        }

        .windows-activation {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(0,0,0,0.8);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            max-width: 300px;
        }

        .windows-activation h4 {
            margin-bottom: 0.5rem;
        }

        .windows-activation p {
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                left: -100%;
                top: 80px;
                height: calc(100vh - 80px);
                z-index: 999;
                transition: left 0.3s;
            }
            .sidebar.open {
                left: 0;
            }
            .main-content {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
        .switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
}
.switch input { display: none; }
.slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 26px;
}
.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}
input:checked + .slider {
  background-color: #2196F3;
}
input:checked + .slider:before {
  transform: translateX(22px);
}
.sidebar-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 0.75rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    min-width: 45px;
    height: 45px;
    flex-shrink: 0;
    margin-right: 1.5rem;
}
.sidebar-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
}
.sidebar-toggle i {
    font-size: 1.2rem;
}
</style>
</head>
<body>
    <div class="header">
        <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div>
            <h1>Gate Management System</h1>
            <div class="header-info">UR College of Education, Rukara Campus</div>
        </div>
        <div class="user-info">
            <div class="user-avatar">S</div>
            <div>
                <div><?php echo $_SESSION['first_name'];?></div>
                <div style="font-size: 0.8rem; opacity: 0.9;">(Student)</div>
            </div>
              <form action="/Capstone_project/logout.php" method="post">
                  <button type="submit" class="btn btn-danger">Logout</button>
              </form>
        </div>
    </div>

   

    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">Student Portal</div>
                <a class="sidebar-item" href="/Capstone_project/pages/dashboard_student.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                
                <a class="sidebar-item" href="/Capstone_project/pages/lend_computer.php">
                   <i class="fas fa-share-alt"></i>
                    Lend My Computer
                </a>
                <a class="sidebar-item" href="/Capstone_project/pages/return_computer.php">
                     <i class="fas fa-undo"></i>
                    Return My Computer
                </a>
                <a class="sidebar-item" href="/Capstone_project/pages/Approve.php">
                    <i class="fas fa-laptop-house"></i>
                    Approve
                </a>   
               
               
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">Account</div>
                <a class="sidebar-item" href="/Capstone_project/pages/profile.php">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </div>
        </nav>

        <main class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section">
                <div class="dashboard-header">
                    <h2 class="dashboard-title">💻 Student Dashboard</h2>
                    <p class="dashboard-subtitle">Welcome, Dear Student</p>
                </div>

                
                <div class="content-section">
                    <h3 class="section-title">Recent Activity</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Notes</th>
                                <th>Device</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivity">
                            <?php
                            while ($row=$result->fetch_assoc())
                            {?>
                                 <tr>
                                <td><?php echo $row['lend_date'];?></td>
                                <td><?php echo $row['notes'];?></td>
                                <td>HP Desktop #003</td>
                                <td><span class="status-badge status-available"><?php echo $row['status'];?></span></td>
                            </tr>
                                
                           <?php }
                            
                            ?>
                           
                           
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- My Devices Section -->
            <div id="devices" class="content-section hidden">
                <h2 class="section-title">💻 My Devices</h2>
                <div class="action-buttons">
                    <button class="action-btn" onclick="showAddDeviceModal()">
                        ➕ Add New Device
                    </button>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>Type</th>
                            <th>Serial Number</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="devicesTable">
                        <tr>
                            <td>Dell Inspiron 15</td>
                            <td>Laptop</td>
                            <td>DL123456789</td>
                            <td><span class="status-badge status-available">Available</span></td>
                            <td>
                                <button class="btn" onclick="lendDevice('Dell Inspiron 15')">Lend</button>
                                <button class="btn btn-secondary" onclick="editDevice('Dell Inspiron 15')">Edit</button>
                            </td>
                        </tr>
                        <tr>
                            <td>HP Pavilion Desktop</td>
                            <td>Desktop</td>
                            <td>HP987654321</td>
                            <td><span class="status-badge status-borrowed">Lent Out</span></td>
                            <td>
                                <button class="btn btn-secondary" disabled>Lent to John Doe</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Lend Computer Section -->
            <div id="lend" class="content-section hidden">
                <h2 class="section-title">📤 Lend My Computer</h2>
                <form id="lendForm">
                    <div class="form-group">
                        <label for="deviceSelect">Select Device to Lend:</label>
                        <select id="deviceSelect" required>
                            <option value="">Choose a device...</option>
                            <option value="dell-inspiron">Dell Inspiron 15</option>
                            <option value="lenovo-thinkpad">Lenovo ThinkPad</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="borrowerName">Borrower Name:</label>
                        <input type="text" id="borrowerName" required>
                    </div>
                    <div class="form-group">
                        <label for="borrowerEmail">Borrower Email:</label>
                        <input type="email" id="borrowerEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="lendDuration">Lending Duration (days):</label>
                        <input type="number" id="lendDuration" min="1" max="30" required>
                    </div>
                    <div class="form-group">
                        <label for="lendNotes">Additional Notes:</label>
                        <textarea id="lendNotes" rows="3" placeholder="Any special instructions or notes..."></textarea>
                    </div>
                    <button type="submit" class="btn">📤 Lend Computer</button>
                </form>
            </div>

            <!-- Return Computer Section -->
            <div id="return" class="content-section hidden">
                <h2 class="section-title">🔄 Return My Computer</h2>
                <div id="returnList">
                    <div class="content-section">
                        <h3>Currently Lent Devices</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>S.Number</th>
                                    <th>Borrower</th>
                                    <th>Lent Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>HP Pavilion Desktop</td>
                                    <td>John Doe</td>
                                    <td>2025-07-05</td>
                                    <td>2025-07-12</td>
                                    <td><span class="status-badge status-borrowed">On Loan</span></td>
                                    <td>
                                        <button class="btn" onclick="returnDevice('HP Pavilion Desktop')">Mark as Returned</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Borrowed Computers Section -->
            <div id="borrowed" class="content-section hidden">
                <h2 class="section-title">📥 My Borrowed Computers</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>S.Number</th>
                            <th>Owner</th>
                            <th>Borrowed Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="borrowedTable">
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 2rem;">
                                No borrowed computers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- My Cards Section -->
            <div id="cards" class="content-section hidden">
                <h2 class="section-title">🎫 My Cards</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon card">🎫</div>
                        <div class="stat-content">
                            <h3>Student ID</h3>
                            <p>Active - Expires: Dec 2025</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon card">🍽️</div>
                        <div class="stat-content">
                            <h3>Dining Card</h3>
                            <p>Balance: $127.50</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon card">📚</div>
                        <div class="stat-content">
                            <h3>Library Card</h3>
                            <p>Active - 3 books borrowed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entry/Exit Logs Section -->
            <div id="logs" class="content-section hidden">
                <h2 class="section-title">📋 Entry/Exit Logs</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th>Gate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2025-07-08 14:30</td>
                            <td>Entry</td>
                            <td>Main Gate</td>
                            <td><span class="status-badge status-available">Successful</span></td>
                        </tr>
                        <tr>
                            <td>2025-07-08 08:15</td>
                            <td>Entry</td>
                            <td>Main Gate</td>
                            <td><span class="status-badge status-available">Successful</span></td>
                        </tr>
                        <tr>
                            <td>2025-07-07 17:45</td>
                            <td>Exit</td>
                            <td>Main Gate</td>
                            <td><span class="status-badge status-available">Successful</span></td>
                        </tr>
                        <tr>
                            <td>2025-07-07 08:30</td>
                            <td>Entry</td>
                            <td>Main Gate</td>
                            <td><span class="status-badge status-available">Successful</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Profile Section -->
            <div id="profile" class="content-section hidden">
                <h2 class="section-title">👤 Profile</h2>
                <form id="profileForm">
                    <div class="form-group">
                        <label for="fullName">Full Name:</label>
                        <input type="text" id="fullName" value="Norah Natasha" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" value="norah.natasha@ur.ac.rw" required>
                    </div>
                    <div class="form-group">
                        <label for="studentId">Student ID:</label>
                        <input type="text" id="studentId" value="2023-STU-001" readonly>
                    </div>
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <input type="text" id="department" value="Computer Science" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" value="+250 123 456 789">
                    </div>
                    <button type="submit" class="btn">💾 Update Profile</button>
                </form>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBorrowModal()">&times;</span>
            <h2>📥 Borrow a Computer</h2>
            <form id="borrowForm">
                <div class="form-group">
                    <label for="searchOwner">Search by Owner Name:</label>
                    <input type="text" id="searchOwner" placeholder="Enter owner's name...">
                </div>
                <div class="form-group">
                    <label for="availableDevices">Available Devices:</label>
                    <select id="availableDevices" required>
                        <option value="">Select a device...</option>
                        <option value="jane-macbook">Jane Smith - MacBook Pro</option>
                        <option value="mike-dell">Mike Johnson - Dell XPS</option>
                        <option value="sarah-hp">Sarah Wilson - HP Pavilion</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="borrowDuration">Borrow Duration (days):</label>
                    <input type="number" id="borrowDuration" min="1" max="30" required>
                </div>
                <div class="form-group">
                    <label for="borrowReason">Reason for Borrowing:</label>
                    <textarea id="borrowReason" rows="3" placeholder="Why do you need this computer?" required></textarea>
                </div>
                <button type="submit" class="btn">📥 Send Borrow Request</button>
            </form>
        </div>
    </div>

  <?php include '../includes/footer.php'?>
<script>
function toggleDashboardOption() {
    var toggle = document.getElementById('dashboardToggle');
    var status = document.getElementById('toggleStatus');
    status.textContent = toggle.checked ? 'On' : 'Off';
}
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}
</script>