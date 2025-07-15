
<?php
require_once '../config/config.php';
?>
<?php
$conn = new mysqli("localhost", "root", "", "gate_management_system");

// Correct error checking
if ($conn->connect_error) {
    die("Error of database Connection: " . $conn->connect_error);
} 

// Query to get students
$sql = "SELECT * FROM users WHERE role_id=4";
$result = $conn->query($sql);
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
        <a class="sidebar-item active" href="/Capstone_project/pages/lend_computer.php">
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
      <div class="form-container">
        <h2>Lend My Computer</h2>
        <form id="lendForm" action="submit_lend.php" method="POST">
          <div class="form-group">
            <label for="deviceSelect">Select Reg Number:</label>
            <select id="deviceSelect" name="reg_number" required>
              <option value="">Choose a Reg Number...</option>
              <?php 
              // Check if there are results
              if ($result && $result->num_rows > 0) {
                  // Fetch and display each registration number
                  while($row = $result->fetch_assoc()) {
                      echo '<option value="' . htmlspecialchars($row['id']) . '">' . 
                           htmlspecialchars($row['username']) . '. ' . htmlspecialchars($row['last_name']) . '</option>';
                  }
              } else {
                  echo '<option value="">No registration numbers found</option>';
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label for="lendNotes">Additional Notes:</label>
            <textarea id="lendNotes" name="notes" rows="3" placeholder="Any special instructions or notes..."></textarea>
          </div>

          <button type="submit" class="btn">Lend Computer</button>  
        </form>
        <a href="dashboard_student.php" class="back-link">← Back to Dashboard</a>
      </div>
    </main>
  </div>
  
  <footer class="footer">
    &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
  </footer>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>