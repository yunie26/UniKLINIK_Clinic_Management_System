var rows = 0;

class MedicineInfo {
  constructor(name, batch_id, expiry_date, quantity, mrp, total) {
    this.name = name;
    this.batch_id = batch_id;
    this.expiry_date = expiry_date;
    this.quantity = quantity;
    this.mrp = mrp;
    this.total = total;
  }
}

function addRow() {
  if(typeof addRow.counter == 'undefined')
    addRow.counter = 1;
  var node = document.createElement("div");
  var cls = document.createAttribute("id");
  cls.value = "medicine_row_" + addRow.counter;
  node.setAttributeNode(cls);
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200) {
      node.innerHTML = xhttp.responseText;
      document.getElementById("invoice_medicine_list_div").appendChild(node);
      // Trigger getTotal after row is added
      getTotal();
    }
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=add_row&row_id=" + cls.value + "&row_number=" + addRow.counter, true);
  xhttp.send();
  addRow.counter++;
  rows++;
}

function removeRow(row_id) {
  if(rows <= 1)
    alert("Can't delete only one row is there!");
  else {
    document.getElementById(row_id).remove();
    rows--;
    getTotal(); // Recalculate total after removal
  }
}

function getInvoiceNumber() {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById("invoice_number").value = xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=current_invoice_number", true);
  xhttp.send();
}

function medicineOptions(text, id) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById(id).innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=medicine_list&text=" + text.trim(), true);
  xhttp.send();
}

function fillFields(medicine_name, id) {
  fill(medicine_name, 'batch_id_' + id, 'BATCH_ID');
  fill(medicine_name, 'available_quantity_' + id, 'QUANTITY');
  fill(medicine_name, 'expiry_date_' + id, 'EXPIRY_DATE');
  fill(medicine_name, 'mrp_' + id, 'MRP');

  calculateRowTotal(id);
  getTotal();

  var expiry_date = document.getElementById('expiry_date_' + id).value;
  if(checkExpiry(expiry_date, 'medicine_name_error_' + id) != -1)
    document.getElementById("medicine_name_error_" + id).style.display = "none";
  else
    return;
  var available_quantity = document.getElementById("available_quantity_" + id).value;
  if(!checkAvailableQuantity(available_quantity, id))
    return;
  document.getElementById("medicine_name_" + id).blur();
}

function fill(name, field_name, column) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById(field_name).value = xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=fill&name=" + name + "&column=" + column, false);
  xhttp.send();
}

function calculateRowTotal(id) {
  var mrpEl = document.getElementById("mrp_" + id);
  var qtyEl = document.getElementById("quantity_" + id);
  var totalEl = document.getElementById("total_" + id);
  if (!mrpEl || !qtyEl || !totalEl) return;

  var mrp = parseFloat(mrpEl.value);
  var qty = parseFloat(qtyEl.value);
  if (isNaN(mrp)) mrp = 0;
  if (isNaN(qty) || qty <= 0) {
    totalEl.value = (0).toFixed(2);
    return;
  }

  var errEl = document.getElementById('quantity_error_' + id);
  if (errEl && !Number.isInteger(qty)) {
    errEl.style.display = "block";
    errEl.innerHTML = "Invalid quantity!";
    totalEl.value = (0).toFixed(2);
    return;
  }
  if (errEl) errEl.style.display = "none";

  totalEl.value = (mrp * qty).toFixed(2);
}

function getTotal() {
  var final_total = 0;
  var rows = document.querySelectorAll('[id^="medicine_row_"]');

  for(var i = 0; i < rows.length; i++) {
    var totalInput = rows[i].querySelector('[id^="total_"]');
    if(totalInput && totalInput.value) {
      var rowTotal = parseFloat(totalInput.value);
      if(!isNaN(rowTotal)) {
        final_total += rowTotal;
      }
    }
  }

  var finalEl = document.getElementById("final_total");
  if (finalEl) finalEl.value = final_total.toFixed(2);

  var paidAmount = document.querySelector('input[name="paid_amount"]');
  if(paidAmount && paidAmount.value) {
    getChange(paidAmount.value);
  }
}

function updateQuantity(id) {
  calculateRowTotal(id);
  getTotal();
}

function checkAvailableQuantity(value, id) {
  var medicine_name = document.getElementById("medicine_name_" + id).value;
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=check_quantity&medicine_name=" + medicine_name, false);
  xhttp.send();
  if(Number.parseInt(xhttp.responseText) == 0) {
    document.getElementById("medicine_name_error_" + id).style.display = "block";
    document.getElementById("medicine_name_error_" + id).innerHTML = "Out of Stock!";
    return -1;
  }
  else if(value > Number.parseInt(xhttp.responseText)) {
    document.getElementById("quantity_error_" + id).style.display = "block";
    document.getElementById("quantity_error_" + id).innerHTML = "only " + xhttp.responseText + " in stock!";
    return -2;
  }
  return 999;
}

function getChange(paid_amt) {
  var final_total = parseFloat(document.getElementById("final_total").value) || 0;
  paid_amt = parseFloat(paid_amt) || 0;

  var change = paid_amt - final_total;

  // Elakkan nilai negatif
  if (change < 0) {
    change = 0;
  }

  document.getElementById("change_amt").value = change.toFixed(2);
}

function isCustomer(name, contact_number) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=is_customer&name=" + name + "&contact_number=" + contact_number, false);
  xhttp.send();
  return xhttp.responseText;
}

