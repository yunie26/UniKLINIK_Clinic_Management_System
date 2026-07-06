<?php
require "db_connection.php";
require_once __DIR__ . '/staff_schema.php';

if ($con) {
  clinic_ensure_staff_profile_columns($con);
  if (isset($_GET["action"])) {
    $action = $_GET["action"];

    if ($action == "delete") {
      $id = intval($_GET["id"]);
      $query = "DELETE FROM staff WHERE ID = $id";
      $result = mysqli_query($con, $query);
      if ($result)
        showStaff(0);
    } elseif ($action == "edit") {
      $id = intval($_GET["id"]);
      showStaff($id);
    } elseif ($action == "update") {
      $id = intval($_GET["id"]);
      $name = ucwords(mysqli_real_escape_string($con, $_GET["name"]));
      $email = mysqli_real_escape_string($con, $_GET["email"]);
      $contact_number = mysqli_real_escape_string($con, $_GET["contact_number"]);
      $role = mysqli_real_escape_string($con, $_GET["role"] ?? "Clinic Assistant");
      $status = mysqli_real_escape_string($con, $_GET["status"] ?? "Active");
      $password = $_GET["password"];
      $confirm_password = $_GET["confirm_password"];

      if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        showStaff($id);
        exit;
      }

      if (!empty($password)) {
        // Save plain text password (not recommended for production!)
        $query = "UPDATE staff SET 
          NAME = '$name', 
          EMAIL = '$email', 
          CONTACT_NUMBER = '$contact_number', 
          ROLE = '$role',
          STATUS = '$status',
          PASSWORD = '$password', 
          CONFIRM_PASSWORD = '$password' 
          WHERE ID = $id";
      } else {
        // If no new password, don't update password fields
        $query = "UPDATE staff SET 
          NAME = '$name', 
          EMAIL = '$email', 
          CONTACT_NUMBER = '$contact_number',
          ROLE = '$role',
          STATUS = '$status'
          WHERE ID = $id";
      }

      $result = mysqli_query($con, $query);
      if ($result)
        showStaff(0);
      else
        echo "Failed to update staff. Error: " . mysqli_error($con);
    } elseif ($action == "cancel") {
      showStaff(0);
    } elseif ($action == "search") {
      $text = strtoupper(mysqli_real_escape_string($con, $_GET["text"]));
      searchStaff($text);
    }
  }
}

function showStaff($id)
{
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    $query = "SELECT * FROM staff ORDER BY ID DESC";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      if ($row['ID'] == $id)
        showEditStaffRow($seq_no, $row);
      else
        showStaffRow($seq_no, $row);
    }
  }
}

function showStaffRow($seq_no, $row)
{
  ?>
  <tr>
    <td><?= $seq_no; ?></td>
    <td><?= $row['ID']; ?></td>
    <td><?= htmlspecialchars($row['NAME']); ?></td>
    <td><?= htmlspecialchars($row['EMAIL']); ?></td>
    <td><?= htmlspecialchars($row['CONTACT_NUMBER']); ?></td>
    <td><?= htmlspecialchars($row['ROLE'] ?? 'Clinic Assistant'); ?></td>
    <td><?= htmlspecialchars($row['STATUS'] ?? 'Active'); ?></td>
    <td>
      <button class="btn btn-info btn-sm" onclick="editStaff(<?= $row['ID']; ?>);">
        <i class="fa fa-pencil"></i>
      </button>
      <button class="btn btn-danger btn-sm" onclick="deleteStaff(<?= $row['ID']; ?>);">
        <i class="fa fa-trash"></i>
      </button>
    </td>
  </tr>
  <?php
}

function showEditStaffRow($seq_no, $row)
{
  ?>
  <tr>
    <td><?= $seq_no; ?></td>
    <td><?= $row['ID']; ?></td>
    <td><input type="text" class="form-control" id="staff_name" value="<?= htmlspecialchars($row['NAME']); ?>"
        placeholder="Name"></td>
    <td><input type="email" class="form-control" id="staff_email" value="<?= htmlspecialchars($row['EMAIL']); ?>"
        placeholder="Email"></td>
    <td><input type="text" class="form-control" id="staff_contact_number"
        value="<?= htmlspecialchars($row['CONTACT_NUMBER']); ?>" placeholder="Contact Number"></td>
    <td>
      <select id="staff_role" class="form-control">
        <option value="Clinic Assistant" <?= (($row['ROLE'] ?? '') === 'Clinic Assistant') ? 'selected' : '' ?>>Clinic Assistant</option>
        <option value="Medical Assistant" <?= (($row['ROLE'] ?? '') === 'Medical Assistant') ? 'selected' : '' ?>>Medical Assistant</option>
        <option value="Doctor" <?= (($row['ROLE'] ?? '') === 'Doctor') ? 'selected' : '' ?>>Doctor</option>
      </select>
    </td>
    <td>
      <select id="staff_status" class="form-control">
        <option value="Active" <?= (($row['STATUS'] ?? '') === 'Active') ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= (($row['STATUS'] ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
      </select>
    </td>
    <td>
      <input type="password" class="form-control" id="staff_password" placeholder="New Password (optional)">
      <input type="password" class="form-control mt-1" id="staff_confirm_password" placeholder="Confirm Password">
      <button class="btn btn-success btn-sm mt-1" onclick="updateStaff(<?= $row['ID']; ?>);"><i
          class="fa fa-edit"></i></button>
      <button class="btn btn-danger btn-sm mt-1" onclick="cancel();"><i class="fa fa-close"></i></button>
    </td>
  </tr>
  <?php
}

function searchStaff($text)
{
  require "db_connection.php";
  if ($con) {
    $seq_no = 0;
    $query = "SELECT * FROM staff WHERE UPPER(NAME) LIKE '%$text%' OR UPPER(EMAIL) LIKE '%$text%'";
    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_array($result)) {
      $seq_no++;
      showStaffRow($seq_no, $row);
    }
  }
}
?>
