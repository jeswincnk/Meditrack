<?php
session_start();
include 'database.php';

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Get patient information from database
$patient_id = $_SESSION['patient_id'];
$stmt = $conn->prepare("SELECT * FROM register WHERE Patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// Get patient's booking history
$stmt = $conn->prepare("SELECT b.*, d.Name as DoctorName, d.Department 
                        FROM booking b 
                        LEFT JOIN doctorreg d ON b.Doctor_id = d.Doctor_id 
                        WHERE b.Patient_id = ? 
                        ORDER BY b.Select_date DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while($row = $bookings_result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

// Get patient's prescriptions
$stmt = $conn->prepare("SELECT p.*, d.Name as DoctorName 
                        FROM prescription p 
                        LEFT JOIN doctorreg d ON p.Doctor_id = d.Doctor_id 
                        WHERE p.Patient_id = ? 
                        ORDER BY p.Date_issued DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$prescriptions_result = $stmt->get_result();
$prescriptions = [];
while($row = $prescriptions_result->fetch_assoc()) {
    $prescriptions[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Patient Profile - MEDI TRACK</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="Tooplate">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/tooplate-style.css">
    
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #f35525, #d63384);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            margin: 0 auto 20px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #f35525;
        }

        .profile-info {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            padding: 30px;
        }

        .left-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
        }

        .right-section {
            padding: 25px;
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #333;
        }

        .info-value {
            color: #666;
        }

        .section-title {
            color: #f35525;
            border-bottom: 2px solid #f35525;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #f35525;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .booking-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            margin-top: 20px;
        }

        .btn-custom {
            background: #f35525;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .btn-custom:hover {
            background: #d63384;
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #f35525;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #f35525;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .navbar-nav .nav-link {
            color: #333 !important;
        }

        .navbar-nav .nav-link:hover {
            color: #f35525 !important;
        }
    </style>
</head>
<body id="top" data-spy="scroll" data-target=".navbar-collapse" data-offset="50">

    <!-- PRE LOADER -->
    <section class="preloader">
        <div class="spinner">
            <span class="spinner-rotate"></span>
        </div>
    </section>

    <!-- HEADER -->
    <header>
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-3">
                    <p>Welcome to a Professional medical tracking</p>
                </div>
                <div class="col-md-8 col-sm-9 text-align-right">
                    <span class="phone-icon"><i class="fa fa-phone"></i> 6235388392</span>
                    <span class="date-icon"><i class="fa fa-calendar-plus-o"></i> 8:00 AM - 10:00 PM (Mon-sat)</span>
                    <span class="email-icon"><i class="fa fa-envelope-o"></i> <a href="#">medistrack@gmail.com</a></span>
                </div>
            </div>
        </div>
    </header>

    <!-- MENU -->
    <section class="navbar navbar-default navbar-static-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon icon-bar"></span>
                    <span class="icon icon-bar"></span>
                    <span class="icon icon-bar"></span>
                </button>
                <a href="index.php" class="navbar-brand"><i class="fa fa-h-square"></i>MEDI TRACK</a>
            </div>

            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#team">Doctors</a></li>
                    <li><a href="index.php#appointment">Book Appointment</a></li>
                    <li><a href="#" style="color: #f35525;">Welcome, <?php echo htmlspecialchars($patient['Name']); ?></a></li>
                    <li><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </section>

    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fa fa-user"></i>
            </div>
            <h2><?php echo htmlspecialchars($patient['Name']); ?></h2>
            <p>Patient ID: <?php echo $patient['Patient_id']; ?></p>
        </div>

        <!-- Statistics -->
        <div style="padding: 30px 30px 0 30px;">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($bookings); ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($bookings, function($b) { return $b['Status'] == 'Confirmed'; })); ?></div>
                    <div class="stat-label">Confirmed Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($prescriptions); ?></div>
                    <div class="stat-label">Prescriptions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $patient['Age']; ?></div>
                    <div class="stat-label">Age</div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="profile-info">
            <!-- Left Section - Personal Info -->
            <div class="left-section">
                <h3 class="section-title">Personal Information</h3>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['Name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['Email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?php echo $patient['Age']; ?> years</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['Gender']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['Phone_no']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Blood Group:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['Blood_group']); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="index.php#appointment" class="btn-custom">
                        <i class="fa fa-calendar"></i> Book New Appointment
                    </a>
                    <a href="index.php" class="btn-custom btn-secondary">
                        <i class="fa fa-home"></i> Back to Home
                    </a>
                </div>
            </div>

            <!-- Right Section - Appointments & Prescriptions -->
            <div class="right-section">
                <!-- Recent Appointments -->
                <h3 class="section-title">Recent Appointments</h3>
                <?php if(empty($bookings)): ?>
                    <div class="info-card">
                        <p>No appointments found. <a href="index.php#appointment">Book your first appointment</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach(array_slice($bookings, 0, 5) as $booking): ?>
                        <div class="booking-card">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5><i class="fa fa-calendar"></i> Appointment with Dr. <?php echo htmlspecialchars($booking['DoctorName']); ?></h5>
                                    <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($booking['Select_date'])); ?></p>
                                    <p><strong>Department:</strong> <?php echo htmlspecialchars($booking['Select_department']); ?></p>
                                    <?php if(!empty($booking['Additional_message'])): ?>
                                        <p><strong>Message:</strong> <?php echo htmlspecialchars($booking['Additional_message']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-right">
                                    <span class="booking-status status-<?php echo strtolower($booking['Status']); ?>">
                                        <?php echo htmlspecialchars($booking['Status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Recent Prescriptions -->
                <h3 class="section-title">Recent Prescriptions</h3>
                <?php if(empty($prescriptions)): ?>
                    <div class="info-card">
                        <p>No prescriptions found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach(array_slice($prescriptions, 0, 3) as $prescription): ?>
                        <div class="booking-card">
                            <h5><i class="fa fa-prescription"></i> Prescription by Dr. <?php echo htmlspecialchars($prescription['DoctorName']); ?></h5>
                            <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($prescription['Date_issued'])); ?></p>
                            <p><strong>Medicine:</strong> <?php echo htmlspecialchars($prescription['Medicine_name']); ?></p>
                            <p><strong>Dosage:</strong> <?php echo htmlspecialchars($prescription['Dosage']); ?></p>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($prescription['Duration']); ?></p>
                            <?php if(!empty($prescription['Instructions'])): ?>
                                <p><strong>Instructions:</strong> <?php echo htmlspecialchars($prescription['Instructions']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer data-stellar-background-ratio="5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-4">
                    <div class="footer-thumb"> 
                        <h4 class="wow fadeInUp" data-wow-delay="0.4s">Contact Info</h4>
                        <p>Focus on free-flowing connections,with strong attraction and elegant direction.take care to balance pain with top-tier results.</p>
                        <div class="contact-info">
                            <p><i class="fa fa-phone"></i> 6235388392</p>
                            <p><i class="fa fa-envelope-o"></i> <a href="#">meditrack@gmail.com</a></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-4"> 
                    <div class="footer-thumb"> 
                        <h4 class="wow fadeInUp" data-wow-delay="0.4s">Latest News</h4>
                        <div class="latest-stories">
                            <div class="stories-image">
                                <a href="#"><img src="images/news-image.jpg" class="img-responsive" alt=""></a>
                            </div>
                            <div class="stories-info">
                                <a href="#"><h5>Amazing Technology</h5></a>
                                <span>March 08, 2020</span>
                            </div>
                        </div>
                        <div class="latest-stories">
                            <div class="stories-image">
                                <a href="#"><img src="images/news-image.jpg" class="img-responsive" alt=""></a>
                            </div>
                            <div class="stories-info">
                                <a href="#"><h5>New Healing Process</h5></a>
                                <span>February 20, 2020</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-4"> 
                    <div class="footer-thumb">
                        <div class="opening-hours">
                            <h4 class="wow fadeInUp" data-wow-delay="0.4s">Opening Hours</h4>
                            <p>Monday - Friday <span>08:00 AM - 10:00 PM</span></p>
                            <p>Saturday <span>09:00 AM - 08:00 PM</span></p>
                            <p>Sunday <span>Closed</span></p>
                        </div> 
                        <ul class="social-icon">
                            <li><a href="#" class="fa fa-facebook-square" attr="facebook icon"></a></li>
                            <li><a href="#" class="fa fa-twitter"></a></li>
                            <li><a href="#" class="fa fa-instagram"></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-12 col-sm-12 border-top">
                    <div class="col-md-4 col-sm-6">
                        <div class="copyright-text"> 
                            <p>Copyright &copy; 2020 Your Company | Design: <a rel="nofollow" href="https://www.facebook.com/tooplate" target="_parent">Tooplate</a></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="footer-link"> 
                            <a href="#">Laboratory Tests</a>
                            <a href="#">Departments</a>
                            <a href="#">Insurance Policy</a>
                            <a href="#">Careers</a>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-2 text-align-center">
                        <div class="angle-up-btn"> 
                            <a href="#top" class="smoothScroll wow fadeInUp" data-wow-delay="1.2s"><i class="fa fa-angle-up"></i></a>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </footer>

    <!-- SCRIPTS -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/jquery.stellar.min.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/smoothscroll.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/custom.js"></script>

</body>
</html>