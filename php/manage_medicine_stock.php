<?php
require "db_connection.php";
require_once __DIR__ . '/clinic_expiry.php';

if (!defined('CLINIC_LOW_STOCK_THRESHOLD')) {
  define('CLINIC_LOW_STOCK_THRESHOLD', 10);
}
if (!defined('CLINIC_EXPIRY_SOON_DAYS')) {
  define('CLINIC_EXPIRY_SOON_DAYS', 30);
}

if ($con) {
  if (isset($_GET["action"]) && $_GET["action"] == "delete") {
    $id = intval($_GET["id"]);
    $query = "DELETE FROM medicines_stock WHERE ID = $id";
    $result = mysqli_query($con, $query);
    if (!empty($result))
      showMedicinesStock("0");
  }

  if (isset($_GET["action"]) && $_GET["action"] == "edit") {
    $id = $_GET["id"];
    showMedicinesStock($id);
  }

  if (isset($_GET["action"]) && $_GET["action"] == "update") {
    $id = intval($_GET["id"]);
    $batch_id = mysqli_real_escape_string($con, $_GET["batch_id"]);
    $expiry_date = mysqli_real_escape_string($con, $_GET["expiry_date"]);
    $quantity = intval($_GET["quantity"]);
    $mrp = floatval($_GET["mrp"]);
    $rate = floatval($_GET["rate"]);
    $is_updated = updateMedicineStock($id, $batch_id, $expiry_date, $quantity, $mrp, $rate);
    if ($is_updated) {
      echo "SUCCESS::Medicine stock updated successfully.";
    } else {
      echo "ERROR::Failed to update medicine stock.";
    }
    showMedicinesStock("0");
  }

  if (isset($_GET["action"]) && $_GET["action"] == "cancel")
    showMedicinesStock("0");

  if (isset($_GET["action"]) && $_GET["action"] == "search")
    searchMedicineStock(strtoupper($_GET["text"]), $_GET["tag"]);
}

function showMedicinesStock($id)
{
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    $query = "SELECT medicines.NAME, medicines.PACKING, medicines.GENERIC_NAME, medicines.SUPPLIER_NAME, medicines_stock.ID AS STOCK_ID, medicines_stock.BATCH_ID, medicines_stock.EXPIRY_DATE, medicines_stock.QUANTITY, medicines_stock.MRP, medicines_stock.RATE FROM medicines INNER JOIN medicines_stock ON medicines.NAME = medicines_stock.NAME";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      if ($row['BATCH_ID'] == $id)
        showEditOptionsRow($seq_no, $row);
      else
        showMedicineStockRow($seq_no, $row);
    }
  }
}

function showMedicineStockRow($seq_no, $row)
{
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['NAME']; ?></td>
    <td><?php echo $row['PACKING']; ?></td>
    <td><?php echo $row['GENERIC_NAME']; ?></td>
    <td><?php echo $row['EXPIRY_DATE']; ?></td>
    <td><?php echo $row['SUPPLIER_NAME']; ?></td>
    <td><?php echo $row['QUANTITY']; ?></td>
    <td><?php echo $row['MRP']; ?></td>
  </tr>
  <?php
}

