<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

// Get all the form data
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$instructor_name = $_POST['instructor_name'] ?? '';
$start_datetime = $_POST['start_datetime'] ?? '';
$location = $_POST['location'] ?? '';
$max_capacity = $_POST['max_capacity'] ?? 0;
$status = $_POST['status'] ?? 'Scheduled';

// --- START OF FIX ---
// Get the new category data from the form
$category_main = $_POST['category_main'] ?? null;
$category_sub = $_POST['category_sub'] ?? null;
$category_specialization = $_POST['category_specialization'] ?? null;
// --- END OF FIX ---


// Basic validation
if (empty($title) || empty($start_datetime) || empty($max_capacity)) {
    echo json_encode(['success' => false, 'message' => 'Title, Start Date, and Max Capacity are required.']);
    exit();
}

// Format the date for MySQL
$mysql_datetime = date('Y-m-d H:i:s', strtotime($start_datetime));

// --- UPDATE SQL QUERY ---
$sql = "INSERT INTO workshops (title, description, instructor_name, start_datetime, location, max_capacity, status, category_main, category_sub, category_specialization) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// --- UPDATE BIND PARAMETERS ---
// The type string is now 'sssssissss'
$stmt->bind_param("sssssissss", $title, $description, $instructor_name, $mysql_datetime, $location, $max_capacity, $status, $category_main, $category_sub, $category_specialization);


if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

