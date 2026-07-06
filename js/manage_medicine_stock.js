function deleteMedicineStock(id) {
  var acknowledgement = document.getElementById("medicine_stock_acknowledgement");
  if (acknowledgement) acknowledgement.innerHTML = "";
  var confirmation = confirm("Are you sure?");
  if(confirmation) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if(xhttp.readyState == 4 && xhttp.status == 200)
        document.getElementById('medicines_stock_div').innerHTML = xhttp.responseText;
    };
    xhttp.open("GET", "php/manage_medicine_stock.php?action=delete&id=" + id, true);
    xhttp.send();
  }
}

function editMedicineStock(id) {
  var acknowledgement = document.getElementById("medicine_stock_acknowledgement");
  if (acknowledgement) acknowledgement.innerHTML = "";
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('medicines_stock_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_medicine_stock.php?action=edit&id=" + id, true);
  xhttp.send();
}

function updateMedicineStock(id) {
  var batch_id = document.getElementById("batch_id_" + id) || document.getElementById("batch_id");
  var expiry_date = document.getElementById("expiry_date_" + id) || document.getElementById("expiry_date");
  var quantity = document.getElementById("quantity_" + id) || document.getElementById("quantity");
  var mrp = document.getElementById("mrp_" + id) || document.getElementById("mrp");
  var rate = document.getElementById("rate_" + id) || document.getElementById("rate");
  var expiryErrId = (document.getElementById("expiry_date_error_" + id) ? "expiry_date_error_" + id : "expiry_date_error");
  var quantityErrId = (document.getElementById("quantity_error_" + id) ? "quantity_error_" + id : "quantity_error");
  var mrpErrId = (document.getElementById("mrp_error_" + id) ? "mrp_error_" + id : "mrp_error");
  var rateErrId = (document.getElementById("rate_error_" + id) ? "rate_error_" + id : "rate_error");
  var acknowledgement = document.getElementById("medicine_stock_acknowledgement");
  if (acknowledgement) acknowledgement.innerHTML = "";

  if(!batch_id || !expiry_date || !quantity || !mrp || !rate) {
    if (acknowledgement) {
      acknowledgement.className = "text-danger font-weight-bold mb-2";
      acknowledgement.innerHTML = "Medicine stock form is not ready. Please refresh and try again.";
    }
    return;
  }

  if(!checkExpiry(expiry_date.value, expiryErrId))
    expiry_date.focus();
  else if(!checkQuantity(quantity.value, quantityErrId))
    quantity.focus();
  else if(!checkValue(mrp.value, mrpErrId))
    mrp.focus();
  else if(!checkValue(rate.value, rateErrId))
    rate.focus();
  else if(Number.parseInt(mrp.value) < Number.parseFloat(rate.value)) {
    var rateErrEl = document.getElementById(rateErrId);
    if (rateErrEl) {
      rateErrEl.style.display = "block";
      rateErrEl.innerHTML = "Rate must be less than MRP!";
    }
    rate.focus();
  }
  else {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if(xhttp.readyState == 4 && xhttp.status == 200) {
        var response = xhttp.responseText;
        if (response.indexOf("SUCCESS::") === 0) {
          var parts = response.split("\n");
          var statusLine = parts.shift();
          var message = statusLine.replace("SUCCESS::", "");
          if (acknowledgement) {
            acknowledgement.className = "text-success font-weight-bold mb-2";
            acknowledgement.innerHTML = message;
          }
          document.getElementById('medicines_stock_div').innerHTML = parts.join("\n");
        } else if (response.indexOf("ERROR::") === 0) {
          var errorParts = response.split("\n");
          var errorLine = errorParts.shift();
          var errorMessage = errorLine.replace("ERROR::", "");
          if (acknowledgement) {
            acknowledgement.className = "text-danger font-weight-bold mb-2";
            acknowledgement.innerHTML = errorMessage;
          }
          document.getElementById('medicines_stock_div').innerHTML = errorParts.join("\n");
        } else {
          document.getElementById('medicines_stock_div').innerHTML = response;
        }
      }
    };
    xhttp.open("GET", "php/manage_medicine_stock.php?action=update&id=" + encodeURIComponent(id) + "&batch_id=" + encodeURIComponent(batch_id.value) + "&expiry_date=" + encodeURIComponent(expiry_date.value) + "&quantity=" + encodeURIComponent(quantity.value) + "&mrp=" + encodeURIComponent(mrp.value) + "&rate=" + encodeURIComponent(rate.value), true);
    xhttp.send();
  }
}

function cancel() {
  var acknowledgement = document.getElementById("medicine_stock_acknowledgement");
  if (acknowledgement) acknowledgement.innerHTML = "";
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('medicines_stock_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_medicine_stock.php?action=cancel", true);
  xhttp.send();
}

function searchMedicineStock(text, tag) {
  var acknowledgement = document.getElementById("medicine_stock_acknowledgement");
  if (acknowledgement) acknowledgement.innerHTML = "";
  if(tag == "NAME") {
    document.getElementById("by_generic_name").value = "";
    document.getElementById("by_suppliers_name").value = "";
  }
  if(tag == "GENERIC_NAME") {
    document.getElementById("by_name").value = "";
    document.getElementById("by_suppliers_name").value = "";
  }
  if(tag == "SUPPLIER_NAME") {
    document.getElementById("by_name").value = "";
    document.getElementById("by_generic_name").value = "";
  }

  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('medicines_stock_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_medicine_stock.php?action=search&text=" + encodeURIComponent(text) + "&tag=" + encodeURIComponent(tag), true);
  xhttp.send();
}
