function editStaff(id) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('staff_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_staff.php?action=edit&id=" + id, true);
  xhttp.send();
}

function deleteStaff(id) {
  if(confirm("Are you sure you want to delete this staff?")) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if(xhttp.readyState == 4 && xhttp.status == 200)
        document.getElementById('staff_div').innerHTML = xhttp.responseText;
    };
    xhttp.open("GET", "php/manage_staff.php?action=delete&id=" + id, true);
    xhttp.send();
  }
}

function updateStaff(id) {
  var name = document.getElementById("staff_name").value;
  var email = document.getElementById("staff_email").value;
  var contact_number = document.getElementById("staff_contact_number").value;
  var roleEl = document.getElementById("staff_role");
  var statusEl = document.getElementById("staff_status");
  var role = roleEl ? roleEl.value : "Clinic Assistant";
  var status = statusEl ? statusEl.value : "Active";
  var password = document.getElementById("staff_password").value;
  var confirm_password = document.getElementById("staff_confirm_password").value;

  // Optional: validate inputs here

  var params = "action=update&id=" + id +
               "&name=" + encodeURIComponent(name) +
               "&email=" + encodeURIComponent(email) +
               "&contact_number=" + encodeURIComponent(contact_number) +
               "&role=" + encodeURIComponent(role) +
               "&status=" + encodeURIComponent(status) +
               "&password=" + encodeURIComponent(password) +
               "&confirm_password=" + encodeURIComponent(confirm_password);

  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('staff_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_staff.php?" + params, true);
  xhttp.send();
}



function cancel() {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('staff_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_staff.php?action=cancel", true);
  xhttp.send();
}
