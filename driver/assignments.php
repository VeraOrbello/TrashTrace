<?php
require_once "config.php";
require_once "middleware.php";

// Redirect if not a driver
if ($_SESSION['user_type'] !== 'driver') {
    header("location: dashboard.php");
    exit;
}

$driver_id = $_SESSION['id'];
$current_date = date('Y-m-d');

// Get driver's assignments
$assignments = [];
$stmt = $pdo->prepare("
    SELECT da.*, 
           ps.schedule_date,
           ps.barangay as pickup_barangay,
           ps.zone as pickup_zone,
           COUNT(pa.id) as total_pickups,
           SUM(CASE WHEN pa.status = 'completed' THEN 1 ELSE 0 END) as completed_pickups
    FROM driver_daily_assignments da
    LEFT JOIN pickup_assignments pa ON da.id = pa.driver_daily_assignment_id
    LEFT JOIN pickup_schedules ps ON pa.pickup_id = ps.id
    WHERE da.driver_id = ? AND da.assignment_date >= ?
    GROUP BY da.id
    ORDER BY da.assignment_date DESC, da.start_time
");
$stmt->execute([$driver_id, $current_date]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's assignment
$today_assignment = null;
$stmt = $pdo->prepare("
    SELECT da.*, 
           GROUP_CONCAT(DISTINCT ps.barangay) as barangays,
           GROUP_CONCAT(DISTINCT ps.zone) as zones
    FROM driver_daily_assignments da
    LEFT JOIN pickup_assignments pa ON da.id = pa.driver_daily_assignment_id
    LEFT JOIN pickup_schedules ps ON pa.pickup_id = ps.id
    WHERE da.driver_id = ? AND da.assignment_date = ?
    GROUP BY da.id
    LIMIT 1
");
$stmt->execute([$driver_id, $current_date]);
$today_assignment = $stmt->fetch(PDO::FETCH_ASSOC);

// Start/stop assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'start_assignment' && isset($_POST['assignment_id'])) {
        $stmt = $pdo->prepare("
            UPDATE driver_daily_assignments 
            SET status = 'in_progress', started_at = NOW() 
            WHERE id = ? AND driver_id = ? AND status = 'pending'
        ");
        $stmt->execute([$_POST['assignment_id'], $driver_id]);
        
        // Update pickup assignments status
        $stmt = $pdo->prepare("
            UPDATE pickup_assignments 
            SET status = 'in_progress', started_at = NOW() 
            WHERE driver_daily_assignment_id = ?
        ");
        $stmt->execute([$_POST['assignment_id']]);
        
        header("location: ./assignments.php");
        exit;
    }
    
    if ($_POST['action'] == 'complete_assignment' && isset($_POST['assignment_id'])) {
        $stmt = $pdo->prepare("
            UPDATE driver_daily_assignments 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = ? AND driver_id = ? AND status = 'in_progress'
        ");
        $stmt->execute([$_POST['assignment_id'], $driver_id]);
        
        // Update pickup assignments status
        $stmt = $pdo->prepare("
            UPDATE pickup_assignments 
            SET status = 'completed', completed_at = NOW() 
            WHERE driver_daily_assignment_id = ?
        ");
        $stmt->execute([$_POST['assignment_id']]);
        
        header("location: ./assignments.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - TrashTrace</title>
    <link rel="stylesheet" href="css/driver/assignments.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="driver-container">
        <!-- Header -->
        <header class="driver-header">
            <div class="header-left">
                <h1><i class="fas fa-tasks"></i> My Assignments</h1>
                <p class="current-date"><?php echo date('F j, Y'); ?></p>
            </div>
            <div class="header-right">
                <a href="driver_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Today's Assignment -->
        <?php if ($today_assignment): ?>
        <div class="today-assignment card">
            <div class="card-header">
                <h2><i class="fas fa-calendar-day"></i> Today's Assignment</h2>
                <span class="status-badge status-<?php echo $today_assignment['status']; ?>">
                    <?php echo ucfirst($today_assignment['status']); ?>
                </span>
            </div>
            
            <div class="assignment-details">
                <div class="detail-row">
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Location</h4>
                            <p><?php echo $today_assignment['barangays'] ?? 'Not specified'; ?></p>
                            <?php if ($today_assignment['zones']): ?>
                            <small>Zones: <?php echo $today_assignment['zones']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Schedule</h4>
                            <p><?php echo date('h:i A', strtotime($today_assignment['start_time'])) . ' - ' . 
                                       date('h:i A', strtotime($today_assignment['end_time'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-stopwatch"></i>
                        <div>
                            <h4>Progress</h4>
                            <p><?php echo $today_assignment['completed_stops']; ?> / <?php echo $today_assignment['total_stops']; ?> stops</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php 
                                    echo $today_assignment['total_stops'] > 0 ? 
                                    ($today_assignment['completed_stops'] / $today_assignment['total_stops']) * 100 : 0; ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($today_assignment['status'] == 'pending'): ?>
                <form method="POST" class="action-form">
                    <input type="hidden" name="action" value="start_assignment">
                    <input type="hidden" name="assignment_id" value="<?php echo $today_assignment['id']; ?>">
                    <button type="submit" class="btn btn-start">
                        <i class="fas fa-play"></i> Start Assignment
                    </button>
                </form>
                <?php elseif ($today_assignment['status'] == 'in_progress'): ?>
                <div class="action-buttons">
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="action" value="complete_assignment">
                        <input type="hidden" name="assignment_id" value="<?php echo $today_assignment['id']; ?>">
                        <button type="submit" class="btn btn-complete">
                            <i class="fas fa-check-circle"></i> Complete Assignment
                        </button>
                    </form>
                    <a href="driver_collections.php?assignment=<?php echo $today_assignment['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-trash-alt"></i> Record Collection
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-assignment card">
            <i class="fas fa-calendar-times"></i>
            <h3>No Assignment Today</h3>
            <p>You don't have any assignments scheduled for today.</p>
        </div>
        <?php endif; ?>

        <!-- Upcoming Assignments -->
        <div class="upcoming-assignments card">
            <h2><i class="fas fa-calendar-alt"></i> Upcoming Assignments</h2>
            
            <?php if (count($assignments) > 0): ?>
            <div class="assignments-list">
                <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-item">
                    <div class="assignment-date">
                        <span class="day"><?php echo date('d', strtotime($assignment['assignment_date'])); ?></span>
                        <span class="month"><?php echo date('M', strtotime($assignment['assignment_date'])); ?></span>
                    </div>
                    
                    <div class="assignment-info">
                        <h4><?php echo $assignment['pickup_barangay'] ?? 'Multiple Locations'; ?></h4>
                        <p class="time">
                            <i class="far fa-clock"></i>
                            <?php echo $assignment['start_time'] ? date('h:i A', strtotime($assignment['start_time'])) . ' - ' . 
                                   date('h:i A', strtotime($assignment['end_time'])) : 'Time not set'; ?>
                        </p>
                        <p class="progress">
                            <?php echo $assignment['completed_pickups']; ?> / <?php echo $assignment['total_pickups']; ?> pickups
                        </p>
                    </div>
                    
                    <div class="assignment-status">
                        <span class="status-badge status-<?php echo $assignment['status']; ?>">
                            <?php echo ucfirst($assignment['status']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>No upcoming assignments</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total-assignments">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($assignments); ?></h3>
                    <p>Total Assignments</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php 
                        $pending = array_filter($assignments, fn($a) => $a['status'] == 'pending');
                        echo count($pending);
                    ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon in-progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <h3><?php 
                        $in_progress = array_filter($assignments, fn($a) => $a['status'] == 'in_progress');
                        echo count($in_progress);
                    ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php 
                        $completed = array_filter($assignments, fn($a) => $a['status'] == 'completed');
                        echo count($completed);
                    ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/driver/assignments.js"></script>
</body>
</html>
