var rows = 0;

class MedicineStock {
  constructor(name, batch_id, expiry_date, quantity, mrp) {
    this.name = name;
    this.batch_id = batch_id;
    this.expiry_date = expiry_date;
    this.quantity = quantity;
    this.mrp = mrp;
  }
}

class NewMedicine {
  constructor(name, packing, generic_name, supplier_name) {
    this.name = name;
    this.packing = packing;
    this.generic_name = generic_name;
    this.supplier_name = supplier_name;
  }
}

function addRow() {
  if(typeof addRow.counter == 'undefined')
    addRow.counter = 1;
  var node = document.createElement("div");
  var id = document.createAttribute("id");
  id.value = "medicine_row_" + addRow.counter;
  node.setAttributeNode(id);
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200) {
      node.innerHTML = xhttp.responseText;
      document.getElementById("purchase_medicine_list_div").appendChild(node);
      calculateGrandTotal();
    }
  };
  xhttp.open("GET", "php/add_new_purchase.php?action=add_row&row_id=" + id.value + "&row_number=" + addRow.counter, true);
  xhttp.send();
  addRow.counter++;
  rows++;
}

function removeRow(row_id) {
  if(rows <= 1) {
    alert("Can't delete, only one row is there!");
  } else {
    document.getElementById(row_id).remove();
    rows--;
    calculateGrandTotal();
  }
}

function medicineOptions(text, id) {
  medicineOptionsPurchase(text, id);
}

/** Datalist options for medicine name (purchase rows use ids like medicine_list_purchase_N). */
function medicineOptionsPurchase(text, datalistId) {
  var dl = document.getElementById(datalistId);
  if (!dl) {
    return;
  }
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (xhttp.readyState == 4 && xhttp.status == 200) {
      dl.innerHTML = xhttp.responseText;
    }
  };
  xhttp.open("GET", "php/add_new_purchase.php?action=medicine_list&text=" + encodeURIComponent(text.trim()), true);
  xhttp.send();
}

function fillPurchaseStockFieldSync(name, fieldId, column) {
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", "php/add_new_purchase.php?action=fill&name=" + encodeURIComponent(name) + "&column=" + encodeURIComponent(column), false);
  xhttp.send(null);
  var el = document.getElementById(fieldId);
  if (el) {
    el.value = xhttp.responseText;
  }
}

/** Same stock row as new receipt: latest medicines_stock row for this name (ORDER BY ID DESC). */
function fillPurchaseStockRow(medicine_name, rowNum) {
  if (!medicine_name || !String(medicine_name).trim()) {
    return;
  }
  var n = String(rowNum);
  var name = String(medicine_name).trim();
  var err = document.getElementById("medicine_name_error_" + n);
  if (err) {
    err.style.display = "none";
    err.innerHTML = "";
  }
  fillPurchaseStockFieldSync(name, "batch_id_" + n, "BATCH_ID");
  fillPurchaseStockFieldSync(name, "available_quantity_" + n, "QUANTITY");
  fillPurchaseStockFieldSync(name, "expiry_date_" + n, "EXPIRY_DATE");
  fillPurchaseStockFieldSync(name, "mrp_" + n, "MRP");

  getAmount(n);

  var batchEl = document.getElementById("batch_id_" + n);
  if (!batchEl || !String(batchEl.value).trim()) {
    if (err) {
      err.style.display = "block";
      err.innerHTML = "Medicine not found in stock. Add stock first or check spelling.";
    }
    return;
  }

  var expiryVal = document.getElementById("expiry_date_" + n).value;
  if (typeof checkExpiry === "function" && checkExpiry(expiryVal, "medicine_name_error_" + n) === -1) {
    return;
  }
  if (err) {
    err.style.display = "none";
  }

  var mn = document.getElementById("medicine_name_" + n);
  if (mn) {
    mn.blur();
  }
  updatePurchaseQuantity(n);
}

function updatePurchaseQuantity(id) {
  var qtyEl = document.getElementById("quantity_" + id);
  if (!qtyEl) {
    return;
  }
  var qtyVal = qtyEl.value;
  if (qtyVal !== "" && qtyVal !== null && typeof checkQuantity === "function") {
    var n = parseFloat(qtyVal);
    if (!isNaN(n) && (n < 0 || !Number.isInteger(n))) {
      checkQuantity(qtyVal, "quantity_error_" + id);
      getAmount(id);
      return;
    }
    var errEl = document.getElementById("quantity_error_" + id);
    if (errEl) errEl.style.display = "none";
  }
  getAmount(id);
}

