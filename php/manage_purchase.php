<?php
require_once __DIR__ . '/app_bootstrap.php';
require_once __DIR__ . '/clinic_purchase_order_lines.php';
require "db_connection.php";

if ($con) {
  if (isset($_GET["action"]) && $_GET["action"] == "delete") {
    $id = $_GET["id"];
    $query = "DELETE FROM purchases WHERE VOUCHER_NUMBER = $id";
    $result = mysqli_query($con, $query);
    if (!empty($result))
      showPurchases(0);
  }

  if (isset($_GET["action"]) && $_GET["action"] == "edit") {
    $id = $_GET["id"];
    showPurchases($id);
  }

  if (isset($_GET["action"]) && $_GET["action"] == "update") {
    $id = $_GET["id"];
    $suppliers_name = ucwords($_GET["suppliers_name"]);
    $invoice_date = $_GET["invoice_date"];
    $grand_total = $_GET["grand_total"];
    $payment_status = $_GET["payment_status"];
    updatePurchase($id, $suppliers_name, $invoice_date, $grand_total, $payment_status);
  }

  if (isset($_GET["action"]) && $_GET["action"] == "cancel")
    showPurchases(0);

  if (isset($_GET["action"]) && $_GET["action"] == "search")
    searchPurchase(strtoupper($_GET["text"]), $_GET["tag"]);

  if (isset($_GET["action"]) && $_GET["action"] == "print_purchase")
    printPurchaseByVoucher(intval($_GET["voucher_number"]));

  if (isset($_GET["action"]) && $_GET["action"] == "print_purchase_by_invoice")
    printPurchaseByInvoice(intval($_GET["invoice_number"]));

  if (isset($_GET["action"]) && $_GET["action"] == "pdf_purchase")
    pdfPurchaseByVoucher(intval($_GET["voucher_number"]));

  if (isset($_GET["action"]) && $_GET["action"] == "pdf_purchase_by_invoice")
    pdfPurchaseByInvoice(intval($_GET["invoice_number"]));
}

