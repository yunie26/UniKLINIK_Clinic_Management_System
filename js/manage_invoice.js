function deleteInvoice(invoice_number) {
  var confirmation = confirm("Are you sure?");
  if(confirmation) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if(xhttp.readyState == 4 && xhttp.status == 200)
        document.getElementById('invoices_div').innerHTML = xhttp.responseText;
    };
    xhttp.open("GET", "php/manage_invoice.php?action=delete&invoice_number=" + invoice_number, true);
    xhttp.send();
  }
}

function refresh() {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('invoices_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_invoice.php?action=refresh", true);
  xhttp.send();
}

function searchInvoice(text, tag) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById('invoices_div').innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/manage_invoice.php?action=search&text=" + encodeURIComponent(text) + "&tag=" + encodeURIComponent(tag), true);
  xhttp.send();
}

function downloadInvoicePdf(invoice_number) {
  window.open("php/manage_invoice.php?action=pdf_invoice&invoice_number=" + encodeURIComponent(invoice_number), "_blank");
}