function purchaseQuantityBlur(id) {
  var qtyEl = document.getElementById("quantity_" + id);
  if (!qtyEl) {
    return;
  }
  if (qtyEl.value === "" || qtyEl.value === null) {
    return;
  }
  if (typeof checkQuantity === "function" && !checkQuantity(qtyEl.value, "quantity_error_" + id)) {
    return;
  }
  getAmount(id);
}

function isSupplier(name) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_purchase.php?action=is_supplier&name=" + name, false);
  xhttp.send();
  return xhttp.responseText;
}

function checkInvoice(invoice_number, error) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_purchase.php?action=is_invoice&invoice_number=" + invoice_number, false);
  xhttp.send();
  if(xhttp.responseText == "true") {
    document.getElementById(error).style.display = "block";
    document.getElementById(error).innerHTML = "already added!";
    return true;
  }
  else {
    document.getElementById(error).style.display = "none";
    return false;
  }
}

function isNewMedicine(name, packing) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      xhttp.responseText;
  };
  xhttp.open("GET", "php/add_new_purchase.php?action=is_new_medicine&name=" + name + "&packing=" + packing, false);
  xhttp.send();
  return xhttp.responseText;
}

function getAmount(row_number) {
  var qtyEl = document.getElementById("quantity_" + row_number);
  var mrpEl = document.getElementById("mrp_" + row_number);
  var amtEl = document.getElementById("amount_" + row_number);
  if (!qtyEl || !mrpEl || !amtEl) return;

  var qty = parseFloat(qtyEl.value);
  var mrp = parseFloat(mrpEl.value);
  if (isNaN(qty) || qty < 0) qty = 0;
  if (isNaN(mrp) || mrp < 0) mrp = 0;

  amtEl.value = (qty * mrp).toFixed(2);
  calculateGrandTotal();
}

function calculateGrandTotal() {
  var total = 0;
  var allAmounts = document.querySelectorAll('[id^="amount_"]');
  
  for(var i = 0; i < allAmounts.length; i++) {
    var val = parseFloat(allAmounts[i].value);
    if(!isNaN(val)) {
      total += val;
    }
  }
  
  document.getElementById("grand_total").value = total.toFixed(2);
}

function addPurchase() {
  var suppliers_name = document.getElementById('suppliers_name');
  var invoice_number = document.getElementById('invoice_number');
  var payment_type = document.getElementById('payment_type');
  var invoice_date = document.getElementById('invoice_date');

  if(!notNull(suppliers_name.value, "supplier_name_error")) {
    suppliers_name.focus();
    return false;
  }
  else if(isSupplier(suppliers_name.value) == "false") {
    document.getElementById("supplier_name_error").style.display = "block";
    document.getElementById("supplier_name_error").innerHTML = "Supplier doesn't exists!";
    suppliers_name.focus();
    return false;
  }
  else if(!notNull(invoice_number.value, 'invoice_number_error')) {
    invoice_number.focus();
    return false;
  }
  else if(checkInvoice(invoice_number.value, 'invoice_number_error')) {
    invoice_number.focus();
    return false;
  }
  else if(!checkDate(invoice_date.value, 'date_error')) {
    invoice_date.focus();
    return false;
  }
  
  var parent = document.getElementById('purchase_medicine_list_div');
  if (!parent) {
    alert('Purchase form is missing the medicine list.');
    return false;
  }

  var nameInputs = parent.querySelectorAll('input[id^="medicine_name_"]');
  if (!nameInputs.length) {
    alert('Add at least one medicine line.');
    return false;
  }

  var medicineStockRow = [];
  for (var i = 0; i < nameInputs.length; i++) {
    var medicine_name = nameInputs[i];
    var idMatch = /^medicine_name_(\d+)$/.exec(medicine_name.id);
    if (!idMatch) {
      continue;
    }
    var n = idMatch[1];
    var medicine_name_error = document.getElementById('medicine_name_error_' + n);
    var batch_id = document.getElementById('batch_id_' + n);
    var expiry_date = document.getElementById('expiry_date_' + n);
    var quantity = document.getElementById('quantity_' + n);
    var quantity_error = document.getElementById('quantity_error_' + n);
    var mrp = document.getElementById('mrp_' + n);
    var mrp_error = document.getElementById('mrp_error_' + n);

    if (!batch_id || !expiry_date || !quantity || !mrp) {
      alert('Medicine line is still loading or broken (row ' + n + '). Wait a moment or refresh the page.');
      return false;
    }

    if (!notNull(medicine_name.value, medicine_name_error ? medicine_name_error.getAttribute('id') : '')) {
      medicine_name.focus();
      return false;
    }
    else if (!String(batch_id.value).trim()) {
      if (medicine_name_error) {
        medicine_name_error.style.display = "block";
        medicine_name_error.innerHTML = "Select a medicine from the list to load batch and price.";
      }
      medicine_name.focus();
      return false;
    }
    else if (typeof checkExpiry === "function") {
      var exOk = checkExpiry(expiry_date.value, medicine_name_error ? medicine_name_error.getAttribute("id") : "");
      if (exOk !== true) {
        medicine_name.focus();
        return false;
      }
    }
    else if (!checkQuantity(quantity.value, quantity_error ? quantity_error.getAttribute('id') : '')) {
      quantity.focus();
      return false;
    }
    else if (quantity.value == 0) {
      if (quantity_error) {
        quantity_error.style.display = "block";
        quantity_error.innerHTML = "Increase quantity or remove row!";
      }
      quantity.focus();
      return false;
    }
    else if (!checkValue(mrp.value, mrp_error ? mrp_error.getAttribute("id") : "")) {
      medicine_name.focus();
      return false;
    }
    medicineStockRow.push(new MedicineStock(medicine_name.value, batch_id.value, expiry_date.value, quantity.value, mrp.value));
  }

  if (!medicineStockRow.length) {
    alert('Add at least one medicine line.');
    return false;
  }

  var grand_total = document.getElementById("grand_total");

  var voucher = savePurchaseHeaderSync(suppliers_name.value, invoice_number.value, payment_type.value, invoice_date.value, grand_total.value);
  if (voucher == null) {
    return false;
  }

  for (var j = 0; j < medicineStockRow.length; j++) {
    var r = medicineStockRow[j];
    addMedicineStockSync(r.name, r.batch_id, r.expiry_date, r.quantity, r.mrp, invoice_number.value);
    addPurchaseOrderLineSync(voucher, j, r.name, r.batch_id, r.expiry_date, r.quantity, r.mrp);
  }

  document.getElementById('purchase_acknowledgement').innerHTML = 'Purchase saved...';
  document.getElementById('latest_order_invoice_number').value = invoice_number.value;
  var pdfOb = document.getElementById('pdf_order_button');
  if (pdfOb) pdfOb.style.display = "block";
}