function ensurePrintLogTable($con)
{
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

function logPrintEvent($doc_type, $doc_id)
{
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

function loadOrderPayload($con, $voucher_number)
{
  $voucher_number = intval($voucher_number);
  $query = "SELECT * FROM purchases WHERE VOUCHER_NUMBER = $voucher_number";
  $result = mysqli_query($con, $query);
  $purchase = mysqli_fetch_assoc($result);
  if (!$purchase) {
    return null;
  }
  $invoice_number = intval($purchase['INVOICE_NUMBER']);
  $items = array();
  clinic_ensure_purchase_order_lines_table($con);
  $lines_q = "SELECT MEDICINE_NAME AS NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP FROM purchase_order_lines WHERE VOUCHER_NUMBER = $voucher_number ORDER BY LINE_ORDER ASC, ID ASC";
  $lines_r = mysqli_query($con, $lines_q);
  if ($lines_r) {
    while ($item = mysqli_fetch_assoc($lines_r)) {
      $items[] = $item;
    }
  }
  if (empty($items)) {
    $items_query = "SELECT NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP FROM medicines_stock WHERE INVOICE_NUMBER = $invoice_number";
    $items_result = mysqli_query($con, $items_query);
    if ($items_result) {
      while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
      }
    }
  }
  return array('purchase' => $purchase, 'items' => $items);
}

function buildOrderHtmlBody($payload, $for_pdf)
{
  $purchase = $payload['purchase'];
  $items = $payload['items'];
  $h = function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  };
  $accent = '#79aef8';
  $darkBlue = '#2563eb';

  $sum = 0;
  $rows = '';
  $i = 0;
  foreach ($items as $item) {
    $amount = floatval($item['QUANTITY']) * floatval($item['MRP']);
    $sum += $amount;
    $i++;
    if ($for_pdf) {
      $rows .= '<tr>';
      $rows .= '<td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . $h($item['NAME']) . '</td>';
      $rows .= '<td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;">' . $h($item['BATCH_ID']) . '</td>';
      $rows .= '<td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;">' . $h($item['EXPIRY_DATE']) . '</td>';
      $rows .= '<td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;">' . $h($item['QUANTITY']) . '</td>';
      $rows .= '<td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;">RM ' . $h(number_format((float) $item['MRP'], 2)) . '</td>';
      $rows .= '<td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;">RM ' . $h(number_format($amount, 2)) . '</td>';
      $rows .= '</tr>';
    } else {
      $rows .= '<tr>';
      $rows .= '<td style="padding: 10px;">' . $h($item['NAME']) . '</td>';
      $rows .= '<td style="padding: 10px; text-align: center;">' . $h($item['BATCH_ID']) . '</td>';
      $rows .= '<td style="padding: 10px; text-align: center;">' . $h($item['EXPIRY_DATE']) . '</td>';
      $rows .= '<td style="padding: 10px; text-align: right;">' . $h($item['QUANTITY']) . '</td>';
      $rows .= '<td style="padding: 10px; text-align: right;">RM ' . $h(number_format((float) $item['MRP'], 2)) . '</td>';
      $rows .= '<td style="padding: 10px; text-align: right;">RM ' . $h(number_format($amount, 2)) . '</td>';
      $rows .= '</tr>';
    }
  }

  $storedTotal = isset($purchase['TOTAL_AMOUNT']) ? (float) $purchase['TOTAL_AMOUNT'] : $sum;
  $back = $for_pdf ? '' : '<div class="mt-4"><a href="manage_purchase.php" class="btn btn-outline-secondary btn-sm">← Back to purchases</a></div>';

  if ($for_pdf) {
    $html = '<div style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; max-width: 800px; margin: 0 auto; padding: 20px;">';

    // Header - Clinic Name
    $html .= '<div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid ' . $accent . '; padding-bottom: 15px;">';
    $html .= '<h1 style="font-size: 22px; margin: 0; color: #1e293b;">U.n.i KLINIK</h1>';
    $html .= '<p style="margin: 5px 0 0 0; color: #64748b; font-size: 10px;">Sentul, 15000 Kuala Lumpur | 03-12345678 | info@uniklinik.com</p>';
    $html .= '</div>';

    // PURCHASE ORDER Title
    $html .= '<h2 style="text-align: center; font-size: 16px; color: ' . $accent . '; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">PURCHASE ORDER</h2>';

    // Purchase Order Info (top right)
    $html .= '<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div><strong>Purchase Order #</strong> ' . $h($purchase['VOUCHER_NUMBER']) . '</div>';
    $html .= '<div><strong>Date Purchased Order</strong> ' . $h($purchase['PURCHASE_DATE']) . '</div>';
    $html .= '</div></div>';

    // ORDER FROM Section
    $html .= '<div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">';
    $html .= '<div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">';
    $html .= '<h3 style="color: ' . $darkBlue . '; font-size: 12px; margin: 0; font-weight: 600;">Order From</h3>';
    $html .= '</div>';
    $html .= '<div style="padding: 15px; background-color: #ffffff;">';
    $html .= '<div><strong>Supplier Name:</strong> ' . $h($purchase['SUPPLIER_NAME']) . '</div>';
    $html .= '<div style="margin-top: 8px;"><strong>Invoice No.:</strong> ' . $h($purchase['INVOICE_NUMBER']) . '</div>';
    $html .= '<div style="margin-top: 8px;"><strong>Payment Status:</strong> ' . $h($purchase['PAYMENT_STATUS']) . '</div>';
    $html .= '</div></div>';

    // MEDICINE DETAILS Title
    $html .= '<h3 style="color: ' . $darkBlue . '; font-size: 11px; margin-bottom: 8px;">MEDICINE DETAILS</h3>';

    // Items Table
    $html .= '<table style="width:100%;border-collapse:collapse;font-size:10.5px;margin-top:10px;margin-bottom:20px;border:1px solid #e2e8f0;">';
    $html .= '<thead><tr style="background:' . $accent . ';color:white;">';
    $html .= '<th style="padding:12px;text-align:left;">Medicine Name</th>';
    $html .= '<th style="padding:12px;text-align:center;">Medicine ID</th>';
    $html .= '<th style="padding:12px;text-align:center;">Expiry Date</th>';
    $html .= '<th style="padding:12px;text-align:right;">Quantity</th>';
    $html .= '<th style="padding:12px;text-align:right;">Price (RM)</th>';
    $html .= '<th style="padding:12px;text-align:right;">Total Amount</th>';
    $html .= '</thead><tbody>';
    $html .= $rows;
    if (empty($items)) {
      $html .= '<tr><td style="padding:10px;text-align:center;" colspan="6">- No medicine data -</td></tr>';
    }
    $html .= '</tbody></table>';

    // Totals
    $html .= '<div style="margin-top: 20px; padding-top: 15px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div style="margin-bottom: 8px;"><strong>Subtotal:</strong> RM ' . $h(number_format($sum, 2)) . '</div>';
    $html .= '<div><strong>Total:</strong> <span style="color: ' . $accent . '; font-size: 14px; font-weight: bold;">RM ' . $h(number_format($storedTotal, 2)) . '</span></div>';
    $html .= '</div></div>';

    // Thank you note
    $html .= '<div style="text-align: center; margin-top: 25px; padding-top: 10px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 9px;">';
    $html .= '<strong>Thank you for your business!</strong>';
    $html .= '</div>';

    $html .= '</div>';
  } else {
    $html = '<div class="container my-4"><div class="card shadow-sm border-0" style="max-width: 1000px; margin: 0 auto; border-radius: 10px; overflow: hidden;">';
    $html .= '<div style="background: ' . $accent . '; padding: 15px 20px; text-align: center;">';
    $html .= '<h2 style="margin: 0; color: white; letter-spacing: 2px; font-size: 20px;">U.n.i KLINIK</h2>';
    $html .= '<p style="margin: 5px 0 0; color: rgba(255,255,255,0.9); font-size: 10px;">Sentul, 15000 Kuala Lumpur | 03-12345678 | info@uniklinik.com</p>';
    $html .= '</div>';
    $html .= '<div class="card-body p-4">';
    $html .= '<h3 style="text-align: center; color: ' . $accent . '; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; font-size: 16px;">PURCHASE ORDER</h3>';

    // Purchase Order Info
    $html .= '<div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div><strong>Purchase Order #</strong> ' . $h($purchase['VOUCHER_NUMBER']) . '</div>';
    $html .= '<div><strong>Date Purchased Order</strong> ' . $h($purchase['PURCHASE_DATE']) . '</div>';
    $html .= '</div></div>';

    // ORDER FROM Section
    $html .= '<div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">';
    $html .= '<div style="background-color: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">';
    $html .= '<h4 style="color: ' . $darkBlue . '; font-size: 13px; margin: 0; font-weight: 600;">Order From</h4>';
    $html .= '</div>';
    $html .= '<div style="padding: 15px; background-color: #ffffff;">';
    $html .= '<div><strong>Supplier Name:</strong> ' . $h($purchase['SUPPLIER_NAME']) . '</div>';
    $html .= '<div class="mt-2"><strong>Invoice No.:</strong> ' . $h($purchase['INVOICE_NUMBER']) . '</div>';
    $html .= '<div class="mt-2"><strong>Payment Status:</strong> ' . $h($purchase['PAYMENT_STATUS']) . '</div>';
    $html .= '</div></div>';

    // MEDICINE DETAILS
    $html .= '<h4 style="color: ' . $darkBlue . '; font-size: 13px; margin-bottom: 8px;">MEDICINE DETAILS</h4>';

    $html .= '<div class="table-responsive mt-3 mb-4"><table class="table table-bordered" style="border-color:#e2e8f0;">';
    $html .= '<thead><tr style="background:' . $accent . ';color:white;"><th style="padding:12px;">Medicine Name</th><th style="padding:12px;text-align:center;">Medicine ID</th><th style="padding:12px;text-align:center;">Expiry Date</th><th style="padding:12px;text-align:right;">Quantity</th><th style="padding:12px;text-align:right;">Price (RM)</th><th style="padding:12px;text-align:right;">Total Amount</th></thead><tbody>';
    $html .= $rows;
    if (empty($items)) {
      $html .= '<tr><td style="padding:10px;text-align:center;" colspan="6">- No medicine data -</tr></tr>';
    }
    $html .= '</tbody></table></div>';

    // Totals
    $html .= '<div style="margin-top: 20px; padding-top: 15px;">';
    $html .= '<div style="text-align: right;">';
    $html .= '<div style="margin-bottom: 8px;"><strong>Subtotal:</strong> RM ' . $h(number_format($sum, 2)) . '</div>';
    $html .= '<div><strong>Total:</strong> <span style="color: ' . $accent . '; font-size: 16px; font-weight: bold;">RM ' . $h(number_format($storedTotal, 2)) . '</span></div>';
    $html .= '</div></div>';

    // Thank you note
    $html .= '<div style="text-align: center; margin-top: 25px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #64748b;">';
    $html .= '<strong>Thank you for your business!</strong>';
    $html .= '</div>';

    $html .= $back . '</div></div></div>';
  }
  return $html;
}

