<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

if (!isset($_GET['search']) || empty(trim($_GET['search']))) {
    echo json_encode([]);
    exit();
}

// --- START OF CHANGE ---
$search_term_like = '%' . trim($_GET['search']) . '%';
$search_term_exact = trim($_GET['search']);

// Find users matching the search term by name, email, OR user_id
$user_sql = "SELECT user_id, full_name, email, job_title FROM users WHERE full_name LIKE ? OR email LIKE ? OR user_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("sss", $search_term_like, $search_term_like, $search_term_exact);
// --- END OF CHANGE ---

$stmt_user->execute();
$users_result = $stmt_user->get_result();

$users_data = [];

while ($user = $users_result->fetch_assoc()) {
    $user_id = $user['user_id'];
    
    // Get all workshops for this user
    $workshop_sql = "
        SELECT 
            w.title,
            w.start_datetime,
            w.status AS workshop_status,
            r.status AS registration_status
        FROM registrations r
        JOIN workshops w ON r.workshop_id = w.workshop_id
        WHERE r.user_id = ?
        ORDER BY w.start_datetime DESC
    ";
    
    $stmt_workshop = $conn->prepare($workshop_sql);
    $stmt_workshop->bind_param("i", $user_id);
    $stmt_workshop->execute();
    $workshops_result = $stmt_workshop->get_result();
    
    $user_workshops = ['upcoming' => [], 'past' => []];
    while($workshop = $workshops_result->fetch_assoc()) {
        if ($workshop['workshop_status'] === 'Scheduled' && ($workshop['registration_status'] === 'Approved' || $workshop['registration_status'] === 'Pending')) {
            $user_workshops['upcoming'][] = $workshop;
        } else {
            $user_workshops['past'][] = $workshop;
        }
    }
    
    $user['workshops'] = $user_workshops;
    $users_data[] = $user;
    
    $stmt_workshop->close();
}

$stmt_user->close();
$conn->close();

echo json_encode($users_data);
?>

