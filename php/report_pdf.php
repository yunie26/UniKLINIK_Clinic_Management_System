<?php
require_once __DIR__ . '/app_bootstrap.php';
require "db_connection.php";
require_once __DIR__ . '/clinic_pdf.php';

if (!$con) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Database connection failed.";
  exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($con, $_GET['start_date']) : "";
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($con, $_GET['end_date']) : "";

function clinic_report_range_label($start_date, $end_date) {
  if ($start_date === "" || $end_date === "") {
    return "All dates";
  }
  return $start_date . " to " . $end_date;
}

function clinic_build_sales_pdf($con, $start_date, $end_date) {
  $where = "";
  if ($start_date !== "" && $end_date !== "") {
    $where = " WHERE invoices.INVOICE_DATE BETWEEN '$start_date' AND '$end_date'";
  }
  $query = "SELECT invoices.INVOICE_DATE, invoices.INVOICE_ID, customers.NAME, invoices.NET_TOTAL
            FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID
            $where
            ORDER BY invoices.INVOICE_DATE ASC, invoices.INVOICE_ID ASC";
  $result = mysqli_query($con, $query);
  $rows = "";
  $seq = 0;
  $total = 0.0;
  if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
      $seq++;
      $net = (float)$r['NET_TOTAL'];
      $total += $net;
      $rows .= "<tr>
        <td style='padding:6px;border:1px solid #dbe2ea;'>$seq</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['INVOICE_DATE']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['INVOICE_ID']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['NAME']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;text-align:right;'>RM " . number_format($net, 2) . "</td>
      </tr>";
    }
  }
  if ($rows === "") {
    $rows = "<tr><td colspan='5' style='padding:8px;border:1px solid #dbe2ea;text-align:center;color:#64748b;'>No data found for selected range.</td></tr>";
  }
  return array($rows, $total);
}

function clinic_build_purchase_pdf($con, $start_date, $end_date) {
  $where = "";
  if ($start_date !== "" && $end_date !== "") {
    $where = " WHERE purchases.PURCHASE_DATE BETWEEN '$start_date' AND '$end_date'";
  }
  $query = "SELECT purchases.PURCHASE_DATE, purchases.VOUCHER_NUMBER, purchases.INVOICE_NUMBER, purchases.SUPPLIER_NAME, purchases.TOTAL_AMOUNT
            FROM purchases
            $where
            ORDER BY purchases.PURCHASE_DATE ASC, purchases.VOUCHER_NUMBER ASC";
  $result = mysqli_query($con, $query);
  $rows = "";
  $seq = 0;
  $total = 0.0;
  if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
      $seq++;
      $amt = (float)$r['TOTAL_AMOUNT'];
      $total += $amt;
      $rows .= "<tr>
        <td style='padding:6px;border:1px solid #dbe2ea;'>$seq</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['PURCHASE_DATE']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['VOUCHER_NUMBER']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['INVOICE_NUMBER']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;'>" . htmlspecialchars($r['SUPPLIER_NAME']) . "</td>
        <td style='padding:6px;border:1px solid #dbe2ea;text-align:right;'>RM " . number_format($amt, 2) . "</td>
      </tr>";
    }
  }
  if ($rows === "") {
    $rows = "<tr><td colspan='6' style='padding:8px;border:1px solid #dbe2ea;text-align:center;color:#64748b;'>No data found for selected range.</td></tr>";
  }
  return array($rows, $total);
}

$title = ($type === 'purchase') ? "Ordering Report" : "Sales Report";
$range = clinic_report_range_label($start_date, $end_date);

if ($type === 'purchase') {
  list($rows, $total) = clinic_build_purchase_pdf($con, $start_date, $end_date);
  $table = "<table style='width:100%;border-collapse:collapse;font-size:11px;'>
    <thead><tr style='background:#f1f5f9;color:#334155;'>
      <th style='padding:7px;border:1px solid #dbe2ea;'>No</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Ordering Date</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Voucher #</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Ordering Number</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Supplier Name</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Total Amount</th>
    </tr></thead><tbody>$rows</tbody></table>";
} else {
  list($rows, $total) = clinic_build_sales_pdf($con, $start_date, $end_date);
  $table = "<table style='width:100%;border-collapse:collapse;font-size:11px;'>
    <thead><tr style='background:#f1f5f9;color:#334155;'>
      <th style='padding:7px;border:1px solid #dbe2ea;'>No</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Sales Date</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Invoice Number</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Patient Name</th>
      <th style='padding:7px;border:1px solid #dbe2ea;'>Total Amount</th>
    </tr></thead><tbody>$rows</tbody></table>";
}

$html = "<html><head><meta charset='UTF-8'></head><body>
<div style='font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1e293b;'>
  <table style='width:100%;border-collapse:collapse;margin-bottom:12px;background:#0d9488;color:#fff;'><tr>
    <td style='padding:12px 14px;font-size:15px;font-weight:bold;'>$title</td>
    <td style='padding:12px 14px;text-align:right;'>$range</td>
  </tr></table>
  $table
  <p style='text-align:right;margin-top:10px;font-size:12px;font-weight:bold;'>Grand Total: RM " . number_format($total, 2) . "</p>
</div>
</body></html>";

$filename = ($type === 'purchase' ? 'ordering_report' : 'sales_report') . '.pdf';
clinic_send_pdf_html($html, $filename);
?>
