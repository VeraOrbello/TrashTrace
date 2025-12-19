<?php
require_once "config.php";


if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'barangay_worker'){
    header('location: barangay_dashboard.php');
    exit;
}

$user_id = $_SESSION["id"] ?? 0;
$user_barangay = $_SESSION["barangay"] ?? '';
$full_name = $_SESSION["full_name"] ?? 'User';

$notifications = [];
$sql = "SELECT * FROM notifications WHERE user_id = :user_id OR (user_id IS NULL AND barangay = :barangay) ORDER BY created_at DESC LIMIT 20";

try {
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindParam(":barangay", $user_barangay, PDO::PARAM_STR);
        
        if($stmt->execute()){
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
    }
} catch(PDOException $e) {
    error_log("Database error in res_notif.php: " . $e->getMessage());
    $notifications = [];
}

$unread_count = 0;
foreach($notifications as $notification) {
    if(!$notification['is_read']) {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - TrashTrace</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/res_notif.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
    <div class="header-content">
        <div class="logo">TrashTrace</div>
        <nav>
            <ul>
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="res_schedule.php" class="nav-link">Schedule</a></li>
                <li><a href="res_notif.php" class="nav-link active">Notifications</a></li>
                <li><a href="res_profile.php" class="nav-link">Profile</a></li>
                <li class="user-menu">
                    <span>Welcome, <?php echo $_SESSION["full_name"]; ?></span>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </li>
            </ul>
        </nav>
    </div>
</header>
        <main class="notifications-main">
            <div class="container">
                <div class="notifications-header">
                    <h1>Notifications</h1>
                    <div class="notifications-actions">
                        <button id="mark-all-read" class="btn btn-secondary">Mark All as Read</button>
                        <button id="refresh-notifications" class="btn btn-primary">Refresh</button>
                    </div>
                </div>

                <div class="notifications-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($notifications); ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $unread_count; ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($notifications) - $unread_count; ?></div>
                        <div class="stat-label">Read</div>
                    </div>
                </div>

                <div class="notifications-list" id="notifications-list">
                    <?php if(empty($notifications)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🔔</div>
                            <h3>No notifications yet</h3>
                            <p>You'll see important updates here when they arrive.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                                <div class="notification-icon">
                                    <?php
                                    $icon = '📢';
                                    if(isset($notification['type'])) {
                                        switch($notification['type']) {
                                            case 'pickup_scheduled': $icon = '📅'; break;
                                            case 'pickup_completed': $icon = '✅'; break;
                                            case 'pickup_delayed': $icon = '⚠️'; break;
                                            case 'pickup_cancelled': $icon = '❌'; break;
                                            case 'emergency': $icon = '🚨'; break;
                                            default: $icon = '📢';
                                        }
                                    }
                                    echo $icon;
                                    ?>
                                </div>
                                <div class="notification-content">
                                    <h4><?php echo htmlspecialchars($notification['title'] ?? 'No Title'); ?></h4>
                                    <p><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                                    <span class="notification-time">
                                        <?php 
                                        if(isset($notification['created_at'])) {
                                            echo date('M j, Y g:i A', strtotime($notification['created_at']));
                                        } else {
                                            echo 'Date not available';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="notification-actions">
                                    <?php if(!$notification['is_read']): ?>
                                        <span class="unread-dot"></span>
                                    <?php endif; ?>
                                    <button class="mark-read-btn" title="Mark as read">✓</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="load-more-container">
                    <button id="load-more" class="btn btn-outline">Load More</button>
                </div>
            </div>
        </main>
    </div>

    <script>
        const userId = <?php echo json_encode($user_id); ?>;
        const userBarangay = <?php echo json_encode($user_barangay); ?>;
        const initialUnreadCount = <?php echo $unread_count; ?>;
        let currentPage = 1;
        
     
        
    </script>
    <script src="js/res_notif.js"></script>
</body>
</html>