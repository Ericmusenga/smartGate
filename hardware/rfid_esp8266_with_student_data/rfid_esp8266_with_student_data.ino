#include <SPI.h>          // Required for MFRC522 communication
#include <MFRC522.h>        // RFID/NFC reader library
#include <ESP8266WiFi.h>    // WiFi connectivity for ESP8266
#include <ESP8266HTTPClient.h> // HTTP client for making web requests
#include <ArduinoJson.h>    // For parsing JSON responses from the server (install via Library Manager)

// --- RFID and GPIO Pin Definitions ---
#define SS_PIN D4           // Slave Select (SDA) pin for MFRC522 (GPIO2 / D4 on NodeMCU)
#define RST_PIN D3          // Reset pin for MFRC522 (GPIO0 / D3 on NodeMCU)
#define GREEN_LED D1        // Green LED connected to D1 (GPIO5)
#define RED_LED D2          // Red LED connected to D2 (GPIO4)
#define BUZZER_PIN D0       // Buzzer connected to D0 (GPIO16)

// --- WiFi Credentials ---
const char* ssid = "samsung1"; // Your WiFi network SSID
const char* password = "Claude123!@"; // Your WiFi network password

// --- Server Endpoints ---
const char* checkURL = "http:// 192.168.67.226/Capstone_project/hardware/check_uid.php";
const char* serverURL = "http:// 192.168.67.226/Capstone_project/pages/logs.php";

// --- RFID Reader Object ---
MFRC522 mfrc522(SS_PIN, RST_PIN);

// --- Memory for Tracking Cards ---
const int MAX_CARDS = 50;
String knownUIDs[MAX_CARDS];
bool isInside[MAX_CARDS];
int passengerCount = 0;

// --- Student Information Structure ---
struct StudentInfo {
  String registration_number;
  String first_name;
  String last_name;
  String full_name;
  String email;
  String department;
  String program;
  String year_of_study;
  String phone;
  String gender;
  String username;
  bool has_account;
  bool is_first_login;
  int student_id;
  String card_type;
  String card_number;
};

StudentInfo currentStudent; // Store current student info

// --- Function Prototypes ---
bool isUIDAllowed(String uid);
int findCard(String uid);
int addCard(String uid);
void showFeedback(bool statusIn);
void showUnauthorizedFeedback();
void showErrorFeedback();
void sendToServer(String uid, String timestamp, bool statusIn);
void sendUnauthorizedToServer(String uid, String timestamp);
String getTimestamp();
void printStudentInfo(StudentInfo student);

