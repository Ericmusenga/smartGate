# API Integration Guide

This guide explains how to integrate the Entry/Exit API with your existing system and external devices.

## Quick Start

### 1. Test API Connection
First, test if the API is accessible:

```bash
curl -H "X-API-Key: gate_system_2024" \
     http://your-domain.com/Capstone_project/api/test.php
```

### 2. Process RFID Card
Process a card scan:

```bash
curl -X POST \
     -H "X-API-Key: gate_system_2024" \
     -H "Content-Type: application/json" \
     -d '{"card_number": "RFID2023001", "gate_number": 1}' \
     http://your-domain.com/Capstone_project/api/entry_exit/process.php
```

## Integration Methods

### 1. Web Interface Integration

Add this JavaScript to your existing web pages:

```javascript
// Function to process RFID card
async function processRFIDCard(cardNumber, gateNumber) {
    try {
        const response = await fetch('/api/entry_exit/process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': 'gate_system_2024'
            },
            body: JSON.stringify({
                card_number: cardNumber,
                gate_number: gateNumber
            })
        });
        
        const result = await response.json();
        
        if (result.status === 200) {
            // Success - show notification
            showNotification(`${result.data.action} successful for ${result.data.student.name}`);
            return result.data;
        } else {
            // Error - show error message
            showError(result.message);
            return null;
        }
    } catch (error) {
        console.error('API Error:', error);
        showError('Network error occurred');
        return null;
    }
}

// Function to get student information
async function getStudentInfo(cardNumber) {
    try {
        const response = await fetch(`/api/entry_exit/student_info.php?card_number=${cardNumber}`, {
            headers: {
                'X-API-Key': 'gate_system_2024'
            }
        });
        
        const result = await response.json();
        return result.status === 200 ? result.data : null;
    } catch (error) {
        console.error('API Error:', error);
        return null;
    }
}

// Function to get campus status
async function getCampusStatus() {
    try {
        const response = await fetch('/api/entry_exit/status.php', {
            headers: {
                'X-API-Key': 'gate_system_2024'
            }
        });
        
        const result = await response.json();
        return result.status === 200 ? result.data : null;
    } catch (error) {
        console.error('API Error:', error);
        return null;
    }
}
```

### 2. PHP Integration

Add this PHP code to your existing pages:

```php
<?php
// Include API configuration
require_once 'api/config.php';

// Function to process RFID card
function processRFIDCard($cardNumber, $gateNumber) {
    $url = 'http://your-domain.com/Capstone_project/api/entry_exit/process.php';
    
    $data = [
        'card_number' => $cardNumber,
        'gate_number' => $gateNumber
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: gate_system_2024',
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

// Function to get student information
function getStudentInfo($cardNumber) {
    $url = "http://your-domain.com/Capstone_project/api/entry_exit/student_info.php?card_number=" . urlencode($cardNumber);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: gate_system_2024'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

// Usage example
if (isset($_POST['card_number'])) {
    $result = processRFIDCard($_POST['card_number'], $_POST['gate_number']);
    if ($result) {
        echo "Success: " . $result['message'];
    } else {
        echo "Error processing card";
    }
}
?>
```

### 3. RFID Reader Integration

For hardware RFID readers, create a simple interface:

```html
<!DOCTYPE html>
<html>
<head>
    <title>RFID Reader Interface</title>
    <script>
        // Listen for RFID reader input (usually acts as keyboard)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const cardNumber = document.getElementById('cardInput').value;
                if (cardNumber) {
                    processCard(cardNumber);
                    document.getElementById('cardInput').value = '';
                }
            }
        });

        async function processCard(cardNumber) {
            try {
                const response = await fetch('/api/entry_exit/process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': 'gate_system_2024'
                    },
                    body: JSON.stringify({
                        card_number: cardNumber,
                        gate_number: 1 // Set your gate number
                    })
                });

                const result = await response.json();
                
                if (result.status === 200) {
                    // Show success message
                    document.getElementById('status').innerHTML = 
                        `<div class="success">${result.data.action} successful for ${result.data.student.name}</div>`;
                } else {
                    // Show error message
                    document.getElementById('status').innerHTML = 
                        `<div class="error">${result.message}</div>`;
                }
            } catch (error) {
                document.getElementById('status').innerHTML = 
                    '<div class="error">Network error</div>';
            }
        }
    </script>
    <style>
        .success { color: green; }
        .error { color: red; }
        #cardInput { display: none; } /* Hide input, RFID reader will fill it */
    </style>
</head>
<body>
    <input type="text" id="cardInput" autofocus>
    <div id="status"></div>
</body>
</html>
```

