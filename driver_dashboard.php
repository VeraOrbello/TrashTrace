<?php
require_once 'php/middleware.php';
// Auto-protection will ensure only 'barangay_driver' can access this
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - TrashTrace</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/driver_dashboard.css"> <!-- You'll create this -->
</head>
<body>
<nav class="driver-nav">
    <a href="driver_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="driver/assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
    <a href="driver/collections.php"><i class="fas fa-trash-alt"></i> Collections</a>
    <a href="driver/profile.php"><i class="fas fa-user"></i> Profile</a>
    <a href="driver/history.php"><i class="fas fa-history"></i> History</a>
    <a href="driver/earnings.php"><i class="fas fa-money-bill-wave"></i> Earnings</a>
</nav>
    
    <div class="container">
        <h1>Driver Dashboard</h1>
        
        <div class="driver-stats">
            <div class="stat-card">
                <h3>Today's Pickups</h3>
                <p class="stat-number" id="today-count">0</p>
            </div>
            <div class="stat-card">
                <h3>Completed</h3>
                <p class="stat-number" id="completed-count">0</p>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <p class="stat-number" id="pending-count">0</p>
            </div>
        </div>
        
        <div class="todays-tasks">
            <h2>Today's Assignments</h2>
            <div id="assignments-list">
                <p>Loading assignments...</p>
            </div>
        </div>
        
        <div class="quick-actions">
            <button onclick="startShift()" class="btn-primary">Start Shift</button>
            <button onclick="viewSchedule()" class="btn-secondary">View Full Schedule</button>
            <button onclick="reportIssue()" class="btn-warning">Report Issue</button>
        </div>
    </div>
    
    <script src="js/driver_dashboard.js"></script>
</body>
</html>