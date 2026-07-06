<?php

require_once __DIR__ . '/clinic_invoice_lines.php';
require_once __DIR__ . '/clinic_sales_schema.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'commit_invoice') {
  commitInvoice();
  exit;
}

if (isset($_GET['action']) && $_GET['action'] == "add_row")
  createMedicineInfoRow();

if (isset($_GET['action']) && $_GET['action'] == "is_customer")
  isCustomer(strtoupper($_GET['name']), $_GET['contact_number']);

if (isset($_GET['action']) && $_GET['action'] == "is_invoice")
  isInvoiceExist($_GET['invoice_number']);

if (isset($_GET['action']) && $_GET['action'] == "is_medicine")
  isMedicine(strtoupper($_GET['name']));

if (isset($_GET['action']) && $_GET['action'] == "current_invoice_number")
  getInvoiceNumber();

if (isset($_GET['action']) && $_GET['action'] == "medicine_list")
  showMedicineList(strtoupper($_GET['text']));

if (isset($_GET['action']) && $_GET['action'] == "fill")
  fill(strtoupper($_GET['name']), $_GET['column']);

if (isset($_GET['action']) && $_GET['action'] == "check_quantity")
  checkAvailableQuantity(strtoupper($_GET['medicine_name']));

if (isset($_GET['action']) && $_GET['action'] == "update_stock")
  updateStock(strtoupper($_GET['name']), $_GET['batch_id'], intval($_GET['quantity']));

if (isset($_GET['action']) && $_GET['action'] == "add_sale")
  addSale();

if (isset($_GET['action']) && $_GET['action'] == "add_new_invoice")
  addNewInvoice();

function isCustomer($name, $contact_number)
{
  require "db_connection.php";
  if ($con) {
    $query = "SELECT * FROM customers WHERE UPPER(NAME) = '$name' AND CONTACT_NUMBER = '$contact_number'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_array($result);
    echo ($row) ? "true" : "false";
  }
}

function isInvoiceExist($invoice_number)
{
  require "db_connection.php";
  if ($con) {
    clinic_ensure_sales_invoice_columns($con);
    clinic_ensure_invoice_lines_table($con);
    $n = intval($invoice_number);
    $query = "SELECT ID FROM sales WHERE INVOICE_NUMBER = $n OR INVOICE_ID = $n LIMIT 1";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_fetch_array($result)) {
      echo "true";
      return;
    }
    $query2 = "SELECT ID FROM invoice_lines WHERE INVOICE_ID = $n LIMIT 1";
    $result2 = mysqli_query($con, $query2);
    echo ($result2 && mysqli_fetch_array($result2)) ? "true" : "false";
  }
}

function isMedicine($name)
{
  require "db_connection.php";
  if ($con) {
    $query = "SELECT * FROM medicines_stock WHERE UPPER(NAME) = '$name'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_array($result);
    echo ($row) ? "true" : "false";
  }
}

function createMedicineInfoRow()
{
  $row_id = $_GET['row_id'];
  $row_number = $_GET['row_number'];
?>

<div class="medicine-card">
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="medicine-label">Medicine Name</label>
      <input id="medicine_name_<?php echo $row_number; ?>" name="medicine_name" class="form-control medicine-input"
        list="medicine_list_<?php echo $row_number; ?>" placeholder="Search Medicine"
        onkeydown="medicineOptions(this.value, 'medicine_list_<?php echo $row_number; ?>');"
        onfocus="medicineOptions(this.value, 'medicine_list_<?php echo $row_number; ?>');"
        onchange="fillFields(this.value, '<?php echo $row_number; ?>');">
      <datalist id="medicine_list_<?php echo $row_number; ?>">
        <?php showMedicineList("") ?>
      </datalist>
      <code class="text-danger small" id="medicine_name_error_<?php echo $row_number; ?>" style="display: none;"></code>
    </div>

    <div class="col-md-2 mb-3">
      <label class="medicine-label">Medicine ID</label>
      <input type="text" class="form-control medicine-readonly" id="batch_id_<?php echo $row_number; ?>" disabled>
    </div>

    <div class="col-md-2 mb-3">
      <label class="medicine-label">Available Stock</label>
      <input type="number" class="form-control medicine-readonly" id="available_quantity_<?php echo $row_number; ?>" disabled>
    </div>

    <div class="col-md-2 mb-3">
      <label class="medicine-label">Expiry Date</label>
      <input type="text" class="form-control medicine-readonly" id="expiry_date_<?php echo $row_number; ?>" disabled>
    </div>

    <div class="col-md-2 mb-3">
      <label class="medicine-label">Quantity</label>
      <input type="number" class="form-control medicine-input" id="quantity_<?php echo $row_number; ?>" value="0"
        oninput="updateQuantity('<?php echo $row_number; ?>');"
        onchange="updateQuantity('<?php echo $row_number; ?>');"
        onblur="checkAvailableQuantity(this.value, '<?php echo $row_number; ?>');">
      <code class="text-danger small" id="quantity_error_<?php echo $row_number; ?>" style="display: none;"></code>
    </div>
  </div>

  <div class="row">
    <div class="col-md-2 mb-3">
      <label class="medicine-label">Price (RM)</label>
      <input type="number" class="form-control medicine-readonly" id="mrp_<?php echo $row_number; ?>" disabled>
    </div>

    <div class="col-md-2 mb-3">
      <label class="medicine-label">Total Amount</label>
      <input type="text" class="form-control amount-box" id="total_<?php echo $row_number; ?>" disabled>
    </div>

    <div class="col-md-3 d-flex align-items-end mb-3">
      <button type="button" class="btn btn-success mr-2 action-btn" onclick="addRow();">
        <i class="fa fa-plus"></i> Add
      </button>
      <button type="button" class="btn btn-danger action-btn" onclick="removeRow('<?php echo $row_id ?>');">
        <i class="fa fa-trash"></i> Remove
      </button>
    </div>
  </div>
</div>

<?php
}

