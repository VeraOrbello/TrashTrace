<?php
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Only drivers can access this page
if($_SESSION["user_type"] !== 'driver'){
    header("location: dashboard.php");
    exit;
}

$driver_id = $_SESSION["id"];
$driver_name = $_SESSION["full_name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - TrashTrace</title>
    <link rel="stylesheet" href="css/driver_dashboard.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2ecc71;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
            align-items: center;
        }
        
        .nav-link {
            text-decoration: none;
            color: #333;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: #f0f7ff;
            color: #2ecc71;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: 20px;
        }
        
        .btn-outline {
            background: white;
            border: 2px solid #2ecc71;
            color: #2ecc71;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background: #2ecc71;
            color: white;
        }
        
        .dashboard-main {
            flex: 1;
            padding: 30px 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .welcome-title {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .card-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .card-content p {
            color: #666;
            line-height: 1.5;
        }
        
        .btn-primary {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            display: inline-block;
            width: fit-content;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background: #27ae60;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="logo">TrashTrace</div>
                <nav>
                    <ul>
                        <li><a href="driver_dashboard.php" class="nav-link active">Dashboard</a></li>
                        <li><a href="driver/assignments.php" class="nav-link">Assignments</a></li>
                        <li><a href="driver/routes.php" class="nav-link">Routes</a></li>
                        <li><a href="driver/collections.php" class="nav-link">Collections</a></li>
                        <li><a href="driver/earnings.php" class="nav-link">Earnings</a></li>
                        <li class="user-menu">
                            <span>Welcome, <?php echo htmlspecialchars($driver_name); ?></span>
                            <a href="logout.php" class="btn btn-outline">Logout</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="container">
                <h1 class="welcome-title">Driver Dashboard</h1>
                <p class="welcome-subtitle">Welcome back, <?php echo htmlspecialchars($driver_name); ?>!</p>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h2>Today's Assignments</h2>
                        <div class="card-content">
                            <p>Check your pickup assignments for today.</p>
                            <a href="driver/assignments.php" class="btn btn-primary">View Assignments</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h2>My Routes</h2>
                        <div class="card-content">
                            <p>View your assigned collection routes.</p>
                            <a href="driver/routes.php" class="btn btn-primary">View Routes</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h2>Collections</h2>
                        <div class="card-content">
                            <p>Log your daily collections.</p>
                            <a href="driver/collections.php" class="btn btn-primary">Log Collections</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <h2>Earnings</h2>
                        <div class="card-content">
                            <p>Track your earnings and payments.</p>
                            <a href="driver/earnings.php" class="btn btn-primary">View Earnings</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>