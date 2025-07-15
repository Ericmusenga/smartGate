<?php
header("Access-Control-Allow-Origin: *");
define('DATA_FILE', 'logs.json');

// Database connection
$host = 'localhost';
$dbname = 'musengimana';
$username = 'root'; // change if different
$password = '';     // change if needed
$port = 4306;

$conn = new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event     = $_POST['event'] ?? 'UNKNOWN';
    $count     = intval($_POST['count'] ?? 0);
    $status    = $_POST['status'] ?? 'UNKNOWN';
    $timestamp = $_POST['timestamp'] ?? date('H:i:s');
    $uid       = $_POST['uid'] ?? 'UNKNOWN';
    $computer  = gethostbyaddr($_SERVER['REMOTE_ADDR']); // IP-based computer name

    // Save to JSON file (keep existing logic)
    $data = compact('event', 'count', 'status', 'timestamp', 'uid');
    $logs = file_exists(DATA_FILE) ? json_decode(file_get_contents(DATA_FILE), true) : [];
    if (!is_array($logs)) $logs = [];
    $logs[] = $data;
    file_put_contents(DATA_FILE, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Save to MySQL
    $stmt = $conn->prepare("INSERT INTO rfid_logs (uid, status, count, event, timestamp, computer, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssisss", $uid, $status, $count, $event, $timestamp, $computer);
    $stmt->execute();
    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode(["message" => "Saved to both JSON & DB", "data" => $data]);
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
    <h1>RFID Scan Logs (Live from Database)</h1>
    <?php
        // Display from DB instead of JSON
        $result = $conn->query("SELECT uid, status, count, event, timestamp, computer, created_at FROM rfid_logs ORDER BY created_at DESC LIMIT 50");

        if ($result->num_rows === 0) {
            echo "<p>No logs found in database.</p>";
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
