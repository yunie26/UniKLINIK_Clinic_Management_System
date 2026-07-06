<?php

/**
 * Convert UI / stock expiry strings (e.g. MM/YY "12/26") to MySQL DATE (Y-m-d).
 * Uses last day of that month. Returns null only when input is empty/whitespace.
 */
function clinic_expiry_input_to_mysql_date($raw) {
  $raw = trim((string) $raw);
  if ($raw === '') {
    return null;
  }
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
    return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
  }
  if (preg_match('/^(\d{1,2})\/(\d{4})$/', $raw, $m)) {
    $month = (int) $m[1];
    $year = (int) $m[2];
    if ($month < 1 || $month > 12) {
      return null;
    }
    return date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
  }
  if (preg_match('/^(\d{1,2})\/(\d{2})$/', $raw, $m)) {
    $month = (int) $m[1];
    $yy = (int) $m[2];
    $year = 2000 + $yy;
    if ($month < 1 || $month > 12) {
      return null;
    }
    return date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
  }
  $ts = strtotime($raw);
  if ($ts !== false) {
    return date('Y-m-d', $ts);
  }
  return null;
}

/**
 * For NOT NULL DATE columns: never null; use last-resort sentinel if unparseable.
 */
function clinic_expiry_for_not_null_date($raw) {
  $d = clinic_expiry_input_to_mysql_date($raw);
  if ($d !== null) {
    return $d;
  }
  $raw = trim((string) $raw);
  if ($raw === '') {
    return '2099-12-31';
  }
  return '2099-12-31';
}
