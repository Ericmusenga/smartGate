<?php
header("Access-Control-Allow-Origin: *");

// MySQL connection info
$host = 'localhost';
$dbname = 'gate_management_system';
$username = 'root'; // Change if you use another username
$password = '';     // Change if your DB has a password
$port = 4306;

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and receive POST data
    $event     = $_POST['event'] ?? 'UNKNOWN';
    $count     = intval($_POST['count'] ?? 0);
    $status    = $_POST['status'] ?? 'UNKNOWN';
    $timestamp = $_POST['timestamp'] ?? date('H:i:s');
    $uid       = $_POST['uid'] ?? 'UNKNOWN';
    $computer  = gethostbyaddr($_SERVER['REMOTE_ADDR']); // get computer name/IP

    // Prepare SQL insert
    $stmt = $conn->prepare("INSERT INTO rfid_logs (uid, status, count, event, timestamp, computer, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssisss", $uid, $status, $count, $event, $timestamp, $computer);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Saved", "data" => compact('uid', 'status', 'count', 'event', 'timestamp', 'computer')]);
    } else {
        echo json_encode(["error" => "Insert failed: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>RFID Scan Logs</title>
    <meta http-equiv="refresh" content="5">
    <style>
        body { font-family: monospace; background: #f9f9f9; padding: 20px; }
        h1 { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; max-width: 1000px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #eee; }
        tr:nth-child(even) { background-color: #fdfdfd; }
    </style>
</head>
<body>
    <h1>RFID Scan Logs (Live View)</h1>
    <?php
        // Fetch data from MySQL
        $result = $conn->query("SELECT uid, status, count, event, timestamp, computer, created_at FROM rfid_logs ORDER BY created_at DESC LIMIT 50");

        if ($result->num_rows === 0) {
            echo "<p>No logs found.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Timestamp</th><th>UID</th><th>Status</th><th>Count</th><th>Event</th><th>Computer</th><th>Created At</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['timestamp']) . "</td>";
                echo "<td>" . htmlspecialchars($row['uid']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>" . intval($row['count']) . "</td>";
                echo "<td>" . htmlspecialchars($row['event']) . "</td>";
                echo "<td>" . htmlspecialchars($row['computer']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        $conn->close();
    ?>
    <p>Auto-updated at: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
