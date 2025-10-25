
<?php
// admin_dashboard.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Get counts for dashboard statistics
$stmt_doctors = $conn->prepare("SELECT COUNT(*) as doctor_count FROM doctorreg");
$stmt_doctors->execute();
$result_doctors = $stmt_doctors->get_result();
$doctor_count = $result_doctors->fetch_assoc()['doctor_count'];

$stmt_patients = $conn->prepare("SELECT COUNT(*) as patient_count FROM register");
$stmt_patients->execute();
$result_patients = $stmt_patients->get_result();
$patient_count = $result_patients->fetch_assoc()['patient_count'];

$stmt_bookings = $conn->prepare("SELECT COUNT(*) as booking_count FROM booking");
$stmt_bookings->execute();
$result_bookings = $stmt_bookings->get_result();
$booking_count = $result_bookings->fetch_assoc()['booking_count'];



// Get recent bookings
$stmt_recent = $conn->prepare("SELECT b.*, r.Name as PatientName, d.Name as DoctorName 
                              FROM booking b 
                              LEFT JOIN register r ON b.Patient_id = r.Patient_id 
                              LEFT JOIN doctorreg d ON b.Doctor_id = d.Doctor_id 
                              ORDER BY b.Booking_id DESC LIMIT 5");
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
$recent_bookings = [];
while ($row = $result_recent->fetch_assoc()) {
    $recent_bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - MEDITRACK</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Yellow-Black Theme Styling for Admin Dashboard */
        :root {
            --primary-color: #FFD700; /* Gold/Yellow */
            --secondary-color: #212121; /* Dark Gray/Black */
            --accent-color: #FFC107; /* Amber */
            --text-light: #FFFFFF;
            --text-dark: #212121;
            --danger-color: #FF3D00;
            --success-color: #00C853;
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
            background-color: #FFC107;
            color: #212121;
        }

        .badge-approved {
            background-color: #00C853;
            color: white;
        }

        .badge-rejected {
            background-color: #FF3D00;
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

        .document-preview {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .document-info {
            margin-bottom: 20px;
        }

        .document-info p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>MEDI TRACK </h2>
            </div>
            <ul class="nav">
                <li>
                    <a href="admin_dashboard.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patient.php">
                        <i class="fas fa-users"></i>
                        <span>Patient</span>
                    </a>
                </li>
                
                <li>
                    <a href="admin_doctors.php">
                        <i class="fas fa-book"></i>
                        <span>Doctors</span>
                    </a>
                </li>
                <li>
                    <a href="admin_bookings.php" >
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patientdocuments.php" >
                        <i class="fas fa-file-alt"></i>
                        <span>Patient Documents</span>
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
                <div class="logo">MEDITRACK Hospital Management</div>
                <div class="user-profile">
                    <span>Admin</span>
                    <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-user-md fa-3x mb-3" style="color: var(--primary-color);"></i>
                            <h2><?php echo $doctor_count; ?></h2>
                            <h5>Total Doctors</h5>
                            <a href="admin_doctors.php" class="btn btn-sm btn-primary mt-2">View Doctors</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3" style="color: var(--primary-color);"></i>
                            <h2><?php echo $patient_count; ?></h2>
                            <h5>Total Patients</h5>
                            <a href="admin_patient.php" class="btn btn-sm btn-primary mt-2">View Patients</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-3x mb-3" style="color: var(--primary-color);"></i>
                            <h2><?php echo $booking_count; ?></h2>
                            <h5>Total Bookings</h5>
                            <a href="admin_bookings.php" class="btn btn-sm btn-primary mt-2">View Bookings</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Section -->
            <div class="card">
                <div class="card-header">
                    <h5>Recent Appointments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($recent_bookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No bookings found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($recent_bookings as $booking): ?>
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
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['Select_department']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo date('d M Y', strtotime($booking['Select_date'])); ?></strong><br>
                                                <small class="text-muted">Age: <?php echo $booking['Age']; ?> | <?php echo $booking['Gender']; ?></small>
                                            </td>
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
                                        <a href="admin_bookings.php?id=<?php echo $booking['Booking_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Overview Chart -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>System Statistics</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statsChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <a href="admin_doctors.php" class="btn btn-primary w-100">
                                        <i class="fas fa-user-md me-2"></i> Manage Doctors
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="admin_patient.php" class="btn btn-primary w-100">
                                        <i class="fas fa-users me-2"></i> Manage Patients
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="admin_bookings.php" class="btn btn-primary w-100">
                                        <i class="fas fa-calendar-check me-2"></i> Manage Bookings
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="admin_prescriptions.php" class="btn btn-primary w-100">
                                        <i class="fas fa-prescription me-2"></i> Prescriptions
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="admin_medical_records.php" class="btn btn-primary w-100">
                                        <i class="fas fa-notes-medical me-2"></i> Medical Records
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="admin_billing.php" class="btn btn-primary w-100">
                                        <i class="fas fa-file-invoice-dollar me-2"></i> Billing
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentModalLabel">Document Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="document-info">
                        <p><strong>Student:</strong> <span id="modal-student"></span></p>
                        <p><strong>Document Type:</strong> <span id="modal-type"></span></p>
                        <p><strong>Current Status:</strong> <span id="modal-status"></span></p>
                    </div>
                    
                    <div class="document-preview-container">
                        <h6>Document Preview:</h6>
                        <div class="text-center">
                            <a href="#" id="document-link" target="_blank" class="btn btn-sm btn-primary mb-3">
                                <i class="fas fa-external-link-alt"></i> Open Document in New Tab
                            </a>
                        </div>
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe id="document-preview" class="embed-responsive-item" style="width: 100%; height: 400px;" src="" allowfullscreen></iframe>
                        </div>
                    </div>
                    
                    <form id="document-form" method="POST" action="">
                        <input type="hidden" name="document_id" id="document-id">
                        <div class="mb-3 mt-4">
                            <label for="status" class="form-label">Update Status:</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments:</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="update_document" class="btn btn-primary">Update Document Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle document view button click
            $('.view-document').click(function() {
                var id = $(this).data('id');
                var student = $(this).data('student');
                var type = $(this).data('type');
                var path = $(this).data('path');
                var status = $(this).data('status');
                var comments = $(this).data('comments');
                
                // Format the document type for display
                var formattedType = type.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                
                // Set modal values
                $('#document-id').val(id);
                $('#modal-student').text(student);
                $('#modal-type').text(formattedType);
                
                // Set status badge
                var statusHtml = '';
                if (status === 'pending') {
                    statusHtml = '<span class="badge badge-pending">Pending</span>';
                } else if (status === 'approved') {
                    statusHtml = '<span class="badge badge-approved">Approved</span>';
                } else if (status === 'rejected') {
                    statusHtml = '<span class="badge badge-rejected">Rejected</span>';
                }
                $('#modal-status').html(statusHtml);
                
                // Set document preview
                $('#document-preview').attr('src', path);
                $('#document-link').attr('href', path);
                
                // Set form values
                $('#status').val(status);
                $('#comments').val(comments || '');
            });
        });
        
        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('statsChart').getContext('2d');
            var statsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Doctors', 'Patients', 'Bookings'],
                    datasets: [{
                        label: 'System Statistics',
                        data: [<?php echo $doctor_count; ?>, <?php echo $patient_count; ?>, <?php echo $booking_count; ?>],
                        backgroundColor: [
                            '#FFD700',
                            '#FFC107',
                            '#FF9800'
                        ],
                        borderColor: [
                            '#212121',
                            '#212121',
                            '#212121'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>