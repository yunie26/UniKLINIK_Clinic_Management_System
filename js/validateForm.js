function notNull(text, error) {
  var result = document.getElementById(error);
  if (!result) {
    if (typeof text !== "string") return text !== null && text !== undefined;
    return text.trim() !== "";
  }
  result.style.display = "block";
  if(text < 0) {
    result.innerHTML = "Invalid!";
    return false;
  }
  else if(text.trim() == "") {
    result.innerHTML = "Must be filled out!";
    return false;
  }
  result.style.display = "none";
  return true;
}

function validateName(name, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(name.trim() == "") {
    result.innerHTML = "Must be filled out!";
    return false;
  }
  result.innerHTML = "Must contain only letters!";
  for(var i = 0; i < name.length; i++)
    if(!((name[i] >= 'a' && name[i] <= 'z') || (name[i] >= 'A' && name[i] <= 'Z') || name[i] == ' '))
      return false;
  result.style.display = "none";
  return true;
}

function validateContactNumber(contact_number, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(contact_number.length != 10) {
    result.innerHTML = "Must contain 10 digits!";
    return false;
  }
  else
    result.style.display = "none";
  return true;
}

function validateAddress(address, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(address.trim().length < 10) {
    result.innerHTML = "Please enter more specific address!";
    return false;
  }
  else
    result.style.display = "none";
  return true;
}

function checkExpiry(date, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(date.trim() == "" || date.trim().length != 5 || date[2] != "/")
    result.innerHTML = "Please enter date in mm/yy format!";
  else if(date.slice(0, 2) < 1 || date.slice(0, 2) > 12)
    result.innerHTML = "Invalid month!";
  else if(new Date("20" + date.slice(3, 5), date.slice(0, 2)) < new Date()) {
    result.innerHTML = "Expired Medicine!";
    return -1;
  }
  else {
    result.style.display = "none";
    return true;
  }
  return false;
}

function checkQuantity(quantity, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(quantity < 0 || !Number.isInteger(parseFloat(quantity)))
    result.innerHTML = "Invalid quantity!";
  else {
    result.style.display = "none";
    return true;
  }
  return false;
}

function checkValue(value, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(value < 0 || value == "")
    result.innerHTML = "Invalid!";
  else {
    result.style.display = "none";
    return true;
  }
  return false;
}

function checkDate(date, error) {
  var result = document.getElementById(error);
  result.style.display = "block";
  if(date == "")
    result.innerHTML = "Mustn't be empty!!";
  else {
    var parts = date.split("-");
    var selected = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    if(selected > today)
      result.innerHTML = "Mustn't be future date!";
    else {
      result.style.display = "none";
      return true;
    }
  }
  return false;
}

function addCustomer() {
  document.getElementById("customer_acknowledgement").innerHTML = "";
  var customer_name = document.getElementById("customer_name");
  var contact_number = document.getElementById("customer_contact_number");
  var customer_address = document.getElementById("customer_address");
  var doctor_name = document.getElementById("customer_doctors_name");
  var doctor_address = document.getElementById("customer_doctors_address");
  if(!validateName(customer_name.value, "name_error"))
    customer_name.focus();
  else if(!validateContactNumber(contact_number.value, "contact_number_error"))
    contact_number.focus();
  else if(!validateAddress(customer_address.value, "address_error"))
    customer_address.focus();
  else if(!validateName(doctor_name.value, 'doctor_name_error'))
    doctor_name.focus();
  else if(!validateAddress(doctor_address.value, 'doctor_address_error'))
    doctor_address.focus();
  else {
    var xhttp = new XMLHttpRequest();
  	xhttp.onreadystatechange = function() {
  		if(xhttp.readyState = 4 && xhttp.status == 200)
  			document.getElementById("customer_acknowledgement").innerHTML = xhttp.responseText;
  	};
  	xhttp.open("GET", "php/add_new_customer.php?name=" + customer_name.value + "&contact_number=" + contact_number.value + "&address=" + customer_address.value + "&doctor_name=" + doctor_name.value + "&doctor_address=" + doctor_address.value, true);
  	xhttp.send();
  }
  return false;
}

function validateEmail(email, error) {
  var result = document.getElementById(error);
  result.style.display = "block";

  if (email.trim() == "") {
    result.innerHTML = "Must be filled out!";
    return false;
  }

  var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!emailPattern.test(email)) {
    result.innerHTML = "Invalid email format!";
    return false;
  }

  result.style.display = "none";
  return true;
}


