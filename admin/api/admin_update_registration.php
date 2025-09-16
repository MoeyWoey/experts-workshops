<?php
// Use the PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
require __DIR__ . '/db_connect.php'; 

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['registration_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$registration_id = (int)$data['registration_id'];
$new_status = $data['status'];

// Get User Email, Name, Workshop Title, and the registration's CURRENT status BEFORE updating
$info_sql = "
    SELECT u.email, u.full_name, w.title, r.status as current_status
    FROM registrations r
    JOIN users u ON r.user_id = u.user_id
    JOIN workshops w ON r.workshop_id = w.workshop_id
    WHERE r.registration_id = ?
";
$stmt_info = $conn->prepare($info_sql);
$stmt_info->bind_param("i", $registration_id);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
$info = $result_info->fetch_assoc();
$stmt_info->close();

if (!$info) {
    echo json_encode(['success' => false, 'message' => 'Registration not found.']);
    exit();
}

// Update the registration status in the database
$update_sql = "UPDATE registrations SET status = ? WHERE registration_id = ?";
$stmt_update = $conn->prepare($update_sql);
$stmt_update->bind_param("si", $new_status, $registration_id);

if ($stmt_update->execute()) {
    
    // --- START OF TESTING CHANGE ---
    /*
    // The entire email sending block is commented out for testing.
    // To re-enable, remove the /* on this line and the */ /*at the end of the block.

    $to = $info['email'];
    $workshop_title = $info['title'];
    $user_name = $info['full_name'];
    $current_status = $info['current_status'];
    $subject = '';
    $message = '';

    if ($new_status == 'Approved') {
        $subject = "تم تأكيد تسجيلك في ورشة عمل: " . $workshop_title;
        $message = "مرحباً " . $user_name . "،\n\n" . "يسعدنا إعلامك بأنه تم تأكيد تسجيلك في ورشة العمل '" . $workshop_title . "'.\n\n" . "نتطلع لرؤيتك هناك.\n\n" . "مع خالص التحيات,\n" . "فريق عمل الورش";
    } elseif ($new_status == 'Cancelled') {
        if ($current_status == 'Approved') {
            // This is a removal notification
            $subject = "إشعار بإلغاء تسجيلك في ورشة عمل: " . $workshop_title;
            $message = "مرحباً " . $user_name . "،\n\n" . "هذا إشعار بأنه قد تم إلغاء تسجيلك في ورشة العمل '" . $workshop_title . "'.\n\n" . "إذا كانت لديك أي أسئلة، يرجى التواصل مع فريق الدعم.\n\n" . "مع خالص التحيات,\n" . "فريق عمل الورش";
        } else {
            // This is a rejection notification
            $subject = "تحديث بخصوص تسجيلك في ورشة عمل: " . $workshop_title;
            $message = "مرحباً " . $user_name . "،\n\n" . "هذا تحديث بخصوص تسجيلك في ورشة العمل '" . $workshop_title . "'. نعتذر عن عدم قبول طلبك في الوقت الحالي.\n\n" . "إذا كانت لديك أي أسئلة، يرجى التواصل مع فريق الدعم.\n\n" . "مع خالص التحيات,\n" . "فريق عمل الورش";
        }
    }
    
    if (!empty($subject)) {
        
        // Include PHPMailer files
        require __DIR__ . '/lib/PHPMailer.php';
        require __DIR__ . '/lib/SMTP.php';
        require __DIR__ . '/lib/Exception.php';

        $mail = new PHPMailer(true);

        try {
            // Server settings from the secure credentials file
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to, $user_name);

            // Content
            $mail->isHTML(false); 
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
        } catch (Exception $e) {
            // error_log("Mailer Error: {$mail->ErrorInfo}");
        }
    }
    */
    // --- END OF TESTING CHANGE ---
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt_update->close();
$conn->close();
?>

