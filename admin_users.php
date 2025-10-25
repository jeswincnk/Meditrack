<?php
// admin_users.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Adding a new student
if (isset($_POST['add_student'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $course_enrolled = $_POST['course_enrolled'];

    $stmt = $conn->prepare("INSERT INTO students (name, email, password, dob, gender, phone, address, course_enrolled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $username, $email, $password, $dob, $gender, $phone, $address, $course_enrolled);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Student added successfully!');</script>";
}

// Adding a new instructor
if (isset($_POST['add_instructor'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $location = $_POST['location'];
    $experience = $_POST['experience'];
    $certifications = $_POST['certifications'];
    $availability = $_POST['availability'];

    $stmt = $conn->prepare("INSERT INTO instructor (name, email, password, phone, gender, location, experience, certifications, availability) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $name, $email, $password, $phone, $gender, $location, $experience, $certifications, $availability);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Instructor added successfully!');</script>";
}

// Deleting a student
if (isset($_GET['delete_student'])) {
    $user_id = $_GET['delete_student'];

    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Student deleted successfully!');</script>";
}

// Deleting an instructor
if (isset($_GET['delete_instructor'])) {
    $instructor_id = $_GET['delete_instructor'];

    $stmt = $conn->prepare("DELETE FROM instructor WHERE instructor_id = ?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Instructor deleted successfully!');</script>";
}

// Updating a student
if (isset($_POST['update_student'])) {
    $user_id = $_POST['student_id'];
    $username = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $course_enrolled = $_POST['course_enrolled'];
     
    $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone = ?, address = ?, dob = ?, gender = ?, course_enrolled = ? WHERE student_id = ?");
    $stmt->bind_param("sssssssi", $username, $email, $phone, $address, $dob, $gender, $course_enrolled, $user_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Student updated successfully!');</script>";
}

// Updating an instructor
if (isset($_POST['update_instructor'])) {
    $instructor_id = $_POST['instructor_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $location = $_POST['location'];
    $experience = $_POST['experience'];
    $certifications = $_POST['certifications'];
    $availability = $_POST['availability'];
     
    $stmt = $conn->prepare("UPDATE instructor SET name = ?, email = ?, phone = ?, gender = ?, location = ?, experience = ?, certifications = ?, availability = ? WHERE instructor_id = ?");
    $stmt->bind_param("ssssssssi", $name, $email, $phone, $gender, $location, $experience, $certifications, $availability, $instructor_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Instructor updated successfully!');</script>";
}

// Fetch students
$stmt = $conn->prepare("SELECT * FROM students");
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch instructors
$stmt = $conn->prepare("SELECT * FROM instructor");
$stmt->execute();
$result = $stmt->get_result();
$instructors = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count total users
$total_students = count($students);
$total_instructors = count($instructors);
$total_users = $total_students + $total_instructors;

// Count active bookings
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM course_bookings WHERE status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
$active_bookings = $result->fetch_assoc()['count'];
$stmt->close();

// Count pending documents
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_documents WHERE approval_status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$pending_documents = $result->fetch_assoc()['count'];
$stmt->close();

// Get user type filter
$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Management - Admin Panel</title>
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

        .dashboard .overview {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .dashboard .overview .box {
            background-color: var(--secondary-color);
            color: var(--text-light);
            padding: 25px;
            border-radius: 10px;
            flex: 1;
            min-width: 200px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-color);
        }

        .dashboard .overview .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .dashboard .overview .box h3 {
            margin-top: 0;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .dashboard .overview .box p {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .dashboard .overview .box i {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--primary-color);
            opacity: 0.8;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border: none;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            padding: 15px 20px;
            font-weight: 600;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 25px;
        }

        .table {
            width: 100%;
            margin-top: 20px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border: none;
        }

        .table td {
            padding: 12px 15px;
            border-top: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table tr:hover {
            background-color: rgba(255, 215, 0, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #e6c200;
            color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border: none;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .btn-success {
            background-color: var(--success-color);
            border: none;
        }

        .add-user-form input, 
        .add-user-form select, 
        .add-user-form button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .add-user-form select {
            background-color: white;
        }

        .add-user-form button {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-user-form button:hover {
            background-color: #e6c200;
        }

        .modal-header {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border-bottom: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-footer {
            border-top: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }

        .nav-tabs .nav-link {
            color: var(--secondary-color);
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-options a {
            padding: 8px 15px;
            background-color: #f0f0f0;
            color: var(--secondary-color);
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-options a.active {
            background-color: var(--primary-color);
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
            <h2>DRV SCL Admin </h2>
            </div>
            <ul class="nav">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_users.php" class="active">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="admin_instructors.php">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Instructors</span>
                    </a>
                </li>
                <li>
                    <a href="admin_courses.php">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li>
                    <a href="admin_bookings.php" >
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="admin_documents.php">
                        <i class="fas fa-file-alt"></i>
                        <span>Documents</span>
                    </a>
                </li>
                <li>
                    <a href="admin_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
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
                <div class="logo">
                    User Management
                </div>
                <div class="user-profile">
                    <span>Welcome, <?php echo isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin'; ?></span>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <div class="dashboard">
                <!-- Overview Section -->
                <div class="overview">
                    <div class="box">
                        <i class="fas fa-users"></i>
                        <h3>Total Users</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                    <div class="box">
                        <i class="fas fa-user-graduate"></i>
                        <h3>Students</h3>
                        <p><?php echo $total_students; ?></p>
                    </div>
                    <div class="box">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h3>Instructors</h3>
                        <p><?php echo $total_instructors; ?></p>
                    </div>
                    <div class="box">
                        <i class="fas fa-file-alt"></i>
                        <h3>Pending Documents</h3>
                        <p><?php echo $pending_documents; ?></p>
                    </div>
                </div>

                <!-- User Management Section -->
                <div class="card">
                    <div class="card-header">
                        <span>User Management</span>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                <i class="fas fa-plus"></i> Add Student
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstructorModal">
                                <i class="fas fa-plus"></i> Add Instructor
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search Box -->
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Search users by name, email, or phone..." class="form-control">
                       <!-- Filter Options -->
                      
                        </div>

                        

                        <!-- Tabs for User Types -->
                        <ul class="nav nav-tabs" id="userTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="true">Students</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="instructors-tab" data-bs-toggle="tab" data-bs-target="#instructors" type="button" role="tab" aria-controls="instructors" aria-selected="false">Instructors</button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="userTabsContent">
                            <!-- Students Tab -->
                            <div class="tab-pane fade show active" id="students" role="tabpanel" aria-labelledby="students-tab">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Course</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo $student['student_id']; ?></td>
                                                <td><?php echo $student['name']; ?></td>
                                                <td><?php echo $student['email']; ?></td>
                                                <td><?php echo $student['phone']; ?></td>
                                                <td><?php echo $student['course_enrolled']; ?></td>
                                                <td>
                                                    <button class="btn btn-primary editStudentBtn"
                                                        data-id="<?php echo $student['student_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($student['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($student['phone']); ?>"
                                                        data-address="<?php echo htmlspecialchars($student['address']); ?>"
                                                        data-dob="<?php echo htmlspecialchars($student['dob']); ?>"
                                                        data-gender="<?php echo htmlspecialchars($student['gender']); ?>"
                                                        data-course="<?php echo htmlspecialchars($student['course_enrolled']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <a href="admin_users.php?delete_student=<?php echo $student['student_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this student?');">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                    <a href="admin_user_details.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-info">
                                                        <i class="fas fa-info-circle"></i> Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Instructors Tab -->
                            <div class="tab-pane fade" id="instructors" role="tabpanel" aria-labelledby="instructors-tab">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Location</th>
                                            <th>Experience</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($instructors as $instructor): ?>
                                            <tr>
                                                <td><?php echo $instructor['instructor_id']; ?></td>
                                                <td><?php echo $instructor['name']; ?></td>
                                                <td><?php echo $instructor['email']; ?></td>
                                                <td><?php echo $instructor['phone']; ?></td>
                                                <td><?php echo $instructor['location']; ?></td>
                                                <td><?php echo $instructor['experience']; ?></td>
                                                <td>
                                                    <button class="btn btn-primary editInstructorBtn"
                                                        data-id="<?php echo $instructor['instructor_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($instructor['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($instructor['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($instructor['phone']); ?>"
                                                        data-gender="<?php echo htmlspecialchars($instructor['gender']); ?>"
                                                        data-location="<?php echo htmlspecialchars($instructor['location']); ?>"
                                                        data-experience="<?php echo htmlspecialchars($instructor['experience']); ?>"
                                                        data-certifications="<?php echo htmlspecialchars($instructor['certifications']); ?>"
                                                        data-availability="<?php echo htmlspecialchars($instructor['availability']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <a href="admin_users.php?delete_instructor=<?php echo $instructor['instructor_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this instructor?');">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                    <a href="admin_user_details.php?instructor_id=<?php echo $instructor['instructor_id']; ?>" class="btn btn-info">
                                                        <i class="fas fa-info-circle"></i> Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="add-user-form" method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="username" placeholder="Full Name" required>
                                <input type="email" name="email" placeholder="Email Address" required>
                                <input type="password" name="password" placeholder="Password" required>
                                <input type="text" name="phone" placeholder="Phone Number" required>
                            </div>
                            <div class="col-md-6">
                                <input type="date" name="dob" placeholder="Date of Birth" required>
                                <select name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                <input type="text" name="address" placeholder="Address" required>
                                <select name="course_enrolled" required>
                                    <option value="">Select Course</option>
                                    <option value="2 Wheeler">2 Wheeler</option>
                                    <option value="4 Wheeler">4 Wheeler</option>
                                    <option value="Heavy">Heavy Vehicle</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_student">Add Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="admin_dashboard.php" method="post" id="editUserForm">
                        <input type="hidden" name="student_id" id="editUserId">
                        <div class="mb-3">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" name="name" class="form-control" id="editUserName">
                        </div>
                        <div class="mb-3">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" class="form-control" id="editUserEmail">
                        </div>
                        <button type="submit" name="update_user" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Instructor Modal --> 
    <div class="modal fade" id="addInstructorModal" tabindex="-1" aria-labelledby="addInstructorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-lg"> 
            <div class="modal-content"> 
                <div class="modal-header"> 
                    <h5 class="modal-title" id="addInstructorModalLabel"><i class="fas fa-chalkboard-teacher"></i> Add New Instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="instructorName" class="form-label"><i class="fas fa-user"></i> Full Name</label>
                                    <input type="text" class="form-control" id="instructorName" name="instructor_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="instructorEmail" class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                                    <input type="email" class="form-control" id="instructorEmail" name="instructor_email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="instructorPhone" class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                                    <input type="text" class="form-control" id="instructorPhone" name="instructor_phone">
                                </div>
                                <div class="mb-3">
                                    <label for="instructorPassword" class="form-label"><i class="fas fa-lock"></i> Password</label>
                                    <input type="password" class="form-control" id="instructorPassword" name="instructor_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="instructorSpecialization" class="form-label"><i class="fas fa-certificate"></i> Specialization</label>
                                    <input type="text" class="form-control" id="instructorSpecialization" name="instructor_specialization">
                                </div>
                                <div class="mb-3">
                                    <label for="instructorExperience" class="form-label"><i class="fas fa-briefcase"></i> Years of Experience</label>
                                    <input type="number" class="form-control" id="instructorExperience" name="instructor_experience" min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="instructorBio" class="form-label"><i class="fas fa-user-circle"></i> Biography</label>
                                    <textarea class="form-control" id="instructorBio" name="instructor_bio" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="instructorImage" class="form-label"><i class="fas fa-image"></i> Profile Image</label>
                                    <input type="file" class="form-control" id="instructorImage" name="instructor_image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-car"></i> License Types</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="licenseA" name="license_types[]" value="A">
                                    <label class="form-check-label" for="licenseA">Type A</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="licenseB" name="license_types[]" value="B">
                                    <label class="form-check-label" for="licenseB">Type B</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="licenseC" name="license_types[]" value="C">
                                    <label class="form-check-label" for="licenseC">Type C</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="licenseD" name="license_types[]" value="D">
                                    <label class="form-check-label" for="licenseD">Type D</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="licenseE" name="license_types[]" value="E">
                                    <label class="form-check-label" for="licenseE">Type E</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_instructor" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Add Instructor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $(".editBtn").click(function () {
                $("#editUserId").val($(this).data("id"));
                $("#editUserName").val($(this).data("name"));
                $("#editUserEmail").val($(this).data("email"));
                $("#editUserModal").modal("show");
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>