<?php
session_start();
header('Content-Type: application/json');

// --- FIX: Use the new connection file in the same directory ---
require __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Hard-coded for testing
}
$current_user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['workshop_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$workshop_id = $data['workshop_id'];

// Check for an existing registration
$check_sql = "SELECT registration_id, status FROM registrations WHERE user_id = ? AND workshop_id = ?";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("ii", $current_user_id, $workshop_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$existing_registration = $result->fetch_assoc();
$stmt_check->close();

if ($existing_registration) {
    if ($existing_registration['status'] == 'Cancelled') {
        // Re-register: Update status from Cancelled to Pending
        $update_sql = "UPDATE registrations SET status = 'Pending', registration_date = NOW() WHERE registration_id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("i", $existing_registration['registration_id']);
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم إعادة تسجيلك بنجاح. طلبك الآن قيد المراجعة.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إعادة التسجيل.']);
        }
        $stmt_update->close();
    } else {
        // User is already registered (Approved or Pending)
        echo json_encode(['success' => false, 'message' => 'أنت مسجل بالفعل في هذه الورشة.']);
    }
} else {
    // New registration: Insert a new record
    $insert_sql = "INSERT INTO registrations (user_id, workshop_id, status) VALUES (?, ?, 'Pending')";
    $stmt_insert = $conn->prepare($insert_sql);
    $stmt_insert->bind_param("ii", $current_user_id, $workshop_id);
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تقديم طلب تسجيلك بنجاح.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التسجيل.']);
    }
    $stmt_insert->close();
}

$conn->close();
?>

