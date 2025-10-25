<?php
// doctor_dashboard.php
include 'database.php';

// Check if the user is logged in and is a doctor
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Get doctor information
$doctor_id = $_SESSION['doctor_id'];
$stmt = $conn->prepare("SELECT * FROM doctorreg WHERE Doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

// Get booking statistics for this doctor
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved_bookings,
    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings
    FROM booking WHERE Doctor_id = ?");
$stmt_stats->bind_param("i", $doctor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Get today's appointments
$today = date('Y-m-d');
$stmt_today = $conn->prepare("SELECT b.*, r.Name as PatientName, r.Email as PatientEmail, r.Phone_no as PatientPhone
                              FROM booking b 
                              LEFT JOIN register r ON b.Patient_id = r.Patient_id 
                              WHERE b.Doctor_id = ? AND b.Select_date = ? AND b.Status = 'Approved'
                              ORDER BY b.Booking_id ASC");
$stmt_today->bind_param("is", $doctor_id, $today);
$stmt_today->execute();
$result_today = $stmt_today->get_result();
$today_appointments = [];
while ($row = $result_today->fetch_assoc()) {
    $today_appointments[] = $row;
}
$stmt_today->close();

// Get recent appointments
$stmt_recent = $conn->prepare("SELECT b.*, r.Name as PatientName, r.Email as PatientEmail, r.Phone_no as PatientPhone
                               FROM booking b 
                               LEFT JOIN register r ON b.Patient_id = r.Patient_id 
                               WHERE b.Doctor_id = ? 
                               ORDER BY b.Select_date DESC, b.Booking_id DESC 
                               LIMIT 10");
$stmt_recent->bind_param("i", $doctor_id);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
$recent_appointments = [];
while ($row = $result_recent->fetch_assoc()) {
    $recent_appointments[] = $row;
}
$stmt_recent->close();

// Get patient count for this doctor
$stmt_patients = $conn->prepare("SELECT COUNT(DISTINCT Patient_id) as patient_count FROM booking WHERE Doctor_id = ?");
$stmt_patients->bind_param("i", $doctor_id);
$stmt_patients->execute();
$patient_count = $stmt_patients->get_result()->fetch_assoc()['patient_count'];
$stmt_patients->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Doctor Dashboard - MEDITRACK</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Green Theme Styling for Doctor Dashboard */
        :root {
            --primary-color: #28a745; /* Green */
            --secondary-color: #1e7e34; /* Dark Green */
            --accent-color: #20c997; /* Teal */
            --text-light: #FFFFFF;
            --text-dark: #212121;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-green: #d4edda;
            --border-green: #c3e6cb;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            display: flex;
            color: var(--text-dark);
        }

        .container {
            display: flex;
            width: 100%;
            margin: 0;
            padding: 0;
            max-width: none;
        }

        .sidebar {
            background-color: var(--secondary-color);
            width: 250px;
            height: 100vh;
            padding: 20px;
            color: var(--text-light);
            position: fixed;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .logo {
            border-bottom: 1px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .sidebar .logo h2 {
            font-size: 24px;
            text-align: center;
            color: var(--primary-color);
            font-weight: 700;
        }

        .nav {
            list-style-type: none;
            padding: 0;
        }

        .nav li {
            margin: 15px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav li a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 16px;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav li a:hover, .nav li a.active {
            background-color: var(--primary-color);
            color: var(--text-light);
            transform: translateX(5px);
        }

        .nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .header .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--secondary-color);
        }

        .header .user-profile {
            display: flex;
            align-items: center;
        }

        .header .user-profile span {
            margin-right: 15px;
            font-weight: 500;
        }

        .header .user-profile a {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            background-color: rgba(220, 53, 69, 0.1);
            transition: all 0.3s ease;
        }

        .header .user-profile a:hover {
            background-color: var(--danger-color);
            color: var(--text-light);
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .welcome-section h2 {
            margin-bottom: 10px;
        }

        .welcome-section p {
            margin-bottom: 0;
            opacity: 0.9;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0;
            color: var(--primary-color);
        }

        .stat-card p {
            margin: 5px 0 0 0;
            color: var(--text-dark);
            font-weight: 500;
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--secondary-color);
            color: var(--text-light);
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--text-light);
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: var(--text-light);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--secondary-color);
            color: var(--text-light);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:nth-child(even) {
            background-color: var(--light-green);
        }

        .table tr:hover {
            background-color: var(--border-green);
        }

        .badge {
            padding: 8px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 12px;
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: var(--text-dark);
        }

        .badge-approved {
            background-color: var(--success-color);
            color: white;
        }

        .badge-rejected {
            background-color: var(--danger-color);
            color: white;
        }

        .badge-completed {
            background-color: var(--info-color);
            color: white;
        }

        .appointment-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .appointment-item h6 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .appointment-item p {
            margin-bottom: 5px;
        }

        .appointment-item .time {
            font-weight: bold;
            color: var(--secondary-color);
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .no-appointments {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-appointments i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>MEDI TRACK</h2>
            </div>
            <ul class="nav">
                <li>
                    <a href="doctor_dashboard.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_bookings.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>My Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_patients.php">
                        <i class="fas fa-users"></i>
                        <span>My Patients</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_prescriptions.php">
                        <i class="fas fa-prescription"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_medical_records.php">
                        <i class="fas fa-file-medical"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_profile.php">
                        <i class="fas fa-user-md"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="logo">Doctor Dashboard</div>
                <div class="user-profile">
                    <span>Welcome, Dr. <?php echo htmlspecialchars($doctor['Name']); ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2><i class="fas fa-user-md"></i> Welcome, Dr. <?php echo htmlspecialchars($doctor['Name']); ?>!</h2>
                <p>Department: <?php echo htmlspecialchars($doctor['Department']); ?> | Email: <?php echo htmlspecialchars($doctor['Email']); ?></p>
                <p>Today is <?php echo date('l, F j, Y'); ?> - Ready to provide excellent care to your patients.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <p>Total Appointments</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <p>Pending Appointments</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $stats['approved_bookings']; ?></h3>
                    <p>Approved Appointments</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $patient_count; ?></h3>
                    <p>Total Patients</p>
                </div>
            </div>

            <div class="row">
                <!-- Today's Appointments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Today's Appointments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($today_appointments)): ?>
                                <div class="no-appointments">
                                    <i class="fas fa-calendar-times"></i>
                                    <h5>No Appointments Today</h5>
                                    <p>You have a free schedule today. Enjoy your day!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($today_appointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($appointment['PatientName'] ?? $appointment['Name']); ?></h6>
                                        <p><strong>Time:</strong> <span class="time">Morning Session</span></p>
                                        <p><strong>Age:</strong> <?php echo $appointment['Age']; ?> | <strong>Gender:</strong> <?php echo $appointment['Gender']; ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['PatientPhone'] ?? $appointment['Phone_no']); ?></p>
                                        <p><strong>Department:</strong> <?php echo htmlspecialchars($appointment['Select_department']); ?></p>
                                        <?php if (!empty($appointment['Additional_message'])): ?>
                                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['Additional_message']); ?></p>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="doctor_medical_records.php?patient_id=<?php echo $appointment['Patient_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-file-medical"></i> View Records
                                            </a>
                                            <a href="doctor_prescriptions.php?patient_id=<?php echo $appointment['Patient_id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-prescription"></i> Prescriptions
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Appointments</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_appointments)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent appointments</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach (array_slice($recent_appointments, 0, 5) as $appointment): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($appointment['PatientName'] ?? $appointment['Name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($appointment['PatientEmail'] ?? $appointment['Email']); ?></small>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($appointment['Select_date'])); ?></td>
                                                    <td>
                                                        <?php if ($appointment['Status'] == 'Pending'): ?>
                                                            <span class="badge badge-pending">Pending</span>
                                                        <?php elseif ($appointment['Status'] == 'Approved'): ?>
                                                            <span class="badge badge-approved">Approved</span>
                                                        <?php elseif ($appointment['Status'] == 'Rejected'): ?>
                                                            <span class="badge badge-rejected">Rejected</span>
                                                        <?php elseif ($appointment['Status'] == 'Completed'): ?>
                                                            <span class="badge badge-completed">Completed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="doctor_medical_records.php?patient_id=<?php echo $appointment['Patient_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="doctor_bookings.php" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-calendar-check me-2"></i> Manage Bookings
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="doctor_patients.php" class="btn btn-success w-100 mb-3">
                                        <i class="fas fa-users me-2"></i> View Patients
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="doctor_prescriptions.php" class="btn btn-info w-100 mb-3">
                                        <i class="fas fa-prescription me-2"></i> Prescriptions
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="doctor_profile.php" class="btn btn-warning w-100 mb-3">
                                        <i class="fas fa-user-md me-2"></i> My Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes

        // Add some interactive features
        $(document).ready(function() {
            // Add hover effects to appointment items
            $('.appointment-item').hover(
                function() {
                    $(this).css('transform', 'scale(1.02)');
                },
                function() {
                    $(this).css('transform', 'scale(1)');
                }
            );

            // Add click effects to stat cards
            $('.stat-card').click(function() {
                $(this).css('transform', 'scale(0.95)');
                setTimeout(() => {
                    $(this).css('transform', 'scale(1)');
                }, 150);
            });
        });
    </script>
</body>
</html>
