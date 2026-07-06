<?php

function clinic_ensure_staff_profile_columns($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if ($done) {
    return;
  }
  $done = true;

  $needed = array(
    "GENDER" => "ALTER TABLE staff ADD COLUMN GENDER varchar(20) DEFAULT NULL",
    "DOB" => "ALTER TABLE staff ADD COLUMN DOB date DEFAULT NULL",
    "ADDRESS" => "ALTER TABLE staff ADD COLUMN ADDRESS varchar(255) DEFAULT NULL",
    "ROLE" => "ALTER TABLE staff ADD COLUMN ROLE varchar(50) NOT NULL DEFAULT 'Clinic Assistant'",
    "STATUS" => "ALTER TABLE staff ADD COLUMN STATUS varchar(20) NOT NULL DEFAULT 'Active'",
    "SECRET_QUESTION" => "ALTER TABLE staff ADD COLUMN SECRET_QUESTION varchar(255) DEFAULT NULL",
    "SECRET_ANSWER" => "ALTER TABLE staff ADD COLUMN SECRET_ANSWER varchar(255) DEFAULT NULL"
  );

  foreach ($needed as $col => $sql) {
    $check = mysqli_query($con, "SHOW COLUMNS FROM staff LIKE '" . mysqli_real_escape_string($con, $col) . "'");
    if ($check && mysqli_num_rows($check) === 0) {
      mysqli_query($con, $sql);
    }
  }
}

?>
