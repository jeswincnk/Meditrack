<?php
include 'database.php';
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$stmt = $conn->prepare("SELECT * FROM doctorreg WHERE Doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

// Handle prescription addition
if (isset($_POST['add_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $medicine_name = $_POST['medicine_name'];
    $dosage = $_POST['dosage'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    $date_issued = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO prescription (Patient_id, Doctor_id, Date_issued, Medicine_name, Dosage, Duration, Instructions) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $patient_id, $doctor_id, $date_issued, $medicine_name, $dosage, $duration, $instructions);
    
    if ($stmt->execute()) {
        $success_message = "Prescription added successfully!";
    } else {
        $error_message = "Error adding prescription: " . $stmt->error;
    }
    $stmt->close();
}

// Get prescriptions
$query = "SELECT p.*, r.Name as PatientName FROM prescription p 
          LEFT JOIN register r ON p.Patient_id = r.Patient_id 
          WHERE p.Doctor_id = ? ORDER BY p.Date_issued DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$prescriptions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get patients
$stmt = $conn->prepare("SELECT DISTINCT r.Patient_id, r.Name FROM register r 
                        INNER JOIN booking b ON r.Patient_id = b.Patient_id 
                        WHERE b.Doctor_id = ? ORDER BY r.Name");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php
$stmt_doctors = $conn->prepare("SELECT Doctor_id, Name, Department FROM doctorreg ORDER BY Name");
$stmt_doctors->execute();
$result_doctors = $stmt_doctors->get_result();
$doctors = $result_doctors->fetch_all(MYSQLI_ASSOC);
$stmt_doctors->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Prescriptions - MEDITRACK</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .prescription-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .prescription-card:hover {
            transform: translateY(-5px);
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
                    <a href="doctor_dashboard.php">
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
                    <a href="doctor_prescriptions.php" class="active">
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
                <div class="logo">Prescriptions Management</div>
                <div class="user-profile">
                    <span>Welcome, Dr. <?php echo htmlspecialchars($doctor['Name']); ?></span>
                    <a href="logout.php">Logout</a>
                </div>
            </div>

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

            <!-- Add Prescription Button -->
            <div class="mb-4">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                    <i class="fas fa-plus"></i> Add New Prescription
                </button>
            </div>

                <!-- Prescriptions List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-prescription-bottle"></i> Prescriptions List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prescriptions)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-prescription-bottle fa-3x text-muted mb-3"></i>
                                <h5>No Prescriptions Found</h5>
                                <p class="text-muted">No prescriptions have been created yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="prescription-card">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="text-primary mb-0">
                                                    <i class="fas fa-pills"></i> <?php echo htmlspecialchars($prescription['Medicine_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('d M Y', strtotime($prescription['Date_issued'])); ?>
                                                </small>
                                            </div>
                                            
                                            <div class="bg-light p-3 rounded mb-3">
                                                <div class="mb-2">
                                                    <strong>Dosage:</strong> <?php echo htmlspecialchars($prescription['Dosage']); ?>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Duration:</strong> <?php echo htmlspecialchars($prescription['Duration']); ?>
                                                </div>
                                                <div>
                                                    <strong>Instructions:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($prescription['Instructions'])); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>Patient:</strong> <?php echo htmlspecialchars($prescription['PatientName']); ?><br>
                                                <strong>Prescription ID:</strong> #<?php echo $prescription['Prescription_id']; ?>
                                            </div>
                                            
                                            <div>
                                                <a href="doctor_medical_records.php?patient_id=<?php echo $prescription['Patient_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-file-medical"></i> View Records
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Prescription Modal -->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">Patient *</label>
                                    <select class="form-select" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['Patient_id']; ?>">
                                                <?php echo htmlspecialchars($patient['Name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="medicine_name" class="form-label">Medicine Name *</label>
                                    <input type="text" class="form-control" name="medicine_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dosage" class="form-label">Dosage *</label>
                                    <input type="text" class="form-control" name="dosage" 
                                           placeholder="e.g., 500mg twice daily" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration *</label>
                                    <input type="text" class="form-control" name="duration" 
                                           placeholder="e.g., 7 days" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="instructions" class="form-label">Instructions *</label>
                            <textarea class="form-control" name="instructions" rows="4" 
                                      placeholder="Detailed instructions for the patient..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_prescription" class="btn btn-success">Add Prescription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
