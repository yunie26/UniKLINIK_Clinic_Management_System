<?php

function clinic_notification_url_for_role($url, $role) {
  $url = (string)$url;
  if ($url === '') {
    return $url;
  }
  if ($role !== 'staff') {
    return $url;
  }

  $map = array(
    'manage_medicine_stock.php' => 'manage_medicine_stock_staff.php',
    'manage_purchase.php' => 'manage_purchase_staff.php',
    'manage_invoice.php' => 'manage_invoice_staff.php',
    'manage_customer.php' => 'manage_customer_staff.php',
    'manage_supplier.php' => 'manage_supplier_staff.php',
    'new_invoice.php' => 'new_invoice_staff.php',
    'sales_report.php' => 'sales_report_staff.php',
    'purchase_report.php' => 'purchase_report_staff.php'
  );

  foreach ($map as $from => $to) {
    if (strpos($url, $from) !== false) {
      return str_replace($from, $to, $url);
    }
  }
  return $url;
}

?>
