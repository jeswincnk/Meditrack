<?php
include 'database.php';
// Adding a new patient
if (isset($_POST['submit'])) {
    $username = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['cnfpassword'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $blood = $_POST['blood'];
    
    // Form validation
    $errors = [];
    
    // Check if fields are empty
    if(empty($username)) {
        $errors[] = "Name is required";
    }
    
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if(empty($password)) {
        $errors[] = "Password is required";
    }
    
    if($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if(empty($age) || !is_numeric($age)) {
        $errors[] = "Valid age is required";
    }
    
    if(empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT * FROM register WHERE Email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();
    if($result->num_rows > 0) {
        $errors[] = "Email already registered";
    }
    $checkEmail->close();
    
    // If no errors, proceed with registration
    if(empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Secure password
        
        $stmt = $conn->prepare("INSERT INTO register (Name, Email, Age, Gender, Phone_no, Blood_group, Password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $age, $gender, $phone, $blood, $hashedPassword);
        
        if($stmt->execute()) {
            echo "<script>alert('Patient registered successfully!'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Registration failed. Please try again.');</script>";
        }
        $stmt->close();
    } else {
        // Display errors
        echo "<script>alert('".implode("\\n", $errors)."');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <title>MEDI TRACK</title>



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
                         <form  role="form" method="post" action="register.php">

                              <!-- SECTION TITLE -->
                              <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                   <h2>Register</h2>
                              </div>

                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <div class="col-md-6 col-sm-6">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Full Name">
                                   </div>

                                   <div class="col-md-6 col-sm-6">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Your Email">
                                   </div>

                                   <div class="col-md-6 col-sm-6">
                                        <label for="age">Age</label>
                                        <input type="number" name="age" value="" class="form-control" placeholder="Your Age" required>
                                   </div>

                                   <div class="col-md-6 col-sm-6">
                                        <label for="gender">Gender</label>
                                        <select class="form-control" name="gender" required>
                                             <option value="">Select Gender</option>
                                             <option value="Male">Male</option>
                                             <option value="Female">Female</option>
                                             <option value="Other">Other</option>
                                        </select>
                                   </div>
                                   <div class="col-md-6 col-sm-6">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number" required>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <label for="blood">Blood Group</label>
                                        <select class="form-control" name="blood" required>
                                             <option value="">Select Blood Group</option>
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
                                    <div class="col-md-6 col-sm-6">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    </div>
                                     <div class="col-md-6 col-sm-6">
                                        <label for="cnfpassword">Confirm Password</label>
                                        <input type="password" class="form-control" id="cnfpassword" name="cnfpassword" placeholder="Confirm Password" required>
                                    </div>

                                   <div class="col-md-12 col-sm-12">
                                        <button type="submit" class="form-control" id="cf-submit" name="submit">Register</button>
                                        <div style="text-align: center; margin-top: 15px;">
                                            <a href="login.php">Already have an account? Sign in</a>
                                        </div>
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