<?php
require_once __DIR__ . '/app_bootstrap.php';
require "../php/db_connection.php"; // adjust path if needed

if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($con) {
    $staff_id = intval($_SESSION['staff_id']);
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Attachment logic: save to images/
    $attachment_path = '';
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
    $target_dir = "../images/"; // Save to images folder
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = uniqid('leave_') . "_" . basename($_FILES["attachment"]["name"]);
    $target_file = $target_dir . $file_name;
    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        $attachment_path = "images/" . $file_name; // Accessible by web
    }
}

    if (!$leave_type || !$start_date || !$end_date || !$reason) {
        header('Location: ../apply_leave.php?message=' . urlencode('All fields are required.'));
        exit();
    }

    $check_query = "SELECT * FROM staff_leave WHERE STAFF_ID = $staff_id 
                    AND LEAVE_TYPE = '" . strtoupper($leave_type) . "'
                    AND START_DATE = '$start_date'
                    AND END_DATE = '$end_date'";
    $check_result = mysqli_query($con, $check_query);

    if (mysqli_fetch_array($check_result)) {
        header('Location: ../apply_leave.php?message=' . urlencode('Leave already applied for this period and type.'));
        exit();
    } else {
        // Make sure the ATTACHMENT field exists in your staff_leave table!
        $insert_query = "INSERT INTO staff_leave (STAFF_ID, LEAVE_TYPE, START_DATE, END_DATE, REASON, ATTACHMENT, STATUS)
                         VALUES ($staff_id, '$leave_type', '$start_date', '$end_date', '$reason', '$attachment_path', 'Pending')";
        $insert_result = mysqli_query($con, $insert_query);

        if ($insert_result) {
            header('Location: ../apply_leave.php?message=' . urlencode('Leave applied successfully!'));
        } else {
            header('Location: ../apply_leave.php?message=' . urlencode('Error applying leave.'));
        }
        exit();
    }
} else {
    header('Location: ../apply_leave.php?message=' . urlencode('Database connection failed.'));
    exit();
}
?>
