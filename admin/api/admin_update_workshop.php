<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

// Get all the form data
$workshop_id = $_POST['workshop_id'] ?? 0;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$instructor_name = $_POST['instructor_name'] ?? '';
$start_datetime = $_POST['start_datetime'] ?? '';
$location = $_POST['location'] ?? '';
$max_capacity = $_POST['max_capacity'] ?? 0;
$status = $_POST['status'] ?? 'Scheduled';
$category_main = $_POST['category_main'] ?? '';
$category_sub = $_POST['category_sub'] ?? '';
$category_specialization = $_POST['category_specialization'] ?? '';


// --- START OF FIX ---
// Convert empty strings from the form to NULL for the database.
$category_main = !empty($category_main) ? $category_main : null;
$category_sub = !empty($category_sub) ? $category_sub : null;
$category_specialization = !empty($category_specialization) ? $category_specialization : null;
// --- END OF FIX ---


// Basic validation
if (empty($workshop_id) || empty($title) || empty($start_datetime) || empty($max_capacity)) {
    echo json_encode(['success' => false, 'message' => 'Workshop ID, Title, Start Date, and Max Capacity are required.']);
    exit();
}

// Format the date for MySQL
$mysql_datetime = date('Y-m-d H:i:s', strtotime($start_datetime));

// SQL query remains the same
$sql = "UPDATE workshops SET 
            title = ?, 
            description = ?, 
            instructor_name = ?, 
            start_datetime = ?, 
            location = ?, 
            max_capacity = ?, 
            status = ?,
            category_main = ?,
            category_sub = ?,
            category_specialization = ?
        WHERE workshop_id = ?";
$stmt = $conn->prepare($sql);

// Bind parameters remain the same
$stmt->bind_param("sssssissssi", $title, $description, $instructor_name, $mysql_datetime, $location, $max_capacity, $status, $category_main, $category_sub, $category_specialization, $workshop_id);


if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

