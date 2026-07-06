<?php
require "db_connection.php";
require_once __DIR__ . '/staff_schema.php';

if ($con) {
    clinic_ensure_staff_profile_columns($con);

    $name = ucwords(trim(mysqli_real_escape_string($con, $_POST["name"] ?? "")));
    $contact_number = trim(mysqli_real_escape_string($con, $_POST["contact_number"] ?? ""));
    $email = trim(mysqli_real_escape_string($con, $_POST["email"] ?? ""));
    $password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");
    $gender = trim(mysqli_real_escape_string($con, $_POST["gender"] ?? ""));
    $dob = trim(mysqli_real_escape_string($con, $_POST["dob"] ?? ""));
    $address = trim(mysqli_real_escape_string($con, $_POST["address"] ?? ""));
    $role = trim(mysqli_real_escape_string($con, $_POST["role"] ?? "Clinic Assistant"));
    $status = trim(mysqli_real_escape_string($con, $_POST["status"] ?? "Active"));

    if ($name === "" || $contact_number === "" || $email === "" || $password === "" || $confirm_password === "") {
        echo "All required fields must be filled out!";
        exit;
    }

    $check = "SELECT ID FROM staff WHERE EMAIL='$email' OR CONTACT_NUMBER='$contact_number' LIMIT 1";
    $result = mysqli_query($con, $check);
    if ($result && mysqli_num_rows($result) > 0) {
        echo "Staff with this email or contact number already exists!";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    $insert = "INSERT INTO staff (NAME, EMAIL, CONTACT_NUMBER, PASSWORD, CONFIRM_PASSWORD, GENDER, DOB, ADDRESS, ROLE, STATUS)
               VALUES ('$name', '$email', '$contact_number', '$password', '$confirm_password', '$gender', " . ($dob === "" ? "NULL" : "'$dob'") . ", '$address', '$role', '$status')";

    if (mysqli_query($con, $insert)) {
        echo "Staff added successfully!";
    } else {
        echo "Failed to add staff! Error: " . mysqli_error($con);
    }
} else {
    echo "Database connection failed!";
}
?>