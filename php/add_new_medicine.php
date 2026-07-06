<?php
require "db_connection.php";

if ($con) {

  $name = ucwords($_GET["name"]);
  $packing = strtoupper($_GET["packing"]);
  $generic_name = ucwords($_GET["generic_name"]);
  $suppliers_name = ucwords($_GET["suppliers_name"]);
  $expiry_date = isset($_GET["expiry_date"]) ? trim($_GET["expiry_date"]) : "";
  $quantity = isset($_GET["quantity"]) ? intval($_GET["quantity"]) : 0;
  $mrp = isset($_GET["mrp"]) ? floatval($_GET["mrp"]) : 0;
  $rate = $mrp;

  /* ---------- 1. CHECK SUPPLIER EXISTS ---------- */
  $supplier_check = "SELECT * FROM suppliers 
                     WHERE UPPER(NAME) = '" . strtoupper($suppliers_name) . "'";
  $supplier_result = mysqli_query($con, $supplier_check);

  if (mysqli_num_rows($supplier_result) == 0) {
    echo "<div class='alert alert-danger'>
            Supplier <strong>$suppliers_name</strong> does not exist. 
            Please add new supplier first!
          </div>";
    exit;
}


  /* ---------- 2. CHECK DUPLICATE MEDICINE ---------- */
  $query = "SELECT * FROM medicines 
            WHERE UPPER(NAME) = '" . strtoupper($name) . "' 
            AND UPPER(PACKING) = '" . strtoupper($packing) . "' 
            AND UPPER(SUPPLIER_NAME) = '" . strtoupper($suppliers_name) . "'";
  
  $result = mysqli_query($con, $query);
  $row = mysqli_fetch_array($result);

  if ($row) {
    echo "Medicine $name with $packing already exists by supplier $suppliers_name!";
  } 
  else {

    /* ---------- 3. INSERT MEDICINE ---------- */
    $query = "INSERT INTO medicines 
              (NAME, PACKING, GENERIC_NAME, SUPPLIER_NAME) 
              VALUES ('$name', '$packing', '$generic_name', '$suppliers_name')";
    
    $result = mysqli_query($con, $query);

    if ($result) {

      /* ---------- 4. ADD INITIAL STOCK ---------- */
      $batch_id = "BATCH" . rand(1000, 9999);
      if ($expiry_date === "") $expiry_date = "12/26";
      if ($quantity <= 0) $quantity = 1;
      if ($mrp <= 0) $mrp = 1;
      if ($rate <= 0) $rate = $mrp;

      $insert_stock = "INSERT INTO medicines_stock 
                      (NAME, BATCH_ID, EXPIRY_DATE, QUANTITY, MRP, RATE) 
                      VALUES 
                      ('$name', '$batch_id', '$expiry_date', $quantity, $mrp, $rate)";
      
      mysqli_query($con, $insert_stock);

      echo "$name added successfully.";
    } 
    else {
      echo "Failed to add $name!";
    }
  }
}
?>
