<?php
// doctor_medical_records.php
include 'database.php';

// Check if the user is logged in and is a doctor
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
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

// Handle medical record addition
if (isset($_POST['add_record'])) {
    $patient_id = $_POST['patient_id'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $notes = $_POST['notes'];
    $date_recorded = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO medical_records (Patient_id, Doctor_id, Record_date, Diagnosis, Treatment, Notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $patient_id, $doctor_id, $date_recorded, $diagnosis, $treatment, $notes);
    
    if ($stmt->execute()) {
        $success_message = "Medical record added successfully!";
    } else {
        $error_message = "Error adding medical record: " . $stmt->error;
    }
    $stmt->close();
}

// Get filter parameters
$patient_filter = $_GET['patient_id'] ?? '';

// Get medical records
$query = "SELECT mr.*, r.Name as PatientName, r.Email as PatientEmail, r.Phone_no as PatientPhone, r.Age, r.Gender, r.Blood_group
          FROM medical_records mr 
          LEFT JOIN register r ON mr.Patient_id = r.Patient_id 
          WHERE mr.Doctor_id = ?";

$params = [$doctor_id];
$types = "i";

if (!empty($patient_filter)) {
    $query .= " AND mr.Patient_id = ?";
    $params[] = $patient_filter;
    $types .= "i";
}

$query .= " ORDER BY mr.Record_date DESC, mr.Record_id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$medical_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get patients for filter dropdown
$stmt = $conn->prepare("SELECT DISTINCT r.Patient_id, r.Name FROM register r 
                        INNER JOIN booking b ON r.Patient_id = b.Patient_id 
                        WHERE b.Doctor_id = ? ORDER BY r.Name");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get selected patient info if filtering
$selected_patient = null;
if (!empty($patient_filter)) {
    $stmt = $conn->prepare("SELECT * FROM register WHERE Patient_id = ?");
    $stmt->bind_param("i", $patient_filter);
    $stmt->execute();
    $selected_patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get medical record statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT Patient_id) as patients_with_records,
    COUNT(DISTINCT DATE(Record_date)) as record_days
    FROM medical_records WHERE Doctor_id = ?");
$stmt_stats->bind_param("i", $doctor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Medical Records - MEDITRACK</title>
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

        .medical-record-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .medical-record-card:hover {
            transform: translateY(-5px);
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
            color: #212121;
            font-weight: 500;
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .patient-info {
            background: #d4edda;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .patient-info h6 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .record-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .record-content h6 {
            color: var(--secondary-color);
            margin-bottom: 10px;
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
                    <a href="doctor_prescriptions.php">
                        <i class="fas fa-prescription"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_medical_records.php" class="active">
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
                <div class="logo">Medical Records Management</div>
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

                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="fas fa-file-medical"></i>
                        <h3><?php echo $stats['total_records']; ?></h3>
                        <p>Total Medical Records</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $stats['patients_with_records']; ?></h3>
                        <p>Patients with Records</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar"></i>
                        <h3><?php echo $stats['record_days']; ?></h3>
                        <p>Record Days</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-plus-circle"></i>
                        <h3>+</h3>
                        <p>Add New Record</p>
                    </div>
                </div>

                <!-- Selected Patient Info -->
                <?php if ($selected_patient): ?>
                    <div class="patient-info">
                        <h6><i class="fas fa-user"></i> Patient Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($selected_patient['Name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($selected_patient['Email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($selected_patient['Phone_no']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Age:</strong> <?php echo $selected_patient['Age']; ?> | <strong>Gender:</strong> <?php echo $selected_patient['Gender']; ?></p>
                                <p><strong>Blood Group:</strong> <?php echo $selected_patient['Blood_group']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filters Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Medical Records</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <label for="patient_id" class="form-label">Patient</label>
                                <select class="form-select" name="patient_id" id="patient_id">
                                    <option value="">All Patients</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['Patient_id']; ?>" 
                                                <?php echo $patient_filter == $patient['Patient_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                        <?php if (!empty($patient_filter)): ?>
                            <div class="mt-3">
                                <a href="doctor_medical_records.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Medical Record Button -->
                <div class="mb-4">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                        <i class="fas fa-plus"></i> Add New Medical Record
                    </button>
                </div>

                <!-- Medical Records List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-medical"></i> Medical Records List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medical_records)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                                <h5>No Medical Records Found</h5>
                                <p class="text-muted">No medical records match your current filters.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($medical_records as $record): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="medical-record-card">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="text-primary mb-0">
                                                    <i class="fas fa-file-medical"></i> Record #<?php echo $record['Record_id']; ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('d M Y', strtotime($record['Record_date'])); ?>
                                                </small>
                                            </div>
                                            
                                            <div class="record-content">
                                                <h6><i class="fas fa-stethoscope"></i> Diagnosis</h6>
                                                <p><?php echo nl2br(htmlspecialchars($record['Diagnosis'])); ?></p>
                                            </div>
                                            
                                            <div class="record-content">
                                                <h6><i class="fas fa-pills"></i> Treatment</h6>
                                                <p><?php echo nl2br(htmlspecialchars($record['Treatment'])); ?></p>
                                            </div>
                                            
                                            <?php if (!empty($record['Notes'])): ?>
                                                <div class="record-content">
                                                    <h6><i class="fas fa-notes-medical"></i> Notes</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($record['Notes'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3">
                                                <strong>Patient:</strong> <?php echo htmlspecialchars($record['PatientName']); ?><br>
                                                <strong>Record ID:</strong> #<?php echo $record['Record_id']; ?>
                                            </div>
                                            
                                            <div>
                                                <a href="doctor_prescriptions.php?patient_id=<?php echo $record['Patient_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-prescription"></i> View Prescriptions
                                                </a>
                                                <a href="doctor_bookings.php?patient_id=<?php echo $record['Patient_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-calendar"></i> View Appointments
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

    <!-- Add Medical Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medical Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
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
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis *</label>
                            <textarea class="form-control" name="diagnosis" rows="3" 
                                      placeholder="Enter the diagnosis..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="treatment" class="form-label">Treatment *</label>
                            <textarea class="form-control" name="treatment" rows="3" 
                                      placeholder="Enter the treatment plan..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Any additional notes or observations..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_record" class="btn btn-success">Add Medical Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    if (this.querySelector('h3').textContent === '+') {
                        new bootstrap.Modal(document.getElementById('addRecordModal')).show();
                    } else {
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = 'scale(1)';
                        }, 150);
                    }
                });
            });

            // Auto-submit form on patient selection
            const patientSelect = document.getElementById('patient_id');
            if (patientSelect) {
                patientSelect.addEventListener('change', function() {
                    if (this.value) {
                        this.form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>
