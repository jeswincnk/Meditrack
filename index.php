<?php
session_start();
include("database.php");

// Get all departments from doctors
$departments_query = "SELECT DISTINCT Department FROM doctorreg ORDER BY Department";
$departments_result = $conn->query($departments_query);
$departments = [];
while($row = $departments_result->fetch_assoc()) {
    $departments[] = $row['Department'];
}

// Get all doctors with their departments
$doctors_query = "SELECT * FROM doctorreg ORDER BY Department, Name";
$doctors_result = $conn->query($doctors_query);
$doctors = [];
while($row = $doctors_result->fetch_assoc()) {
    $doctors[] = $row;
}

// Handle appointment booking
$booking_message = '';
if(isset($_POST['submit']) && isset($_SESSION['patient_id'])) {
    $patient_id = $_SESSION['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $date = $_POST['date'];
    $department = $_POST['department'];
    $message = $_POST['message'];
    
    // Get doctor info
    $stmt = $conn->prepare("SELECT Name FROM doctorreg WHERE Doctor_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    $doctor_info = $doctor_result->fetch_assoc();
    $stmt->close();
    
    // Insert booking
    $stmt = $conn->prepare("INSERT INTO booking (Patient_id, Doctor_id, Name, Email, Age, Gender, Phone_no, Select_date, Select_department, Additional_message, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("iississsss", $patient_id, $doctor_id, $name, $email, $age, $gender, $phone, $date, $department, $message);
    
    if($stmt->execute()) {
        $booking_message = '<div class="alert alert-success">Appointment booked successfully! We will contact you soon.</div>';
    } else {
        $booking_message = '<div class="alert alert-danger">Error booking appointment. Please try again.</div>';
    }
    $stmt->close();
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
     
     <style>
        .department-section {
            margin: 40px 0;
            padding: 30px 0;
            background: #f8f9fa;
        }
        
        .doctor-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
        }
        
        .doctor-info h4 {
            color: #f35525;
            margin-bottom: 10px;
        }
        
        .doctor-info p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .book-btn {
            background: #f35525;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .book-btn:hover {
            background: #d63384;
        }
        
        .department-title {
            color: #f35525;
            border-bottom: 2px solid #f35525;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
                 .appointment-form {
             background: white;
             padding: 30px;
             border-radius: 10px;
             box-shadow: 0 4px 15px rgba(0,0,0,0.1);
         }
         
         .appointment-form .form-control {
             margin-bottom: 15px;
             border-radius: 5px;
             border: 1px solid #ddd;
             padding: 10px 15px;
         }
         
         .appointment-form .form-control:focus {
             border-color: #f35525;
             box-shadow: 0 0 0 0.2rem rgba(243, 85, 37, 0.25);
         }
         
         .appointment-form label {
             font-weight: 600;
             color: #333;
             margin-bottom: 5px;
         }
         
         .appointment-form .btn {
             background: #f35525;
             border: none;
             padding: 12px 30px;
             border-radius: 5px;
             font-weight: 600;
             transition: all 0.3s ease;
         }
         
         .appointment-form .btn:hover {
             background: #d63384;
             transform: translateY(-2px);
         }
         
         .appointment-form .section-title {
             margin-bottom: 30px;
         }
         
         .appointment-form .wow {
             margin-bottom: 20px;
         }
        
        .login-required {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        
        .alert {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
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

                    <!-- lOGO TEXT HERE -->
                    <a href="index.php" class="navbar-brand"><i class="fa fa-h-square"></i>MEDI TRACK</a>
               </div>

               <!-- MENU LINKS -->
               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                         <li><a href="#top" class="smoothScroll">Home</a></li>
                         <li><a href="#about" class="smoothScroll">About Us</a></li>
                         <li><a href="#team" class="smoothScroll">Doctors</a></li>
                         <li><a href="#appointment" class="smoothScroll">Book Appointment</a></li>
                         <li><a href="#google-map" class="smoothScroll">Contact</a></li>
                         <?php
              if (isset($_SESSION['patient_id'])):
              ?>
                 <li><a href="profile.php" style="color: #f35525;">Welcome, <?php echo $_SESSION['patient_name']; ?></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out"></i> Sign out</a></li>
              <?php
              elseif (isset($_SESSION['doctor_id'])):
              ?>
                 <li><a href="doctor_dashboard.php" style="color: #f35525;">Dr. <?php echo $_SESSION['doctor_name']; ?></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out"></i> Sign out</a></li>
              <?php
              elseif (isset($_SESSION['admin_id'])):
              ?>
                 <li><a href="admin_dashboard.php" style="color: #f35525;">Admin Panel</a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out"></i> Sign out</a></li>
              <?php
              else:
              ?>
                <li class="appointment-btn"><a href="login.php">Login</a></li>
                <li class="appointment-btn"><a href="register.php">Register</a></li>
              <?php
              endif;
              ?>
                         
                    </ul>
               </div>

          </div>
     </section>


       <!-- HOME -->
     <section id="home" class="slider" data-stellar-background-ratio="0.5">
          <div class="container">
               <div class="row">

                         <div class="owl-carousel owl-theme">
                              <div class="item item-first">
                                   <div class="caption">
                                        <div class="col-md-offset-1 col-md-10">
                                             <h3>Let's make your life happier</h3>
                                             <h1>Healthy Living</h1>
                                             <a href="#team" class="section-btn btn btn-default smoothScroll">Meet Our Doctors</a>
                                        </div>
                                   </div>
                              </div>

                              <div class="item item-second">
                                   <div class="caption">
                                        <div class="col-md-offset-1 col-md-10">
                                             <h3>Aenean luctus lobortis tellus</h3>
                                             <h1>New Lifestyle</h1>
                                             <a href="#about" class="section-btn btn btn-default btn-gray smoothScroll">More About Us</a>
                                        </div>
                                   </div>
                              </div>

                              <div class="item item-third">
                                   <div class="caption">
                                        <div class="col-md-offset-1 col-md-10">
                                             <h3>Pellentesque nec libero nisi</h3>
                                             <h1>Your Health Benefits</h1>
                                             <a href="#appointment" class="section-btn btn btn-default btn-blue smoothScroll">Book Appointment</a>
                                        </div>
                                   </div>
                              </div>
                         </div>

               </div>
          </div>
     </section>


     <!-- ABOUT -->
     <section id="about">
          <div class="container">
               <div class="row">

                    <div class="col-md-6 col-sm-6">
                         <div class="about-info">
                              <h2 class="wow fadeInUp" data-wow-delay="0.6s">WELCOME TO MEDITRACK YOUR PARTNER IN WELLNESS</h2>
                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <p>At MEDI TRACK,We believe in smarter,simpler healthcare.</p>
                                   <p>Our goal is to help you stay healthy by tracking your wellness every step of the way.</p>
                                   <p>With expert care,real-time health insights,and a commitment to your well-being.</p>
                                   <p>MEDI TRACK is your trusted partner in better living.</p>
                              </div>
                              <figure class="profile wow fadeInUp" data-wow-delay="1s">
                                   <img src="images/author-image.jpg" class="img-responsive" alt="">
                                   <figcaption>
                                        <h3>Dr.john Abraham</h3>
                                        <p>Medical Director</p>
                                   </figcaption>
                              </figure>
                         </div>
                    </div>
                    
               </div>
          </div>
     </section>


     <!-- TEAM -->
     <section id="team" data-stellar-background-ratio="1">
          <div class="container">
               <div class="row">

                    <div class="col-md-12 col-sm-12">
                         <div class="about-info">
                           <h2 class="wow fadeInUp" data-wow-delay="0.1s">Our Doctors</h2>
                           <p class="wow fadeInUp" data-wow-delay="0.3s">Meet our expert doctors across various departments</p>
                         </div>
                    </div>

                    <div class="clearfix"></div>

                    <?php if(empty($doctors)): ?>
                        <div class="col-md-12">
                            <div class="text-center">
                                <h3>No doctors available at the moment</h3>
                                <p>Please check back later or contact us for more information.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php 
                        $current_department = '';
                        foreach($doctors as $doctor): 
                            if($doctor['Department'] != $current_department):
                                if($current_department != '') echo '</div></div>'; // Close previous department
                                $current_department = $doctor['Department'];
                        ?>
                            <div class="department-section">
                                <div class="container">
                                    <h3 class="department-title wow fadeInUp" data-wow-delay="0.2s"><?php echo htmlspecialchars($doctor['Department']); ?></h3>
                                    <div class="row">
                        <?php endif; ?>
                        
                        <div class="col-md-4 col-sm-6">
                             <div class="team-thumb wow fadeInUp" data-wow-delay="0.2s">
                                  <img src="images/team-image1.jpg" class="img-responsive" alt="">

                                       <div class="team-info">
                                            <h3><?php echo htmlspecialchars($doctor['Name']); ?></h3>
                                            <p><?php echo htmlspecialchars($doctor['Department']); ?></p>
                                            <div class="team-contact-info">
                                                 <p><i class="fa fa-phone"></i> <?php echo htmlspecialchars($doctor['Phone_no']); ?></p>
                                                 <p><i class="fa fa-envelope-o"></i> <a href="mailto:<?php echo htmlspecialchars($doctor['Email']); ?>"><?php echo htmlspecialchars($doctor['Email']); ?></a></p>
                                                 <p><i class="fa fa-user"></i> <?php echo htmlspecialchars($doctor['Age']); ?> years, <?php echo htmlspecialchars($doctor['Gender']); ?></p>
                                            </div>
                                            <?php if(isset($_SESSION['patient_id'])): ?>
                                                <button class="book-btn" onclick="bookAppointment(<?php echo $doctor['Doctor_id']; ?>, '<?php echo htmlspecialchars($doctor['Name']); ?>', '<?php echo htmlspecialchars($doctor['Department']); ?>')">
                                                    <i class="fa fa-calendar"></i> Book Appointment
                                                </button>
                                            <?php else: ?>
                                                <p style="color: #f35525; font-weight: bold;">Login to book appointment</p>
                                            <?php endif; ?>
                                       </div>
                             </div>
                        </div>
                        
                        <?php endforeach; ?>
                        <?php if($current_department != ''): ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
               </div>
          </div>
     </section>
     
     <!-- MAKE AN APPOINTMENT -->
     <section id="appointment" data-stellar-background-ratio="3">
          <div class="container">
               <div class="row">

                    <div class="col-md-6 col-sm-6">
                         <img src="images/appointment-image.jpg" class="img-responsive" alt="">
                    </div>

                    <div class="col-md-6 col-sm-6">
                         <!-- CONTACT FORM HERE -->
                         <?php if(isset($_SESSION['patient_id'])): ?>
                            <?php echo $booking_message; ?>
                            <form id="appointment-form" role="form" method="post" action="#appointment" class="appointment-form">

                                 <!-- SECTION TITLE -->
                                 <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                      <h2>Make an appointment</h2>
                                 </div>

                                 <div class="wow fadeInUp" data-wow-delay="0.8s">
                                      <div class="row">
                                           <div class="col-md-6 col-sm-6">
                                                <label for="name">Name</label>
                                                <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" value="<?php echo isset($_SESSION['patient_name']) ? htmlspecialchars($_SESSION['patient_name']) : ''; ?>" required>
                                           </div>

                                           <div class="col-md-6 col-sm-6">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required>
                                           </div>
                                      </div>

                                      <div class="row">
                                           <div class="col-md-6 col-sm-6">
                                                <label for="age">Age</label>
                                                <input type="number" class="form-control" id="age" name="age" placeholder="Your Age" value="<?php echo isset($_SESSION['patient_age']) ? $_SESSION['patient_age'] : ''; ?>" required>
                                           </div>

                                           <div class="col-md-6 col-sm-6">
                                                <label for="gender">Gender</label>
                                                <select class="form-control" id="gender" name="gender" required>
                                                     <option value="">Select Gender</option>
                                                     <option value="Male" <?php echo (isset($_SESSION['patient_gender']) && $_SESSION['patient_gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                     <option value="Female" <?php echo (isset($_SESSION['patient_gender']) && $_SESSION['patient_gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                     <option value="Other" <?php echo (isset($_SESSION['patient_gender']) && $_SESSION['patient_gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                           </div>
                                      </div>

                                      <div class="row">
                                           <div class="col-md-6 col-sm-6">
                                                <label for="date">Select Date</label>
                                                <input type="date" name="date" value="" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                           </div>

                                           <div class="col-md-6 col-sm-6">
                                                <label for="doctor_id">Select Doctor</label>
                                                <select class="form-control" id="doctor_id" name="doctor_id" required>
                                                     <option value="">Select Doctor</option>
                                                     <?php foreach($doctors as $doctor): ?>
                                                         <option value="<?php echo $doctor['Doctor_id']; ?>" data-department="<?php echo htmlspecialchars($doctor['Department']); ?>">
                                                             Dr. <?php echo htmlspecialchars($doctor['Name']); ?> - <?php echo htmlspecialchars($doctor['Department']); ?>
                                                         </option>
                                                     <?php endforeach; ?>
                                                </select>
                                           </div>
                                      </div>

                                      <div class="row">
                                           <div class="col-md-6 col-sm-6">
                                                <label for="department">Department</label>
                                                <input type="text" class="form-control" id="department" name="department" readonly>
                                           </div>

                                           <div class="col-md-6 col-sm-6">
                                                <label for="phone">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone" value="<?php echo isset($_SESSION['patient_phone']) ? $_SESSION['patient_phone'] : ''; ?>" required>
                                           </div>
                                      </div>

                                      <div class="row">
                                           <div class="col-md-12 col-sm-12">
                                                <label for="message">Additional Message</label>
                                                <textarea class="form-control" rows="5" id="message" name="message" placeholder="Message"></textarea>
                                           </div>
                                      </div>

                                      <div class="row">
                                           <div class="col-md-12 col-sm-12">
                                                <button type="submit" class="btn btn-primary form-control" id="cf-submit" name="submit">
                                                     <i class="fa fa-calendar"></i> Book Appointment
                                                </button>
                                           </div>
                                      </div>
                                 </div>
                           </form>
                         <?php else: ?>
                            <div class="login-required">
                                <h3><i class="fa fa-lock"></i> Login Required</h3>
                                <p>Please login to book an appointment with our doctors.</p>
                                <a href="login.php" class="btn btn-primary">Login</a>
                                <a href="register.php" class="btn btn-success">Register</a>
                            </div>
                         <?php endif; ?>
                    </div>

               </div>
          </div>
     </section>


     <!-- GOOGLE MAP -->
     <section id="google-map">
     <!-- How to change your own map point
            1. Go to Google Maps
            2. Click on your location point
            3. Click "Share" and choose "Embed map" tab
            4. Copy only URL and paste it within the src="" field below
	-->
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3647.3030413476204!2d100.5641230193719!3d13.757206847615207!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xf51ce6427b7918fc!2sG+Tower!5e0!3m2!1sen!2sth!4v1510722015945" width="100%" height="350" frameborder="0" style="border:0" allowfullscreen></iframe>
     </section>           


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
     
     <script>
        // Auto-fill department when doctor is selected
        document.getElementById('doctor_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const department = selectedOption.getAttribute('data-department');
            document.getElementById('department').value = department;
        });
        
        // Function to book appointment from doctor card
        function bookAppointment(doctorId, doctorName, department) {
            document.getElementById('doctor_id').value = doctorId;
            document.getElementById('department').value = department;
            
            // Scroll to appointment form
            document.getElementById('appointment').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }
     </script>

</body>
</html>