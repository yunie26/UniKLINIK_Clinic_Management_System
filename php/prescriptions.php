<?php
require_once __DIR__ . '/app_bootstrap.php';
require "db_connection.php";
require_once __DIR__ . '/prescription_schema.php';
require_once __DIR__ . '/notification_store.php';

if (!$con) {
  echo "Database connection failed.";
  exit;
}

clinic_ensure_prescription_tables($con);

if (isset($_GET['action']) && $_GET['action'] === 'create') {
  $doctor = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;
  $patient_name = mysqli_real_escape_string($con, trim($_GET['patient_name'] ?? ''));
  $patient_contact = mysqli_real_escape_string($con, trim($_GET['patient_contact'] ?? ''));
  $diagnosis = mysqli_real_escape_string($con, trim($_GET['diagnosis'] ?? ''));
  $medicine_list = mysqli_real_escape_string($con, trim($_GET['medicine_list'] ?? ''));
  $notes = mysqli_real_escape_string($con, trim($_GET['notes'] ?? ''));
  $prescription_date_raw = trim($_GET['prescription_date'] ?? '');
  $prescription_date_sql = 'NULL';
  if ($prescription_date_raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $prescription_date_raw)) {
    $prescription_date_sql = "'" . mysqli_real_escape_string($con, $prescription_date_raw) . "'";
  }
  if ($doctor <= 0 || $patient_name === '' || $medicine_list === '') {
    echo "Missing required fields.";
    exit;
  }
  $q = "INSERT INTO prescriptions (DOCTOR_STAFF_ID, PATIENT_NAME, PATIENT_CONTACT, DIAGNOSIS, PRESCRIPTION_DATE, MEDICINE_LIST, NOTES, STATUS, COUNTER_READ)
        VALUES ($doctor, '$patient_name', '$patient_contact', '$diagnosis', $prescription_date_sql, '$medicine_list', '$notes', 'SENT', 0)";
  if (mysqli_query($con, $q)) {
    $newRxId = intval(mysqli_insert_id($con));
    if ($newRxId > 0) {
      clinic_ensure_notification_events_table($con);
      $pn_label = trim($_GET['patient_name'] ?? '');
      if (strlen($pn_label) > 120) {
        $pn_label = substr($pn_label, 0, 117) . '...';
      }
      $msg = '#' . $newRxId . ' for ' . ($pn_label !== '' ? $pn_label : 'patient') . ' — open counter queue.';
      clinic_store_notification_event(
        $con,
        'prescription_new_' . $newRxId,
        'staff',
        'New prescription',
        $msg,
        'counter_prescriptions.php',
        date('Y-m-d H:i:s')
      );
    }
    echo "Prescription sent to counter.";
  } else {
    echo "Failed to save prescription.";
  }
  exit;
}

function clinic_prescription_doctor_owns($con, $prescription_id, $doctor_staff_id) {
  $prescription_id = intval($prescription_id);
  $doctor_staff_id = intval($doctor_staff_id);
  if ($prescription_id < 1 || $doctor_staff_id < 1) {
    return false;
  }
  $q = "SELECT ID FROM prescriptions WHERE ID = $prescription_id AND DOCTOR_STAFF_ID = $doctor_staff_id LIMIT 1";
  $r = mysqli_query($con, $q);
  return $r && mysqli_fetch_assoc($r);
}

