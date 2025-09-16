<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

// A single query to get all workshops with their registration counts and new categories.
$sql = "
    SELECT
        w.*,
        w.status as STATUS,
        (SELECT COUNT(*) FROM registrations r WHERE r.workshop_id = w.workshop_id AND r.status = 'Approved') as approved_count,
        (SELECT COUNT(*) FROM registrations r WHERE r.workshop_id = w.workshop_id AND r.status = 'Pending') as pending_count
    FROM
        workshops w
    ORDER BY 
        w.start_datetime DESC
";

$result = $conn->query($sql);

$workshops = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $workshops[] = $row;
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'workshops' => $workshops
]);
?>

