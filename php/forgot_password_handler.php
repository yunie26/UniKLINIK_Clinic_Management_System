<?php
require "db_connection.php";
require_once __DIR__ . '/staff_schema.php';
require_once __DIR__ . '/secret_questions.php';

header('Content-Type: application/json');

if (!$con) {
  echo json_encode(array('success' => false, 'message' => 'Database connection failed.'));
  exit;
}

clinic_ensure_staff_profile_columns($con);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_question') {
  $email = trim($_GET['email'] ?? '');

  if ($email === '') {
    echo json_encode(array('success' => false, 'message' => 'Please enter your email address.'));
    exit;
  }

  $stmt = mysqli_prepare($con, "SELECT SECRET_QUESTION FROM staff WHERE EMAIL = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $email);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);

  if (!$row) {
    echo json_encode(array('success' => false, 'message' => 'No account found with that email address.'));
    exit;
  }

  $question = trim((string)($row['SECRET_QUESTION'] ?? ''));
  if ($question === '' || !clinic_is_valid_secret_question($question)) {
    echo json_encode(array(
      'success' => false,
      'message' => 'No secret question is set for this account. Please log in and update your profile, or contact the administrator.'
    ));
    exit;
  }

  echo json_encode(array('success' => true, 'question' => $question));
  exit;
}

if ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $secret_answer = trim($_POST['secret_answer'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if ($email === '' || $secret_answer === '' || $password === '' || $confirm_password === '') {
    echo json_encode(array('success' => false, 'message' => 'All fields are required.'));
    exit;
  }

  if (strpos($password, ' ') !== false) {
    echo json_encode(array('success' => false, 'message' => 'Password must not contain spaces.'));
    exit;
  }

  if (strlen($password) < 6) {
    echo json_encode(array('success' => false, 'message' => 'Password must be at least 6 characters.'));
    exit;
  }

  if ($password !== $confirm_password) {
    echo json_encode(array('success' => false, 'message' => 'Passwords do not match.'));
    exit;
  }

  $stmt = mysqli_prepare($con, "SELECT ID, SECRET_ANSWER, SECRET_QUESTION FROM staff WHERE EMAIL = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $email);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);

  if (!$row) {
    echo json_encode(array('success' => false, 'message' => 'Invalid email or secret answer.'));
    exit;
  }

  $storedQuestion = trim((string)($row['SECRET_QUESTION'] ?? ''));
  $storedAnswer = trim((string)($row['SECRET_ANSWER'] ?? ''));

  if ($storedQuestion === '' || $storedAnswer === '') {
    echo json_encode(array(
      'success' => false,
      'message' => 'No secret question is set for this account. Please contact the administrator.'
    ));
    exit;
  }

  if (strcasecmp($secret_answer, $storedAnswer) !== 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid email or secret answer.'));
    exit;
  }

  $stmt2 = mysqli_prepare($con, "UPDATE staff SET PASSWORD = ?, CONFIRM_PASSWORD = ? WHERE ID = ?");
  mysqli_stmt_bind_param($stmt2, "ssi", $password, $password, $row['ID']);

  if (mysqli_stmt_execute($stmt2)) {
    echo json_encode(array('success' => true, 'message' => 'Password reset successfully. You can now log in.'));
  } else {
    echo json_encode(array('success' => false, 'message' => 'Failed to reset password. Please try again.'));
  }
  exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid request.'));
