function deletePurchase(id) {
  var confirmation = confirm("Are you sure?");
  if(confirmation) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if(xhttp.readyState == 4 && xhttp.status == 200)
        document.getElementById('purchases_div').innerHTML = xhttp.responseText;
    };
    xhttp.open("GET", "php/manage_purchase.php?action=delete&id=" + id, true);
    xhttp.send();
  }
}

function editPurchase(id) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('purchases_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_purchase.php?action=edit&id=" + id, true);
  xhttp.send();
}

function updatePurchase(id) {
  var suppliers_name = document.getElementById("suppliers_name");
  var invoice_date = document.getElementById("invoice_date");
  var grand_total = document.getElementById("grand_total");
  var payment_status = document.getElementById("payment_status");
  //alert(payment_status.value);
  //if(!notNull(suppliers_name.value, "supplier_name_error"))
    //suppliers_name.focus();
  //else if(isSupplier(suppliers_name.value) == "false") {
    //document.getElementById("supplier_name_error").style.display = "block";
    //document.getElementById("supplier_name_error").innerHTML = "Supplier doesn't exists!";
    //suppliers_name.focus();
  //}
  //else
  if(!checkDate(invoice_date.value, 'date_error'))
    invoice_date.focus();
  else {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if(xhttp.readyState == 4 && xhttp.status == 200)
        document.getElementById('purchases_div').innerHTML = xhttp.responseText;
    };
    xhttp.open("GET", "php/manage_purchase.php?action=update&id=" + encodeURIComponent(id) + "&suppliers_name=" + encodeURIComponent(suppliers_name.value) + "&invoice_date=" + encodeURIComponent(invoice_date.value) + "&grand_total=" + encodeURIComponent(grand_total.value) + "&payment_status=" + encodeURIComponent(payment_status.value), true);
    xhttp.send();
  }
}

function downloadOrderPdf(voucher_number) {
  window.open("php/manage_purchase.php?action=pdf_purchase&voucher_number=" + encodeURIComponent(voucher_number), "_blank");
}

function cancel() {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('purchases_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_purchase.php?action=cancel", true);
  xhttp.send();
}

function searchPurchase(text, tag) {
  if(tag == "VOUCHER_NUMBER") {
    document.getElementById("by_suppliers_name").value = "";
    document.getElementById("by_invoice_number").value = "";
    document.getElementById("by_purchase_date").value = "";
  }
  if(tag == "SUPPLIER_NAME") {
    document.getElementById("by_invoice_number").value = "";
    document.getElementById("by_purchase_date").value = "";
  }
  if(tag == "INVOICE_NUMBER") {
    document.getElementById("by_suppliers_name").value = "";
    document.getElementById("by_purchase_date").value = "";
  }
  if(tag == "PURCHASE_DATE") {
    document.getElementById("by_suppliers_name").value = "";
    document.getElementById("by_invoice_number").value = "";
  }

  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('purchases_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_purchase.php?action=search&text=" + encodeURIComponent(text) + "&tag=" + encodeURIComponent(tag), true);
  xhttp.send();
}
