<?php
require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/clinic_invoice_lines.php';
require_once __DIR__ . '/clinic_sales_schema.php';

if (isset($_GET["action"])) {
  require "db_connection.php";

  if ($_GET["action"] == "delete") {
    $invoice_number = intval($_GET["invoice_number"]);
    clinic_ensure_sales_invoice_columns($con);
    clinic_delete_invoice_lines($con, $invoice_number);
    mysqli_query($con, "DELETE FROM sales WHERE INVOICE_ID = $invoice_number OR INVOICE_NUMBER = $invoice_number");
    $query = "DELETE FROM invoices WHERE INVOICE_ID = $invoice_number";
    $result = mysqli_query($con, $query);
    if (!empty($result)) showInvoices();
  }

  if ($_GET["action"] == "refresh") showInvoices();

  if ($_GET["action"] == "search")
    searchInvoice(strtoupper($_GET["text"]), $_GET["tag"]);

  if ($_GET["action"] == "print_invoice")
    printInvoice($_GET["invoice_number"]);

  if ($_GET["action"] == "pdf_invoice")
    pdfInvoice($_GET["invoice_number"]);
}

function ensurePrintLogTable($con) {
  $create = "CREATE TABLE IF NOT EXISTS print_logs (
    ID int(11) NOT NULL AUTO_INCREMENT,
    DOC_TYPE varchar(20) NOT NULL,
    DOC_ID int(11) NOT NULL,
    USER_ROLE varchar(20) NOT NULL,
    USER_NAME varchar(100) NOT NULL,
    PRINTED_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
  mysqli_query($con, $create);
}

function logPrintEvent($doc_type, $doc_id) {
  require "db_connection.php";
  if ($con) {
    ensurePrintLogTable($con);
    $user_role = isset($_SESSION['admin']) ? 'admin' : 'staff';
    $user_name = isset($_SESSION['admin']) ? $_SESSION['admin'] : (isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'unknown');
    $doc_type = mysqli_real_escape_string($con, $doc_type);
    $doc_id = intval($doc_id);
    $user_role = mysqli_real_escape_string($con, $user_role);
    $user_name = mysqli_real_escape_string($con, $user_name);
    $query = "INSERT INTO print_logs (DOC_TYPE, DOC_ID, USER_ROLE, USER_NAME) VALUES ('$doc_type', $doc_id, '$user_role', '$user_name')";
    mysqli_query($con, $query);
  }
}

