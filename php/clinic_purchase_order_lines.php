<?php

function clinic_ensure_purchase_order_lines_table($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if ($done) {
    return;
  }
  $done = true;
  $sql = "CREATE TABLE IF NOT EXISTS `purchase_order_lines` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `VOUCHER_NUMBER` int(11) NOT NULL,
    `LINE_ORDER` int(11) NOT NULL DEFAULT 0,
    `MEDICINE_NAME` varchar(100) NOT NULL,
    `BATCH_ID` varchar(20) NOT NULL,
    `EXPIRY_DATE` varchar(10) NOT NULL,
    `QUANTITY` int(11) NOT NULL,
    `MRP` double NOT NULL,
    PRIMARY KEY (`ID`),
    KEY `VOUCHER_NUMBER` (`VOUCHER_NUMBER`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
  mysqli_query($con, $sql);
}
