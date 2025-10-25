<?php
session_start();
include('database.php'); // Ensure this file contains the database connection setup

if (isset($_POST['submit'])) {
    $email = $_POST['name'];
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    $error = "";

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Based on user type selection
    if ($userType == "Admin") {
        // Check if user is an admin
        $admin_query = "SELECT * FROM admin WHERE username = ? LIMIT 1";
        $stmt_admin = $conn->prepare($admin_query);

        if (!$stmt_admin) {
            die("SQL Error (Admin Check): " . $conn->error);
        }

        $stmt_admin->bind_param("s", $email);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();
        $admin = $result_admin->fetch_assoc();
        $stmt_admin->close();

        if ($admin && $password === $admin['password']) { 
            // If admin credentials match, redirect to admin dashboard
            $_SESSION['admin_id'] = $admin['adminId'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid admin credentials!";
        }
    } elseif ($userType == "Doctor") {
        // Check if user is a doctor
        $doctor_query = "SELECT * FROM doctorreg WHERE Email = ? LIMIT 1";
        $stmt_doctor = $conn->prepare($doctor_query);

        if (!$stmt_doctor) {
            die("SQL Error (Doctor Check): " . $conn->error);
        }

        $stmt_doctor->bind_param("s", $email);
        $stmt_doctor->execute();
        $result_doctor = $stmt_doctor->get_result();
        $doctor = $result_doctor->fetch_assoc();
        $stmt_doctor->close();

        if (!$doctor) {
            $error = "No doctor account found with this email.";
        } elseif (!password_verify($password, $doctor['Password'])) {
            $error = "Incorrect password!";
        } else {
            $_SESSION['doctor_id'] = $doctor['Doctor_id'];
            $_SESSION['doctor_name'] = $doctor['Name'];
            $_SESSION['doctor_email'] = $doctor['Email'];
            $_SESSION['user_type'] = 'doctor';
            $_SESSION['login'] = true;
            header("Location: doctor_dashboard.php"); // Redirect doctors to their dashboard
            exit();
        }
    } else {
        // Default to patient login
        $query = "SELECT * FROM register WHERE Email = ? LIMIT 1";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            die("SQL Error (Patient Check): " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = "No patient account found with this email.";
        } elseif (!password_verify($password, $user['Password'])) {
            $error = "Incorrect password!";
        } else {
            $_SESSION['patient_id'] = $user['Patient_id'];
            $_SESSION['patient_name'] = $user['Name'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['patient_age'] = $user['Age'];
            $_SESSION['patient_gender'] = $user['Gender'];
            $_SESSION['patient_phone'] = $user['Phone_no'];
            $_SESSION['patient_blood_group'] = $user['Blood_group'];
            $_SESSION['user_type'] = 'patient';
            $_SESSION['login'] = true;
            header("Location: profile.php"); // Redirect patients to their profile page
            exit();
        }
    }
    
    // If we get here, there was an error
    if ($error) {
        echo "<script>alert('$error');</script>";
    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <title>MEDI TRACK</title>
<!--

Template 2098 Health

http://www.tooplate.com/view/2098-health

-->
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

     <!-- MAIN CSS -->
     <link rel="stylesheet" href="css/tooplate-style.css">

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

                    <!-- lOGO TEXT HERE -->
                    <a href="index.php" class="navbar-brand"><i class="fa fa-h-square"></i>MEDI TRACK</a>
               </div>

               <!-- MENU LINKS -->
               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                         <li><a href="index.php" class="smoothScroll">Home</a></li>
                         <li><a href="#about" class="smoothScroll">About Us</a></li>
                         <li><a href="#team" class="smoothScroll">Doctors</a></li>
                         <li><a href="#google-map" class="smoothScroll">Contact</a></li>
                         
                    </ul>
               </div>

          </div>
     </section>

     <section id="login" data-stellar-background-ratio="3">
          <div class="container">
               <div class="row">

                    <div class="col-md-6 col-sm-6">
                         <img src="images/appointment-image.jpg" class="img-responsive" alt="">
                    </div>

                    <div class="col-md-6 col-sm-6">
                         <!-- CONTACT FORM HERE -->
                         <form  role="form" method="post" action="login.php">

                              <!-- SECTION TITLE -->
                              <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                   <h2>Login</h2>
                              </div>

                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <div class="col-md-6 col-sm-6">
                                        <label for="name">Email/Username</label>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Email or Username" required>
                                   </div>

                                   <div class="col-md-6 col-sm-6">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Your password" required>
                                   </div>


                                   <div class="col-md-6 col-sm-6">
                                        <label for="user_type">Login as</label>
                                        <select class="form-control" name="user_type" id="user_type">
                                             <option value="Admin">Admin</option>
                                             <option value="Doctor">Doctor</option>
                                             <option value="Patient" selected>Patient</option>
                                        </select>
                                   </div>

                                   <div class="col-md-12 col-sm-12">
                                        <button type="submit" class="form-control" id="cf-submit" name="submit">Login</button>
                                        <a href="register.php">Don't have an account yet?sign up</a>
                                   </div>
                              </div>
                        </form>
                    </div>

               </div>
          </div>
     </section>

     
     <!-- FOOTER -->
     <footer data-stellar-background-ratio="5">
          <div class="container">
               <div class="row">

                    <div class="col-md-4 col-sm-4">
                         <div class="footer-thumb"> 
                              <h4 class="wow fadeInUp" data-wow-delay="0.4s">Contact Info</h4>
                              <p>Fusce at libero iaculis, venenatis augue quis, pharetra lorem. Curabitur ut dolor eu elit consequat ultricies.</p>

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
                                   <p>Copyright &copy; 2020 Your Company 
                                   
                                   | Design: <a rel="nofollow" href="https://www.facebook.com/tooplate" target="_parent">Tooplate</a></p>
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