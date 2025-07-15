<?php
require_once '../config/config.php';

$conn = new mysqli("localhost", "root", "", "gate_management_system");

// Check connection
if ($conn->connect_error) {
    die("Error of database Connection: " . $conn->connect_error);
} 

// Check if session user_id exists
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

// Query to get computer lending records
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM computer_lending WHERE borrower_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result(); // Get result from prepared statement, not from query()

// Initialize variables
$lender_id = null;
$student_id = null;
$result_student = null;
$lending_data = null;

// Check if there are results and fetch the data
if ($result && $result->num_rows > 0) {
    $lending_data = $result->fetch_assoc(); // Store the lending data
    $lender_id = $lending_data['lender_id'];
    
    // Get user details
    $sql_details = "SELECT * FROM users WHERE id = ?";
    $stmt_details = $conn->prepare($sql_details);
    $stmt_details->bind_param("i", $lender_id);
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    if ($result_details && $result_details->num_rows > 0) {
        $user_row = $result_details->fetch_assoc();
        $student_id = $user_row['student_id'];
        
        // Get student details
        $sql_student = "SELECT * FROM students WHERE id = ?";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lend My Computer</title>
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
      color: white;
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

    .sidebar-item i {
      margin-right: 0.75rem;
      font-size: 1.1rem;
    }

    .main-content {
      flex: 1;
      padding: 2rem;
      background-color: #f5f5f5;
      margin-bottom: 80px; /* Add bottom margin for fixed footer */
    }
    
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 1rem 2rem;
      text-align: center;
      color: #7f8c8d;
      border-top: 1px solid rgba(0, 0, 0, 0.1);
      z-index: 1000;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .form-container {
      background-color: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: auto;
    }

    h2 {
      margin-bottom: 1.5rem;
      text-align: center;
      color: #2196F3;
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
      border: 1px solid #ccc;
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
      width: 100%;
      margin-top: 1rem;
    }

    .btn:hover {
      background-color: #1976D2;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 1rem;
      text-decoration: none;
      color: #2196F3;
      font-weight: 500;
    }

    .back-link:hover {
      text-decoration: underline;
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

      .form-container {
        margin: 0 auto;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <div>
      <h1>🏫 Gate Management System</h1>
      <div class="header-info">UR College of Education, Rukara Campus</div>
    </div>
    <div class="user-info">
      <div class="user-avatar">N</div>
      <div>
        <div><?php echo $_SESSION['first_name']; ?></div>
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
       
        <a class="sidebar-item active" href="/Capstone_project/pages/Approve.php">
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
     <div class="form-container">
  <h2>Pending Computer Lending Requests</h2>
  <table style="width: 100%; border-collapse: collapse; text-align: left;">
    <thead style="background-color: #e3f2fd;">
      <tr>
        <th style="padding: 0.75rem; border-bottom: 1px solid #ddd;">Student Name</th>
        <th style="padding: 0.75rem; border-bottom: 1px solid #ddd;">Serial Number</th>
        <th style="padding: 0.75rem; border-bottom: 1px solid #ddd;">Borrower Reg No</th>
        <th  style="padding: 0.75rem; border-bottom: 1px solid #ddd;">Date</th>
        <th style="padding: 0.75rem; border-bottom: 1px solid #ddd;">Status</th>
        <th style="padding: 0.75rem; border-bottom: 1px solid #ddd;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php 
              // Check if there are results
              if ($result_student && $result_student->num_rows > 0) {
                  // Fetch and display each registration number
                  while($row = $result_student->fetch_assoc()) {?>
                     <tr>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $row['first_name']?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $row['serial_number']?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $lending_data['borrower_reg_number']?></td>
                        
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $lending_data['created_at']?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $lending_data['status'];?></td>
                        <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                          <?php if ($lending_data['status']=='pending') {
                          ?>
                          <form method="GET" action="process_lend.php" style="display: flex; gap: 0.5rem;">
                            <input type="hidden" name="id" value="<?php echo $lending_data['id']?>" />
                            <button name="accept" value="accept" class="btn" style="background-color: #4CAF50;">Accept</button>
                            <button name="reject" value="reject" class="btn" style="background-color: #f44336;">Reject</button>
                          </form>
                          <?php
                          }
                          else
                          {
                          echo "No Action yet";
                          }?>
                        </td>
                      </tr>
              <?php    }
                    
              } else {
                  echo '<tr><td colspan="5" style="padding: 0.75rem; text-align: center;">No Request Found</td></tr>';
              }
              ?>
      <!-- Example row - you should generate this dynamically using PHP -->
     

      <!-- You can repeat more rows dynamically -->
    </tbody>
  </table>
  <a href="dashboard_student.php" class="back-link">← Back to Dashboard</a>
</div>

    </main>
  </div>
  
  <footer class="footer">
    &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
  </footer>
</body>
</html>