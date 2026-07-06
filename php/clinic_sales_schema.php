<?php

require_once __DIR__ . '/clinic_expiry.php';

/**
 * Normalize `sales` table for POS receipts and build compatible INSERTs.
 */
function clinic_sales_column_set($con) {
  $cols = array();
  if (!$con) {
    return $cols;
  }
  $cr = mysqli_query($con, "SHOW COLUMNS FROM sales");
  if ($cr) {
    while ($c = mysqli_fetch_assoc($cr)) {
      $cols[strtoupper($c['Field'])] = $c;
    }
  }
  return $cols;
}

function clinic_ensure_sales_invoice_columns($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if ($done) {
    return;
  }
  $done = true;
  $cols = clinic_sales_column_set($con);
  $alters = array();
  if (!isset($cols['CUSTOMER_ID'])) {
    $alters[] = "ADD COLUMN `CUSTOMER_ID` int(11) NOT NULL DEFAULT 0";
  }
  if (!isset($cols['INVOICE_NUMBER'])) {
    $alters[] = "ADD COLUMN `INVOICE_NUMBER` int(11) NOT NULL DEFAULT 0";
  }
  if (!isset($cols['BATCH_ID'])) {
    $alters[] = "ADD COLUMN `BATCH_ID` varchar(100) NOT NULL DEFAULT ''";
  }
  if (!empty($alters)) {
    mysqli_query($con, "ALTER TABLE sales " . implode(", ", $alters));
  }
}

/**
 * Insert one sale row; omits TOTAL when column is generated.
 */
function clinic_insert_sale_line($con, $customer_id, $invoice_id, $medicine_name, $batch_id, $expiry_date, $quantity, $mrp, $line_total) {
  if (!$con) {
    return false;
  }
  clinic_ensure_sales_invoice_columns($con);
  $cols = clinic_sales_column_set($con);
  $customer_id = intval($customer_id);
  $invoice_id = intval($invoice_id);
  $medicine_name = mysqli_real_escape_string($con, $medicine_name);
  $batch_id = mysqli_real_escape_string($con, $batch_id);
  $exp_d = clinic_expiry_for_not_null_date($expiry_date);
  $expiry_sql = "'" . mysqli_real_escape_string($con, $exp_d) . "'";
  $quantity = intval($quantity);
  $mrp = floatval($mrp);
  $line_total = floatval($line_total);

  $fields = array();
  $values = array();

  if (isset($cols['CUSTOMER_ID'])) {
    $fields[] = 'CUSTOMER_ID';
    $values[] = (string) $customer_id;
  }
  if (isset($cols['INVOICE_NUMBER'])) {
    $fields[] = 'INVOICE_NUMBER';
    $values[] = (string) $invoice_id;
  }
  if (isset($cols['INVOICE_ID'])) {
    $fields[] = 'INVOICE_ID';
    $values[] = (string) $invoice_id;
  }
  $fields[] = 'MEDICINE_NAME';
  $values[] = "'" . $medicine_name . "'";
  if (isset($cols['BATCH_ID'])) {
    $fields[] = 'BATCH_ID';
    $values[] = "'" . $batch_id . "'";
  }
  $fields[] = 'EXPIRY_DATE';
  $values[] = $expiry_sql;
  $fields[] = 'QUANTITY';
  $values[] = (string) $quantity;
  $fields[] = 'MRP';
  $values[] = (string) $mrp;

  if (isset($cols['TOTAL'])) {
    $extra = strtoupper(trim($cols['TOTAL']['Extra'] ?? ''));
    $is_generated = ($extra !== '' && strpos($extra, 'GENERATED') !== false);
    if (!$is_generated) {
      $fields[] = 'TOTAL';
      $values[] = (string) $line_total;
    }
  }

  $q = 'INSERT INTO sales (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
  return mysqli_query($con, $q);
}
