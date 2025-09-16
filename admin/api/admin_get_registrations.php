<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

// --- This script correctly uses $_GET ---
if (!isset($_GET['workshop_id']) || !is_numeric($_GET['workshop_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid Workshop ID is required.']);
    exit();
}

$workshop_id = (int)$_GET['workshop_id'];

// --- START OF CHANGE ---
$sql = "
    SELECT 
        r.registration_id, 
        r.registration_date, 
        r.status, 
        u.full_name,
        u.job_title, 
        u.email,
        w.workshop_id 
    FROM registrations r
    JOIN users u ON r.user_id = u.user_id
    JOIN workshops w ON r.workshop_id = w.workshop_id
    WHERE r.workshop_id = ?
    ORDER BY u.full_name ASC
";
// --- END OF CHANGE ---

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query preparation failed.']);
    exit();
}

$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$result = $stmt->get_result();

$registrations = [];
while($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($registrations);
?>