function isInvoiceExist(invoice_number) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
    xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=is_invoice&invoice_number=" + invoice_number, false);
  xhttp.send();
  return xhttp.responseText;
}

function isMedicine(name) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=is_medicine&name=" + name, false);
  xhttp.send();
  return xhttp.responseText;
}

function addInvoice() {
  var customers_name = document.getElementById('customers_name');
  var customers_contact_number = document.getElementById('customers_contact_number');
  var invoice_number = document.getElementById('invoice_number');
  var invoice_date = document.getElementById('invoice_date');

  if(!notNull(customers_name.value, "customer_name_error")) {
    customers_name.focus();
    return false;
  }
  else if(isCustomer(customers_name.value, customers_contact_number.value) == "false") {
    document.getElementById("customer_name_error").style.display = "block";
    document.getElementById("customer_name_error").innerHTML = "Customer doesn't exists!";
    customers_name.focus();
    return false;
  }
  else if(isInvoiceExist(invoice_number.value) == "true") {
    document.getElementById("invoice_acknowledgement").innerHTML = "Already saved Invoice!";
    return false;
  }
  else if(!checkDate(invoice_date.value, 'date_error')) {
    invoice_date.focus();
    return false;
  }
  
  // Get all medicine rows
  var rows = document.querySelectorAll('[id^="medicine_row_"]');
  var medicines = [];
  
  for(var i = 0; i < rows.length; i++) {
    var row = rows[i];
    
    var medicine_name = row.querySelector('[id^="medicine_name_"]');
    var batch_id = row.querySelector('[id^="batch_id_"]');
    var expiry_date = row.querySelector('[id^="expiry_date_"]');
    var quantity = row.querySelector('[id^="quantity_"]');
    var mrp = row.querySelector('[id^="mrp_"]');
    var total = row.querySelector('[id^="total_"]');
    
    var medicine_name_error = row.querySelector('[id^="medicine_name_error_"]');
    var quantity_error = row.querySelector('[id^="quantity_error_"]');
    
    if(!medicine_name || !medicine_name.value) {
      medicine_name.focus();
      return false;
    }
    
    if(isMedicine(medicine_name.value) == "false") {
      if(medicine_name_error) {
        medicine_name_error.style.display = "block";
        medicine_name_error.innerHTML = "Medicine doesn't exists!";
      }
      medicine_name.focus();
      return false;
    }
    
    if(!checkExpiry(expiry_date.value, medicine_name_error ? medicine_name_error.getAttribute('id') : '')) {
      medicine_name.focus();
      return false;
    }
    
    if(!quantity.value || quantity.value == 0) {
      if(quantity_error) {
        quantity_error.style.display = "block";
        quantity_error.innerHTML = "Increase quantity or remove row!";
      }
      quantity.focus();
      return false;
    }
    
    var available = checkAvailableQuantity(quantity.value, quantity.getAttribute('id').slice(9, 10));
    if(available == -1 || available == -2) {
      return false;
    }
    
    medicines.push({
      name: medicine_name.value,
      batch_id: batch_id.value,
      expiry_date: expiry_date.value,
      quantity: quantity.value,
      mrp: mrp.value,
      total: total.value
    });
  }
  
  var final_total = document.getElementById("final_total").value;

  var fd = new FormData();
  fd.append("action", "commit_invoice");
  fd.append("customers_name", customers_name.value);
  fd.append("customers_contact_number", customers_contact_number.value);
  fd.append("invoice_date", invoice_date.value);
  fd.append("final_total", final_total);
  fd.append("lines_json", JSON.stringify(medicines));

  var xhttp = new XMLHttpRequest();
  xhttp.open("POST", "php/add_new_invoice.php", false);
  xhttp.send(fd);

  var resp = (xhttp.responseText || "").trim();
  if (resp.indexOf("Invoice saved") === -1) {
    document.getElementById("invoice_acknowledgement").innerHTML = resp || "Could not save invoice.";
    return false;
  }
  document.getElementById("invoice_acknowledgement").innerHTML = resp.split("|")[0] || resp;
  var m = resp.match(/INVOICE:(\d+)/);
  if (m) {
    document.getElementById("invoice_number").value = m[1];
  }

  document.getElementById("save_button").style.display = "none";
  document.getElementById("new_invoice_button").style.display = "block";
  var pdfBtn = document.getElementById("pdf_button");
  if (pdfBtn) pdfBtn.style.display = "block";

  return false;
}

function updateStock(name, batch_id, quantity) {
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", "php/add_new_invoice.php?action=update_stock&name=" + name + "&batch_id=" + batch_id + "&quantity=" + quantity, true);
  xhttp.send();
}

function addSale(customers_name, customers_contact_number, invoice_number, medicine_name, batch_id, expiry_date, quantity, mrp, total) {
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", "php/add_new_invoice.php?action=add_sale&customers_name=" + customers_name + "&customers_contact_number=" + customers_contact_number + "&invoice_number=" + invoice_number + "&medicine_name=" + medicine_name + "&batch_id=" + batch_id + "&expiry_date=" + expiry_date +  "&quantity=" + quantity + "&mrp=" + mrp + "&total=" + total, true);
  xhttp.send();
}

function addNewInvoice(customers_name, customers_contact_number, invoice_date, final_total) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById("invoice_acknowledgement").innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_invoice.php?action=add_new_invoice&customers_name=" + customers_name + "&customers_contact_number=" + customers_contact_number + "&invoice_date=" + invoice_date + "&final_total=" + final_total, true);
  xhttp.send();
}
