var reportChart = null;

function showReport(title) {
  var start_date = document.getElementById('start_date').value;
  var end_date = document.getElementById('end_date').value;
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(xhttp.readyState == 4 && xhttp.status == 200)
      document.getElementById(title + "_report_div").innerHTML = xhttp.responseText;
  };
  xhttp.open("GET", "php/report.php?action=" + encodeURIComponent(title) + "&start_date=" + encodeURIComponent(start_date) + "&end_date=" + encodeURIComponent(end_date), true);
  xhttp.send();
  loadReportChart(title, start_date, end_date);
}

function loadReportChart(title, start_date, end_date) {
  var chartCanvas = document.getElementById(title + "_chart");
  if (!chartCanvas) return;

  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function () {
    if (xhttp.readyState == 4 && xhttp.status == 200) {
      var data = JSON.parse(xhttp.responseText || '{"labels":[],"values":[]}');
      renderReportChart(title, data.labels || [], data.values || []);
    }
  };
  xhttp.open("GET", "php/report.php?action=" + encodeURIComponent(title + "_chart") + "&start_date=" + encodeURIComponent(start_date || "") + "&end_date=" + encodeURIComponent(end_date || ""), true);
  xhttp.send();
}

function renderReportChart(title, labels, values) {
  var ctx = document.getElementById(title + "_chart").getContext("2d");
  if (reportChart) {
    reportChart.destroy();
  }
  reportChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [{
        label: title == "sales" ? "Sales (RM)" : "Orderings (RM)",
        data: values,
        backgroundColor: title == "sales" ? "rgba(40, 167, 69, 0.6)" : "rgba(220, 53, 69, 0.6)",
        borderColor: title == "sales" ? "rgba(40, 167, 69, 1)" : "rgba(220, 53, 69, 1)",
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

function printReport(report_title) {
  var start_date = document.getElementById('start_date').value;
  var end_date = document.getElementById('end_date').value;

  var page_content = document.body.innerHTML;

  var print_content = document.getElementById("print_content").innerHTML;

  if(start_date == "" || end_date == "")
    print_content = "<div class='h3 text-center text-primary'>" + report_title + " Report</div><br>" + print_content;
  else
    print_content = "<div class='h3 text-center text-primary'>" + report_title + " Report Between " + start_date + " and " + end_date + "</div><br><br>" + print_content;

  document.body.innerHTML = print_content;
  window.print();
  document.body.innerHTML = page_content;
}

function initReportPage(title) {
  loadReportChart(title, "", "");
}

function openReportPdf(title) {
  var start_date = document.getElementById('start_date').value;
  var end_date = document.getElementById('end_date').value;
  var t = (title || "").toLowerCase();
  var type = t === "purchase" ? "purchase" : "sales";
  var url = "php/report_pdf.php?type=" + encodeURIComponent(type)
    + "&start_date=" + encodeURIComponent(start_date || "")
    + "&end_date=" + encodeURIComponent(end_date || "");
  var w = window.open(url, "_blank");
  if (!w) {
    window.location.href = url;
  }
}
