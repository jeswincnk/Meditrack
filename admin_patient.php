<?php
// admin_patient.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php"); // Redirect to admin login page
    exit();
}

// Handle patient addition
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $blood = $_POST['blood'];

    // Validate inputs
    $errors = [];
    if(empty($name)) $errors[] = "Name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(empty($_POST['password'])) $errors[] = "Password is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    if(empty($errors)) {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT * FROM register WHERE Email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists. Please use a different email.";
        } else {
            $stmt = $conn->prepare("INSERT INTO register (Name, Email, Age, Gender, Phone_no, Blood_group, Password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $name, $email, $age, $gender, $phone, $blood, $password);
            
            if ($stmt->execute()) {
                $success_message = "Patient added successfully!";
            } else {
                $error_message = "Error adding patient: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Handle patient deletion
if (isset($_POST['delete_id'])) {
    $patient_id = $_POST['delete_id'];

    // Check if patient has any appointments
    $check_bookings = $conn->prepare("SELECT COUNT(*) as booking_count FROM booking WHERE Patient_id = ?");
    $check_bookings->bind_param("i", $patient_id);
    $check_bookings->execute();
    $booking_result = $check_bookings->get_result()->fetch_assoc();
    
    if ($booking_result['booking_count'] > 0) {
        $error_message = "Cannot delete patient with existing appointments. Please cancel appointments first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM register WHERE Patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        
        if ($stmt->execute()) {
            $success_message = "Patient deleted successfully!";
        } else {
            $error_message = "Error deleting patient: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_bookings->close();
}

// Get all patients
$stmt = $conn->prepare("SELECT * FROM register");
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin - Manage Patients</title>
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
                    <a href="admin_patient.php" class="active">
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
                <div class="logo">Patients Management</div>
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
                            <h5>Add New Patient</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="admin_patient.php">
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
                                    <input type="number" class="form-control" id="age" name="age" min="1" max="120" required>
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
                                    <label for="blood" class="form-label">Blood Group</label>
                                    <select class="form-select" id="blood" name="blood" required>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" name="submit">Add Patient</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Manage Patients</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Blood Group</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td><?php echo $patient['Patient_id']; ?></td>
                                        <td><?php echo $patient['Name']; ?></td>
                                        <td><?php echo $patient['Email']; ?></td>
                                        <td><?php echo $patient['Age']; ?></td>
                                        <td><?php echo $patient['Gender']; ?></td>
                                        <td><?php echo $patient['Blood_group']; ?></td>
                                        <td><?php echo $patient['Phone_no']; ?></td>
                                        <td>
                                            <form method="post" action="admin_patient.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $patient['Patient_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($patients)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No patients found</td>
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
                            <iframe id="document-preview" class="embed-responsive-item"
                                style="width: 100%; height: 400px;" src="" allowfullscreen></iframe>
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
                            <button type="submit" name="update_document" class="btn btn-primary">Update Document
                                Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Handle document view button click
            $('.view-document').click(function () {
                var id = $(this).data('id');
                var student = $(this).data('student');
                var type = $(this).data('type');
                var path = $(this).data('path');
                var status = $(this).data('status');
                var comments = $(this).data('comments');

                // Format the document type for display
                var formattedType = type.replace(/_/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });

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
    </script>
</body>

</html>