### 4. Mobile App Integration

For mobile applications, use standard HTTP requests:

```javascript
// React Native / Flutter / Native App
const API_BASE = 'http://your-domain.com/Capstone_project/api';

// Process card
async function processCard(cardNumber, gateNumber) {
    const response = await fetch(`${API_BASE}/entry_exit/process.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-Key': 'gate_system_2024'
        },
        body: JSON.stringify({
            card_number: cardNumber,
            gate_number: gateNumber
        })
    });
    
    return await response.json();
}

// Get student info
async function getStudentInfo(cardNumber) {
    const response = await fetch(`${API_BASE}/entry_exit/student_info.php?card_number=${cardNumber}`, {
        headers: {
            'X-API-Key': 'gate_system_2024'
        }
    });
    
    return await response.json();
}
```

## Dashboard Integration

### Add to Admin Dashboard

Add this to your admin dashboard to show real-time statistics:

```php
<?php
// Add to dashboard_admin.php
function getCampusStats() {
    $url = 'http://your-domain.com/Capstone_project/api/entry_exit/status.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: gate_system_2024'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$stats = getCampusStats();
?>

<!-- Add to dashboard HTML -->
<div class="card">
    <div class="card-header">
        <h5>Real-time Campus Status</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h3><?php echo $stats['data']['total_inside'] ?? 0; ?></h3>
                <p>Students Inside</p>
            </div>
            <div class="col-md-4">
                <h3><?php echo count($stats['data']['recent_entries'] ?? []); ?></h3>
                <p>Recent Entries</p>
            </div>
            <div class="col-md-4">
                <h3><?php echo count($stats['data']['gate_statistics'] ?? []); ?></h3>
                <p>Active Gates</p>
            </div>
        </div>
    </div>
</div>
```

### Add to Security Dashboard

Add this to your security dashboard for real-time monitoring:

```php
<?php
// Add to dashboard_security.php
function getRecentActivity() {
    $url = 'http://your-domain.com/Capstone_project/api/entry_exit/logs.php?limit=10';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: security_api_key'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$recentActivity = getRecentActivity();
?>

<!-- Add to security dashboard HTML -->
<div class="card">
    <div class="card-header">
        <h5>Recent Activity</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Student</th>
                        <th>Action</th>
                        <th>Gate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentActivity['data']['logs'] ?? [] as $log): ?>
                    <tr>
                        <td><?php echo date('H:i', strtotime($log['created_at'])); ?></td>
                        <td><?php echo $log['student']['name']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $log['status'] === 'entered' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($log['status']); ?>
                            </span>
                        </td>
                        <td>Gate <?php echo $log['gate_number']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
```

## Error Handling

Always implement proper error handling:

```javascript
async function safeApiCall(apiFunction, ...args) {
    try {
        const result = await apiFunction(...args);
        
        if (result && result.status === 200) {
            return result.data;
        } else {
            console.error('API Error:', result?.message || 'Unknown error');
            return null;
        }
    } catch (error) {
        console.error('Network Error:', error);
        return null;
    }
}

// Usage
const studentInfo = await safeApiCall(getStudentInfo, 'RFID2023001');
if (studentInfo) {
    // Process student info
} else {
    // Handle error
}
```

## Security Considerations

1. **API Keys**: Keep API keys secure and rotate them regularly
2. **HTTPS**: Use HTTPS in production
3. **Rate Limiting**: Respect rate limits to avoid being blocked
4. **Input Validation**: Always validate input before sending to API
5. **Error Logging**: Log errors for debugging but don't expose sensitive information

## Testing

Use the provided test tools:

1. **Test Interface**: `/api/entry_exit/test_interface.html`
2. **Test Script**: `/api/test_api.php`
3. **API Test**: `/api/test.php`

## Support

For integration support:
1. Check the API documentation: `/api/README.md`
2. Test with the provided interfaces
3. Review error logs for debugging
4. Contact system administrator for API key management 