<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { exit(); }

header('Content-Type: application/json');
require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$stmt = $conn->prepare("UPDATE workshops SET title=?, description=?, instructor_name=?, start_datetime=?, location=?, max_capacity=?, status=? WHERE workshop_id=?");
$stmt->bind_param("sssssisi", 
    $data['title'], 
    $data['description'], 
    $data['instructor_name'], 
    $data['start_datetime'], 
    $data['location'], 
    $data['max_capacity'], 
    $data['status'],
    $data['workshop_id']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
