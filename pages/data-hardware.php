<?php
// Updated Database Configuration
$dbHost = 'localhost';

$dbUser = 'root';
$dbPass = '';
$dbName = 'gate_management_system'; // Your database

// API endpoint
$apiUrl = 'https://ibitaro.jftech.rw/pazzo/fyp.txt';

// Connect to database with custom port
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents('fetch_error.log', date('Y-m-d H:i:s') . " - DB Connection Failed: " . $e->getMessage() . "\n", FILE_APPEND);
    exit("❌ Database connection failed.");
}

// Fetch data from API
$apiData = @file_get_contents($apiUrl);
if ($apiData === false) {
    file_put_contents('fetch_error.log', date('Y-m-d H:i:s') . " - Failed to fetch data from API\n", FILE_APPEND);
    exit("❌ Failed to fetch data.");
}

// Process each line
$lines = explode("\n", trim($apiData));
$insertCount = 0;

foreach ($lines as $line) {
    if (empty($line)) continue;

    $data = json_decode($line, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents('fetch_error.log', date('Y-m-d H:i:s') . " - JSON Error: " . json_last_error_msg() . "\n", FILE_APPEND);
        continue;
    }

    // Validate required fields
    if (!isset($data['server_timestamp'], $data['device_timestamp'], $data['event'], 
               $data['rfid_uid'], $data['passenger_count'], $data['status'])) {
        file_put_contents('fetch_error.log', date('Y-m-d H:i:s') . " - Missing fields: " . json_encode($data) . "\n", FILE_APPEND);
        continue;
    }

    try {
        // Avoid duplicate entries
        $check = $pdo->prepare("SELECT id FROM passenger_data WHERE server_timestamp = :timestamp AND rfid_uid = :uid");
        $check->execute([
            ':timestamp' => $data['server_timestamp'],
            ':uid' => $data['rfid_uid']
        ]);

        if ($check->rowCount() > 0) continue;

        // Insert data
        $stmt = $pdo->prepare("INSERT INTO passenger_data 
            (server_timestamp, device_timestamp, event, rfid_uid, passenger_count, status)
            VALUES (:server_ts, :device_ts, :event, :uid, :count, :status)");

        $stmt->execute([
            ':server_ts' => $data['server_timestamp'],
            ':device_ts' => $data['device_timestamp'],
            ':event'     => $data['event'],
            ':uid'       => $data['rfid_uid'],
            ':count'     => (int)$data['passenger_count'],
            ':status'    => $data['status']
        ]);

        $insertCount++;
    } catch (PDOException $e) {
        file_put_contents('fetch_error.log', date('Y-m-d H:i:s') . " - Insert Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Log success
file_put_contents('fetch_success.log', date('Y-m-d H:i:s') . " - Inserted $insertCount records\n", FILE_APPEND);

echo "✅ Done. Inserted $insertCount new records.";
?>