function showEditOptionsRow($seq_no, $row)
{
  $sid = intval($row['STOCK_ID']);
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['NAME']; ?></td>
    <td><?php echo $row['PACKING']; ?></td>
    <td><?php echo $row['GENERIC_NAME']; ?></td>
    <td>
      <input type="hidden" value="<?php echo $row['BATCH_ID']; ?>" id="batch_id_<?php echo $sid; ?>">
      <input type="text" class="form-control" value="<?php echo $row['EXPIRY_DATE']; ?>" placeholder="Expiry"
        id="expiry_date_<?php echo $sid; ?>" onblur="checkExpiry(this.value, 'expiry_date_error_<?php echo $sid; ?>');">
      <code class="text-danger small font-weight-bold float-right" id="expiry_date_error_<?php echo $sid; ?>" style="display: none;"></code>
    </td>
    <td><?php echo $row['SUPPLIER_NAME']; ?></td>
    <td>
      <input type="number" class="form-control" value="<?php echo $row['QUANTITY']; ?>" placeholder="Quantity"
        id="quantity_<?php echo $sid; ?>" onkeyup="checkQuantity(this.value, 'quantity_error_<?php echo $sid; ?>');">
      <code class="text-danger small font-weight-bold float-right" id="quantity_error_<?php echo $sid; ?>" style="display: none;"></code>
    </td>
    <td>
      <input type="number" class="form-control" value="<?php echo $row['MRP']; ?>" placeholder="MRP" id="mrp_<?php echo $sid; ?>"
        onkeyup="checkValue(this.value, 'mrp_error_<?php echo $sid; ?>');">
      <code class="text-danger small font-weight-bold float-right" id="mrp_error_<?php echo $sid; ?>" style="display: none;"></code>
    </td>
    <td>
      <input type="number" class="form-control" value="<?php echo $row['RATE']; ?>" placeholder="Rate" id="rate_<?php echo $sid; ?>"
        onkeyup="checkValue(this.value, 'rate_error_<?php echo $sid; ?>');">
      <code class="text-danger small font-weight-bold float-right" id="rate_error_<?php echo $sid; ?>" style="display: none;"></code>
    </td>
    <td>
      <button href="" class="btn btn-success btn-sm" onclick="updateMedicineStock(<?php echo $row['STOCK_ID']; ?>);">
        <i class="fa fa-edit"></i>
      </button>
      <button class="btn btn-danger btn-sm" onclick="cancel();">
        <i class="fa fa-close"></i>
      </button>
    </td>
  </tr>
  <?php
}

function updateMedicineStock($id, $batch_id, $expiry_date, $quantity, $mrp, $rate)
{
  require "db_connection.php";
  $query = "UPDATE medicines_stock SET BATCH_ID = '$batch_id', EXPIRY_DATE = '$expiry_date', QUANTITY = $quantity, MRP = $mrp, RATE = $rate WHERE ID = $id";
  $result = mysqli_query($con, $query);
  return !empty($result) && mysqli_affected_rows($con) >= 0;
}

function searchMedicineStock($text, $column)
{
  require "db_connection.php";
  if (!$con) {
    return;
  }
  $seq_no = 0;
  $lowThreshold = (int) CLINIC_LOW_STOCK_THRESHOLD;
  $soonDays = (int) CLINIC_EXPIRY_SOON_DAYS;

  $baseSelect = "SELECT medicines.NAME, medicines.PACKING, medicines.GENERIC_NAME, medicines.SUPPLIER_NAME, "
              . "medicines_stock.ID AS STOCK_ID, medicines_stock.BATCH_ID, medicines_stock.EXPIRY_DATE, "
              . "medicines_stock.QUANTITY, medicines_stock.MRP, medicines_stock.RATE "
              . "FROM medicines INNER JOIN medicines_stock ON medicines.NAME = medicines_stock.NAME";

  if ($column == "EXPIRY_DATE" || $column == "EXPIRING_SOON") {
    $query = $baseSelect;
  } else if ($column == "LOW_STOCK") {
    $query = $baseSelect . " WHERE medicines_stock.QUANTITY <= $lowThreshold ORDER BY medicines_stock.QUANTITY ASC, medicines.NAME ASC";
  } else if ($column == "QUANTITY") {
    $query = $baseSelect . " WHERE medicines_stock.QUANTITY = 0";
  } else {
    $query = $baseSelect . " WHERE UPPER(medicines.$column) LIKE '%$text%'";
  }

  $result = mysqli_query($con, $query);
  if (!$result) {
    return;
  }

  if ($column == "EXPIRY_DATE" || $column == "EXPIRING_SOON") {
    $todayTs = strtotime(date('Y-m-d'));
    while ($row = mysqli_fetch_array($result)) {
      $rawExp = (string) $row['EXPIRY_DATE'];
      if ($rawExp === '') continue;
      $mysqlDate = clinic_expiry_input_to_mysql_date($rawExp);
      if ($mysqlDate === null) continue;
      $expTs = strtotime($mysqlDate);
      if ($expTs === false) continue;
      $diffDays = (int) floor(($expTs - $todayTs) / 86400);

      $matches = false;
      if ($column == "EXPIRY_DATE" && $diffDays < 0) {
        $matches = true;
      } else if ($column == "EXPIRING_SOON" && $diffDays >= 0 && $diffDays <= $soonDays) {
        $matches = true;
      }
      if ($matches) {
        $seq_no++;
        showMedicineStockRow($seq_no, $row);
      }
    }
  } else {
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      showMedicineStockRow($seq_no, $row);
    }
  }
}

?>