<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2;
}
$current_user_id = $_SESSION['user_id'];

if (!$current_user_id) {
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}

// --- START OF FIX ---
// The SQL query now correctly selects all three category columns.
$sql = "
SELECT 
    w.workshop_id, 
    w.title, 
    w.description,
    w.instructor_name, 
    w.start_datetime, 
    w.location, 
    w.max_capacity,
    w.status,
    w.category_main,
    w.category_sub,
    w.category_specialization,
    r.status as registration_status
FROM 
    workshops w
LEFT JOIN 
    registrations r ON w.workshop_id = r.workshop_id AND r.user_id = ?
";
// --- END OF FIX ---

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$my_upcoming = [];
$my_past = [];
$available = [];

while ($row = $result->fetch_assoc()) {
    $reg_status = $row['registration_status'];
    $workshop_status = $row['status'];

    if ($workshop_status === 'Scheduled') {
        if ($reg_status === 'Approved' || $reg_status === 'Pending') {
            $my_upcoming[] = $row;
        } else {
            $available[] = $row;
        }
    } else {
        if ($reg_status === 'Approved' || $reg_status === 'Pending') {
            $my_past[] = $row;
        }
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'my_upcoming' => $my_upcoming,
    'my_past' => $my_past,
    'available' => $available
]);
?>