void setup() {
  Serial.begin(19200);

  // Initialize SPI bus for RFID reader
  SPI.begin();
  mfrc522.PCD_Init();

  // Configure GPIO pins
  pinMode(GREEN_LED, OUTPUT);
  pinMode(RED_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  // Connect to WiFi
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected! IP address: " + WiFi.localIP().toString());
  Serial.println("Ready to scan RFID cards.");
}

void loop() {
  // Check if a new card is present and can be read
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  // Convert the UID bytes to a hexadecimal string
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  Serial.println("\nCard Scanned UID: " + uid);
  String timestamp = getTimestamp();

  // Check if UID is allowed and get student information
  bool isAuthorized = isUIDAllowed(uid);
  if (!isAuthorized) {
    Serial.println("❌ Unauthorized UID detected!");
    showUnauthorizedFeedback();
    // Send unauthorized card data to server for logging
    sendUnauthorizedToServer(uid, timestamp);
    return;
  }

  // Print student information
  Serial.println("✅ Authorized card detected!");
  printStudentInfo(currentStudent);

  // Manage Card Entry/Exit Status
  int idx = findCard(uid);
  bool statusIn;

  if (idx >= 0) {
    // Card found in knownUIDs, toggle its status
    isInside[idx] = !isInside[idx];
    statusIn = isInside[idx];
    passengerCount += statusIn ? 1 : -1;
    passengerCount = max(0, passengerCount);
  } else {
    // New card, add it to knownUIDs and mark as IN
    idx = addCard(uid);
    if (idx < 0) {
      Serial.println("❌ Card list full! Cannot track new card.");
      showErrorFeedback();
      return;
    }
    knownUIDs[idx] = uid;
    isInside[idx] = true;
    statusIn = true;
    passengerCount++;
  }

  // Print Transaction Details
  Serial.printf("[%s] UID: %s -> %s (Count: %d)\n", timestamp.c_str(), uid.c_str(),
                statusIn ? "IN" : "OUT", passengerCount);
  Serial.printf("Student: %s (%s) - %s\n", 
                currentStudent.full_name.c_str(), 
                currentStudent.registration_number.c_str(),
                currentStudent.department.c_str());

  // Provide Feedback and Send Data to Server
  showFeedback(statusIn);
  sendToServer(uid, timestamp, statusIn);

  // Display All Currently Tracked UIDs
  Serial.println("📋 All scanned UIDs so far:");
  for (int i = 0; i < MAX_CARDS; i++) {
    if (knownUIDs[i].length() > 0) {
      Serial.printf(" - %s : %s\n", knownUIDs[i].c_str(), isInside[i] ? "IN" : "OUT");
    }
  }

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
}

/**
 * Checks with the PHP server if the given UID is allowed and gets student information.
 * Parses the JSON response from the server.
 * Returns true if allowed, false otherwise.
 */
bool isUIDAllowed(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected for UID check.");
    return false;
  }

  HTTPClient http;
  WiFiClient client;

  String url = String(checkURL) + "?uid=" + uid;
  Serial.println("Checking URL: " + url);

  http.begin(client, url);
  int httpCode = http.GET();

  bool allowed = false;

  if (httpCode == HTTP_CODE_OK) {
    String payload = http.getString();
    Serial.println("Server Response (check_uid.php): " + payload);

    // Allocate a larger DynamicJsonDocument for student data
    DynamicJsonDocument doc(1024); // Increased size for student information
    DeserializationError error = deserializeJson(doc, payload);

    if (error) {
      Serial.print(F("deserializeJson() failed: "));
      Serial.println(error.f_str());
      return false;
    }

    // Access the "allowed" field from the parsed JSON
    allowed = doc["allowed"].as<bool>();

    if (allowed) {
      // Extract student information
      currentStudent.student_id = doc["student_id"].as<int>();
      currentStudent.card_type = doc["card_type"].as<String>();
      currentStudent.card_number = doc["card_number"].as<String>();
      
      // Extract student details from the nested "student" object
      JsonObject student = doc["student"];
      currentStudent.registration_number = student["registration_number"].as<String>();
      currentStudent.first_name = student["first_name"].as<String>();
      currentStudent.last_name = student["last_name"].as<String>();
      currentStudent.full_name = student["full_name"].as<String>();
      currentStudent.email = student["email"].as<String>();
      currentStudent.department = student["department"].as<String>();
      currentStudent.program = student["program"].as<String>();
      currentStudent.year_of_study = student["year_of_study"].as<String>();
      currentStudent.phone = student["phone"].as<String>();
      currentStudent.gender = student["gender"].as<String>();
      currentStudent.username = student["username"].as<String>();
      currentStudent.has_account = student["has_account"].as<bool>();
      currentStudent.is_first_login = student["is_first_login"].as<bool>();
    } else {
      String message = doc["message"].as<String>();
      Serial.println("Server message: " + message);
    }
  } else {
    Serial.printf("[HTTP] GET failed, error: %s (Code: %d)\n", http.errorToString(httpCode).c_str(), httpCode);
  }

  http.end();
  return allowed;
}

/**
 * Prints student information to Serial Monitor
 */
void printStudentInfo(StudentInfo student) {
  Serial.println("📋 Student Information:");
  Serial.printf("  Name: %s\n", student.full_name.c_str());
  Serial.printf("  Registration: %s\n", student.registration_number.c_str());
  Serial.printf("  Email: %s\n", student.email.c_str());
  Serial.printf("  Department: %s\n", student.department.c_str());
  Serial.printf("  Program: %s\n", student.program.c_str());
  Serial.printf("  Year: %s\n", student.year_of_study.c_str());
  Serial.printf("  Phone: %s\n", student.phone.c_str());
  Serial.printf("  Gender: %s\n", student.gender.c_str());
  Serial.printf("  Card Type: %s\n", student.card_type.c_str());
  Serial.printf("  Has Account: %s\n", student.has_account ? "Yes" : "No");
  if (student.has_account) {
    Serial.printf("  Username: %s\n", student.username.c_str());
    Serial.printf("  First Login: %s\n", student.is_first_login ? "Yes" : "No");
  }
  Serial.println();
}

/**
 * Searches for a UID in the knownUIDs array.
 * Returns the index if found, -1 otherwise.
 */