function loadReceiptPayload($con, $invoice_number) {
  $invoice_number = intval($invoice_number);
  $query = "SELECT invoices.*, customers.NAME, customers.ADDRESS, customers.CONTACT_NUMBER, customers.DOCTOR_NAME, customers.DOCTOR_ADDRESS 
            FROM invoices 
            INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID 
            WHERE invoices.INVOICE_ID = $invoice_number";
  $result = mysqli_query($con, $query);
  $row = mysqli_fetch_assoc($result);
  if (!$row) {
    return null;
  }
  $lines = array();
  clinic_ensure_invoice_lines_table($con);
  $ilq = "SELECT MEDICINE_NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP, LINE_TOTAL AS TOTAL FROM invoice_lines WHERE INVOICE_ID = $invoice_number ORDER BY LINE_ORDER ASC, ID ASC";
  $ilr = mysqli_query($con, $ilq);
  if ($ilr) {
    while ($l = mysqli_fetch_assoc($ilr)) {
      $lines[] = $l;
    }
  }
  if (!empty($lines)) {
    return array('invoice' => $row, 'lines' => $lines);
  }

  $lines = array();
  static $salesColumns = null;
  if ($salesColumns === null) {
    $salesColumns = array();
    $cr = mysqli_query($con, "SHOW COLUMNS FROM sales");
    if ($cr) {
      while ($c = mysqli_fetch_assoc($cr)) {
        $salesColumns[strtoupper($c['Field'])] = true;
      }
    }
  }

  $hasBatch = isset($salesColumns['BATCH_ID']);
  $hasTotal = isset($salesColumns['TOTAL']);
  $selectBatch = $hasBatch ? "BATCH_ID" : "'' AS BATCH_ID";
  $selectTotal = $hasTotal ? "TOTAL" : "(QUANTITY * MRP) AS TOTAL";

  $whereCandidates = array();
  if (isset($salesColumns['INVOICE_ID'])) $whereCandidates[] = 'INVOICE_ID';
  if (isset($salesColumns['INVOICE_NUMBER'])) $whereCandidates[] = 'INVOICE_NUMBER';
  if (empty($whereCandidates)) $whereCandidates[] = 'INVOICE_ID';

  foreach ($whereCandidates as $whereInvoice) {
    $sq = "SELECT MEDICINE_NAME, $selectBatch, EXPIRY_DATE, QUANTITY, MRP, $selectTotal FROM sales WHERE $whereInvoice = $invoice_number ORDER BY ID ASC";
    $sr = mysqli_query($con, $sq);
    if ($sr) {
      while ($l = mysqli_fetch_assoc($sr)) {
        $lines[] = $l;
      }
    }
    if (!empty($lines)) {
      break;
    }
  }

  // Legacy recovery path:
  // Some historical entries saved sales rows under a mismatched invoice key.
  // If direct key lookup found nothing, try finding the best-matching sales group
  // for the same customer by comparing summed line totals with invoice net/total.
  if (empty($lines) && isset($salesColumns['CUSTOMER_ID']) && !empty($row['CUSTOMER_ID'])) {
    $customerId = intval($row['CUSTOMER_ID']);
    $targetTotal = isset($row['NET_TOTAL']) ? floatval($row['NET_TOTAL']) : floatval($row['TOTAL_AMOUNT']);
    $bestCol = null;
    $bestKey = null;
    $bestDiff = null;

    foreach ($whereCandidates as $groupCol) {
      $groupSql = "SELECT $groupCol AS K, SUM(QUANTITY * MRP) AS S
                   FROM sales
                   WHERE CUSTOMER_ID = $customerId
                   GROUP BY $groupCol";
      $groupRes = mysqli_query($con, $groupSql);
      if (!$groupRes) {
        continue;
      }
      while ($g = mysqli_fetch_assoc($groupRes)) {
        if (!isset($g['K']) || $g['K'] === null || $g['K'] === '') {
          continue;
        }
        $diff = abs(floatval($g['S']) - $targetTotal);
        if ($bestDiff === null || $diff < $bestDiff) {
          $bestDiff = $diff;
          $bestCol = $groupCol;
          $bestKey = intval($g['K']);
        }
      }
    }

    if ($bestCol !== null && $bestKey !== null) {
      $sq = "SELECT MEDICINE_NAME, $selectBatch, EXPIRY_DATE, QUANTITY, MRP, $selectTotal
             FROM sales
             WHERE CUSTOMER_ID = $customerId AND $bestCol = $bestKey
             ORDER BY ID ASC";
      $sr = mysqli_query($con, $sq);
      if ($sr) {
        while ($l = mysqli_fetch_assoc($sr)) {
          $lines[] = $l;
        }
      }
    }
  }

  return array('invoice' => $row, 'lines' => $lines);
}

