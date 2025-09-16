<?php
// Suppress default PHP errors for the connection attempt
$conn = @new mysqli("sql304.infinityfree.com", "if0_39848913", "Nano159159", "if0_39848913_experts_workshop");

// Check connection and handle errors gracefully with JSON
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    // Provide a clear error message for debugging
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit(); // Stop the script immediately
}

$conn->set_charset("utf8");
?>

