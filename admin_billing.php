<?php
// admin_billing.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Handle billing addition
if (isset($_POST['add_billing'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $service_type = $_POST['service_type'];
    $service_description = $_POST['service_description'];
    $amount = $_POST['amount'];
    $payment_status = $_POST['payment_status'];
    $billing_date = date('Y-m-d');
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO billing (Patient_id, Doctor_id, Service_type, Service_description, Amount, Payment_status, Bill_date, Due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssss", $patient_id, $doctor_id, $service_type, $service_description, $amount, $payment_status, $billing_date, $due_date);
    
    if ($stmt->execute()) {
        $success_message = "Billing record added successfully!";
    } else {
        $error_message = "Error adding billing record: " . $stmt->error;
    }
    $stmt->close();
}

// Handle billing status update
if (isset($_POST['update_payment_status'])) {
    $billing_id = $_POST['billing_id'];
    $new_status = $_POST['payment_status'];
    
    $stmt = $conn->prepare("UPDATE billing SET Payment_status = ? WHERE Bill_id = ?");
    $stmt->bind_param("si", $new_status, $billing_id);
    
    if ($stmt->execute()) {
        $success_message = "Payment status updated successfully!";
    } else {
        $error_message = "Error updating payment status: " . $stmt->error;
    }
    $stmt->close();
}

// Handle billing deletion
if (isset($_POST['delete_billing'])) {
    $billing_id = $_POST['billing_id'];
    
    $stmt = $conn->prepare("DELETE FROM billing WHERE Bill_id = ?");
    $stmt->bind_param("i", $billing_id);
    
    if ($stmt->execute()) {
        $success_message = "Billing record deleted successfully!";
    } else {
        $error_message = "Error deleting billing record: " . $stmt->error;
    }
    $stmt->close();
}

// Get all billing records with patient and doctor details
$stmt = $conn->prepare("SELECT b.*, r.Name as PatientName, r.Email as PatientEmail, 
                       d.Name as DoctorName, d.Department as DoctorDepartment
                       FROM billing b 
                       LEFT JOIN register r ON b.Patient_id = r.Patient_id 
                       LEFT JOIN doctorreg d ON b.Doctor_id = d.Doctor_id 
                       ORDER BY b.Bill_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $billing_records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $billing_records = [];
    $error_message = "Billing table not found. Please create the billing table first.";
}

// Get all patients for dropdown
$stmt_patients = $conn->prepare("SELECT Patient_id, Name FROM register ORDER BY Name");
if ($stmt_patients) {
    $stmt_patients->execute();
    $result_patients = $stmt_patients->get_result();
    $patients = $result_patients->fetch_all(MYSQLI_ASSOC);
    $stmt_patients->close();
} else {
    $patients = [];
}

// Get all doctors for dropdown
$stmt_doctors = $conn->prepare("SELECT Doctor_id, Name, Department FROM doctorreg ORDER BY Name");
if ($stmt_doctors) {
    $stmt_doctors->execute();
    $result_doctors = $stmt_doctors->get_result();
    $doctors = $result_doctors->fetch_all(MYSQLI_ASSOC);
    $stmt_doctors->close();
} else {
    $doctors = [];
}

// Get billing statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_bills,
    SUM(Amount) as total_amount,
    SUM(CASE WHEN Payment_status = 'Paid' THEN Amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN Payment_status = 'Pending' THEN Amount ELSE 0 END) as pending_amount,
    COUNT(CASE WHEN Payment_status = 'Paid' THEN 1 END) as paid_bills,
    COUNT(CASE WHEN Payment_status = 'Pending' THEN 1 END) as pending_bills
    FROM billing");
if ($stmt_stats) {
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
} else {
    $stats = [
        'total_bills' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'pending_amount' => 0,
        'paid_bills' => 0,
        'pending_bills' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin - Billing Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Yellow-Black Theme Styling */
        :root {
            --primary-color: #FFD700;
            /* Gold/Yellow */
            --secondary-color: #212121;
            /* Dark Gray/Black */
            --accent-color: #FFC107;
            /* Amber */
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
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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

        .nav li a:hover,
        .nav li a.active {
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

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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

        .badge-paid {
            background-color: var(--success-color);
            color: white;
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-overdue {
            background-color: var(--danger-color);
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

        .billing-details {
            margin-bottom: 20px;
        }

        .billing-details p {
            margin-bottom: 5px;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .amount {
            font-weight: bold;
            font-size: 1.1em;
        }

        .amount.paid {
            color: var(--success-color);
        }

        .amount.pending {
            color: var(--warning-color);
        }

        .amount.overdue {
            color: var(--danger-color);
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
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patient.php">
                        <i class="fas fa-user"></i>
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
                    <a href="admin_bookings.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patientdocuments.php">
                        <i class="fas fa-file-medical"></i>
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
                    <a href="admin_billing.php" class="active">
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
                <div class="logo">Billing Management</div>
                <div class="user-profile">
                    <span>Admin</span>
                    <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $stats['total_bills']; ?></h3>
                    <p>Total Bills</p>
                </div>
                <div class="stat-card">
                    <h3>$<?php echo number_format($stats['total_amount'], 2); ?></h3>
                    <p>Total Amount</p>
                </div>
                <div class="stat-card">
                    <h3>$<?php echo number_format($stats['paid_amount'], 2); ?></h3>
                    <p>Paid Amount</p>
                </div>
                <div class="stat-card">
                    <h3>$<?php echo number_format($stats['pending_amount'], 2); ?></h3>
                    <p>Pending Amount</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Add New Billing Record</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="admin_billing.php">
                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">Patient</label>
                                    <select class="form-select" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['Patient_id']; ?>">
                                                <?php echo $patient['Name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="doctor_id" class="form-label">Doctor</label>
                                    <select class="form-select" id="doctor_id" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['Doctor_id']; ?>">
                                                <?php echo $doctor['Name'] . ' (' . $doctor['Department'] . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="service_type" class="form-label">Service Type</label>
                                    <select class="form-select" id="service_type" name="service_type" required>
                                        <option value="Consultation">Consultation</option>
                                        <option value="Treatment">Treatment</option>
                                        <option value="Surgery">Surgery</option>
                                        <option value="Lab Test">Lab Test</option>
                                        <option value="Medication">Medication</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="service_description" class="form-label">Service Description</label>
                                    <textarea class="form-control" id="service_description" name="service_description" rows="3" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount ($)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                </div>

                                <div class="mb-3">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" name="add_billing">Add Billing Record</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>All Billing Records</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Service</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($billing_records)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No billing records found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($billing_records as $billing): ?>
                                                <tr>
                                                    <td><?php echo $billing['Bill_id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($billing['PatientName']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($billing['PatientEmail']); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($billing['DoctorName']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($billing['DoctorDepartment']); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($billing['Service_type']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($billing['Service_description']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="amount <?php echo strtolower($billing['Payment_status']); ?>">
                                                            $<?php echo number_format($billing['Amount'], 2); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($billing['Payment_status'] == 'Paid'): ?>
                                                            <span class="badge badge-paid">Paid</span>
                                                        <?php elseif ($billing['Payment_status'] == 'Pending'): ?>
                                                            <?php if (strtotime($billing['Due_date']) < time()): ?>
                                                                <span class="badge badge-overdue">Overdue</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-pending">Pending</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($billing['Due_date'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#billingModal"
                                                                    onclick='viewBilling(<?php echo json_encode($billing, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if ($billing['Payment_status'] == 'Pending'): ?>
                                                                <button class="btn btn-sm btn-success" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#statusModal"
                                                                        onclick="updateStatus(<?php echo $billing['Bill_id']; ?>)">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this billing record?');">
                                                                <input type="hidden" name="billing_id" value="<?php echo $billing['Bill_id']; ?>">
                                                                <button type="submit" name="delete_billing" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
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
        </div>
    </div>

    <!-- Billing Details Modal -->
    <div class="modal fade" id="billingModal" tabindex="-1" aria-labelledby="billingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="billingModalLabel">Billing Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="billingModalBody">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="billing_id" id="statusBillingId">
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" name="payment_status" id="paymentStatusSelect" required>
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_payment_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBilling(billing) {
            const modalBody = document.getElementById('billingModalBody');
            modalBody.innerHTML = `
                <div class="billing-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Patient Information</h6>
                            <p><strong>Name:</strong> ${billing.PatientName}</p>
                            <p><strong>Email:</strong> ${billing.PatientEmail}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-user-md"></i> Doctor Information</h6>
                            <p><strong>Name:</strong> ${billing.DoctorName}</p>
                            <p><strong>Department:</strong> ${billing.DoctorDepartment}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-receipt"></i> Billing Information</h6>
                            <p><strong>Service Type:</strong> ${billing.Service_type}</p>
                            <p><strong>Description:</strong> ${billing.Service_description}</p>
                            <p><strong>Amount:</strong> $${parseFloat(billing.Amount).toFixed(2)}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Payment Details</h6>
                            <p><strong>Billing Date:</strong> ${new Date(billing.Billing_date).toLocaleDateString()}</p>
                            <p><strong>Due Date:</strong> ${new Date(billing.Due_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge ${billing.Payment_status === 'Paid' ? 'badge-paid' : 'badge-pending'}">${billing.Payment_status}</span>
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }

        function updateStatus(billingId) {
            document.getElementById('statusBillingId').value = billingId;
        }
    </script>
</body>

</html>
