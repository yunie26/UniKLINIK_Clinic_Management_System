<?php

function clinic_ensure_prescription_tables($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if ($done) {
    return;
  }
  $done = true;

  $sql = "CREATE TABLE IF NOT EXISTS prescriptions (
    ID int(11) NOT NULL AUTO_INCREMENT,
    DOCTOR_STAFF_ID int(11) NOT NULL,
    PATIENT_NAME varchar(100) NOT NULL,
    PATIENT_CONTACT varchar(20) DEFAULT NULL,
    DIAGNOSIS varchar(255) DEFAULT NULL,
    PRESCRIPTION_DATE date DEFAULT NULL,
    MEDICINE_LIST text NOT NULL,
    NOTES text DEFAULT NULL,
    STATUS varchar(20) NOT NULL DEFAULT 'SENT',
    CREATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    COUNTER_READ tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (ID),
    KEY STATUS (STATUS),
    KEY COUNTER_READ (COUNTER_READ),
    KEY DOCTOR_STAFF_ID (DOCTOR_STAFF_ID)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
  mysqli_query($con, $sql);

  clinic_prescription_migrate_prescription_date($con);
}

function clinic_prescription_migrate_prescription_date($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if ($done) {
    return;
  }
  $done = true;
  $chk = @mysqli_query($con, "SHOW COLUMNS FROM prescriptions LIKE 'PRESCRIPTION_DATE'");
  if ($chk && mysqli_num_rows($chk) > 0) {
    return;
  }
  @mysqli_query($con, "ALTER TABLE prescriptions ADD COLUMN PRESCRIPTION_DATE date DEFAULT NULL AFTER DIAGNOSIS");
}

?>
