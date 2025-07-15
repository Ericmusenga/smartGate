<?php
session_start();
include '../db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$msg = "";

// Disable a card
if (isset($_GET['disable_id'])) {
    $card_id = $_GET['disable_id'];
    $stmt = $conn->prepare("UPDATE rfid_cards SET status = 'disabled' WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $card_id, $student_id);
    if ($stmt->execute()) {
        $msg = "Card disabled successfully.";
    }
}

// Get cards
$stmt = $conn->prepare("SELECT * FROM rfid_cards WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$cards = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My RFID Cards</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h2>My RFID Cards</h2>

    <?php if ($msg): ?>
        <p style="color: green;"><?php echo $msg; ?></p>
    <?php endif; ?>

    <?php if ($cards->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Card UID</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $cards->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['card_uid']); ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td><?php echo $row['registered_at']; ?></td>
                        <td>
                            <?php if ($row['status'] === 'active'): ?>
                                <a href="?disable_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure to disable this card?');">
                                    Disable
                                </a>
                            <?php else: ?>
                                <span style="color: gray;">Disabled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No cards registered yet.</p>
    <?php endif; ?>
</div>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> Gate Management System - UR College of Education, Rukara Campus
</footer>
</body>
</html>
