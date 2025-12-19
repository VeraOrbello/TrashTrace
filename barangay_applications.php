<?php
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

if($_SESSION["user_type"] !== 'admin' && $_SESSION["user_type"] !== 'barangay_worker'){
    header("location: dashboard.php");
    exit;
}

$user_barangay = $_SESSION["barangay"] ?? '';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'workers';

// Get worker applications
$worker_applications = [];
if($current_tab == 'workers') {
    $sql = "SELECT wa.*, u.full_name, u.email, u.user_type 
            FROM worker_applications wa 
            JOIN users u ON wa.user_id = u.id 
            WHERE LOWER(TRIM(wa.barangay)) = LOWER(TRIM(:barangay)) 
            ORDER BY wa.submitted_at DESC";
    
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":barangay", $user_barangay, PDO::PARAM_STR);
        if($stmt->execute()){
            $worker_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
    }
}

// Get driver applications
$driver_applications = [];
if($current_tab == 'drivers') {
    $sql = "SELECT da.*, u.full_name, u.email, u.user_type, u.mobile_number
            FROM driver_applications da 
            JOIN users u ON da.user_id = u.id 
            WHERE u.barangay = :barangay AND da.status = 'pending'
            ORDER BY da.application_date DESC";
    
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":barangay", $user_barangay, PDO::PARAM_STR);
        if($stmt->execute()){
            $driver_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
    }
}

