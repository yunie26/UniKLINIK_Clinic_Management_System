<?php
  require_once __DIR__ . '/clinic_purchase_order_lines.php';

  if(isset($_GET['action']) && $_GET['action'] == "add_row")
    createMedicineInfoRow();

  if(isset($_GET['action']) && $_GET['action'] == "medicine_list")
    showMedicineListForPurchase(isset($_GET['text']) ? $_GET['text'] : '');

  if(isset($_GET['action']) && $_GET['action'] == "fill")
    fillPurchaseStockField(strtoupper($_GET['name'] ?? ''), $_GET['column'] ?? '');

  if(isset($_GET['action']) && $_GET['action'] == "is_supplier")
    isSupplier(strtoupper($_GET['name']));

  if(isset($_GET['action']) && $_GET['action'] == "is_invoice")
    isInvoiceExist(strtoupper($_GET['invoice_number']));

  if(isset($_GET['action']) && $_GET['action'] == "is_new_medicine")
    isNewMedicine(strtoupper($_GET['name']), strtoupper($_GET['packing']));

  if(isset($_GET['action']) && $_GET['action'] == "add_stock")
    addStock();

  if(isset($_GET['action']) && $_GET['action'] == "add_new_purchase")
    addNewPurchase();

  if(isset($_GET['action']) && $_GET['action'] == "add_purchase_line")
    addPurchaseOrderLine();

  function showMedicineListForPurchase($text) {
    require "db_connection.php";
    if ($con) {
      $text = mysqli_real_escape_string($con, strtoupper(trim($text)));
      if ($text === "") {
        $query = "SELECT * FROM medicines_stock ORDER BY NAME ASC";
      } else {
        $query = "SELECT * FROM medicines_stock WHERE UPPER(NAME) LIKE '%$text%' ORDER BY NAME ASC";
      }
      $result = mysqli_query($con, $query);
      if ($result) {
        while ($row = mysqli_fetch_array($result)) {
          echo '<option value="' . htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['NAME'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
      }
    }
  }

  function fillPurchaseStockField($name, $column) {
    require "db_connection.php";
    if (!$con || $name === '') {
      return;
    }
    $allowed = array('BATCH_ID', 'EXPIRY_DATE', 'MRP', 'RATE', 'QUANTITY');
    $column = strtoupper(preg_replace('/[^A-Za-z_]/', '', $column));
    if (!in_array($column, $allowed, true)) {
      return;
    }
    $name_esc = mysqli_real_escape_string($con, $name);
    $query = "SELECT * FROM medicines_stock WHERE UPPER(NAME) = '$name_esc' ORDER BY ID DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) != 0) {
      $row = mysqli_fetch_array($result);
      if (isset($row[$column])) {
        echo $row[$column];
      }
    }
  }

  function isSupplier($name) {
    require "db_connection.php";
    if($con) {
      $query = "SELECT * FROM suppliers WHERE UPPER(NAME) = '$name'";
      $result = mysqli_query($con, $query);
      $row = mysqli_fetch_array($result);
      echo ($row) ? "true" : "false";
    }
  }

  function isInvoiceExist($invoice_number) {
    require "db_connection.php";
    if($con) {
      $query = "SELECT * FROM purchases WHERE INVOICE_NUMBER = $invoice_number";
      $result = mysqli_query($con, $query);
      $row = mysqli_fetch_array($result);
      echo ($row) ? "true" : "false";
    }
  }

  function isNewMedicine($name, $packing) {
    require "db_connection.php";
    if($con) {
      $query = "SELECT * FROM medicines WHERE UPPER(NAME) = '$name' AND UPPER(PACKING) = '$packing'";
      $result = mysqli_query($con, $query);
      $row = mysqli_fetch_array($result);
      echo ($row) ? "false" : "true";
    }
  }

  function addStock() {
    require "db_connection.php";
    $name = ucwords($_GET['name']);
    $batch_id = strtoupper($_GET['batch_id']);
    $expiry_date = $_GET['expiry_date'];
    $quantity = $_GET['quantity'];
    $mrp = $_GET['mrp'];
    $invoice_number = intval($_GET['invoice_number']);
    if($con) {
      $query = "SELECT * FROM medicines_stock WHERE UPPER(NAME) = '".strtoupper($name)."' AND UPPER(BATCH_ID) = '$batch_id'";
      $result = mysqli_query($con, $query);
      $row = mysqli_fetch_array($result);
      if($row) {
        $new_quantity = $row['QUANTITY'] + $quantity;
        $query = "UPDATE medicines_stock SET QUANTITY = $new_quantity, INVOICE_NUMBER = $invoice_number WHERE UPPER(NAME) = '".strtoupper($name)."' AND UPPER(BATCH_ID) = '$batch_id'";
        $result = mysqli_query($con, $query);
      }
      else {
        $query = "INSERT INTO medicines_stock (NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP, INVOICE_NUMBER) VALUES('$name', '$batch_id', '$expiry_date', $quantity, $mrp, $invoice_number)";
        $result = mysqli_query($con, $query);
      }
    }
  }

  function addNewPurchase() {
    require "db_connection.php";
    if (!$con) {
      echo "Failed to save purchase!";
      return;
    }
    $suppliers_name = ucwords($_GET['suppliers_name']);
    $invoice_number = intval($_GET['invoice_number']);
    $payment_type = $_GET['payment_type'];
    $invoice_date = mysqli_real_escape_string($con, $_GET['invoice_date']);
    $grand_total = floatval($_GET['grand_total']);
    $suppliers_name_esc = mysqli_real_escape_string($con, $suppliers_name);
    $payment_status = ($payment_type == "Payment Due") ? "DUE" : "PAID";

    $query = "INSERT INTO purchases (SUPPLIER_NAME, INVOICE_NUMBER, PURCHASE_DATE, TOTAL_AMOUNT, PAYMENT_STATUS) VALUES('$suppliers_name_esc', $invoice_number, '$invoice_date', $grand_total, '$payment_status')";
    $result = mysqli_query($con, $query);
    if ($result) {
      $vid = mysqli_insert_id($con);
      echo "Purchase saved...|VOUCHER:" . intval($vid);
    } else {
      echo "Failed to save purchase!";
    }
  }

  function addPurchaseOrderLine() {
    require "db_connection.php";
    if (!$con) {
      return;
    }
    clinic_ensure_purchase_order_lines_table($con);
    $voucher = intval($_GET['voucher_number']);
    $line_order = intval($_GET['line_order']);
    $name = mysqli_real_escape_string($con, $_GET['name']);
    $batch_id = mysqli_real_escape_string($con, strtoupper($_GET['batch_id']));
    $expiry_date = mysqli_real_escape_string($con, $_GET['expiry_date']);
    $quantity = intval($_GET['quantity']);
    $mrp = floatval($_GET['mrp']);
    $query = "INSERT INTO purchase_order_lines (VOUCHER_NUMBER, LINE_ORDER, MEDICINE_NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP) VALUES ($voucher, $line_order, '$name', '$batch_id', '$expiry_date', $quantity, $mrp)";
    mysqli_query($con, $query);
  }

  function createMedicineInfoRow() {
  $row_id = $_GET['row_id'];
  $row_number = $_GET['row_number'];
?>

<div class="medicine-card">

  <div class="row">

    <!-- Medicine Name -->
    <div class="col-md-4 mb-3">
      <label class="medicine-label">
        Medicine Name
      </label>

      <input
        id="medicine_name_<?php echo $row_number; ?>"
        name="medicine_name"
        class="form-control medicine-input"
        list="medicine_list_purchase_<?php echo $row_number; ?>"
        placeholder="Search Medicine"
        onkeydown="medicineOptionsPurchase(this.value, 'medicine_list_purchase_<?php echo $row_number; ?>');"
        onfocus="medicineOptionsPurchase(this.value, 'medicine_list_purchase_<?php echo $row_number; ?>');"
        onchange="fillPurchaseStockRow(this.value, '<?php echo $row_number; ?>');">

      <datalist id="medicine_list_purchase_<?php echo $row_number; ?>">
        <?php showMedicineListForPurchase(''); ?>
      </datalist>

      <code
        class="text-danger small"
        id="medicine_name_error_<?php echo $row_number; ?>"
        style="display:none;">
      </code>
    </div>

    <!-- Medicine ID -->
    <div class="col-md-2 mb-3">
      <label class="medicine-label">
        Medicine ID
      </label>

      <input
        type="text"
        class="form-control medicine-readonly"
        id="batch_id_<?php echo $row_number; ?>"
        disabled>
    </div>

    <!-- Stock -->
    <div class="col-md-2 mb-3">
      <label class="medicine-label">
        Available Stock
      </label>

      <input
        type="number"
        class="form-control medicine-readonly"
        id="available_quantity_<?php echo $row_number; ?>"
        disabled>
    </div>

    <!-- Expiry -->
    <div class="col-md-2 mb-3">
      <label class="medicine-label">
        Expiry Date
      </label>

      <input
        type="text"
        class="form-control medicine-readonly"
        id="expiry_date_<?php echo $row_number; ?>"
        disabled>
    </div>

    <!-- Quantity -->
    <div class="col-md-2 mb-3">
      <label class="medicine-label">
        Order Quantity
      </label>

      <input
        type="number"
        class="form-control medicine-input"
        id="quantity_<?php echo $row_number; ?>"
        value="0"
        onkeyup="updatePurchaseQuantity('<?php echo $row_number; ?>');"
        onblur="purchaseQuantityBlur('<?php echo $row_number; ?>');">

      <code
        class="text-danger small"
        id="quantity_error_<?php echo $row_number; ?>"
        style="display:none;">
      </code>
    </div>

  </div>

  <!-- Second Row -->

  <div class="row">

    <!-- Price -->
    <div class="col-md-2 mb-3">
      <label class="medicine-label">
        Price (RM)
      </label>

      <input
        type="number"
        class="form-control medicine-readonly"
        id="mrp_<?php echo $row_number; ?>"
        disabled>
    </div>

    <!-- Amount -->
    <div class="col-md-2 mb-3">
      <label class="medicine-label">
        Total Amount
      </label>

      <input
        type="text"
        class="form-control amount-box"
        id="amount_<?php echo $row_number; ?>"
        disabled>
    </div>

   

    <!-- Buttons -->
    <div class="col-md-3 d-flex align-items-end mb-3">

      <button
        type="button"
        class="btn btn-success mr-2 action-btn"
        onclick="addRow();">

        <i class="fa fa-plus"></i>
        Add
      </button>

      <button
        type="button"
        class="btn btn-danger action-btn"
        onclick="removeRow('<?php echo $row_id ?>');">

        <i class="fa fa-trash"></i>
        Remove
      </button>

    </div>

  </div>

</div>

<?php
}
?>