function buildReceiptHtmlBody($invoice_number, $payload, $for_pdf) {
  $row = $payload['invoice'];
  $lines = $payload['lines'];
  $h = function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  };
  $back = $for_pdf ? '' : '<div class="mt-4"><a href="manage_invoice.php" class="btn btn-outline-secondary btn-sm">← Back to invoices</a></div>';
  
  $subtotal = (float) $row['TOTAL_AMOUNT'];
  $discount = (float) $row['TOTAL_DISCOUNT'];
  $total = (float) $row['NET_TOTAL'];
  
  $accent = '#79aef8';
  $darkBlue = '#2563eb';
  
  // Table for Medicine Details
  $table = '';
  if ($for_pdf) {
    $table .= '<table style="width:100%;border-collapse:collapse;font-size:10.5px;margin-top:20px;margin-bottom:20px;border:1px solid #e2e8f0;">';
    $table .= '<thead><tr style="background:' . $accent . ';color:white;">';
    $table .= '<th style="padding:12px;text-align:left;">Medicine Name</th>';
    $table .= '<th style="padding:12px;text-align:center;">Medicine ID</th>';
    $table .= '<th style="padding:12px;text-align:center;">Expiry Date</th>';
    $table .= '<th style="padding:12px;text-align:right;">Quantity</th>';
    $table .= '<th style="padding:12px;text-align:right;">Price (RM)</th>';
    $table .= '<th style="padding:12px;text-align:right;">Total Amount</th>';
    $table .= '</thead><tbody>';
    
    if (!empty($lines)) {
      foreach ($lines as $l) {
        $table .= '<tr>';
        $table .= '<td style="padding:10px;border-bottom:1px solid #e2e8f0;">' . $h($l['MEDICINE_NAME']) . '</td>';
        $table .= '<td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;">' . $h($l['BATCH_ID'] ?? '') . '</td>';
        $table .= '<td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;">' . $h($l['EXPIRY_DATE']) . '</td>';
        $table .= '<td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">' . $h($l['QUANTITY']) . '</td>';
        $table .= '<td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">RM ' . $h(number_format((float) $l['MRP'], 2)) . '</td>';
        $table .= '<td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">RM ' . $h(number_format((float) $l['TOTAL'], 2)) . '</td>';
        $table .= '</tr>';
      }
    } else {
      $table .= '<tr>';
      $table .= '<td style="padding:10px;text-align:center;" colspan="6">- No medicine data -</td>';
      $table .= '</tr>';
    }
    
    $table .= '</tbody></table>';
  } else {
    $table .= '<div class="table-responsive mt-4 mb-4"><table class="table table-bordered" style="border-color:#e2e8f0;">';
    $table .= '<thead><tr style="background:' . $accent . ';color:white;"><th style="padding:12px;">Medicine Name</th><th style="padding:12px;text-align:center;">Medicine ID</th><th style="padding:12px;text-align:center;">Expiry Date</th><th style="padding:12px;text-align:right;">Quantity</th><th style="padding:12px;text-align:right;">Price (RM)</th><th style="padding:12px;text-align:right;">Total Amount</th></thead><tbody>';
    
    if (!empty($lines)) {
      foreach ($lines as $l) {
        $table .= '<tr>';
        $table .= '<td style="padding:10px;">' . $h($l['MEDICINE_NAME']) . '</td>';
        $table .= '<td style="padding:10px;text-align:center;">' . $h($l['BATCH_ID'] ?? '') . '</td>';
        $table .= '<td style="padding:10px;text-align:center;">' . $h($l['EXPIRY_DATE']) . '</td>';
        $table .= '<td style="padding:10px;text-align:right;">' . $h($l['QUANTITY']) . '</td>';
        $table .= '<td style="padding:10px;text-align:right;">RM ' . $h(number_format((float) $l['MRP'], 2)) . '</td>';
        $table .= '<td style="padding:10px;text-align:right;">RM ' . $h(number_format((float) $l['TOTAL'], 2)) . '</td>';
        $table .= '</tr>';
      }
    } else {
      $table .= '<tr>';
      $table .= '<td style="padding:10px;text-align:center;" colspan="6">- No medicine data -</td>';
      $table .= '</tr>';
    }
    
    $table .= '</tbody></table></div>';
  }

  if ($for_pdf) {
    $html = '<div style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; max-width: 800px; margin: 0 auto; padding: 20px;">';
    
    // Header - Clinic Name
    $html .= '<div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid ' . $accent . '; padding-bottom: 15px;">';
    $html .= '<h1 style="font-size: 22px; margin: 0; color: #1e293b;">U.n.i KLINIK</h1>';
    $html .= '<p style="margin: 5px 0 0 0; color: #64748b; font-size: 10px;">Sentul, 15000 Kuala Lumpur | 03-12345678 | info@uniklinik.com</p>';
    $html .= '</div>';
    
    // RECEIPT Title
    $html .= '<h2 style="text-align: center; font-size: 16px; color: ' . $accent . '; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">RECEIPT</h2>';
    
    // Receipt Info (top right)
    $html .= '<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div><strong>Receipt #</strong> ' . $h($invoice_number) . '</div>';
    $html .= '<div><strong>Receipt Date</strong> ' . $h($row['INVOICE_DATE']) . '</div>';
    $html .= '</div></div>';
    
    // Bill To Section (Card Style)
    $html .= '<div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">';
    $html .= '<div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">';
    $html .= '<h3 style="color: ' . $darkBlue . '; font-size: 12px; margin: 0; font-weight: 600;">Bill To</h3>';
    $html .= '</div>';
    $html .= '<div style="padding: 15px; background-color: #ffffff;">';
    $html .= '<div><strong>Patient Name:</strong> ' . $h($row['NAME']) . '</div>';
    $html .= '<div style="margin-top: 6px;"><strong>Patient Address:</strong> ' . $h($row['ADDRESS']) . '</div>';
    $html .= '<div style="margin-top: 6px;"><strong>Contact Number:</strong> ' . $h($row['CONTACT_NUMBER']) . '</div>';
    $html .= '</div></div>';
    
    // Doctor Details Section (Card Style)
    $html .= '<div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">';
    $html .= '<div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">';
    $html .= '<h3 style="color: ' . $darkBlue . '; font-size: 12px; margin: 0; font-weight: 600;">Doctor Details</h3>';
    $html .= '</div>';
    $html .= '<div style="padding: 15px; background-color: #ffffff;">';
    $html .= '<div><strong>Doctor Name:</strong> ' . $h($row['DOCTOR_NAME']) . '</div>';
    $html .= '<div style="margin-top: 6px;"><strong>Doctor Address:</strong> ' . $h($row['DOCTOR_ADDRESS']) . '</div>';
    $html .= '</div></div>';
    
    // MEDICINE DETAILS Title
    $html .= '<h3 style="color: ' . $darkBlue . '; font-size: 11px; margin-bottom: 8px;">MEDICINE DETAILS</h3>';
    $html .= $table;
    
    // Totals
    $html .= '<div style="margin-top: 20px; padding-top: 15px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div style="margin-bottom: 8px;"><strong>Subtotal:</strong> RM ' . $h(number_format($subtotal, 2)) . '</div>';
    $html .= '<div style="margin-top: 8px;"><strong>Total:</strong> <span style="color: ' . $accent . '; font-size: 14px; font-weight: bold;">RM ' . $h(number_format($total, 2)) . '</span></div>';
    $html .= '</div></div>';
    
    // Thank you note
    $html .= '<div style="text-align: center; margin-top: 25px; padding-top: 10px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 9px;">';
    $html .= '<strong>Thank you for choosing U.n.i KLINIK!</strong>';
    $html .= '</div>';
    
    $html .= '</div>';
  } else {
    $html = '<div class="container my-4"><div class="card shadow-sm border-0" style="max-width: 1000px; margin: 0 auto; border-radius: 10px; overflow: hidden;">';
    $html .= '<div style="background: ' . $accent . '; padding: 15px 20px; text-align: center;">';
    $html .= '<h2 style="margin: 0; color: white; letter-spacing: 2px; font-size: 20px;">U.n.i KLINIK</h2>';
    $html .= '<p style="margin: 5px 0 0; color: rgba(255,255,255,0.9); font-size: 10px;">Sentul, 15000 Kuala Lumpur | 03-12345678 | info@uniklinik.com</p>';
    $html .= '</div>';
    $html .= '<div class="card-body p-4">';
    $html .= '<h3 style="text-align: center; color: ' . $accent . '; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; font-size: 16px;">RECEIPT</h3>';
    
    // Receipt Info (top right)
    $html .= '<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div><strong>Receipt #</strong> ' . $h($invoice_number) . '</div>';
    $html .= '<div><strong>Receipt Date</strong> ' . $h($row['INVOICE_DATE']) . '</div>';
    $html .= '</div></div>';
    
    // Bill To Section (Card style)
    $html .= '<div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">';
    $html .= '<div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">';
    $html .= '<h4 style="color: ' . $darkBlue . '; font-size: 13px; margin: 0; font-weight: 600;">Bill To</h4>';
    $html .= '</div>';
    $html .= '<div style="padding: 15px; background-color: #ffffff;">';
    $html .= '<div><strong>Patient Name:</strong> ' . $h($row['NAME']) . '</div>';
    $html .= '<div class="mt-2"><strong>Patient Address:</strong> ' . $h($row['ADDRESS']) . '</div>';
    $html .= '<div class="mt-2"><strong>Contact Number:</strong> ' . $h($row['CONTACT_NUMBER']) . '</div>';
    $html .= '</div></div>';
    
    // Doctor Details Section (Card style)
    $html .= '<div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">';
    $html .= '<div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">';
    $html .= '<h4 style="color: ' . $darkBlue . '; font-size: 13px; margin: 0; font-weight: 600;">Doctor Details</h4>';
    $html .= '</div>';
    $html .= '<div style="padding: 15px; background-color: #ffffff;">';
    $html .= '<div><strong>Doctor Name:</strong> ' . $h($row['DOCTOR_NAME']) . '</div>';
    $html .= '<div class="mt-2"><strong>Doctor Address:</strong> ' . $h($row['DOCTOR_ADDRESS']) . '</div>';
    $html .= '</div></div>';
    
    // MEDICINE DETAILS
    $html .= '<h4 style="color: ' . $darkBlue . '; font-size: 13px; margin-bottom: 8px;">MEDICINE DETAILS</h4>';
    $html .= $table;
    
    // Totals
    $html .= '<div style="margin-top: 20px; padding-top: 15px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div style="margin-bottom: 8px;"><strong>Subtotal:</strong> RM ' . $h(number_format($subtotal, 2)) . '</div>';
    $html .= '<div><strong>Total:</strong> <span style="color: ' . $accent . '; font-size: 16px; font-weight: bold;">RM ' . $h(number_format($total, 2)) . '</span></div>';
    $html .= '</div></div>';
    
    // Thank you note
    $html .= '<div style="text-align: center; margin-top: 25px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #64748b;">';
    $html .= '<strong>Thank you for choosing U.n.i KLINIK!</strong>';
    $html .= '</div>';
    
    $html .= $back . '</div></div></div>';
  }

  return $html;
}