int findCard(String uid) {
  for (int i = 0; i < MAX_CARDS; i++) {
    if (knownUIDs[i] == uid) {
      return i;
    }
  }
  return -1;
}

/**
 * Adds a new UID to the knownUIDs array at the first available (empty) slot.
 * Returns the index where it was added, or -1 if the array is full.
 */
int addCard(String uid) {
  for (int i = 0; i < MAX_CARDS; i++) {
    if (knownUIDs[i].length() == 0) {
      knownUIDs[i] = uid;
      return i;
    }
  }
  return -1;
}

/**
 * Provides visual (LED) and audio (buzzer) feedback based on IN/OUT status.
 */
void showFeedback(bool statusIn) {
  digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(GREEN_LED, statusIn ? HIGH : LOW);
  digitalWrite(RED_LED, statusIn ? LOW : HIGH);
  delay(1000);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);
}

/**
 * Provides specific feedback for an unauthorized access attempt.
 */
void showUnauthorizedFeedback() {
  digitalWrite(GREEN_LED, HIGH);
  digitalWrite(RED_LED, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(2000);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);
  digitalWrite(BUZZER_PIN, LOW);
}

/**
 * Provides feedback for a system error.
 */
void showErrorFeedback() {
  digitalWrite(RED_LED, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(500);
  digitalWrite(RED_LED, LOW);
  digitalWrite(BUZZER_PIN, LOW);
}

/**
 * Sends the RFID scan event data with student information to the logs.php server endpoint.
 */
void sendToServer(String uid, String timestamp, bool statusIn) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected for sending logs.");
    return;
  }

  HTTPClient http;
  WiFiClient client;

  http.begin(client, serverURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  // Construct the POST data string with student information
  String postData = "event=RFID_SCAN"
                    "&count=" + String(passengerCount) +
                    "&status=" + (statusIn ? "IN" : "OUT") +
                    "&timestamp=" + timestamp +
                    "&uid=" + uid +
                    "&student_id=" + String(currentStudent.student_id) +
                    "&registration_number=" + currentStudent.registration_number +
                    "&student_name=" + currentStudent.full_name +
                    "&email=" + currentStudent.email +
                    "&department=" + currentStudent.department +
                    "&program=" + currentStudent.program +
                    "&year_of_study=" + currentStudent.year_of_study +
                    "&phone=" + currentStudent.phone +
                    "&gender=" + currentStudent.gender +
                    "&card_type=" + currentStudent.card_type +
                    "&has_account=" + (currentStudent.has_account ? "1" : "0") +
                    "&username=" + currentStudent.username +
                    "&is_first_login=" + (currentStudent.is_first_login ? "1" : "0");

  Serial.println("Sending to logs.php: " + postData);

  int responseCode = http.POST(postData);

  if (responseCode > 0) {
    Serial.println("Server Response from logs.php: " + http.getString());
  } else {
    Serial.printf("HTTP Error to logs.php: %s (Code: %d)\n", http.errorToString(responseCode).c_str(), responseCode);
  }

  http.end();
}

/**
 * Sends unauthorized card data to the server for logging.
 */
void sendUnauthorizedToServer(String uid, String timestamp) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected for sending unauthorized log.");
    return;
  }

  HTTPClient http;
  WiFiClient client;

  http.begin(client, serverURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  // Construct the POST data string for unauthorized card
  String postData = "event=UNAUTHORIZED_RFID"
                    "&count=" + String(passengerCount) +
                    "&status=UNAUTHORIZED" +
                    "&timestamp=" + timestamp +
                    "&uid=" + uid +
                    "&unauthorized=true" +
                    "&error_type=unauthorized_card";

  Serial.println("Sending unauthorized card to logs.php: " + postData);

  int responseCode = http.POST(postData);

  if (responseCode > 0) {
    Serial.println("Server Response for unauthorized: " + http.getString());
  } else {
    Serial.printf("HTTP Error for unauthorized: %s (Code: %d)\n", http.errorToString(responseCode).c_str(), responseCode);
  }

  http.end();
}

/**
 * Generates a simple timestamp string based on ESP8266 uptime.
 * Format: HH:MM:SS
 */
String getTimestamp() {
  unsigned long s = millis() / 1000;
  int hh = (s / 3600) % 24;
  int mm = (s / 60) % 60;
  int ss = s % 60;

  char buf[9];
  sprintf(buf, "%02d:%02d:%02d", hh, mm, ss);
  return String(buf);
} 