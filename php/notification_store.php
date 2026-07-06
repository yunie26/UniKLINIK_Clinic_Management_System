<?php

function clinic_ensure_notification_events_table($con) {
  if (!$con) {
    return;
  }
  static $done = false;
  if (!$done) {
    $sql = "CREATE TABLE IF NOT EXISTS notification_events (
      ID int(11) NOT NULL AUTO_INCREMENT,
      EVENT_KEY varchar(190) NOT NULL,
      ROLE_SCOPE varchar(20) NOT NULL DEFAULT 'both',
      CATEGORY varchar(40) NOT NULL DEFAULT 'general',
      TITLE varchar(150) NOT NULL,
      MESSAGE varchar(255) NOT NULL,
      URL varchar(255) DEFAULT NULL,
      EVENT_TS datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      LAST_SEEN_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      SEEN_COUNT int(11) NOT NULL DEFAULT 1,
      PRIMARY KEY (ID),
      UNIQUE KEY uq_event_role (EVENT_KEY, ROLE_SCOPE),
      KEY idx_event_ts (EVENT_TS),
      KEY idx_category (CATEGORY)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($con, $sql);
    $done = true;
  }
  clinic_ensure_notification_event_category_column($con);
}

function clinic_ensure_notification_event_category_column($con) {
  if (!$con) {
    return;
  }
  static $checked = false;
  if ($checked) {
    return;
  }
  $checked = true;
  $check = "SELECT COUNT(*) AS cnt
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'notification_events'
               AND COLUMN_NAME  = 'CATEGORY'";
  $r = mysqli_query($con, $check);
  if ($r && ($row = mysqli_fetch_assoc($r)) && intval($row['cnt']) === 0) {
    mysqli_query(
      $con,
      "ALTER TABLE notification_events
         ADD COLUMN CATEGORY varchar(40) NOT NULL DEFAULT 'general' AFTER ROLE_SCOPE,
         ADD KEY idx_category (CATEGORY)"
    );
  }
}

function clinic_ensure_notification_reads_table($con) {
  if (!$con) {
    return;
  }
  static $doneReads = false;
  if ($doneReads) {
    return;
  }
  $doneReads = true;
  $sql = "CREATE TABLE IF NOT EXISTS notification_reads (
    ID int(11) NOT NULL AUTO_INCREMENT,
    VIEWER_ROLE varchar(20) NOT NULL,
    VIEWER_ID varchar(100) NOT NULL,
    EVENT_KEY varchar(190) NOT NULL,
    READ_AT datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY uq_viewer_event (VIEWER_ROLE, VIEWER_ID, EVENT_KEY),
    KEY idx_viewer (VIEWER_ROLE, VIEWER_ID),
    KEY idx_event_key (EVENT_KEY)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
  mysqli_query($con, $sql);
}

function clinic_store_notification_event($con, $event_key, $role_scope, $title, $message, $url, $timestamp, $category = 'general') {
  if (!$con || !$event_key) {
    return;
  }
  clinic_ensure_notification_events_table($con);
  $event_key = mysqli_real_escape_string($con, $event_key);
  $role_scope = mysqli_real_escape_string($con, $role_scope ?: 'both');
  $category = mysqli_real_escape_string($con, $category ?: 'general');
  $title = mysqli_real_escape_string($con, $title ?: '');
  $message = mysqli_real_escape_string($con, $message ?: '');
  $url = mysqli_real_escape_string($con, $url ?: '');
  $ts = mysqli_real_escape_string($con, $timestamp ?: date('Y-m-d H:i:s'));
  // Do not update EVENT_TS on duplicate: the key is reused while the condition persists (e.g. low stock),
  // and refreshing would replace the real event time with "last API poll" time.
  $query = "INSERT INTO notification_events (EVENT_KEY, ROLE_SCOPE, CATEGORY, TITLE, MESSAGE, URL, EVENT_TS, LAST_SEEN_AT, SEEN_COUNT)
            VALUES ('$event_key', '$role_scope', '$category', '$title', '$message', '$url', '$ts', NOW(), 1)
            ON DUPLICATE KEY UPDATE
              CATEGORY = VALUES(CATEGORY),
              TITLE    = VALUES(TITLE),
              MESSAGE  = VALUES(MESSAGE),
              URL      = VALUES(URL),
              LAST_SEEN_AT = NOW(),
              SEEN_COUNT = SEEN_COUNT + 1";
  mysqli_query($con, $query);
}

function clinic_fetch_notification_history($con, $role_scope, $limit = 200) {
  clinic_ensure_notification_events_table($con);
  $role_scope = mysqli_real_escape_string($con, $role_scope ?: 'staff');
  $limit = intval($limit);
  if ($limit <= 0) {
    $limit = 200;
  }
  $query = "SELECT EVENT_KEY, ROLE_SCOPE, CATEGORY, TITLE, MESSAGE, URL, EVENT_TS, LAST_SEEN_AT, SEEN_COUNT
            FROM notification_events
            WHERE ROLE_SCOPE IN ('$role_scope', 'both')
            ORDER BY EVENT_TS DESC, ID DESC
            LIMIT $limit";
  $res = mysqli_query($con, $query);
  $rows = array();
  if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
      $rows[] = $r;
    }
  }
  return $rows;
}

function clinic_mark_notification_read($con, $viewer_role, $viewer_id, $event_key) {
  if (!$con || $viewer_role === '' || $viewer_id === '' || $event_key === '') {
    return;
  }
  clinic_ensure_notification_reads_table($con);
  $viewer_role = mysqli_real_escape_string($con, $viewer_role);
  $viewer_id = mysqli_real_escape_string($con, $viewer_id);
  $event_key = mysqli_real_escape_string($con, $event_key);
  $q = "INSERT INTO notification_reads (VIEWER_ROLE, VIEWER_ID, EVENT_KEY, READ_AT)
        VALUES ('$viewer_role', '$viewer_id', '$event_key', NOW())
        ON DUPLICATE KEY UPDATE READ_AT = NOW()";
  mysqli_query($con, $q);
}

function clinic_fetch_read_keys($con, $viewer_role, $viewer_id, $keys) {
  $out = array();
  if (!$con || empty($keys) || $viewer_role === '' || $viewer_id === '') {
    return $out;
  }
  clinic_ensure_notification_reads_table($con);
  $viewer_role = mysqli_real_escape_string($con, $viewer_role);
  $viewer_id = mysqli_real_escape_string($con, $viewer_id);
  $safeKeys = array();
  foreach ($keys as $k) {
    if ($k === '') continue;
    $safeKeys[] = "'" . mysqli_real_escape_string($con, $k) . "'";
  }
  if (empty($safeKeys)) {
    return $out;
  }
  $in = implode(",", $safeKeys);
  $q = "SELECT EVENT_KEY FROM notification_reads
        WHERE VIEWER_ROLE = '$viewer_role' AND VIEWER_ID = '$viewer_id' AND EVENT_KEY IN ($in)";
  $r = mysqli_query($con, $q);
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      $out[$row['EVENT_KEY']] = true;
    }
  }
  return $out;
}

?>