function showInvoices() {
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    $query = "SELECT * FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      showInvoiceRow($seq_no, $row);
    }
  }
}

function showInvoiceRow($seq_no, $row) {
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['INVOICE_ID']; ?></td>
    <td><?php echo $row['NAME']; ?></td>
    <td><?php echo $row['INVOICE_DATE']; ?></td>
    <td><?php echo $row['TOTAL_AMOUNT']; ?></td>
    <td>
      <button class="btn btn-secondary btn-sm" onclick="downloadInvoicePdf(<?php echo $row['INVOICE_ID']; ?>);" title="Open receipt PDF">
        <i class="fa fa-file-pdf-o"></i>
      </button>
      <button class="btn btn-danger btn-sm" onclick="deleteInvoice(<?php echo $row['INVOICE_ID']; ?>);">
        <i class="fa fa-trash"></i>
      </button>
    </td>
  </tr>
  <?php
}

function searchInvoice($text, $column) {
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    if ($column == 'INVOICE_ID')
      $query = "SELECT * FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID WHERE CAST(invoices.$column AS CHAR) LIKE '%$text%'";
    else if ($column == "INVOICE_DATE")
      $query = "SELECT * FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID WHERE invoices.$column = '$text'";
    else
      $query = "SELECT * FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID WHERE UPPER(customers.$column) LIKE '%$text%'";

    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      showInvoiceRow($seq_no, $row);
    }
  }
}

function printInvoice($invoice_number) {
  require "db_connection.php";
  if (!$con) {
    echo "Database connection failed.";
    return;
  }
  logPrintEvent("RECEIPT", $invoice_number);
  $payload = loadReceiptPayload($con, $invoice_number);
  if (!$payload) {
    echo "Invoice not found.";
    return;
  }
  ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
  <?php
  echo buildReceiptHtmlBody($invoice_number, $payload, false);
}

function pdfInvoice($invoice_number) {
  require "db_connection.php";
  if (!$con) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database connection failed.";
    return;
  }
  logPrintEvent("RECEIPT", $invoice_number);
  $payload = loadReceiptPayload($con, $invoice_number);
  if (!$payload) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Invoice not found.";
    return;
  }
  require_once __DIR__ . '/clinic_pdf.php';
  $body = buildReceiptHtmlBody($invoice_number, $payload, true);
  $full = '<html><head><meta charset="UTF-8"><style>@page { margin: 20px; } body { margin: 0; padding: 0; }</style></head><body>' . $body . '</body></html>';
  clinic_send_pdf_html($full, 'receipt_' . intval($invoice_number) . '.pdf');
}

?>