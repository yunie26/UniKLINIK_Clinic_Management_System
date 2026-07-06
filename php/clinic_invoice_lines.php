<?php

require_once __DIR__ . '/clinic_expiry.php';

/**
 * Snapshot of receipt line items per INVOICE_ID (source of truth for PDFs / history).
 */
function clinic_ensure_invoice_lines_table($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if ($done) {
    return;
  }
  $done = true;
  $sql = "CREATE TABLE IF NOT EXISTS `invoice_lines` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `INVOICE_ID` int(11) NOT NULL,
    `LINE_ORDER` int(11) NOT NULL DEFAULT 0,
    `MEDICINE_NAME` varchar(255) NOT NULL,
    `BATCH_ID` varchar(100) NOT NULL DEFAULT '',
    `EXPIRY_DATE` date DEFAULT NULL,
    `QUANTITY` int(11) NOT NULL,
    `MRP` decimal(10,2) NOT NULL,
    `LINE_TOTAL` decimal(10,2) NOT NULL,
    PRIMARY KEY (`ID`),
    KEY `INVOICE_ID` (`INVOICE_ID`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
  mysqli_query($con, $sql);
}

function clinic_delete_invoice_lines($con, $invoice_id) {
  if (!$con) {
    return;
  }
  clinic_ensure_invoice_lines_table($con);
  $invoice_id = intval($invoice_id);
  mysqli_query($con, "DELETE FROM invoice_lines WHERE INVOICE_ID = $invoice_id");
}

function clinic_insert_invoice_line($con, $invoice_id, $line_order, $medicine_name, $batch_id, $expiry_date, $quantity, $mrp, $line_total) {
  if (!$con) {
    return false;
  }
  clinic_ensure_invoice_lines_table($con);
  $invoice_id = intval($invoice_id);
  $line_order = intval($line_order);
  $medicine_name = mysqli_real_escape_string($con, $medicine_name);
  $batch_id = mysqli_real_escape_string($con, $batch_id);
  $exp_norm = clinic_expiry_input_to_mysql_date($expiry_date);
  $expiry_sql = $exp_norm === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $exp_norm) . "'";
  $quantity = intval($quantity);
  $mrp = floatval($mrp);
  $line_total = floatval($line_total);
  $q = "INSERT INTO invoice_lines (INVOICE_ID, LINE_ORDER, MEDICINE_NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP, LINE_TOTAL)
        VALUES ($invoice_id, $line_order, '$medicine_name', '$batch_id', $expiry_sql, $quantity, $mrp, $line_total)";
  return mysqli_query($con, $q);
}
