<?php

if(isset($_GET['action']) && $_GET['action'] == "purchase")
  showPurchases($_GET['start_date'], $_GET['end_date']);

if(isset($_GET['action']) && $_GET['action'] == "sales")
  showSales($_GET['start_date'], $_GET['end_date']);

if(isset($_GET['action']) && $_GET['action'] == "sales_chart")
  showChartData("sales", $_GET['start_date'], $_GET['end_date']);

if(isset($_GET['action']) && $_GET['action'] == "purchase_chart")
  showChartData("purchase", $_GET['start_date'], $_GET['end_date']);

function showPurchases($start_date, $end_date) {
  ?>
  <thead>
    <tr>
      <th>No.</th>
      <th>Ordering Date</th>
      <th>ID</th>
      <th>Ordering Number</th>
      <th>Supplier Name</th>
      <th>Total Amount</th>
    </tr>
  </thead>
  <tbody>
  <?php
  require "db_connection.php";
  if($con) {
    $seq_no = 0;
    $total = 0;
    if($start_date == "" || $end_date == "")
      $query = "SELECT * FROM purchases";
    else
      $query = "SELECT * FROM purchases WHERE PURCHASE_DATE BETWEEN '$start_date' AND '$end_date'";
    $result = mysqli_query($con, $query);
    while($row = mysqli_fetch_array($result)) {
      $seq_no++;
      showPurchaseRow($seq_no, $row);
      $total = $total + $row['TOTAL_AMOUNT'];
    }
    ?>
    </tbody>
    <tfoot class="font-weight-bold">
      <tr style="text-align: right; font-size: 24px;">
        <td colspan="5" style="color: green;">&nbsp;Total Orderings =</td>
        <td style="color: red;"><?php echo $total; ?></td>
      </tr>
    </tfoot>
    <?php
  }
}

function showPurchaseRow($seq_no, $row) {
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['PURCHASE_DATE']; ?></td>
    <td><?php echo $row['VOUCHER_NUMBER']; ?></td>
    <td><?php echo $row['INVOICE_NUMBER']; ?></td>
    <td><?php echo $row['SUPPLIER_NAME'] ?></td>
    <td><?php echo $row['TOTAL_AMOUNT']; ?></td>
  </tr>
  <?php
}

function showSales($start_date, $end_date) {
  ?>
  <thead>
    <tr>
      <th>No.</th>
      <th>Sales Date</th>
      <th>Invoice Number</th>
      <th>Patient Name</th>
      <th>Total Amount</th>
    </tr>
  </thead>
  <tbody>
  <?php
  require "db_connection.php";
  if($con) {
    $seq_no = 0;
    $total = 0;
    if($start_date == "" || $end_date == "")
      $query = "SELECT * FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID";
    else
      $query = "SELECT * FROM invoices INNER JOIN customers ON invoices.CUSTOMER_ID = customers.ID WHERE INVOICE_DATE BETWEEN '$start_date' AND '$end_date'";
    $result = mysqli_query($con, $query);
    while($row = mysqli_fetch_array($result)) {
      $seq_no++;
      //print_r($row);
      showSalesRow($seq_no, $row);
      $total = $total + $row['NET_TOTAL'];
    }
    ?>
    </tbody>
    <tfoot class="font-weight-bold">
      <tr style="text-align: right; font-size: 24px;">
        <td colspan="4" style="color: green;">&nbsp;Total Sales =</td>
        <td class="text-primary"><?php echo $total; ?></td>
      </tr>
    </tfoot>
    <?php
  }
}

function showSalesRow($seq_no, $row) {
  ?>
  <tr>
    <td><?php echo $seq_no; ?></td>
    <td><?php echo $row['INVOICE_DATE']; ?></td>
    <td><?php echo $row['INVOICE_ID']; ?></td>
    <td><?php echo $row['NAME']; ?></td>
    <td><?php echo $row['NET_TOTAL'] ?></td>
  </tr>
  <?php
}

function showChartData($type, $start_date, $end_date) {
  require "db_connection.php";
  $labels = array();
  $values = array();

  if ($con) {
    if ($type == "sales") {
      if ($start_date == "" || $end_date == "") {
        $query = "SELECT INVOICE_DATE AS report_date, SUM(NET_TOTAL) AS total FROM invoices GROUP BY INVOICE_DATE ORDER BY INVOICE_DATE ASC";
      } else {
        $query = "SELECT INVOICE_DATE AS report_date, SUM(NET_TOTAL) AS total FROM invoices WHERE INVOICE_DATE BETWEEN '$start_date' AND '$end_date' GROUP BY INVOICE_DATE ORDER BY INVOICE_DATE ASC";
      }
    } else {
      if ($start_date == "" || $end_date == "") {
        $query = "SELECT DATE(PURCHASE_DATE) AS report_date, SUM(TOTAL_AMOUNT) AS total FROM purchases GROUP BY DATE(PURCHASE_DATE) ORDER BY DATE(PURCHASE_DATE) ASC";
      } else {
        $query = "SELECT DATE(PURCHASE_DATE) AS report_date, SUM(TOTAL_AMOUNT) AS total FROM purchases WHERE DATE(PURCHASE_DATE) BETWEEN '$start_date' AND '$end_date' GROUP BY DATE(PURCHASE_DATE) ORDER BY DATE(PURCHASE_DATE) ASC";
      }
    }

    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_assoc($result)) {
      $labels[] = $row['report_date'];
      $values[] = floatval($row['total']);
    }
  }

  header('Content-Type: application/json');
  echo json_encode(array("labels" => $labels, "values" => $values));
}

?>
