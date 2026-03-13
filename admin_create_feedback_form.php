<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

$success_message = "";
$error_message = "";

// Create new feedback form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_form'])) {
    $form_title = mysqli_