<?php
require_once __DIR__ . '/app_bootstrap.php';
require "db_connection.php";
require_once __DIR__ . '/prescription_schema.php';
require_once __DIR__ . '/notification_store.php';
require_once __DIR__ . '/notification_url_map.php';
require_once __DIR__ . '/clinic_expiry.php';

if (!defined('CLINIC_LOW_STOCK_THRESHOLD')) {
  define('CLINIC_LOW_STOCK_THRESHOLD', 10);
}

if (!defined('CLINIC_EXPIRY_SOON_DAYS')) {
  define('CLINIC_EXPIRY_SOON_DAYS', 30);
}

$role = isset($_GET['role']) ? $_GET['role'] : 'staff';
$notifications = array();
$viewerRole = ($role === 'admin') ? 'admin' : 'staff';
$viewerId = '';
if ($viewerRole === 'admin') {
  $viewerId = isset($_SESSION['admin']) ? (string) $_SESSION['admin'] : '';
} else {
  $viewerId = isset($_SESSION['staff_id']) ? (string) $_SESSION['staff_id'] : '';
}

if ($con) {
  clinic_ensure_prescription_tables($con);
  clinic_ensure_notification_events_table($con);
  clinic_ensure_notification_reads_table($con);

  if (isset($_GET['action']) && $_GET['action'] === 'mark_read') {
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';
    clinic_mark_notification_read($con, $viewerRole, $viewerId, $key);
    header('Content-Type: application/json');
    echo json_encode(array("ok" => true));
    exit;
  }
  if ($role === 'admin') {
    $pendingLeaveQuery = "SELECT COUNT(*) AS total FROM staff_leave WHERE STATUS = 'Pending'";
    $pendingLeaveResult = mysqli_query($con, $pendingLeaveQuery);
    $pendingLeave = mysqli_fetch_assoc($pendingLeaveResult);
    if (!empty($pendingLeave['total']) && intval($pendingLeave['total']) > 0) {
      $notifications[] = array(
        "key" => "admin_pending_leave_" . time(),
        "category" => "leave",
        "title" => "New Leave Applications",
        "message" => intval($pendingLeave['total']) . " leave request(s) pending approval.",
        "url" => "manage_staff_leave.php",
        "timestamp" => date('Y-m-d H:i:s')
      );
    }

    $pendingOTQuery = "SELECT COUNT(*) AS total FROM ot_requests WHERE status = 'Pending'";
    $pendingOTResult = mysqli_query($con, $pendingOTQuery);
    $pendingOT = mysqli_fetch_assoc($pendingOTResult);
    if (!empty($pendingOT['total']) && intval($pendingOT['total']) > 0) {
      $notifications[] = array(
        "key" => "admin_pending_ot_" . time(),
        "category" => "ot",
        "title" => "New OT Applications",
        "message" => intval($pendingOT['total']) . " OT request(s) pending approval.",
        "url" => "admin_ot_requests.php",
        "timestamp" => date('Y-m-d H:i:s')
      );
    }
  }

  if ($role === 'staff' && isset($_SESSION['staff_id'])) {
    $staffId = intval($_SESSION['staff_id']);
    $staffRole = isset($_SESSION['staff_role']) ? $_SESSION['staff_role'] : 'Clinic Assistant';

    $leaveStatusQuery = "SELECT ID, STATUS, END_DATE FROM staff_leave WHERE STAFF_ID = $staffId AND STATUS IN ('Approved','Rejected') ORDER BY ID DESC LIMIT 5";
    $leaveStatusResult = mysqli_query($con, $leaveStatusQuery);
    while ($row = mysqli_fetch_assoc($leaveStatusResult)) {
      $notifications[] = array(
        "key" => "staff_leave_" . intval($row['ID']) . "_" . $row['STATUS'],
        "category" => "leave",
        "title" => "Leave Request " . $row['STATUS'],
        "message" => "Your leave request was " . strtolower($row['STATUS']) . ".",
        "url" => "view_leave_status.php",
        "timestamp" => $row['END_DATE']
      );
    }

    $otStatusQuery = "SELECT id, status, ot_date FROM ot_requests WHERE staff_id = $staffId AND status IN ('Approved','Rejected') ORDER BY id DESC LIMIT 5";
    $otStatusResult = mysqli_query($con, $otStatusQuery);
    while ($row = mysqli_fetch_assoc($otStatusResult)) {
      $notifications[] = array(
        "key" => "staff_ot_" . intval($row['id']) . "_" . $row['status'],
        "category" => "ot",
        "title" => "OT Request " . $row['status'],
        "message" => "Your OT request was " . strtolower($row['status']) . ".",
        "url" => "staff_ot_requests.php",
        "timestamp" => $row['ot_date']
      );
    }

    if ($staffRole === 'Doctor') {
      $dispensedQuery = "SELECT COUNT(*) AS total, MAX(UPDATED_AT) AS last_evt FROM prescriptions WHERE DOCTOR_STAFF_ID = $staffId AND STATUS = 'DISPENSED'";
      $dispensedResult = mysqli_query($con, $dispensedQuery);
      $dispensed = mysqli_fetch_assoc($dispensedResult);
      if (!empty($dispensed['total']) && intval($dispensed['total']) > 0) {
        $rx_ts = !empty($dispensed['last_evt']) ? $dispensed['last_evt'] : date('Y-m-d H:i:s');
        $notifications[] = array(
          "key" => "doctor_dispensed_" . $staffId,
          "category" => "prescription",
          "title" => "Prescription Update",
          "message" => intval($dispensed['total']) . " prescription(s) marked as dispensed.",
          "url" => "doctor_prescription_history.php",
          "timestamp" => $rx_ts
        );
      }
    }
    /* New prescriptions for counter staff: stored in DB on create (prescription_new_{id}).
       Do not duplicate with a COUNTER_READ aggregate here. */
  }

  mysqli_query($con, "DELETE FROM notification_events WHERE EVENT_KEY = 'low_stock_alert'");
  mysqli_query($con, "DELETE FROM notification_reads  WHERE EVENT_KEY = 'low_stock_alert'");

  $lowThreshold = (int) CLINIC_LOW_STOCK_THRESHOLD;
  $lowStockQuery = "SELECT NAME, BATCH_ID, QUANTITY
                      FROM medicines_stock
                     WHERE QUANTITY <= $lowThreshold
                     ORDER BY QUANTITY ASC, NAME ASC";
  $lowStockResult = mysqli_query($con, $lowStockQuery);
  $lowByName = array();
  if ($lowStockResult) {
    while ($row = mysqli_fetch_assoc($lowStockResult)) {
      $nm = (string) $row['NAME'];
      if ($nm === '')
        continue;
      if (!isset($lowByName[$nm])) {
        $lowByName[$nm] = array('qty' => PHP_INT_MAX, 'batches' => array());
      }
      $q = (int) $row['QUANTITY'];
      if ($q < $lowByName[$nm]['qty']) {
        $lowByName[$nm]['qty'] = $q;
      }
      if (!empty($row['BATCH_ID'])) {
        $lowByName[$nm]['batches'][] = (string) $row['BATCH_ID'];
      }
    }
  }
  foreach ($lowByName as $name => $info) {
    $qty = (int) $info['qty'];
    $keySafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
    if ($qty <= 0) {
      $title = "Out of Stock";
      $msg = $name . " is out of stock. Please restock.";
    } else {
      $title = "Low Stock Alert";
      $msg = $name . " is low in stock (" . $qty . " unit" . ($qty === 1 ? "" : "s") . " left).";
    }
    $notifications[] = array(
      "key" => "low_stock_" . $keySafe,
      "category" => "stock",
      "title" => $title,
      "message" => $msg,
      "url" => "manage_medicine_stock.php?low_stock=1",
      "timestamp" => date('Y-m-d H:i:s')
    );
  }

  $expiryQuery = "SELECT NAME, BATCH_ID, EXPIRY_DATE, QUANTITY FROM medicines_stock";
  $expiryResult = mysqli_query($con, $expiryQuery);
  if ($expiryResult) {
    $todayTs = strtotime(date('Y-m-d'));
    $soonDays = (int) CLINIC_EXPIRY_SOON_DAYS;
    while ($row = mysqli_fetch_assoc($expiryResult)) {
      $rawExp = (string) $row['EXPIRY_DATE'];
      if ($rawExp === '')
        continue;
      $mysqlDate = clinic_expiry_input_to_mysql_date($rawExp);
      if ($mysqlDate === null)
        continue;
      $expTs = strtotime($mysqlDate);
      if ($expTs === false)
        continue;
      $diffDays = (int) floor(($expTs - $todayTs) / 86400);
      $batch = (string) $row['BATCH_ID'];
      $name = (string) $row['NAME'];
      if ($batch === '' || $name === '')
        continue;
      $batchSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $batch);
      if ($diffDays < 0) {
        $notifications[] = array(
          "key" => "expired_" . $batchSafe,
          "category" => "stock",
          "title" => "Expired Medicine",
          "message" => $name . " (Batch " . $batch . ") expired on " . $rawExp . ".",
          "url" => "manage_medicine_stock.php?expired=1",
          "timestamp" => date('Y-m-d H:i:s')
        );
      } else if ($diffDays <= $soonDays) {
        $notifications[] = array(
          "key" => "expiring_" . $batchSafe,
          "category" => "stock",
          "title" => "Medicine Expiring Soon",
          "message" => $name . " (Batch " . $batch . ") expires on " . $rawExp
            . " (" . $diffDays . " day" . ($diffDays === 1 ? "" : "s") . " left).",
          "url" => "manage_medicine_stock.php?expiring=1",
          "timestamp" => date('Y-m-d H:i:s')
        );
      }
    }
  }

  foreach ($notifications as $n) {
    $scope = ($role === 'admin') ? 'admin' : 'staff';
    $k = isset($n['key']) ? $n['key'] : '';
    if (
      strpos($k, 'low_stock_') === 0
      || strpos($k, 'expired_') === 0
      || strpos($k, 'expiring_') === 0
    ) {
      $scope = 'both';
    }
    clinic_store_notification_event(
      $con,
      $n['key'],
      $scope,
      $n['title'],
      $n['message'],
      isset($n['url']) ? $n['url'] : '',
      isset($n['timestamp']) ? $n['timestamp'] : date('Y-m-d H:i:s'),
      isset($n['category']) ? $n['category'] : 'general'
    );
  }

  if (isset($_GET['action']) && $_GET['action'] === 'history') {
    $history = clinic_fetch_notification_history($con, $role, 400);
    $normalized = array();
    foreach ($history as $h) {
      $normalized[] = array(
        "key" => $h['EVENT_KEY'],
        "category" => !empty($h['CATEGORY']) ? $h['CATEGORY'] : 'general',
        "title" => $h['TITLE'],
        "message" => $h['MESSAGE'],
        "url" => clinic_notification_url_for_role($h['URL'], $role),
        "timestamp" => $h['EVENT_TS'],
        "seen_count" => intval($h['SEEN_COUNT'])
      );
    }
    header('Content-Type: application/json');
    echo json_encode(array("notifications" => $normalized, "count" => count($normalized)));
    exit;
  }

  $history = clinic_fetch_notification_history($con, $role, 120);
  $byKey = array();
  foreach ($notifications as $n) {
    $n['url'] = clinic_notification_url_for_role(isset($n['url']) ? $n['url'] : '', $role);
    if (!isset($n['category'])) {
      $n['category'] = 'general';
    }
    $byKey[$n['key']] = $n;
  }
  foreach ($history as $h) {
    $k = $h['EVENT_KEY'];
    if (isset($byKey[$k])) {
      continue;
    }
    if (
      strpos($k, 'low_stock_') === 0
      || strpos($k, 'expired_') === 0
      || strpos($k, 'expiring_') === 0
    ) {
      continue;
    }
    $byKey[$k] = array(
      "key" => $k,
      "category" => !empty($h['CATEGORY']) ? $h['CATEGORY'] : 'general',
      "title" => $h['TITLE'],
      "message" => $h['MESSAGE'],
      "url" => clinic_notification_url_for_role($h['URL'], $role),
      "timestamp" => $h['EVENT_TS']
    );
  }
  $notifications = array_values($byKey);

  /* Counter-queue alerts are for clinic/counter staff, not doctors. */
  if ($role === 'staff' && isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Doctor') {
    $filtered = array();
    foreach ($notifications as $n) {
      $k = isset($n['key']) ? $n['key'] : '';
      if (strpos($k, 'prescription_new_') === 0) {
        continue;
      }
      $filtered[] = $n;
    }
    $notifications = $filtered;
  }

  // DB-backed read filtering per user (shared across devices).
  $keys = array();
  foreach ($notifications as $n) {
    if (isset($n['key']))
      $keys[] = $n['key'];
  }
  $readMap = clinic_fetch_read_keys($con, $viewerRole, $viewerId, $keys);
  $unread = array();
  foreach ($notifications as $n) {
    $k = isset($n['key']) ? $n['key'] : '';
    if ($k !== '' && isset($readMap[$k])) {
      continue;
    }
    $unread[] = $n;
  }
  $notifications = $unread;
}

header('Content-Type: application/json');
echo json_encode(array(
  "notifications" => $notifications,
  "unread_count" => count($notifications)
));
?>