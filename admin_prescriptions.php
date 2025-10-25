<?php
// admin_prescriptions.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Handle prescription addition
if (isset($_POST['add_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    $Date_issued = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO prescription (Patient_id, Doctor_id, Medicine_name, Dosage, Duration, Instructions, Date_issued) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $patient_id, $doctor_id, $medication, $dosage, $duration, $instructions, $Date_issued);
    
    if ($stmt->execute()) {
        $success_message = "Prescription added successfully!";
    } else {
        $error_message = "Error adding prescription: " . $stmt->error;
    }
    $stmt->close();
}

// Handle prescription deletion
if (isset($_POST['delete_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    
    $stmt = $conn->prepare("DELETE FROM prescription WHERE Prescription_id = ?");
    $stmt->bind_param("i", $prescription_id);
    
    if ($stmt->execute()) {
        $success_message = "Prescription deleted successfully!";
    } else {
        $error_message = "Error deleting prescription: " . $stmt->error;
    }
    $stmt->close();
}

// Get all prescriptions with patient and doctor details
$stmt = $conn->prepare("SELECT p.*, r.Name as PatientName, r.Email as PatientEmail, 
                       d.Name as DoctorName, d.Department as DoctorDepartment
                       FROM prescription p 
                       LEFT JOIN register r ON p.Patient_id = r.Patient_id 
                       LEFT JOIN doctorreg d ON p.Doctor_id = d.Doctor_id 
                       ORDER BY p.Date_issued DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $prescriptions = [];
    $error_message = "Prescriptions table not found. Please create the prescriptions table first.";
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

// Get prescription statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_prescriptions,
    COUNT(DISTINCT Patient_id) as unique_patients,
    COUNT(DISTINCT Doctor_id) as unique_doctors
    FROM prescription");
if ($stmt_stats) {
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
} else {
    $stats = [
        'total_prescriptions' => 0,
        'unique_patients' => 0,
        'unique_doctors' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin - Prescriptions Management</title>
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

        .prescription-details {
            margin-bottom: 20px;
        }

        .prescription-details p {
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
                    <a href="admin_prescriptions.php" class="active">
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
                <div class="logo">Prescriptions Management</div>
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
                    <h3><?php echo $stats['total_prescriptions']; ?></h3>
                    <p>Total Prescriptions</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['unique_patients']; ?></h3>
                    <p>Unique Patients</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['unique_doctors']; ?></h3>
                    <p>Active Doctors</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Add New Prescription</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="admin_prescriptions.php">
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
                                    <label for="medication" class="form-label">Medication</label>
                                    <input type="text" class="form-control" id="medication" name="medication" required>
                                </div>

                                <div class="mb-3">
                                    <label for="dosage" class="form-label">Dosage</label>
                                    <input type="text" class="form-control" id="dosage" name="dosage" required>
                                </div>

                                

                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration</label>
                                    <input type="text" class="form-control" id="duration" name="duration" placeholder="e.g., 7 days, 2 weeks" required>
                                </div>

                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="3" placeholder="Special instructions for the patient..."></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" name="add_prescription">Add Prescription</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>All Prescriptions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Medication</th>
                                            <th>Dosage</th>
                                            
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($prescriptions)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No prescriptions found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($prescriptions as $prescription): ?>
                                                <tr>
                                                    <td><?php echo $prescription['Prescription_id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($prescription['PatientName']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($prescription['PatientEmail']); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($prescription['DoctorName']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($prescription['DoctorDepartment']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($prescription['Medicine_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescription['Dosage']); ?></td>
                                                    
                                                    <td><?php echo date('d M Y', strtotime($prescription['Date_issued'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#prescriptionModal"
                                                                    onclick='viewPrescription(<?php echo json_encode($prescription, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this prescription?');">
                                                                <input type="hidden" name="prescription_id" value="<?php echo $prescription['Prescription_id']; ?>">
                                                                <button type="submit" name="delete_prescription" class="btn btn-sm btn-danger">
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

    <!-- Prescription Details Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prescriptionModalLabel">Prescription Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="prescriptionModalBody">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewPrescription(prescription) {
            const modalBody = document.getElementById('prescriptionModalBody');
            modalBody.innerHTML = `
                <div class="prescription-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Patient Information</h6>
                            <p><strong>Name:</strong> ${prescription.PatientName}</p>
                            <p><strong>Email:</strong> ${prescription.PatientEmail}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-user-md"></i> Doctor Information</h6>
                            <p><strong>Name:</strong> ${prescription.DoctorName}</p>
                            <p><strong>Department:</strong> ${prescription.DoctorDepartment}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-pills"></i> Medication Details</h6>
                            <p><strong>Medication:</strong> ${prescription.Medicine_name}</p>
                            <p><strong>Dosage:</strong> ${prescription.Dosage}</p>
                            <p><strong>Duration:</strong> ${prescription.Duration}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Prescription Info</h6>
                            <p><strong>Date:</strong> ${new Date(prescription.Date_issued).toLocaleDateString()}</p>
                            <p><strong>Instructions:</strong></p>
                            <p>${prescription.Instructions || 'No special instructions provided.'}</p>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>
</body>

</html>