if (isset($_GET['action']) && $_GET['action'] === 'doctor_get') {
  header('Content-Type: application/json; charset=UTF-8');
  $doctor = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;
  $id = intval($_GET['id'] ?? 0);
  if ($doctor < 1 || $id < 1 || !clinic_prescription_doctor_owns($con, $id, $doctor)) {
    echo json_encode(array('ok' => false, 'error' => 'Not found.'));
    exit;
  }
  $id_esc = intval($id);
  $q = "SELECT p.*, s.NAME AS DOCTOR_NAME FROM prescriptions p LEFT JOIN staff s ON s.ID = p.DOCTOR_STAFF_ID WHERE p.ID = $id_esc LIMIT 1";
  $r = mysqli_query($con, $q);
  $row = $r ? mysqli_fetch_assoc($r) : null;
  if (!$row) {
    echo json_encode(array('ok' => false, 'error' => 'Not found.'));
    exit;
  }
  echo json_encode(array('ok' => true, 'prescription' => $row));
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'doctor_update') {
  header('Content-Type: application/json; charset=UTF-8');
  $doctor = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;
  $id = intval($_GET['id'] ?? 0);
  if ($doctor < 1 || $id < 1 || !clinic_prescription_doctor_owns($con, $id, $doctor)) {
    echo json_encode(array('ok' => false, 'error' => 'Not allowed.'));
    exit;
  }
  $chk = mysqli_query($con, "SELECT STATUS FROM prescriptions WHERE ID = $id LIMIT 1");
  $st = $chk ? mysqli_fetch_assoc($chk) : null;
  if (!$st || $st['STATUS'] !== 'SENT') {
    echo json_encode(array('ok' => false, 'error' => 'Only prescriptions still marked SENT can be edited.'));
    exit;
  }
  $patient_name = mysqli_real_escape_string($con, trim($_GET['patient_name'] ?? ''));
  $patient_contact = mysqli_real_escape_string($con, trim($_GET['patient_contact'] ?? ''));
  $diagnosis = mysqli_real_escape_string($con, trim($_GET['diagnosis'] ?? ''));
  $medicine_list = mysqli_real_escape_string($con, trim($_GET['medicine_list'] ?? ''));
  $notes = mysqli_real_escape_string($con, trim($_GET['notes'] ?? ''));
  if ($patient_name === '' || $medicine_list === '') {
    echo json_encode(array('ok' => false, 'error' => 'Patient and medicine list are required.'));
    exit;
  }
  $uq = "UPDATE prescriptions SET PATIENT_NAME = '$patient_name', PATIENT_CONTACT = '$patient_contact', DIAGNOSIS = '$diagnosis', MEDICINE_LIST = '$medicine_list', NOTES = '$notes' WHERE ID = $id AND DOCTOR_STAFF_ID = $doctor";
  if (mysqli_query($con, $uq)) {
    echo json_encode(array('ok' => true));
  } else {
    echo json_encode(array('ok' => false, 'error' => 'Update failed.'));
  }
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'doctor_delete') {
  header('Content-Type: application/json; charset=UTF-8');
  $doctor = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;
  $id = intval($_GET['id'] ?? 0);
  if ($doctor < 1 || $id < 1 || !clinic_prescription_doctor_owns($con, $id, $doctor)) {
    echo json_encode(array('ok' => false, 'error' => 'Not allowed.'));
    exit;
  }
  if (mysqli_query($con, "DELETE FROM prescriptions WHERE ID = $id AND DOCTOR_STAFF_ID = $doctor")) {
    echo json_encode(array('ok' => true));
  } else {
    echo json_encode(array('ok' => false, 'error' => 'Delete failed.'));
  }
  exit;
}