function getInvoiceNumber()
{
  require 'db_connection.php';
  if ($con) {
    $query = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_array($result);
    echo $row ? intval($row['AUTO_INCREMENT']) : '1';
  }
}

function showMedicineList($text)
{
  require 'db_connection.php';
  if ($con) {
    if ($text == "")
      $query = "SELECT * FROM medicines_stock";
    else
      $query = "SELECT * FROM medicines_stock WHERE UPPER(NAME) LIKE '%$text%'";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result))
      echo '<option value="' . htmlspecialchars($row['NAME']) . '">' . htmlspecialchars($row['NAME']) . '</option>';
  }
}

function fill($name, $column)
{
  require 'db_connection.php';
  if ($con) {
    $name_esc = mysqli_real_escape_string($con, $name);
    $query = "SELECT * FROM medicines_stock WHERE UPPER(NAME) = '$name_esc' ORDER BY ID DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) != 0) {
      $row = mysqli_fetch_array($result);
      echo $row[$column];
    }
  }
}

function checkAvailableQuantity($name)
{
  require "db_connection.php";
  if ($con) {
    $name_esc = mysqli_real_escape_string($con, $name);
    $query = "SELECT QUANTITY FROM medicines_stock WHERE UPPER(NAME) = '$name_esc' ORDER BY ID DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_array($result);
    echo ($row) ? $row['QUANTITY'] : "false";
  }
}

function updateStock($name, $batch_id, $quantity)
{
  require "db_connection.php";
  if ($con) {
    $query = "UPDATE medicines_stock SET QUANTITY = QUANTITY - $quantity WHERE UPPER(NAME) = '$name' AND BATCH_ID = '$batch_id'";
    $result = mysqli_query($con, $query);
    echo ($result) ? "stock updated" : "failed to update stock";
  }
}

function getCustomerId($name, $contact_number)
{
  require "db_connection.php";
  if ($con) {
    $query = "SELECT ID FROM customers WHERE UPPER(NAME) = '$name' AND CONTACT_NUMBER = '$contact_number'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_array($result);
    return ($row) ? $row['ID'] : 0;
  }
}

function addSale()
{
  $customer_id = getCustomerId(strtoupper($_GET['customers_name']), $_GET['customers_contact_number']);
  $invoice_number = intval($_GET['invoice_number']);
  $medicine_name = $_GET['medicine_name'];
  $batch_id = $_GET['batch_id'];
  $expiry_date = $_GET['expiry_date'];
  $quantity = intval($_GET['quantity']);
  $mrp = floatval($_GET['mrp']);
  $total = floatval($_GET['total']);

  require "db_connection.php";
  if ($con) {
    $ok = clinic_insert_sale_line($con, $customer_id, $invoice_number, $medicine_name, $batch_id, $expiry_date, $quantity, $mrp, $total);
    echo $ok ? "inserted sale" : "falied to add sale...";
  }
}

