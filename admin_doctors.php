<?php
// admin_doctors.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Handle doctor addition
if (isset($_POST['submit'])) {
    $username = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $department = $_POST['Department'];

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT * FROM doctorreg WHERE Email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "Email already exists. Please use a different email.";
    } else {
        $stmt = $conn->prepare("INSERT INTO doctorreg (Name, Email, Age, Gender, Phone_no, Department, Password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $age, $gender, $phone, $department, $password);
        
        if ($stmt->execute()) {
            $success_message = "Doctor added successfully!";
        } else {
            $error_message = "Error adding doctor: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Handle doctor deletion
if (isset($_GET['delete_id'])) {
    $doctor_id = $_GET['delete_id'];

    // Check if doctor has any appointments
    $check_bookings = $conn->prepare("SELECT COUNT(*) as booking_count FROM booking WHERE Doctor_id = ?");
    $check_bookings->bind_param("i", $doctor_id);
    $check_bookings->execute();
    $booking_result = $check_bookings->get_result()->fetch_assoc();
    
    if ($booking_result['booking_count'] > 0) {
        $error_message = "Cannot delete doctor with existing appointments. Please reassign or cancel appointments first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM doctorreg WHERE Doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
        
        if ($stmt->execute()) {
            $success_message = "Doctor deleted successfully!";
        } else {
            $error_message = "Error deleting doctor: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_bookings->close();
}

// Get all doctors
$stmt = $conn->prepare("SELECT * FROM doctorreg");
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin - Manage Doctors</title>
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
                    <a href="admin_dashboard.php">
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
                    <a href="admin_doctors.php" class="active">
                        <i class="fas fa-book"></i>
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
                <div class="logo">Doctors Management</div>
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

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Add New Doctor</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="admin_doctors.php">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>

                                <div class="mb-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="age" name="age" min="25" max="80" required>
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>

                                <div class="mb-3">
                                    <label for="Department" class="form-label">Department</label>
                                    <select class="form-select" id="Department" name="Department" required>
                                        <option value="Cardiologist">Cardiologist</option>
                                        <option value="Neurologist">Neurologist</option>
                                        <option value="Gynecologist">Gynecologist</option>
                                        <option value="Pediatrician">Pediatrician</option>
                                        <option value="Dermatologist">Dermatologist</option>
                                        <option value="Orthopedic">Orthopedic</option>
                                        <option value="Ophthalmologist">Ophthalmologist</option>
                                        <option value="Psychiatrist">Psychiatrist</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" name="submit">Add Doctor</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Manage Doctors</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Doctor Name</th>
                                            <th>Email</th>
                                            <th>Gender</th>
                                            <th>Department</th>
                                            <th>Phone</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($doctors)): ?>
                                            <?php foreach ($doctors as $doctor): ?>
                                                <tr>
                                                    <td><?php echo $doctor['Doctor_id']; ?></td>
                                                    <td><?php echo $doctor['Name']; ?></td>
                                                    <td><?php echo $doctor['Email']; ?></td>
                                                    <td><?php echo $doctor['Gender']; ?></td>
                                                    <td><?php echo $doctor['Department']; ?></td>
                                                    <td><?php echo $doctor['Phone_no']; ?></td>
                                                    <td>
                                                        <a href="admin_doctors.php?delete_id=<?php echo $doctor['Doctor_id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this doctor?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No doctors found</td>
                                            </tr>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
  