function savePurchaseHeaderSync(suppliers_name, invoice_number, payment_type, invoice_date, grand_total) {
  var url = "php/add_new_purchase.php?action=add_new_purchase"
    + "&suppliers_name=" + encodeURIComponent(suppliers_name)
    + "&invoice_number=" + encodeURIComponent(invoice_number)
    + "&payment_type=" + encodeURIComponent(payment_type)
    + "&invoice_date=" + encodeURIComponent(invoice_date)
    + "&grand_total=" + encodeURIComponent(grand_total);
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", url, false);
  xhttp.send(null);
  if (xhttp.status !== 200) {
    alert("Could not save purchase.");
    return null;
  }
  var m = /\|VOUCHER:(\d+)/.exec(xhttp.responseText);
  if (!m) {
    alert(xhttp.responseText.indexOf("Failed") >= 0 ? xhttp.responseText : "Could not save purchase.");
    return null;
  }
  return parseInt(m[1], 10);
}

function addMedicineStockSync(name, batch_id, expiry_date, quantity, mrp, invoice_number) {
  var url = "php/add_new_purchase.php?action=add_stock"
    + "&name=" + encodeURIComponent(name)
    + "&batch_id=" + encodeURIComponent(batch_id)
    + "&expiry_date=" + encodeURIComponent(expiry_date)
    + "&quantity=" + encodeURIComponent(quantity)
    + "&mrp=" + encodeURIComponent(mrp)
    + "&invoice_number=" + encodeURIComponent(invoice_number);
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", url, false);
  xhttp.send(null);
}

function addPurchaseOrderLineSync(voucher, lineOrder, name, batch_id, expiry_date, quantity, mrp) {
  var url = "php/add_new_purchase.php?action=add_purchase_line"
    + "&voucher_number=" + encodeURIComponent(voucher)
    + "&line_order=" + encodeURIComponent(lineOrder)
    + "&name=" + encodeURIComponent(name)
    + "&batch_id=" + encodeURIComponent(batch_id)
    + "&expiry_date=" + encodeURIComponent(expiry_date)
    + "&quantity=" + encodeURIComponent(quantity)
    + "&mrp=" + encodeURIComponent(mrp);
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", url, false);
  xhttp.send(null);
}

function openLatestOrderPdfInNewTab() {
  var invoiceNumber = document.getElementById('latest_order_invoice_number').value;
  if (!invoiceNumber) return;
  window.open("php/manage_purchase.php?action=pdf_purchase_by_invoice&invoice_number=" + encodeURIComponent(invoiceNumber), "_blank");
}