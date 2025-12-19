<?php
require_once "config.php";

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["user_type"] === 'driver'){
        header("location: driver_dashboard.php");
    } else {
        header("location: dashboard.php");
    }
    exit;
}

$full_name = $email = $mobile_number = $password = $confirm_password = "";
$license_number = $vehicle_type = $vehicle_plate = "";
$full_name_err = $email_err = $password_err = $confirm_password_err = $license_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validation and registration logic similar to register.php
    // but specifically for drivers
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Driver - TrashTrace</title>
    <link rel="stylesheet" href="css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Similar to register.php but focused on driver registration -->
</body>
</html>