function commitInvoice()
{
  header('Content-Type: text/plain; charset=UTF-8');
  require "db_connection.php";
  if (!$con) {
    echo "Database connection failed.";
    return;
  }

  $name_raw = isset($_POST['customers_name']) ? trim($_POST['customers_name']) : '';
  $contact = isset($_POST['customers_contact_number']) ? trim($_POST['customers_contact_number']) : '';
  $invoice_date = isset($_POST['invoice_date']) ? trim($_POST['invoice_date']) : '';
  $final_total = isset($_POST['final_total']) ? floatval($_POST['final_total']) : 0;
  $lines_raw = isset($_POST['lines_json']) ? $_POST['lines_json'] : '[]';
  $lines = json_decode($lines_raw, true);

  if ($name_raw === '' || $contact === '') {
    echo "Customer details required.";
    return;
  }
  if (!is_array($lines) || count($lines) === 0) {
    echo "Add at least one medicine line.";
    return;
  }

  $customer_id = getCustomerId(strtoupper($name_raw), $contact);
  if (!$customer_id) {
    echo "Customer doesn't exist.";
    return;
  }

  $invoice_date_esc = mysqli_real_escape_string($con, $invoice_date);
  if ($invoice_date_esc === '') {
    echo "Invoice date required.";
    return;
  }

  mysqli_begin_transaction($con);
  try {
    $q = "INSERT INTO invoices (CUSTOMER_ID, INVOICE_DATE, TOTAL_AMOUNT, TOTAL_DISCOUNT, NET_TOTAL) VALUES($customer_id, '$invoice_date_esc', $final_total, 0, $final_total)";
    if (!mysqli_query($con, $q)) {
      throw new Exception('Failed to save invoice header.');
    }
    $invoice_id = intval(mysqli_insert_id($con));
    if ($invoice_id < 1) {
      throw new Exception('Invalid invoice id after insert.');
    }

    $line_order = 0;
    foreach ($lines as $L) {
      $line_order++;
      $med = isset($L['name']) ? $L['name'] : '';
      $batch = isset($L['batch_id']) ? $L['batch_id'] : '';
      $exp = isset($L['expiry_date']) ? $L['expiry_date'] : '';
      $qty = isset($L['quantity']) ? intval($L['quantity']) : 0;
      $mrp = isset($L['mrp']) ? floatval($L['mrp']) : 0;
      $line_total = isset($L['total']) ? floatval($L['total']) : ($qty * $mrp);
      if ($med === '' || $qty < 1) {
        throw new Exception('Invalid line item.');
      }

      $med_esc = mysqli_real_escape_string($con, $med);
      $batch_esc = mysqli_real_escape_string($con, $batch);
      $batch_u = strtoupper($batch_esc);
      $stock_q = "UPDATE medicines_stock SET QUANTITY = QUANTITY - $qty WHERE UPPER(NAME) = '" . strtoupper($med_esc) . "' AND UPPER(BATCH_ID) = '$batch_u'";
      if (!mysqli_query($con, $stock_q) || mysqli_affected_rows($con) < 1) {
        throw new Exception('Stock update failed for ' . $med . '.');
      }

      if (!clinic_insert_sale_line($con, $customer_id, $invoice_id, $med, $batch, $exp, $qty, $mrp, $line_total)) {
        throw new Exception('Failed to save sale line.');
      }
      if (!clinic_insert_invoice_line($con, $invoice_id, $line_order, $med, $batch, $exp, $qty, $mrp, $line_total)) {
        throw new Exception('Failed to save invoice line snapshot.');
      }
    }

    mysqli_commit($con);
    echo "Invoice saved...|INVOICE:" . $invoice_id;
  } catch (Exception $e) {
    mysqli_rollback($con);
    echo $e->getMessage();
  }
}

function addNewInvoice()
{
  $customer_id = getCustomerId(strtoupper($_GET['customers_name']), $_GET['customers_contact_number']);
  $invoice_date = $_GET['invoice_date'];
  $final_total = $_GET['final_total'];

  require "db_connection.php";
  if ($con) {
    $query = "INSERT INTO invoices (CUSTOMER_ID, INVOICE_DATE, TOTAL_AMOUNT, TOTAL_DISCOUNT, NET_TOTAL) VALUES($customer_id, '$invoice_date', $final_total, 0, $final_total)";
    $result = mysqli_query($con, $query);
    echo ($result) ? "Invoice saved..." : "falied to add invoice...";
  }
}
?>