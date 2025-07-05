<?php
require_once '../includes/header.php';
require_once '../config/config.php';

// Get student_id or reg_no from GET
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$reg_no = isset($_GET['reg_no']) ? trim($_GET['reg_no']) : null;

$student = null;
$error = '';

try {
    $db = getDB();
    if ($student_id) {
        $student = $db->fetch("SELECT * FROM students WHERE id = ?", [$student_id]);
    } elseif ($reg_no) {
        $student = $db->fetch("SELECT * FROM students WHERE registration_number = ?", [$reg_no]);
    }
    if (!$student) {
        $error = 'Student not found.';
    }
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Example photo path logic (replace with your actual logic)
$photo_path = isset($student['photo']) && $student['photo'] ? '../assets/images/students/' . $student['photo'] : '../assets/images/default_photo.png';
?>
<style>
.student-info-card {
    border: 2px solid #333;
    width: 450px;
    padding: 20px;
    background: #fff;
    font-family: Arial, sans-serif;
    margin: 40px auto;
}
.student-info-card .header {
    text-align: center;
    font-weight: bold;
    margin-bottom: 10px;
    font-size: 1.2rem;
}
.student-info-card .info-grid {
    display: flex;
    justify-content: space-between;
}
.student-info-card .info-fields {
    flex: 2;
}
.student-info-card .info-fields p {
    margin: 6px 0;
    font-size: 1rem;
}
.student-info-card .photo-box {
    flex: 1;
    border: 1px solid #333;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 20px;
    background: #f8f8f8;
}
.student-info-card .photo-box img {
    max-width: 100%;
    max-height: 100%;
}
</style>
<main class="main-content">
    <div class="content-wrapper">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
        <div class="student-info-card">
            <div class="header">
                <div>UR CE - RUKARA</div>
                <div>STUDENT INFO</div>
            </div>
            <div class="info-grid">
                <div class="info-fields">
                    <p><strong>Names:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                    <p><strong>Reg No:</strong> <?php echo htmlspecialchars($student['registration_number']); ?></p>
                    <p><strong>Year of Study:</strong> <?php echo htmlspecialchars($student['year_of_study']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department']); ?></p>
                    <p><strong>Program:</strong> <?php echo htmlspecialchars($student['program']); ?></p>
                    <p><strong>Study Mode:</strong> <?php echo htmlspecialchars($student['is_active'] ? 'Active' : 'Inactive'); ?></p>
                    <p><strong>Serial number:</strong> <?php echo htmlspecialchars($student['id']); ?></p>
                </div>
                <div class="photo-box">
                    <img src="<?php echo $photo_path; ?>" alt="Student Photo" />
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?> 