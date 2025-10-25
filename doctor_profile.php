<?php
// doctor_profile.php
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

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $qualification = $_POST['qualification'];
    $experience = $_POST['experience'];
    
    // Handle photo upload
    $photo_path = $doctor['Profile_photo']; // Keep existing photo by default
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_photo']['type'];
        $file_size = $_FILES['profile_photo']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) { // 5MB limit
            $upload_dir = 'uploads/doctors/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'doctor_' . $doctor_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Delete old photo if exists
                if (!empty($doctor['Profile_photo']) && file_exists($doctor['Profile_photo'])) {
                    unlink($doctor['Profile_photo']);
                }
                $photo_path = $upload_path;
            } else {
                $error_message = "Error uploading photo. Please try again.";
            }
        } else {
            $error_message = "Invalid file type or size. Please upload a valid image (JPEG, PNG, GIF) under 5MB.";
        }
    }
    
    if (!isset($error_message)) {
        $stmt_update = $conn->prepare("UPDATE doctorreg SET Name = ?, Email = ?, Phone_no = ?, Department = ?, Qualification = ?, Experience = ?, Profile_photo = ? WHERE Doctor_id = ?");
        $stmt_update->bind_param("sssssssi", $name, $email, $phone, $department, $qualification, $experience, $photo_path, $doctor_id);
        
        if ($stmt_update->execute()) {
            $success_message = "Profile updated successfully!";
            // Refresh doctor data
            $stmt_refresh = $conn->prepare("SELECT * FROM doctorreg WHERE Doctor_id = ?");
            $stmt_refresh->bind_param("i", $doctor_id);
            $stmt_refresh->execute();
            $result = $stmt_refresh->get_result();
            $doctor = $result->fetch_assoc();
            $stmt_refresh->close();
        } else {
            $error_message = "Error updating profile: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $doctor['Password'])) {
        $error_message = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE doctorreg SET Password = ? WHERE Doctor_id = ?");
        $stmt->bind_param("si", $hashed_password, $doctor_id);
        
        if ($stmt->execute()) {
            $success_message = "Password changed successfully!";
        } else {
            $error_message = "Error changing password: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get doctor statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(DISTINCT b.Booking_id) as total_appointments,
    COUNT(DISTINCT b.Patient_id) as total_patients,
    COUNT(DISTINCT p.Prescription_id) as total_prescriptions,
    COUNT(DISTINCT mr.Record_id) as total_medical_records
    FROM doctorreg d 
    LEFT JOIN booking b ON d.Doctor_id = b.Doctor_id 
    LEFT JOIN prescription p ON d.Doctor_id = p.Doctor_id 
    LEFT JOIN medical_records mr ON d.Doctor_id = mr.Doctor_id 
    WHERE d.Doctor_id = ?");
$stmt_stats->bind_param("i", $doctor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Get recent appointments
$stmt_recent = $conn->prepare("SELECT b.*, r.Name as PatientName 
                               FROM booking b 
                               LEFT JOIN register r ON b.Patient_id = r.Patient_id 
                               WHERE b.Doctor_id = ? 
                               ORDER BY b.Select_date DESC 
                               LIMIT 5");
$stmt_recent->bind_param("i", $doctor_id);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
$recent_appointments = $result_recent->fetch_all(MYSQLI_ASSOC);
$stmt_recent->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile - MEDITRACK</title>
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

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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

        .profile-info {
            background: #d4edda;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .profile-info h6 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .info-value {
            color: #212121;
        }

        .recent-appointment {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid var(--accent-color);
        }

        .appointment-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .appointment-patient {
            font-weight: 600;
            color: var(--primary-color);
        }

        .appointment-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .appointment-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-completed { background-color: #cce5ff; color: #004085; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }

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
                    <a href="doctor_medical_records.php">
                        <i class="fas fa-file-medical"></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_profile.php" class="active">
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
                <div class="logo">My Profile</div>
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

            <!-- Profile Header -->
            <div class="profile-header text-center">
                <div class="profile-avatar">
                    <?php if (!empty($doctor['Profile_photo']) && file_exists($doctor['Profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($doctor['Profile_photo']); ?>" alt="Profile Photo" class="profile-photo">
                    <?php else: ?>
                        <i class="fas fa-user-md"></i>
                    <?php endif; ?>
                </div>
                <h3>Dr. <?php echo htmlspecialchars($doctor['Name']); ?></h3>
                <p class="mb-0"><?php echo htmlspecialchars($doctor['Department']); ?> • <?php echo htmlspecialchars($doctor['Qualification']); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $stats['total_appointments']; ?></h3>
                    <p>Total Appointments</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $stats['total_patients']; ?></h3>
                    <p>Total Patients</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-prescription"></i>
                    <h3><?php echo $stats['total_prescriptions']; ?></h3>
                    <p>Prescriptions</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-medical"></i>
                    <h3><?php echo $stats['total_medical_records']; ?></h3>
                    <p>Medical Records</p>
                </div>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user"></i> Profile Information</h5>
                        </div>
                        <div class="card-body">
                                                                 <div class="profile-info">
                                     <div class="info-item">
                                         <span class="info-label">Profile Photo:</span>
                                         <span class="info-value">
                                             <?php if (!empty($doctor['Profile_photo']) && file_exists($doctor['Profile_photo'])): ?>
                                                 <img src="<?php echo htmlspecialchars($doctor['Profile_photo']); ?>" alt="Profile Photo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #28a745;">
                                             <?php else: ?>
                                                 <span class="text-muted">No photo uploaded</span>
                                             <?php endif; ?>
                                         </span>
                                     </div>
                                     <div class="info-item">
                                         <span class="info-label">Name:</span>
                                         <span class="info-value"><?php echo htmlspecialchars($doctor['Name']); ?></span>
                                     </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doctor['Email']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doctor['Phone_no']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Department:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doctor['Department']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Qualification:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doctor['Qualification']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Experience:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($doctor['Experience']); ?> years</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Doctor ID:</span>
                                        <span class="info-value">#<?php echo $doctor['Doctor_id']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                        <i class="fas fa-edit"></i> Edit Profile
                                    </button>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                            </div>
                        </div>

                    <!-- Recent Appointments -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Appointments</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_appointments)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No recent appointments</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_appointments as $appointment): ?>
                                        <div class="recent-appointment">
                                            <div class="appointment-header">
                                                <div class="appointment-patient">
                                                    <?php echo htmlspecialchars($appointment['PatientName'] ?? $appointment['Name']); ?>
                                                </div>
                                                <span class="appointment-status status-<?php echo strtolower($appointment['Status']); ?>">
                                                    <?php echo $appointment['Status']; ?>
                                                </span>
                                            </div>
                                            <div class="appointment-date">
                                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($appointment['Select_date'])); ?>
                                                <span class="ms-3">
                                                    <i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($appointment['Select_department']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="doctor_bookings.php" class="btn btn-outline-primary">
                                        <i class="fas fa-calendar"></i> View All Appointments
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

         <!-- Edit Profile Modal -->
     <div class="modal fade" id="editProfileModal" tabindex="-1">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Edit Profile</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                 </div>
                 <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($doctor['Name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($doctor['Email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($doctor['Phone_no']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department *</label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="Cardiology" <?php echo $doctor['Department'] === 'Cardiology' ? 'selected' : ''; ?>>Cardiology</option>
                                        <option value="Neurology" <?php echo $doctor['Department'] === 'Neurology' ? 'selected' : ''; ?>>Neurology</option>
                                        <option value="Orthopedics" <?php echo $doctor['Department'] === 'Orthopedics' ? 'selected' : ''; ?>>Orthopedics</option>
                                        <option value="Pediatrics" <?php echo $doctor['Department'] === 'Pediatrics' ? 'selected' : ''; ?>>Pediatrics</option>
                                        <option value="Dermatology" <?php echo $doctor['Department'] === 'Dermatology' ? 'selected' : ''; ?>>Dermatology</option>
                                        <option value="General Medicine" <?php echo $doctor['Department'] === 'General Medicine' ? 'selected' : ''; ?>>General Medicine</option>
                                        <option value="Surgery" <?php echo $doctor['Department'] === 'Surgery' ? 'selected' : ''; ?>>Surgery</option>
                                        <option value="Psychiatry" <?php echo $doctor['Department'] === 'Psychiatry' ? 'selected' : ''; ?>>Psychiatry</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                                                 <div class="row">
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="qualification" class="form-label">Qualification *</label>
                                     <input type="text" class="form-control" name="qualification" value="<?php echo htmlspecialchars($doctor['Qualification']); ?>" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="experience" class="form-label">Years of Experience *</label>
                                     <input type="number" class="form-control" name="experience" value="<?php echo htmlspecialchars($doctor['Experience']); ?>" min="0" max="50" required>
                                 </div>
                             </div>
                         </div>
                         
                         <div class="row">
                             <div class="col-md-12">
                                 <div class="mb-3">
                                     <label for="profile_photo" class="form-label">Profile Photo</label>
                                     <div class="d-flex align-items-center">
                                         <?php if (!empty($doctor['Profile_photo']) && file_exists($doctor['Profile_photo'])): ?>
                                             <div class="me-3">
                                                 <img src="<?php echo htmlspecialchars($doctor['Profile_photo']); ?>" alt="Current Photo" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;">
                                             </div>
                                         <?php endif; ?>
                                         <input type="file" class="form-control" name="profile_photo" accept="image/*">
                                     </div>
                                     <small class="form-text text-muted">Upload a new photo (JPEG, PNG, GIF) under 5MB. Leave empty to keep current photo.</small>
                                 </div>
                             </div>
                         </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="change_password" class="btn btn-success">Change Password</button>
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
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>