function addStaff() {
  document.getElementById("staff_acknowledgement").innerHTML = "";
  var name = document.getElementById("staff_name");
  var email = document.getElementById("staff_email");
  var contact_number = document.getElementById("staff_contact_number");
  var password = document.getElementById("staff_password");
  var confirm_password = document.getElementById("staff_confirm_password");

  if (!validateName(name.value, "name_error"))
    name.focus();
  else if (!validateEmail(email.value, "email_error")) 
    email.focus();
  else if (!validateContactNumber(contact_number.value, "contact_number_error"))
    contact_number.focus();
  else if (password.value.trim() === "")
    alert("Password must not be empty.");
  else if (password.value !== confirm_password.value)
    alert("Passwords do not match.");
  else {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
      if (xhttp.readyState == 4 && xhttp.status == 200) {
        document.getElementById("staff_acknowledgement").innerHTML = xhttp.responseText;
      }
    };
    xhttp.open("POST", "php/add_new_staff.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send(
      "name=" + encodeURIComponent(name.value) +
      "&email=" + encodeURIComponent(email.value) +
      "&contact_number=" + encodeURIComponent(contact_number.value) +
      "&password=" + encodeURIComponent(password.value) +
      "&confirm_password=" + encodeURIComponent(confirm_password.value)
    );
  }
}

function addSupplier() {
  document.getElementById("supplier_acknowledgement").innerHTML = "";
  var supplier_name = document.getElementById("supplier_name");
  var supplier_email = document.getElementById("supplier_email");
  var contact_number = document.getElementById("supplier_contact_number");
  var supplier_address = document.getElementById("supplier_address");
  if(!validateName(supplier_name.value, "name_error"))
    supplier_name.focus();
  else if(!validateContactNumber(contact_number.value, "contact_number_error"))
    contact_number.focus();
  else if(!validateAddress(supplier_address.value, "address_error"))
    supplier_address.focus();
  else {
    var xhttp = new XMLHttpRequest();
  	xhttp.onreadystatechange = function() {
  		if(xhttp.readyState = 4 && xhttp.status == 200)
  			document.getElementById("supplier_acknowledgement").innerHTML = xhttp.responseText;
  	};
  	xhttp.open("GET", "php/add_new_supplier.php?name=" + supplier_name.value + "&email=" + supplier_email.value + "&contact_number=" + contact_number.value + "&address=" + supplier_address.value, true);
  	xhttp.send();
  }
}

function addMedicine() {
  document.getElementById("medicine_acknowledgement").innerHTML = "";
  var name = document.getElementById("medicine_name");
  var packing = document.getElementById("packing");
  var generic_name = document.getElementById("generic_name");
  var suppliers_name = document.getElementById("suppliers_name");
  var expiry_date = document.getElementById("expiry_date");
  var quantity = document.getElementById("quantity");
  var mrp = document.getElementById("mrp");
  if(!notNull(name.value, "medicine_name_error"))
    name.focus();
  else if(!notNull(packing.value, "pack_error"))
    packing.focus();
  else if(!notNull(generic_name.value, "generic_name_error"))
    generic_name.focus();
  else if(!notNull(suppliers_name.value, "supplier_name_error"))
    suppliers_name.focus();
  else if(!checkExpiry(expiry_date.value, "expiry_date_error"))
    expiry_date.focus();
  else if(!checkQuantity(quantity.value, "quantity_error"))
    quantity.focus();
  else if(!checkValue(mrp.value, "mrp_error"))
    mrp.focus();
  else {
    var xhttp = new XMLHttpRequest();
  	xhttp.onreadystatechange = function() {
  		if(xhttp.readyState = 4 && xhttp.status == 200)
  			document.getElementById("medicine_acknowledgement").innerHTML = xhttp.responseText;
  	};
  	xhttp.open("GET", "php/add_new_medicine.php?name=" + encodeURIComponent(name.value) + "&packing=" + encodeURIComponent(packing.value) + "&generic_name=" + encodeURIComponent(generic_name.value) + "&suppliers_name=" + encodeURIComponent(suppliers_name.value) + "&expiry_date=" + encodeURIComponent(expiry_date.value) + "&quantity=" + encodeURIComponent(quantity.value) + "&mrp=" + encodeURIComponent(mrp.value), true);
  	xhttp.send();
  }
}
