# Entry/Exit API Documentation

This API provides endpoints for managing student entry and exit activities using RFID cards and manual logging.

## Base URL
```
http://your-domain.com/Capstone_project/api/
```

## Authentication
All API endpoints require an API key to be passed in the request headers:
```
X-API-Key: your_api_key_here
```

### Valid API Keys
- `gate_system_2024` - Main system key
- `security_api_key` - Security officer key
- `admin_api_key` - Administrator key

## Endpoints

### 1. Process RFID Card Entry/Exit
**POST** `/entry_exit/process.php`

Processes RFID card scanning and automatically determines if it's an entry or exit based on current student status.

#### Request Body
```json
{
    "card_number": "RFID2023001",
    "gate_number": 1,
    "security_officer_id": 5,
    "notes": "Optional notes",
    "entry_method": "rfid"
}
```

#### Required Fields
- `card_number` - RFID card number
- `gate_number` - Gate number (1-10)

#### Optional Fields
- `security_officer_id` - ID of security officer
- `notes` - Additional notes
- `entry_method` - Method of entry (default: "rfid")

#### Response (Entry)
```json
{
    "status": 200,
    "message": "Entry recorded successfully",
    "data": {
        "action": "entry",
        "log_id": 123,
        "student": {
            "id": 1,
            "registration_number": "2023/001",
            "name": "John Doe",
            "email": "john@example.com",
            "department": "Computer Science",
            "program": "BSc CS",
            "year_of_study": 2,
            "phone": "123456789"
        },
        "card": {
            "number": "RFID2023001",
            "type": "student"
        },
        "entry_time": "2024-01-15 10:30:00",
        "gate_number": 1,
        "message": "Entry successful"
    },
    "timestamp": "2024-01-15 10:30:00"
}
```

#### Response (Exit)
```json
{
    "status": 200,
    "message": "Exit recorded successfully",
    "data": {
        "action": "exit",
        "log_id": 124,
        "student": {
            "id": 1,
            "registration_number": "2023/001",
            "name": "John Doe",
            "email": "john@example.com",
            "department": "Computer Science",
            "program": "BSc CS",
            "year_of_study": 2,
            "phone": "123456789"
        },
        "card": {
            "number": "RFID2023001",
            "type": "student"
        },
        "exit_time": "2024-01-15 16:30:00",
        "gate_number": 1,
        "previous_entry": "2024-01-15 10:30:00",
        "message": "Exit successful"
    },
    "timestamp": "2024-01-15 16:30:00"
}
```

### 2. Manual Entry/Exit Logging
**POST** `/entry_exit/manual.php`

Allows security officers to manually log student entry/exit without RFID cards.

#### Request Body
```json
{
    "student_id": 1,
    "gate_number": 1,
    "action": "entry",
    "security_officer_id": 5,
    "notes": "Manual entry by security officer",
    "device_id": 3
}
```

#### Required Fields
- `student_id` - Student ID
- `gate_number` - Gate number (1-10)
- `action` - "entry" or "exit"

#### Optional Fields
- `security_officer_id` - ID of security officer
- `notes` - Additional notes
- `device_id` - Device ID if applicable

### 3. Get Student Information
**GET** `/entry_exit/student_info.php?card_number=RFID2023001`

Retrieves comprehensive student information by RFID card number.

#### Query Parameters
- `card_number` - RFID card number (required)

#### Response
```json
{
    "status": 200,
    "message": "Student information retrieved successfully",
    "data": {
        "student": {
            "id": 1,
            "registration_number": "2023/001",
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "123456789",
            "department": "Computer Science",
            "program": "BSc CS",
            "year_of_study": 2,
            "gender": "Male"
        },
        "card": {
            "id": 1,
            "number": "RFID2023001",
            "type": "student",
            "is_active": true,
            "expiry_date": "2025-12-31"
        },
        "current_status": {
            "status": "inside",
            "entry_time": "2024-01-15 10:30:00",
            "gate_number": 1
        },
        "devices": [
            {
                "id": 1,
                "device_name": "Laptop",
                "device_type": "laptop",
                "serial_number": "LAP001",
                "brand": "Dell",
                "model": "Latitude"
            }
        ],
        "recent_logs": [
            {
                "id": 123,
                "entry_time": "2024-01-15 10:30:00",
                "exit_time": null,
                "gate_number": 1,
                "entry_method": "rfid",
                "status": "entered",
                "created_at": "2024-01-15 10:30:00"
            }
        ]
    }
}
```

### 4. Get Entry/Exit Logs
**GET** `/entry_exit/logs.php`

Retrieves entry/exit logs with filtering and pagination.