function showPurchases($id)
{
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    $query = "SELECT * FROM purchases";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      if ($row['VOUCHER_NUMBER'] == $id)
        showEditOptionsRow($seq_no, $row);
      else
        showPurchaseRow($seq_no, $row);
    }
  }
}

function showPurchaseRow($seq_no, $row)
{
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['VOUCHER_NUMBER']; ?></td>
    <td><?php echo $row['SUPPLIER_NAME'] ?></td>
    <td><?php echo $row['INVOICE_NUMBER']; ?></td>
    <td><?php echo $row['PURCHASE_DATE']; ?></td>
    <td><?php echo $row['TOTAL_AMOUNT']; ?></td>
    <td><?php echo $row['PAYMENT_STATUS']; ?></td>
    <td>
      <button class="btn btn-secondary btn-sm" onclick="downloadOrderPdf(<?php echo $row['VOUCHER_NUMBER']; ?>);"
        title="Open order PDF">
        <i class="fa-solid fa-file-pdf"></i> </button>
      <button class="btn btn-danger btn-sm" onclick="deletePurchase(<?php echo $row['VOUCHER_NUMBER']; ?>);">
        <i class="fa fa-trash"></i>
      </button>
    </td>
  </tr>
  <?php
}

function showEditOptionsRow($seq_no, $row)
{
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['VOUCHER_NUMBER'] ?></td>
    <td>
      <input id="suppliers_name" type="text" class="form-control" value="<?php echo $row['SUPPLIER_NAME']; ?>"
        placeholder="Supplier Name" name="suppliers_name" onkeyup="showSuggestions(this.value, 'supplier');" disabled>
    </td>
    <td>
      <input type="number" class="form-control" value="<?php echo $row['INVOICE_NUMBER']; ?>" id="invoice_number"
        disabled>
    </td>
    <td>
      <input type="date" class="datepicker form-control hasDatepicker" id="invoice_date" name="invoice_date"
        value='<?php echo $row['PURCHASE_DATE'] ?>' onblur="checkDate(this.value, 'date_error');">
      <code class="text-danger small font-weight-bold float-right" id="date_error" style="display: none;"></code>
    </td>
    <td><input type="text" class="form-control" value="<?php echo $row['TOTAL_AMOUNT']; ?>" id="grand_total"
        name="grand_total" disabled></td>
    <td>
      <select id="payment_status" class="form-control">
        <option value="DUE" <?php if ($row['PAYMENT_STATUS'] == "DUE")
          echo "selected" ?>>DUE</option>
          <option value="PAID" <?php if ($row['PAYMENT_STATUS'] == "PAID")
          echo "selected" ?>>PAID</option>
        </select>
      </td>
      <td>
        <button href="" class="btn btn-success btn-sm" onclick="updatePurchase(<?php echo $row['VOUCHER_NUMBER']; ?>);">
        <i class="fa fa-edit"></i>
      </button>
      <button class="btn btn-danger btn-sm" onclick="cancel();">
        <i class="fa fa-close"></i>
      </button>
    </td>
  </tr>
  <?php
}