// Handle approval/rejection
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $application_id = $_POST['application_id'] ?? 0;
    $application_type = $_POST['application_type'] ?? ''; // 'worker' or 'driver'
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if($application_type == 'driver') {
        if($action == 'approve') {
            // Get driver application details
            $sql = "SELECT da.*, u.id as user_id 
                    FROM driver_applications da 
                    JOIN users u ON da.user_id = u.id 
                    WHERE da.id = ? AND u.barangay = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id, $user_barangay]);
            $driver_app = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($driver_app) {
                // Update user to driver
                $sql = "UPDATE users SET user_type = 'driver' WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$driver_app['user_id']]);
                
                // Create driver profile
                $sql = "INSERT INTO driver_profiles (driver_id, license_number, vehicle_type, vehicle_plate, status) 
                        VALUES (?, ?, ?, ?, 'active')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $driver_app['user_id'],
                    $driver_app['license_number'],
                    $driver_app['vehicle_type'],
                    $driver_app['vehicle_plate']
                ]);
                
                // Update application status
                $sql = "UPDATE driver_applications SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['id'], $application_id]);
                
                $success_message = "Driver application approved successfully!";
            }
        } else if($action == 'reject') {
            $sql = "UPDATE driver_applications SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['id'], $application_id]);
            
            $success_message = "Driver application rejected.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("location: barangay_applications.php?tab=" . $current_tab);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Barangay - TrashTrace</title>
    <link rel="stylesheet" href="css/barangay_applications.css">
    <link rel="stylesheet" href="css/barangay_dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="logo">TrashTrace</div>
                <nav>
                    <ul>
                        <li><a href="barangay_dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="barangay_schedule.php" class="nav-link">Schedule</a></li>
                        <li>
                            <a href="barangay_applications.php" class="nav-link active">Applications</a>
                        </li>
                        <li><a href="barangay_notifications.php" class="nav-link">Notifications</a></li>
                        <li><a href="barangay_reports.php" class="nav-link">Reports</a></li>
                        <li class="user-menu">
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"] ?? ''); ?></span>
                            <a href="logout.php" class="btn btn-outline">Logout</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="dashboard-main page-transition">
            <div class="container">
                <h1 class="welcome-title">Barangay Applications - <?php echo htmlspecialchars($user_barangay); ?></h1>
                
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <div class="application-tabs">
                    <a href="?tab=workers" class="tab-btn <?php echo $current_tab == 'workers' ? 'active' : ''; ?>">
                        <i class="fas fa-user-hard-hat"></i> Worker Applications
                    </a>
                    <a href="?tab=drivers" class="tab-btn <?php echo $current_tab == 'drivers' ? 'active' : ''; ?>">
                        <i class="fas fa-truck"></i> Driver Applications
                    </a>
                </div>
                
                <!-- Worker Applications Tab -->
                <?php if($current_tab == 'workers'): ?>
                <div class="applications-card dashboard-card">
                    <h2>Worker Applications</h2>
                    <div class="applications-actions">
                        <input id="searchInput" type="search" placeholder="Search by name, id number, email" />
                    </div>
                    <div class="table-wrapper">
                        <table id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>ID Number</th>
                                    <th>Contact</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($worker_applications)): ?>
                                    <?php foreach($worker_applications as $app): ?>
                                    <tr>
                                        <td class="app-name"><?php echo htmlspecialchars($app['full_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($app['id_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($app['contact_number'] ?? '') . '<br>' . htmlspecialchars($app['email'] ?? ''); ?></td>
                                        <td><?php echo isset($app['submitted_at']) ? date('M d, Y g:i A', strtotime($app['submitted_at'])) : ''; ?></td>
                                        <?php
                                            $display_status = $app['status'] ?? '';
                                            $user_type = $app['user_type'] ?? '';
                                            if(strtolower(trim($user_type)) === 'barangay_worker'){
                                                $display_status = 'accepted';
                                            } else {
                                                if(stripos($user_type, 'pending') !== false){
                                                    $display_status = 'pending';
                                                }
                                            }
                                        ?>
                                        <td class="status-cell"><span class="status-badge status-<?php echo htmlspecialchars($display_status); ?>"><?php echo ucfirst($display_status); ?></span></td>
                                        <td>
                                            <button class="btn btn-action view-app-btn" data-id="<?php echo (int)$app['id']; ?>" data-type="worker">View</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">No worker applications found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Driver Applications Tab -->
                <?php if($current_tab == 'drivers'): ?>
                <div class="applications-card dashboard-card">
                    <h2>Driver Applications</h2>
                    <div class="applications-actions">
                        <input id="searchDriverInput" type="search" placeholder="Search by name, license, vehicle" />
                    </div>
                    <div class="table-wrapper">
                        <table id="driverApplicationsTable">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>License Number</th>
                                    <th>Vehicle</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($driver_applications)): ?>
                                    <?php foreach($driver_applications as $app): ?>
                                    <tr>
                                        <td class="app-name"><?php echo htmlspecialchars($app['full_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($app['license_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($app['vehicle_type'] ?? '') . ' - ' . htmlspecialchars($app['vehicle_plate'] ?? ''); ?></td>
                                        <td><?php echo isset($app['application_date']) ? date('M d, Y g:i A', strtotime($app['application_date'])) : ''; ?></td>
                                        <td class="status-cell">
                                            <span class="status-badge status-<?php echo htmlspecialchars($app['status'] ?? 'pending'); ?>">
                                                <?php echo ucfirst($app['status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="application_type" value="driver">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-action btn-approve" onclick="return confirm('Approve this driver application?')">
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="application_type" value="driver">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-action btn-reject" onclick="return confirm('Reject this driver application?')">
                                                    Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">No pending driver applications found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal for viewing application details (for workers) -->
    <div id="applicationModal" class="modal" style="display:none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close">×</button>
            <h3 id="modalName">Application Details</h3>
            <div class="modal-body">
                <div class="modal-row"><strong>ID Number:</strong> <span id="modalIdNumber"></span></div>
                <div class="modal-row"><strong>Contact:</strong> <span id="modalContact"></span></div>
                <div class="modal-row"><strong>City / Barangay / Zone:</strong> <span id="modalLocation"></span></div>
                <div class="modal-row"><strong>Experience:</strong> <span id="modalExperience"></span></div>
                <div class="modal-row"><strong>Availability:</strong> <span id="modalAvailability"></span></div>
                <div class="modal-row"><strong>Vehicle Access:</strong> <span id="modalVehicle"></span></div>
                <div class="modal-row"><strong>Health Conditions:</strong><div id="modalHealth"></div></div>
                <div class="modal-row"><strong>Reason:</strong><div id="modalReason"></div></div>
                <div class="modal-row"><strong>Submitted:</strong> <span id="modalSubmitted"></span></div>
                <div class="modal-row"><strong>Document:</strong><div id="modalDoc"></div></div>
            </div>
            <div class="modal-actions">
                <button id="approveBtn" class="btn btn-action">Approve</button>
                <button id="rejectBtn" class="btn btn-action" style="background:#f8d7da;color:#842029;border-color:#f5c2c7">Reject</button>
            </div>
        </div>
    </div>

    <script src="js/barangay_applications.js"></script>
</body>
</html>