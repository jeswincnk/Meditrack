<?php
// doctor_patients.php
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

// Get filter parameters
$search_filter = $_GET['search'] ?? '';
$blood_filter = $_GET['blood'] ?? '';

// Build the query to get patients who have booked with this doctor
$query = "SELECT DISTINCT r.*, 
          COUNT(b.Booking_id) as total_appointments,
          MAX(b.Select_date) as last_appointment,
          MIN(b.Select_date) as first_appointment
          FROM register r 
          INNER JOIN booking b ON r.Patient_id = b.Patient_id 
          WHERE b.Doctor_id = ?";

$params = [$doctor_id];
$types = "i";

if (!empty($search_filter)) {
    $query .= " AND (r.Name LIKE ? OR r.Email LIKE ? OR r.Phone_no LIKE ?)";
    $search_param = "%$search_filter%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($blood_filter)) {
    $query .= " AND r.Blood_group = ?";
    $params[] = $blood_filter;
    $types .= "s";
}

$query .= " GROUP BY r.Patient_id ORDER BY r.Name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get patient statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(DISTINCT r.Patient_id) as total_patients,
    COUNT(DISTINCT CASE WHEN r.Gender = 'Male' THEN r.Patient_id END) as male_patients,
    COUNT(DISTINCT CASE WHEN r.Gender = 'Female' THEN r.Patient_id END) as female_patients,
    AVG(r.Age) as avg_age
    FROM register r 
    INNER JOIN booking b ON r.Patient_id = b.Patient_id 
    WHERE b.Doctor_id = ?");
$stmt_stats->bind_param("i", $doctor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Get blood group distribution
$stmt_blood = $conn->prepare("SELECT 
    r.Blood_group, COUNT(DISTINCT r.Patient_id) as count
    FROM register r 
    INNER JOIN booking b ON r.Patient_id = b.Patient_id 
    WHERE b.Doctor_id = ? 
    GROUP BY r.Blood_group 
    ORDER BY count DESC");
$stmt_blood->bind_param("i", $doctor_id);
$stmt_blood->execute();
$result_blood = $stmt_blood->get_result();
$blood_groups = [];
while ($row = $result_blood->fetch_assoc()) {
    $blood_groups[] = $row;
}
$stmt_blood->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Patients - MEDITRACK</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Green Theme Styling for Doctor Patients */
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

        .patient-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .patient-card:hover {
            transform: translateY(-5px);
        }

        .patient-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }

        .patient-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .patient-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat-item {
            background: var(--light-green);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .patient-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .no-patients {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-patients i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .blood-group-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .blood-group-a { background-color: #ffebee; color: #c62828; }
        .blood-group-b { background-color: #e3f2fd; color: #1565c0; }
        .blood-group-o { background-color: #f3e5f5; color: #7b1fa2; }
        .blood-group-ab { background-color: #e8f5e8; color: #2e7d32; }
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
                    <a href="doctor_patients.php" class="active">
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
                <div class="logo">My Patients</div>
                <div class="user-profile">
                    <span>Dr. <?php echo htmlspecialchars($doctor['Name']); ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $stats['total_patients']; ?></h3>
                    <p>Total Patients</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-male"></i>
                    <h3><?php echo $stats['male_patients']; ?></h3>
                    <p>Male Patients</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-female"></i>
                    <h3><?php echo $stats['female_patients']; ?></h3>
                    <p>Female Patients</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-birthday-cake"></i>
                    <h3><?php echo round($stats['avg_age']); ?></h3>
                    <p>Average Age</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <h5 class="mb-3"><i class="fas fa-filter"></i> Search & Filter Patients</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search Patients</label>
                        <input type="text" class="form-control" name="search" id="search" 
                               value="<?php echo htmlspecialchars($search_filter); ?>" 
                               placeholder="Search by name, email, or phone...">
                    </div>
                    <div class="col-md-4">
                        <label for="blood" class="form-label">Blood Group</label>
                        <select class="form-select" name="blood" id="blood">
                            <option value="">All Blood Groups</option>
                            <option value="A+" <?php echo $blood_filter === 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo $blood_filter === 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo $blood_filter === 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo $blood_filter === 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="O+" <?php echo $blood_filter === 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo $blood_filter === 'O-' ? 'selected' : ''; ?>>O-</option>
                            <option value="AB+" <?php echo $blood_filter === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo $blood_filter === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
                <?php if (!empty($search_filter) || !empty($blood_filter)): ?>
                    <div class="mt-3">
                        <a href="doctor_patients.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Blood Group Distribution -->
            <?php if (!empty($blood_groups)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tint"></i> Blood Group Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($blood_groups as $bg): ?>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="text-center">
                                        <div class="blood-group-badge blood-group-<?php echo strtolower($bg['Blood_group']); ?>">
                                            <?php echo $bg['Blood_group']; ?>
                                        </div>
                                        <h4 class="mt-2"><?php echo $bg['count']; ?></h4>
                                        <small class="text-muted">Patients</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Patients List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-friends"></i> Patient List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($patients)): ?>
                        <div class="no-patients">
                            <i class="fas fa-user-slash"></i>
                            <h5>No Patients Found</h5>
                            <p>No patients match your current search criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($patients as $patient): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="patient-card">
                                        <div class="patient-header">
                                            <div class="patient-name">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($patient['Name']); ?>
                                            </div>
                                            <span class="blood-group-badge blood-group-<?php echo strtolower($patient['Blood_group']); ?>">
                                                <?php echo $patient['Blood_group']; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="patient-stats">
                                            <div class="stat-item">
                                                <i class="fas fa-birthday-cake"></i> <?php echo $patient['Age']; ?> years
                                            </div>
                                            <div class="stat-item">
                                                <i class="fas fa-venus-mars"></i> <?php echo $patient['Gender']; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="patient-stats">
                                            <div class="stat-item">
                                                <i class="fas fa-calendar-check"></i> <?php echo $patient['total_appointments']; ?> visits
                                            </div>
                                            <div class="stat-item">
                                                <i class="fas fa-clock"></i> Last: <?php echo date('d M Y', strtotime($patient['last_appointment'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['Email']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['Phone_no']); ?></p>
                                        </div>
                                        
                                        <div class="patient-actions">
                                            <a href="doctor_medical_records.php?patient_id=<?php echo $patient['Patient_id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-file-medical"></i> Records
                                            </a>
                                            <a href="doctor_prescriptions.php?patient_id=<?php echo $patient['Patient_id']; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-prescription"></i> Prescriptions
                                            </a>
                                            <a href="doctor_bookings.php?patient_id=<?php echo $patient['Patient_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-calendar"></i> Appointments
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add interactive features
        $(document).ready(function() {
            // Add hover effects to patient cards
            $('.patient-card').hover(
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

            // Auto-submit form on blood group change
            $('#blood').change(function() {
                if ($(this).val()) {
                    $('form').submit();
                }
            });
        });
    </script>
</body>
</html>


