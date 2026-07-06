var NOTIFY_CATEGORIES = {
  stock:        { label: "Stock",        icon: "fa-cubes",        color: "#0ea5e9" },
  leave:        { label: "Leave",        icon: "fa-calendar",     color: "#8b5cf6" },
  ot:           { label: "OT",           icon: "fa-clock-o",      color: "#f59e0b" },
  prescription: { label: "Prescription", icon: "fa-file-text-o",  color: "#10b981" },
  general:      { label: "General",      icon: "fa-bell",         color: "#64748b" }
};

function escapeHtml(s) {
  if (s === null || s === undefined) return "";
  return String(s)
    .replace(/&/g,  "&amp;")
    .replace(/</g,  "&lt;")
    .replace(/>/g,  "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g,  "&#39;");
}

function escapeAttr(s) {
  return escapeHtml(s);
}

function toggleNotifications() {
  var panel = document.getElementById("notification_panel");
  if (!panel) return;
  panel.style.display = panel.style.display === "block" ? "none" : "block";
}

function formatNotificationTime(text) {
  if (!text) return "";
  var s = String(text).replace("T", " ").trim();
  if (s.length >= 16) {
    s = s.slice(0, 16);
  }
  return s + " MYT";
}

function categoryMeta(cat) {
  return NOTIFY_CATEGORIES[cat] || NOTIFY_CATEGORIES.general;
}

function renderNotifications(data) {
  var badge = document.getElementById("notification_count");
  var list  = document.getElementById("notification_list");
  if (!badge || !list) return;

  var items = data.notifications || [];
  badge.style.display = data.unread_count > 0 ? "inline-block" : "none";
  badge.innerText = data.unread_count > 99 ? "99+" : data.unread_count;

  if (items.length === 0) {
    list.innerHTML = '<li class="notification-empty">No new notifications.</li>';
    return;
  }

  var html = "";
  for (var i = 0; i < items.length; i++) {
    var item = items[i];
    var link = item.url ? item.url : "#";
    var meta = categoryMeta(item.category);
    var keyEsc = escapeAttr(item.key);

    html += '<li class="notification-item">';
    html += '<a href="' + escapeAttr(link) + '" onclick="markNotificationRead(\'' + keyEsc + '\');">';

    html += '<span class="notification-cat" '
         +  'style="display:inline-flex;align-items:center;gap:6px;'
         +  'background:' + meta.color + '20;'
         +  'color:'      + meta.color + ';'
         +  'border-radius:999px;padding:2px 8px;font-size:11px;'
         +  'font-weight:600;margin-bottom:4px;">'
         +  '<i class="fa ' + meta.icon + '"></i>'
         +  escapeHtml(meta.label)
         +  '</span>';

    html += '<div class="notification-title">'   + escapeHtml(item.title)   + '</div>';
    html += '<div class="notification-message">' + escapeHtml(item.message) + '</div>';
    html += '<small class="notification-time">'  + escapeHtml(formatNotificationTime(item.timestamp)) + "</small>";
    html += "</a></li>";
  }
  list.innerHTML = html;
}

function markNotificationRead(key) {
  var role = window.NOTIFY_ROLE || "staff";
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", "php/notifications.php?action=mark_read&role=" + encodeURIComponent(role) + "&key=" + encodeURIComponent(key), true);
  xhttp.send();
}

function fetchNotifications() {
  var role = window.NOTIFY_ROLE || "staff";
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function () {
    if (xhttp.readyState == 4 && xhttp.status == 200) {
      var payload;
      try {
        payload = JSON.parse(xhttp.responseText || '{"notifications":[],"unread_count":0}');
      } catch (e) {
        payload = { notifications: [], unread_count: 0 };
      }
      renderNotifications({
        notifications: payload.notifications || [],
        unread_count:  payload.unread_count  || 0
      });
    }
  };
  xhttp.open("GET", "php/notifications.php?role=" + encodeURIComponent(role), true);
  xhttp.send();
}

function initNotifications(role, user) {
  window.NOTIFY_ROLE = role;
  window.NOTIFY_USER = user;
  fetchNotifications();
  setInterval(fetchNotifications, 10000);
}