#### Query Parameters
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 20, max: 100)
- `gate_number` - Filter by gate number
- `student_id` - Filter by student ID
- `date_from` - Filter from date (YYYY-MM-DD)
- `date_to` - Filter to date (YYYY-MM-DD)
- `status` - Filter by status ("entered", "exited")
- `entry_method` - Filter by entry method ("manual", "rfid")

#### Example Request
```
GET /entry_exit/logs.php?page=1&limit=10&gate_number=1&date_from=2024-01-15
```

#### Response
```json
{
    "status": 200,
    "message": "Logs retrieved successfully",
    "data": {
        "logs": [
            {
                "id": 123,
                "student": {
                    "id": 1,
                    "registration_number": "2023/001",
                    "name": "John Doe",
                    "email": "john@example.com",
                    "department": "Computer Science",
                    "program": "BSc CS",
                    "year_of_study": 2
                },
                "card": {
                    "number": "RFID2023001",
                    "type": "student"
                },
                "entry_time": "2024-01-15 10:30:00",
                "exit_time": null,
                "gate_number": 1,
                "entry_method": "rfid",
                "status": "entered",
                "notes": null,
                "security_officer": null,
                "created_at": "2024-01-15 10:30:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_logs": 100,
            "limit": 20,
            "has_next": true,
            "has_prev": false
        }
    }
}
```

### 5. Get Campus Status
**GET** `/entry_exit/status.php`

Retrieves real-time campus status and occupancy information.

#### Query Parameters
- `student_id` - Get specific student status
- `gate_number` - Filter by gate number

#### Response (Overall Status)
```json
{
    "status": 200,
    "message": "Campus status retrieved successfully",
    "data": {
        "total_inside": 45,
        "inside_students": [
            {
                "student_id": 1,
                "registration_number": "2023/001",
                "first_name": "John",
                "last_name": "Doe",
                "department": "Computer Science",
                "program": "BSc CS",
                "year_of_study": 2,
                "entry_time": "2024-01-15 10:30:00",
                "gate_number": 1
            }
        ],
        "gate_statistics": [
            {
                "gate_number": 1,
                "inside_count": 25
            },
            {
                "gate_number": 2,
                "inside_count": 15
            },
            {
                "gate_number": 3,
                "inside_count": 5
            }
        ],
        "department_statistics": [
            {
                "department": "Computer Science",
                "inside_count": 20
            },
            {
                "department": "Engineering",
                "inside_count": 15
            }
        ],
        "recent_entries": [
            {
                "first_name": "John",
                "last_name": "Doe",
                "registration_number": "2023/001",
                "gate_number": 1,
                "entry_time": "2024-01-15 10:30:00",
                "entry_method": "rfid"
            }
        ],
        "timestamp": "2024-01-15 10:30:00"
    }
}
```

#### Response (Specific Student)
```json
{
    "status": 200,
    "message": "Student is currently inside",
    "data": {
        "student": {
            "id": 1,
            "registration_number": "2023/001",
            "name": "John Doe",
            "email": "john@example.com",
            "department": "Computer Science",
            "program": "BSc CS",
            "year_of_study": 2
        },
        "status": "inside",
        "entry_time": "2024-01-15 10:30:00",
        "gate_number": 1,
        "duration": 21600
    }
}
```

### 6. Test API
**GET** `/test.php`

Simple endpoint to test if the API is working.

#### Response
```json
{
    "status": 200,
    "message": "API Test Successful",
    "data": {
        "message": "API is working correctly",
        "version": "1.0.0",
        "endpoints": {
            "entry_exit/process.php": "Process RFID card entry/exit",
            "entry_exit/manual.php": "Manual entry/exit logging",
            "entry_exit/student_info.php": "Get student information by card",
            "entry_exit/logs.php": "Get entry/exit logs",
            "entry_exit/status.php": "Get campus status"
        },
        "valid_api_keys": [
            "gate_system_2024",
            "security_api_key",
            "admin_api_key"
        ],
        "timestamp": "2024-01-15 10:30:00"
    }
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
    "status": 400,
    "message": "Error description",
    "data": null,
    "timestamp": "2024-01-15 10:30:00"
}
```

### Common Error Codes
- `400` - Bad Request (missing/invalid parameters)
- `401` - Unauthorized (invalid/missing API key)
- `403` - Forbidden (card expired, student inactive)
- `404` - Not Found (student/card not found)
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Testing

Use the provided test interface at `/api/entry_exit/test_interface.html` to test the API endpoints interactively.

## Integration Examples

### JavaScript Example
```javascript
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
        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}
```

### PHP Example
```php
function callEntryExitAPI($endpoint, $data = null, $method = 'GET') {
    $url = 'http://your-domain.com/Capstone_project/api/' . $endpoint;
    
    $headers = [
        'X-API-Key: gate_system_2024',
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
``` 