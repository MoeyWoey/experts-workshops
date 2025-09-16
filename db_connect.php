<?php
// This is a simplified connection file for testing.
// Place this file in the same directory as your API scripts (e.g., htdocs).

// --- IMPORTANT: Fill in your actual credentials from InfinityFree ---
$servername = "sql304.infinityfree.com"; // Your host name
$username = "if0_39848913";           // Your DB username
$password = "Nano159159";    // Your DB password
$dbname = "if0_39848913_experts_workshop";     // Your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and send a clean JSON error if it fails
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    // Send a specific error message
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}
// --- START OF FIX ---
// Set the character set to utf8mb4 to support Arabic characters
$conn->set_charset("utf8mb4");
// --- END OF FIX ---
?>
