<?php require_once __DIR__ . '/php/app_bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="utf-8">
  <title>New Receipt</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">

  <!-- Icons -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/sidenav2.css">

  <!-- JS -->
  <script src="js/suggestions.js"></script>
  <script src="js/add_new_invoice.js"></script>
  <script src="js/manage_invoice.js"></script>
  <script src="js/validateForm.js"></script>
  <script src="js/restrict.js"></script>

  <style>
    /* MODAL */
    #add_new_customer_model {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(4px);
    }

    #add_new_customer_model .modal-dialog {
      margin-top: 70px;
    }

    /* CARD */
    .receipt-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 30px;
      box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
      border: 1px solid #eef2f7;
      margin-bottom: 30px;
    }

    .section-title {
      font-size: 18px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title i {
      color: #0ea5e9;
    }

    /* FORM */
    .form-group label {
      font-weight: 600;
      color: #334155;
      margin-bottom: 8px;
    }

    .form-control {
      height: 48px;
      border-radius: 14px;
      border: 1px solid #dbe4ee;
      background: #f8fafc;
      transition: 0.2s ease;
    }

    .form-control:focus {
      border-color: #0ea5e9;
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.10);
      background: #ffffff;
    }

    /* BUTTON */
    .btn-modern {
      height: 48px;
      border: none;
      border-radius: 14px;
      font-weight: 600;
      transition: 0.25s ease;
    }

    .btn-primary-modern {
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
    }

    .btn-primary-modern:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(14, 165, 233, 0.25);
      color: white;
    }

    .btn-secondary-modern {
      background: #f1f5f9;
      color: #334155;
    }

    .btn-secondary-modern:hover {
      background: #e2e8f0;
    }

    /* NEW PATIENT BUTTON */
    .new-patient-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #0ea5e9;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
      transition: 0.2s ease;
      background: transparent;
      border: none;
      padding: 0;
    }

    .new-patient-btn:hover {
      color: #0284c7;
    }

    /* TOTAL BOX */
    .total-box {
      background: #f8fafc;
      border-radius: 18px;
      padding: 20px;
      border: 1px solid #e2e8f0;
    }

    .total-box label {
      font-size: 16px;
      margin-bottom: 10px;
    }

    #final_total {
      font-size: 20px;
      font-weight: 700;
      text-align: center;
      background: white;
    }

    /* MEDICINE CARD */
    .medicine-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 20px;
      margin-bottom: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: .25s;
    }

    .medicine-card:hover {
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }

    .medicine-label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #475569;
      margin-bottom: 8px;
    }

    .medicine-input {
      height: 46px;
      border-radius: 12px;
      border: 1px solid #dbe4ee;
      background: #fff;
    }

    .medicine-input:focus {
      border-color: #0ea5e9;
      box-shadow: 0 0 0 4px rgba(14, 165, 233, .10);
    }

    .medicine-readonly {
      height: 46px;
      border-radius: 12px;
      background: #f1f5f9;
      font-weight: 600;
    }

    .amount-box {
      height: 46px;
      border-radius: 12px;
      background: #ecfeff;
      color: #0f766e;
      font-weight: 700;
      text-align: center;
    }

    .action-btn {
      height: 46px;
      border-radius: 12px;
      font-weight: 600;
      min-width: 120px;
    }

    /* PAYMENT CARD - TUKAR KE WARNA SAMA MACAM GRAND TOTAL */
    .payment-card {
      background: #f8fafc;
      border-radius: 18px;
      padding: 20px;
      border: 1px solid #e2e8f0;
      height: 100%;
    }

    .payment-card label {
      font-size: 14px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 10px;
    }

    /* CHANGE CARD - GUNA SAME CLASS ATAU TUKAR JUGA */
    .change-card {
      background: #f8fafc;
      border-radius: 18px;
      padding: 20px;
      border: 1px solid #e2e8f0;
      height: 100%;
    }

    .change-card label {
      font-size: 14px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 10px;
    }

    /* MOBILE */
    @media (max-width: 768px) {
      .receipt-card {
        padding: 20px;
      }

      .payment-row .col-md-3,
      .payment-row .col-md-2,
      .payment-row .col-md-4 {
        margin-bottom: 15px;
      }
    }
  </style>
</head>

