<?php
// doctor_bookings.php
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

// Handle booking status updates
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    $doctor_notes = $_POST['doctor_notes'] ?? '';
    
    $stmt = $conn->prepare("UPDATE booking SET Status = ? WHERE Booking_id = ? AND Doctor_id = ?");
    $stmt->bind_param("sii", $new_status, $booking_id, $doctor_id);
    
    if ($stmt->execute()) {
        $success_message = "Appointment status updated successfully!";
    } else {
        $error_message = "Error updating appointment status: " . $stmt->error;
    }
    $stmt->close();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build the query with filters
$query = "SELECT b.*, r.Name as PatientName, r.Email as PatientEmail, r.Phone_no as PatientPhone, r.Blood_group
          FROM booking b 
          LEFT JOIN register r ON b.Patient_id = r.Patient_id 
          WHERE b.Doctor_id = ?";

$params = [$doctor_id];
$types = "i";

if (!empty($status_filter)) {
    $query .= " AND b.Status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    $query .= " AND b.Select_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY b.Select_date DESC, b.Booking_id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Bookings - MEDITRACK</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Green Theme Styling for Doctor Bookings */
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

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
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

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background-color: var(--secondary-color);
            color: var(--text-light);
            border-radius: 15px 15px 0 0;
        }

        .modal-footer {
            border-top: none;
        }

        .booking-details {
            margin-bottom: 20px;
        }

        .booking-details p {
            margin-bottom: 5px;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .patient-info {
            background: var(--light-green);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .patient-info h6 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .no-bookings {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-bookings i {
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
                    <a href="doctor_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_bookings.php" class="active">
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
                <div class="logo">My Appointments</div>
                <div class="user-profile">
                    <span>Dr. <?php echo htmlspecialchars($doctor['Name']); ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

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
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $stats['approved_bookings']; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-double"></i>
                    <h3><?php echo $stats['completed_bookings']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Appointments</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" id="date" value="<?php echo $date_filter; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                <?php if (!empty($status_filter) || !empty($date_filter)): ?>
                    <div class="mt-3">
                        <a href="doctor_bookings.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bookings Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> My Appointments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="no-bookings">
                            <i class="fas fa-calendar-times"></i>
                            <h5>No Appointments Found</h5>
                            <p>You don't have any appointments matching your current filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Date & Time</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <div class="patient-info">
                                                    <h6><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['PatientName'] ?? $booking['Name']); ?></h6>
                                                    <p><strong>Age:</strong> <?php echo $booking['Age']; ?> | <strong>Gender:</strong> <?php echo $booking['Gender']; ?></p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['PatientPhone'] ?? $booking['Phone_no']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['PatientEmail'] ?? $booking['Email']); ?></p>
                                                    <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($booking['Blood_group']); ?></p>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo date('d M Y', strtotime($booking['Select_date'])); ?></strong><br>
                                                <small class="text-muted">Morning Session</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['Select_department']); ?></td>
                                            <td>
                                                <?php if ($booking['Status'] == 'Pending'): ?>
                                                    <span class="badge badge-pending">Pending</span>
                                                <?php elseif ($booking['Status'] == 'Approved'): ?>
                                                    <span class="badge badge-approved">Approved</span>
                                                <?php elseif ($booking['Status'] == 'Rejected'): ?>
                                                    <span class="badge badge-rejected">Rejected</span>
                                                <?php elseif ($booking['Status'] == 'Completed'): ?>
                                                    <span class="badge badge-completed">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#bookingModal"
                                                            onclick="viewBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($booking['Status'] == 'Pending'): ?>
                                                        <button class="btn btn-sm btn-success" 
                                                                onclick="updateStatus(<?php echo $booking['Booking_id']; ?>, 'Approved')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="updateStatus(<?php echo $booking['Booking_id']; ?>, 'Rejected')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($booking['Status'] == 'Approved'): ?>
                                                        <button class="btn btn-sm btn-warning" 
                                                                onclick="updateStatus(<?php echo $booking['Booking_id']; ?>, 'Completed')">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="doctor_medical_records.php?patient_id=<?php echo $booking['Patient_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-file-medical"></i>
                                                    </a>
                                                    <a href="doctor_prescriptions.php?patient_id=<?php echo $booking['Patient_id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-prescription"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingModalBody">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="statusBookingId">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status</label>
                            <select class="form-select" name="status" id="statusSelect" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="doctor_notes" class="form-label">Doctor Notes (Optional)</label>
                            <textarea class="form-control" name="doctor_notes" id="doctorNotes" rows="3" placeholder="Add any notes about this appointment..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBooking(booking) {
            const modalBody = document.getElementById('bookingModalBody');
            modalBody.innerHTML = `
                <div class="booking-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Patient Information</h6>
                            <p><strong>Name:</strong> ${booking.PatientName || booking.Name}</p>
                            <p><strong>Email:</strong> ${booking.PatientEmail || booking.Email}</p>
                            <p><strong>Phone:</strong> ${booking.PatientPhone || booking.Phone_no}</p>
                            <p><strong>Age:</strong> ${booking.Age}</p>
                            <p><strong>Gender:</strong> ${booking.Gender}</p>
                            <p><strong>Blood Group:</strong> ${booking.Blood_group}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Appointment Details</h6>
                            <p><strong>Date:</strong> ${new Date(booking.Select_date).toLocaleDateString()}</p>
                            <p><strong>Department:</strong> ${booking.Select_department}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge ${getStatusBadgeClass(booking.Status)}">${booking.Status}</span>
                            </p>
                            <p><strong>Booking ID:</strong> #${booking.Booking_id}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-comment"></i> Additional Message</h6>
                            <p>${booking.Additional_message || 'No additional message provided.'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fas fa-tools"></i> Quick Actions</h6>
                            <a href="doctor_medical_records.php?patient_id=${booking.Patient_id}" class="btn btn-sm btn-primary me-2">
                                <i class="fas fa-file-medical"></i> View Medical Records
                            </a>
                            <a href="doctor_prescriptions.php?patient_id=${booking.Patient_id}" class="btn btn-sm btn-success me-2">
                                <i class="fas fa-prescription"></i> Manage Prescriptions
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'Pending': return 'badge-pending';
                case 'Approved': return 'badge-approved';
                case 'Rejected': return 'badge-rejected';
                case 'Completed': return 'badge-completed';
                default: return 'badge-pending';
            }
        }

        function updateStatus(bookingId, status) {
            document.getElementById('statusBookingId').value = bookingId;
            document.getElementById('statusSelect').value = status;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Auto-refresh page after status update
        <?php if (isset($success_message)): ?>
            setTimeout(function() {
                location.reload();
            }, 2000);
        <?php endif; ?>

        // Add interactive features
        $(document).ready(function() {
            // Add hover effects to patient info cards
            $('.patient-info').hover(
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