// Counter staff actions: view/edit/delete any prescription that is still at the counter (STATUS = SENT)
if (isset($_GET['action']) && $_GET['action'] === 'counter_get') {
  header('Content-Type: application/json; charset=UTF-8');
  $id = intval($_GET['id'] ?? 0);
  if ($id < 1) {
    echo json_encode(array('ok' => false, 'error' => 'Not found.'));
    exit;
  }
  $q = "SELECT p.*, s.NAME AS DOCTOR_NAME
        FROM prescriptions p
        LEFT JOIN staff s ON s.ID = p.DOCTOR_STAFF_ID
        WHERE p.ID = $id
        LIMIT 1";
  $r = mysqli_query($con, $q);
  $row = $r ? mysqli_fetch_assoc($r) : null;
  if (!$row) {
    echo json_encode(array('ok' => false, 'error' => 'Not found.'));
    exit;
  }
  echo json_encode(array('ok' => true, 'prescription' => $row));
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'counter_update') {
  header('Content-Type: application/json; charset=UTF-8');
  $id = intval($_GET['id'] ?? 0);
  if ($id < 1) {
    echo json_encode(array('ok' => false, 'error' => 'Not allowed.'));
    exit;
  }
  $staffRole = isset($_SESSION['staff_role']) ? (string)$_SESSION['staff_role'] : '';
  if ($staffRole === 'Doctor') {
    echo json_encode(array('ok' => false, 'error' => 'Not allowed.'));
    exit;
  }
  $chk = mysqli_query($con, "SELECT STATUS FROM prescriptions WHERE ID = $id LIMIT 1");
  $st = $chk ? mysqli_fetch_assoc($chk) : null;
  if (!$st || $st['STATUS'] !== 'SENT') {
    echo json_encode(array('ok' => false, 'error' => 'Only prescriptions still at the counter (SENT) can be edited.'));
    exit;
  }
  $patient_name = mysqli_real_escape_string($con, trim($_GET['patient_name'] ?? ''));
  $patient_contact = mysqli_real_escape_string($con, trim($_GET['patient_contact'] ?? ''));
  $diagnosis = mysqli_real_escape_string($con, trim($_GET['diagnosis'] ?? ''));
  $medicine_list = mysqli_real_escape_string($con, trim($_GET['medicine_list'] ?? ''));
  $notes = mysqli_real_escape_string($con, trim($_GET['notes'] ?? ''));
  if ($patient_name === '' || $medicine_list === '') {
    echo json_encode(array('ok' => false, 'error' => 'Patient and medicine list are required.'));
    exit;
  }
  $uq = "UPDATE prescriptions
         SET PATIENT_NAME = '$patient_name',
             PATIENT_CONTACT = '$patient_contact',
             DIAGNOSIS = '$diagnosis',
             MEDICINE_LIST = '$medicine_list',
             NOTES = '$notes'
         WHERE ID = $id";
  if (mysqli_query($con, $uq)) {
    echo json_encode(array('ok' => true));
  } else {
    echo json_encode(array('ok' => false, 'error' => 'Update failed.'));
  }
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'counter_delete') {
  header('Content-Type: application/json; charset=UTF-8');
  $id = intval($_GET['id'] ?? 0);
  if ($id < 1) {
    echo json_encode(array('ok' => false, 'error' => 'Not allowed.'));
    exit;
  }
  $staffRole = isset($_SESSION['staff_role']) ? (string)$_SESSION['staff_role'] : '';
  if ($staffRole === 'Doctor') {
    echo json_encode(array('ok' => false, 'error' => 'Not allowed.'));
    exit;
  }
  $chk = mysqli_query($con, "SELECT STATUS FROM prescriptions WHERE ID = $id LIMIT 1");
  $st = $chk ? mysqli_fetch_assoc($chk) : null;
  if (!$st || $st['STATUS'] !== 'SENT') {
    echo json_encode(array('ok' => false, 'error' => 'Only prescriptions still at the counter (SENT) can be deleted.'));
    exit;
  }
  if (mysqli_query($con, "DELETE FROM prescriptions WHERE ID = $id")) {
    echo json_encode(array('ok' => true));
  } else {
    echo json_encode(array('ok' => false, 'error' => 'Delete failed.'));
  }
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'doctor_list') {
  $doctor = isset($_SESSION['staff_id']) ? intval($_SESSION['staff_id']) : 0;
  $where = "p.DOCTOR_STAFF_ID = " . intval($doctor);
  $filter_date = trim($_GET['filter_date'] ?? '');
  if ($filter_date !== '') {
    $d = mysqli_real_escape_string($con, $filter_date);
    $where .= " AND DATE(p.CREATED_AT) = '$d'";
  }
  $filter_patient = trim($_GET['filter_patient'] ?? '');
  if ($filter_patient !== '') {
    $p = mysqli_real_escape_string($con, $filter_patient);
    $where .= " AND p.PATIENT_NAME LIKE '%$p%'";
  }
  $filter_doctor = trim($_GET['filter_doctor'] ?? '');
  if ($filter_doctor !== '') {
    $dn = mysqli_real_escape_string($con, $filter_doctor);
    $where .= " AND s.NAME LIKE '%$dn%'";
  }
  $q = "SELECT p.*, s.NAME AS DOCTOR_NAME FROM prescriptions p LEFT JOIN staff s ON s.ID = p.DOCTOR_STAFF_ID WHERE $where ORDER BY p.ID DESC";
  $r = mysqli_query($con, $q);
  if (!$r) {
    exit;
  }
  $any = false;
  while ($row = mysqli_fetch_assoc($r)) {
    $any = true;
    $id = intval($row['ID']);
    $created = htmlspecialchars(substr($row['CREATED_AT'], 0, 10));
    $patient = htmlspecialchars($row['PATIENT_NAME']);
    $docName = htmlspecialchars($row['DOCTOR_NAME'] ?? 'Doctor');
    $canEdit = ($row['STATUS'] === 'SENT');
    echo "<tr>";
    echo "<td class=\"rx-td-data\">" . $id . "</td>";
    echo "<td class=\"rx-td-data\">" . $created . "</td>";
    echo "<td class=\"rx-td-data\">" . $patient . "</td>";
    echo "<td class=\"rx-td-data\">" . $docName . "</td>";
    echo "<td class=\"rx-td-action text-nowrap\">";
    echo "<button type=\"button\" class=\"btn btn-sm btn-info text-white mr-1\" onclick=\"rxView(" . $id . ");\"><i class=\"fa fa-eye\"></i> View</button>";
    if ($canEdit) {
      echo "<button type=\"button\" class=\"btn btn-sm btn-warning mr-1\" onclick=\"rxEdit(" . $id . ");\"><i class=\"fa fa-pencil\"></i> Edit</button>";
      echo "<button type=\"button\" class=\"btn btn-sm btn-danger\" onclick=\"rxDelete(" . $id . ");\"><i class=\"fa fa-trash\"></i> Delete</button>";
    } else {
      echo "<button type=\"button\" class=\"btn btn-sm btn-warning mr-1\" disabled title=\"Dispensed prescriptions cannot be edited\"><i class=\"fa fa-pencil\"></i> Edit</button>";
      echo "<button type=\"button\" class=\"btn btn-sm btn-secondary\" disabled title=\"Dispensed prescriptions cannot be deleted\"><i class=\"fa fa-trash\"></i> Delete</button>";
    }
    echo "</td>";
    echo "</tr>";
  }
  if (!$any) {
    echo "<tr><td colspan=\"5\" class=\"text-center text-muted py-4\">No prescriptions match your filters.</td></tr>";
  }
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'counter_list') {
  mysqli_query($con, "UPDATE prescriptions SET COUNTER_READ = 1 WHERE STATUS = 'SENT'");
  $q = "SELECT p.*, s.NAME AS DOCTOR_NAME FROM prescriptions p LEFT JOIN staff s ON s.ID = p.DOCTOR_STAFF_ID ORDER BY p.ID DESC";
  $r = mysqli_query($con, $q);
  while ($row = mysqli_fetch_assoc($r)) {
    $id = intval($row['ID']);
    $docName = htmlspecialchars($row['DOCTOR_NAME'] ?? 'Doctor');
    $patient = htmlspecialchars($row['PATIENT_NAME'] ?? '');
    $contact = htmlspecialchars($row['PATIENT_CONTACT'] ?? '');
    $medicine = htmlspecialchars($row['MEDICINE_LIST'] ?? '');
    $status = htmlspecialchars($row['STATUS'] ?? '');
    $canEdit = ($row['STATUS'] === 'SENT');
    echo "<tr>";
    echo "<td class=\"rx-td-data\">" . $id . "</td>";
    echo "<td class=\"rx-td-data\">" . $docName . "</td>";
    echo "<td class=\"rx-td-data\">" . $patient . "</td>";
    echo "<td class=\"rx-td-data\">" . $contact . "</td>";
    echo "<td class=\"rx-td-data\">" . $medicine . "</td>";
    echo "<td class=\"rx-td-data\">" . $status . "</td>";
    echo "<td class=\"rx-td-action text-nowrap\">";
    echo "<div class=\"btn-group btn-group-sm\" role=\"group\" aria-label=\"Counter actions\">";
    echo "<button type=\"button\" class=\"btn btn-info text-white\" onclick=\"rxCounterView(" . $id . ");\"><i class=\"fa fa-eye\"></i> View</button>";
    if ($canEdit) {
      echo "<button type=\"button\" class=\"btn btn-warning\" onclick=\"rxCounterEdit(" . $id . ");\"><i class=\"fa fa-pencil\"></i> Edit</button>";
      echo "<button type=\"button\" class=\"btn btn-danger\" onclick=\"rxCounterDelete(" . $id . ");\"><i class=\"fa fa-trash\"></i> Delete</button>";
      echo "<button type=\"button\" class=\"btn btn-success\" onclick=\"rxCounterDispense(" . $id . ");\"><i class=\"fa fa-check\"></i> Dispensed</button>";
    } else {
      echo "<button type=\"button\" class=\"btn btn-warning\" disabled title=\"Dispensed prescriptions cannot be edited\"><i class=\"fa fa-pencil\"></i> Edit</button>";
      echo "<button type=\"button\" class=\"btn btn-secondary\" disabled title=\"Dispensed prescriptions cannot be deleted\"><i class=\"fa fa-trash\"></i> Delete</button>";
      echo "<button type=\"button\" class=\"btn btn-secondary\" disabled><i class=\"fa fa-check\"></i> Dispensed</button>";
    }
    echo "</div>";
    echo "</td>";
    echo "</tr>";
  }
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'dispense') {
  $id = intval($_GET['id'] ?? 0);
  if ($id > 0) {
    mysqli_query($con, "UPDATE prescriptions SET STATUS='DISPENSED' WHERE ID=$id");
  }
  echo "ok";
  exit;
}
?>
