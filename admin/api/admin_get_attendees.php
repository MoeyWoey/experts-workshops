<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

if (!isset($_GET['workshop_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Workshop ID is required.']);
    exit();
}

$workshop_id = (int)$_GET['workshop_id'];

// Get workshop title
$workshop_sql = "SELECT title FROM workshops WHERE workshop_id = ?";
$stmt_workshop = $conn->prepare($workshop_sql);
$stmt_workshop->bind_param("i", $workshop_id);
$stmt_workshop->execute();
$workshop_result = $stmt_workshop->get_result()->fetch_assoc();
$stmt_workshop->close();

if (!$workshop_result) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Workshop not found.']);
    exit();
}

// Get approved attendees
$attendees_sql = "
    SELECT u.full_name, u.email
    FROM registrations r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.workshop_id = ? AND r.status = 'Approved'
    ORDER BY u.full_name ASC
";

$stmt_attendees = $conn->prepare($attendees_sql);
$stmt_attendees->bind_param("i", $workshop_id);
$stmt_attendees->execute();
$attendees_result = $stmt_attendees->get_result();

$attendees = [];
while($row = $attendees_result->fetch_assoc()) {
    $attendees[] = $row;
}
$stmt_attendees->close();
$conn->close();

echo json_encode([
    'success' => true,
    'workshop_title' => $workshop_result['title'],
    'attendees' => $attendees
]);
?>