<body>

  <!-- ADD NEW CUSTOMER MODAL -->
  <div id="add_new_customer_model">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="background: #0ea5e9; color: white;">
          <div class="font-weight-bold">
            <i class="fa fa-user-plus"></i> Add New Patient
          </div>
          <button class="close" style="outline: none; color: white;"
            onclick="document.getElementById('add_new_customer_model').style.display = 'none';">
            <i class="fa fa-close"></i>
          </button>
        </div>
        <div class="modal-body">
          <?php include('sections/add_new_customer.html'); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- SIDENAV -->
  <?php include("sections/sidenav.html"); ?>

  <div class="container-fluid">
    <div class="container">

      <!-- HEADER -->
      <?php
      require "php/header.php";
      createHeader('clipboard', 'New Receipt', 'Create New Patient Receipt');
      ?>

      <!-- PATIENT & RECEIPT CARD -->
      <div class="receipt-card">
        <div class="section-title">
          <i class="fa fa-user-circle"></i>
          Patient & Receipt Information
        </div>

        <div class="row">
          <div class="col-md-4 form-group">
            <label>Patient Name</label>
            <input id="customers_name" type="text" class="form-control" placeholder="Search or enter patient name"
              name="customers_name" onkeyup="showSuggestions(this.value, 'customer');">
            <code class="text-danger small" id="customer_name_error" style="display: none;"></code>
            <div id="customer_suggestions" class="list-group position-fixed"
              style="z-index: 1000; width: 25%; overflow: auto; max-height: 200px; background: white; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
            </div>

            <!-- NEW PATIENT BUTTON - LETAK BAWAH PATIENT NAME -->
            <div class="new-patient-btn mt-2"
              onclick="document.getElementById('add_new_customer_model').style.display = 'block';">
              <i class="fa fa-plus-circle"></i> New Patient
            </div>
          </div>

          <div class="col-md-3 form-group">
            <label>Contact Number</label>
            <input id="customers_contact_number" type="tel" class="form-control" placeholder="Contact Number" disabled>
          </div>

          <div class="col-md-3 form-group">
            <label>Address</label>
            <input id="customers_address" type="text" class="form-control" placeholder="Address" disabled>
          </div>

          <div class="col-md-2 form-group">
            <label>Receipt Number</label>
            <input id="invoice_number" type="text" class="form-control" placeholder="Auto" disabled
              style="background: #eef2f7;">
          </div>
        </div>

        <!-- BUANG NEW PATIENT BUTTON YANG LAMA DI BAWAH -->
        <div class="row">
          <div class="col-md-3 form-group">
            <label>Payment Type</label>
            <select id="payment_type" class="form-control">
              <option value="1">Cash Payment</option>
              <option value="2">Card Payment</option>
              <option value="3">Online Banking</option>
              <option value="4">DuitNow QR</option>
            </select>
          </div>

          <div class="col-md-3 form-group">
            <label>Date</label>
            <input type="date" class="form-control" id="invoice_date" value="<?php echo date('Y-m-d'); ?>"
              onblur="checkDate(this.value, 'date_error');">
            <code class="text-danger small" id="date_error" style="display: none;"></code>
          </div>

          <div class="col-md-3 form-group">
            </div>
          </div>
        </div>
      </div>

      <!-- MEDICINE SECTION CARD -->
      <div class="receipt-card">
        <div class="section-title">
          <i class="fa fa-capsules"></i>
          Medicine Details
        </div>

        <div id="invoice_medicine_list_div"></div>
        <script>
          addRow();
          getInvoiceNumber();
        </script>
      </div>

      <!-- GRAND TOTAL & PAYMENT SECTION - SAME ROW -->
      <div class="row payment-row">
        <!-- Paid Amount - Left -->
        <div class="col-md-3">
          <div class="payment-card">
            <label><i class="fa fa-money-bill-wave"></i> Paid Amount (RM)</label>
            <input type="text" class="form-control" name="paid_amount" id="paid_amount" onkeyup="getChange(this.value);"
              placeholder="0.00">
          </div>
        </div>

        <!-- Change -->
        <div class="col-md-2">
          <div class="change-card">
            <label><i class="fa fa-exchange-alt"></i> Change (RM)</label>
            <input type="text" class="form-control" id="change_amt" disabled placeholder="0.00">
          </div>
        </div>

        <!-- Empty space -->
        <div class="col-md-3"></div>

        <!-- Grand Total - Right Aligned -->
        <div class="col-md-4">
          <div class="total-box">
            <label class="font-weight-bold">Grand Total</label>
            <input type="text" class="form-control" id="final_total" value="0.00" disabled>
          </div>
        </div>
      </div>

      <!-- BUTTON SECTION - CENTERED -->
      <div class="row mt-4 mb-5">
        <div class="col-md-4"></div>

        <div class="col-md-2 form-group" id="save_button">
          <button class="btn btn-modern btn-primary-modern form-control" onclick="addInvoice();" id="saveReceiptBtn">
            <i class="fa fa-save"></i> SAVE RECEIPT
          </button>
        </div>

        <div class="col-md-2 form-group" id="new_invoice_button" style="display: none;">
          <button class="btn btn-modern btn-primary-modern form-control" onclick="location.reload();">
            <i class="fa fa-plus"></i> NEW RECEIPT
          </button>
        </div>

        <div class="col-md-2 form-group" id="pdf_button" style="display: none;">
          <button class="btn btn-modern btn-secondary-modern form-control"
            onclick="downloadInvoicePdf(document.getElementById('invoice_number').value);">
            <i class="fa fa-file-pdf-o"></i> RECEIPT PDF
          </button>
        </div>

        <div class="col-md-2"></div>
      </div>

      <!-- ACKNOWLEDGEMENT -->
      <div id="invoice_acknowledgement" class="col-md-12 h5 text-success font-weight-bold text-center"
        style="font-family: sans-serif;"></div>

    </div>
  </div>

</body>

</html>