<?php
// Define the path to the secure credentials file
$credentials_path = __DIR__ . '/../config/credentials.php';

// Check if the credentials file exists before trying to include it
if (!file_exists($credentials_path)) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    // Send a very specific error message
    echo json_encode(['error' => 'CRITICAL: The credentials file was not found. Please check that the /config/credentials.php file exists outside of your htdocs folder.']);
    exit();
}

// If the file exists, we can safely include it
require_once $credentials_path;

// Create a new database connection using the credentials
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check for connection errors
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed. Please double-check the credentials in your config/credentials.php file.']);
    exit();
}
// --- START OF FIX ---
// Set the character set to utf8mb4 to support Arabic characters
$conn->set_charset("utf8mb4");
// --- END OF FIX ---
?>

