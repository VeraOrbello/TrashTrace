<?php
require_once "../config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

if($_SESSION["user_type"] !== 'driver'){
    header("location: ../dashboard.php");
    exit;
}

$driver_name = $_SESSION["full_name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Assignments - TrashTrace</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2ecc71;
        }
        .nav {
            background: #2ecc71;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .nav a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            padding: 5px 10px;
        }
        .nav a:hover {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        .assignments-list {
            margin-top: 20px;
        }
        .assignment-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Driver Assignments</h1>
        <p>Welcome, <?php echo htmlspecialchars($driver_name); ?>!</p>
        
        <div class="nav">
            <a href="../driver_dashboard.php">Dashboard</a>
            <a href="assignments.php">Assignments</a>
            <a href="routes.php">Routes</a>
            <a href="collections.php">Collections</a>
            <a href="earnings.php">Earnings</a>
            <a href="../logout.php">Logout</a>
        </div>
        
        <div class="assignments-list">
            <h2>Today's Assignments</h2>
            
            <div class="assignment-item">
                <h3>Zone A Collection</h3>
                <p><strong>Time:</strong> 8:00 AM - 12:00 PM</p>
                <p><strong>Area:</strong> Barangay Lahug</p>
                <p><strong>Status:</strong> Pending</p>
                <button style="background: #2ecc71; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    Start Collection
                </button>
            </div>
            
            <div class="assignment-item">
                <h3>Zone B Collection</h3>
                <p><strong>Time:</strong> 1:00 PM - 5:00 PM</p>
                <p><strong>Area:</strong> Barangay Apas</p>
                <p><strong>Status:</strong> Scheduled</p>
                <button style="background: #f39c12; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    View Details
                </button>
            </div>
        </div>
    </div>
</body>
</html>