
<?php
// admin_bookings.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Handle booking status updates
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    $stmt = $conn->prepare("UPDATE booking SET Status = ? WHERE Booking_id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
    } else {
        $error_message = "Error updating booking status: " . $stmt->error;
    }
    $stmt->close();
}

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
    $booking_id = $_POST['booking_id'];
    
    $stmt = $conn->prepare("DELETE FROM booking WHERE Booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking deleted successfully!";
    } else {
        $error_message = "Error deleting booking: " . $stmt->error;
    }
    $stmt->close();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$doctor_filter = $_GET['doctor'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build the query with filters
$query = "SELECT b.*, r.Name as PatientName, r.Email as PatientEmail, r.Phone_no as PatientPhone,
          d.Name as DoctorName, d.Department as DoctorDepartment, d.Email as DoctorEmail
          FROM booking b 
          LEFT JOIN register r ON b.Patient_id = r.Patient_id 
          LEFT JOIN doctorreg d ON b.Doctor_id = d.Doctor_id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND b.Status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($doctor_filter)) {
    $query .= " AND d.Doctor_id = ?";
    $params[] = $doctor_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $query .= " AND b.Select_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY b.Select_date DESC, b.Booking_id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all doctors for filter dropdown
$stmt_doctors = $conn->prepare("SELECT Doctor_id, Name, Department FROM doctorreg ORDER BY Name");
$stmt_doctors->execute();
$result_doctors = $stmt_doctors->get_result();
$doctors = $result_doctors->fetch_all(MYSQLI_ASSOC);
$stmt_doctors->close();

// Get booking statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as approved_bookings,
    SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) as rejected_bookings,
    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings
    FROM booking");
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin - Manage Bookings</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Yellow-Black Theme Styling */
        :root {
            --primary-color: #FFD700; /* Gold/Yellow */
            --secondary-color: #212121; /* Dark Gray/Black */
            --accent-color: #FFC107; /* Amber */
            --text-light: #FFFFFF;
            --text-dark: #212121;
            --danger-color: #FF3D00;
            --success-color: #00C853;
            --warning-color: #FF9800;
            --info-color: #2196F3;
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
            color: var(--secondary-color);
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
            background-color: rgba(255, 61, 0, 0.1);
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
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
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

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .card {
            border: none;
            border-radius: 10px;
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
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--secondary-color);
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
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
            background-color: #f9f9f9;
        }

        .table tr:hover {
            background-color: #f1f1f1;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 12px;
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: white;
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
            border-radius: 10px;
            border: none;
        }

        .modal-header {
            background-color: var(--secondary-color);
            color: var(--text-light);
            border-radius: 10px 10px 0 0;
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
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patient.php">
                        <i class="fas fa-users"></i>
                        <span>Patients</span>
                    </a>
                </li>
                <li>
                    <a href="admin_doctors.php">
                        <i class="fas fa-user-md"></i>
                        <span>Doctors</span>
                    </a>
                </li>
                <li>
                    <a href="admin_bookings.php" class="active">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patientdocuments.php">
                        <i class="fas fa-file-alt"></i>
                        <span>Documents</span>
                    </a>
                </li>
                <li>
                    <a href="admin_prescriptions.php">
                        <i class="fas fa-prescription"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
                <li>
                    <a href="admin_billing.php">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Billing</span>
                    </a>
                </li>
                <li>
                    <a href="admin_logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="logo">Booking Management</div>
                <div class="user-profile">
                    <span>Welcome, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                    <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    <h3><?php echo $stats['total_bookings']; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['approved_bookings']; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['completed_bookings']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Bookings</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="doctor" class="form-label">Doctor</label>
                        <select class="form-select" name="doctor" id="doctor">
                            <option value="">All Doctors</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['Doctor_id']; ?>" <?php echo $doctor_filter == $doctor['Doctor_id'] ? 'selected' : ''; ?>>
                                    <?php echo $doctor['Name'] . ' (' . $doctor['Department'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" id="date" value="<?php echo $date_filter; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                <?php if (!empty($status_filter) || !empty($doctor_filter) || !empty($date_filter)): ?>
                    <div class="mt-3">
                        <a href="admin_bookings.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bookings Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> All Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No bookings found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['Booking_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['PatientName'] ?? $booking['Name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['PatientEmail'] ?? $booking['Email']); ?><br>
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['PatientPhone'] ?? $booking['Phone_no']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['DoctorName']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['DoctorDepartment']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo date('d M Y', strtotime($booking['Select_date'])); ?></strong><br>
                                                <small class="text-muted">Age: <?php echo $booking['Age']; ?> | <?php echo $booking['Gender']; ?></small>
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
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteBooking(<?php echo $booking['Booking_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
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

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
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
                    <h5 class="modal-title">Update Booking Status</h5>
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
                                <option value="Rejected">Rejected</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" name="admin_notes" id="adminNotes" rows="3" placeholder="Add any notes about this booking..."></textarea>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this booking? This action cannot be undone.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="booking_id" id="deleteBookingId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_booking" class="btn btn-danger">Delete Booking</button>
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
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-user-md"></i> Doctor Information</h6>
                            <p><strong>Name:</strong> ${booking.DoctorName}</p>
                            <p><strong>Department:</strong> ${booking.DoctorDepartment}</p>
                            <p><strong>Email:</strong> ${booking.DoctorEmail}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Appointment Details</h6>
                            <p><strong>Date:</strong> ${new Date(booking.Select_date).toLocaleDateString()}</p>
                            <p><strong>Department:</strong> ${booking.Select_department}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge ${getStatusBadgeClass(booking.Status)}">${booking.Status}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-comment"></i> Additional Message</h6>
                            <p>${booking.Additional_message || 'No additional message provided.'}</p>
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

        function deleteBooking(bookingId) {
            document.getElementById('deleteBookingId').value = bookingId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Auto-refresh page after status update
        <?php if (isset($success_message)): ?>
            setTimeout(function() {
                location.reload();
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>