function updatePurchase($id, $suppliers_name, $invoice_date, $grand_total, $payment_status)
{
  require "db_connection.php";
  $query = "UPDATE purchases SET SUPPLIER_NAME = '$suppliers_name', PURCHASE_DATE = '$invoice_date', TOTAL_AMOUNT = $grand_total, PAYMENT_STATUS = '$payment_status' WHERE VOUCHER_NUMBER = $id";
  $result = mysqli_query($con, $query);
  if (!empty($result))
    showPurchases(0);
}

function searchPurchase($text, $column)
{
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    $query = "SELECT * FROM purchases WHERE $column LIKE '%$text%'";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      showPurchaseRow($seq_no, $row);
    }
  }
}

function printPurchaseByVoucher($voucher_number)
{
  require "db_connection.php";
  if (!$con) {
    echo "Database connection failed.";
    return;
  }
  logPrintEvent("ORDER", $voucher_number);
  $payload = loadOrderPayload($con, $voucher_number);
  if (!$payload) {
    echo "Ordering not found.";
    return;
  }
  ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
  <?php
  echo buildOrderHtmlBody($payload, false);
}

function pdfPurchaseByVoucher($voucher_number)
{
  require "db_connection.php";
  if (!$con) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database connection failed.";
    return;
  }
  logPrintEvent("ORDER", $voucher_number);
  $payload = loadOrderPayload($con, $voucher_number);
  if (!$payload) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Ordering not found.";
    return;
  }
  require_once __DIR__ . '/clinic_pdf.php';
  $body = buildOrderHtmlBody($payload, true);
  $full = '<html><head><meta charset="UTF-8"><style>@page { margin: 20px; } body { margin: 0; padding: 0; }</style></head><body>' . $body . '</body></html>';
  clinic_send_pdf_html($full, 'purchase_order_' . intval($voucher_number) . '.pdf');
}

function printPurchaseByInvoice($invoice_number)
{
  require "db_connection.php";
  if (!$con) {
    echo "Database connection failed.";
    return;
  }
  $invoice_number = intval($invoice_number);
  $query = "SELECT * FROM purchases WHERE INVOICE_NUMBER = $invoice_number ORDER BY VOUCHER_NUMBER DESC LIMIT 1";
  $result = mysqli_query($con, $query);
  $purchase = mysqli_fetch_assoc($result);
  if (!$purchase) {
    echo "Ordering not found.";
    return;
  }
  printPurchaseByVoucher(intval($purchase['VOUCHER_NUMBER']));
}

function pdfPurchaseByInvoice($invoice_number)
{
  require "db_connection.php";
  if (!$con) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database connection failed.";
    return;
  }
  $invoice_number = intval($invoice_number);
  $query = "SELECT * FROM purchases WHERE INVOICE_NUMBER = $invoice_number ORDER BY VOUCHER_NUMBER DESC LIMIT 1";
  $result = mysqli_query($con, $query);
  $purchase = mysqli_fetch_assoc($result);
  if (!$purchase) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Ordering not found.";
    return;
  }
  pdfPurchaseByVoucher(intval($purchase['VOUCHER_NUMBER']));
}

?>