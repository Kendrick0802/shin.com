<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$hostname = "localhost";
$username = "root";
$password = "";
$database = "med";

$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Signup
if(isset($_POST['signup'])) {
  try {
      $name = $conn->real_escape_string($_POST['name']);
      $email = $conn->real_escape_string($_POST['email']);
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
      
      // Check if email already exists
      $check_query = "SELECT * FROM users WHERE email=?";
      $check_stmt = $conn->prepare($check_query);
      $check_stmt->bind_param("s", $email);
      $check_stmt->execute();
      $check_result = $check_stmt->get_result();
      
      if($check_result->num_rows > 0) {
          echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
          exit();
      }
      
      // Insert new user
      $insert_query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
      $insert_stmt = $conn->prepare($insert_query);
      $insert_stmt->bind_param("sss", $name, $email, $password);
      
      if($insert_stmt->execute()) {
          echo json_encode(['status' => 'success']);
          exit();
      } else {
          throw new Exception($conn->error);
      }
  } catch (Exception $e) {
      error_log("Registration Error: " . $e->getMessage());
      echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
      exit();
  }
}


// Handle Login
if(isset($_POST['login'])) {
  $email = $conn->real_escape_string($_POST['email']);
  $password = $_POST['password'];
  
  $query = "SELECT * FROM users WHERE email = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if($result->num_rows == 1) {
      $user = $result->fetch_assoc();
      if(password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['name'] = $user['name'];
          echo json_encode(['status' => 'success', 'user_id' => $user['id']]);
          exit();
      }
  }
  echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoHealth Connect - Sustainable Telemedicine Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        :root {
            --primary-color: #2E7D32;
            --secondary-color: #4CAF50;
            --accent-color: #81C784;
            --text-color: #1B5E20;
            --background-color: #E8F5E9;
        }

        body {
            background-color: var(--background-color);
        }

        header {
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            align-items: center;
        }

        .nav-links li {
            margin-left: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            position: relative;
            padding-bottom: 3px;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            left: 0;
            bottom: 0;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .login-btn, .signup-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .login-btn {
            background-color: transparent;
            color: var(--primary-color);
        }

        .signup-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 7rem auto 2rem;
            padding: 0 5%;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .hero-content {
            flex: 1;
            padding: 2rem;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .hero-content p {
            color: var(--text-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .cta-btn {
            padding: 1rem 2rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cta-btn:hover {
            background-color: var(--secondary-color);
        }

        .features {
            padding: 4rem 5%;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .features h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .feature-card {
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 2px solid var(--accent-color);
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--text-color);
            line-height: 1.6;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 2000;
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2.5rem;
            width: 90%;
            max-width: 400px;
            border-radius: 15px;
            position: relative;
            border: 2px solid var(--accent-color);
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        form input {
    width: 100%;
    padding: 1rem 1rem 1rem 2.8rem;
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    background-color: white;
    position: relative;
    z-index: 1;
}

.form-group {
    margin-bottom: 1.2rem;
    position: relative;
    z-index: 2;
}

.form-group i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--secondary-color);
    z-index: 3;
}
        form input {
            width: 100%;
            padding: 1rem 1rem 1rem 2.8rem;
            border: 2px solid var(--accent-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        form input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        form button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            margin-top: 1rem;
        }

        form button:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                text-align: center;
            }

            .hero-content {
                padding: 2rem 1rem;
            }

            .nav-links {
                display: none;
            }

            .hamburger {
                display: block;
            }
        }
    </style>
</head>
<body>
    <header>
        
        <nav>
            <div class="logo">
                <h1>EcoHealth Connect</h1>
            </div>
            <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
              <li><a href="walaser.html">Services</a></li>
              <li><a href="walangab.html">About</a></li>
              
                <li><button class="login-btn" onclick="openLoginModal()">Login</button></li>
                <li><button class="signup-btn" onclick="openSignupModal()">Sign Up</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Sustainable Healthcare for a Better Tomorrow</h1>
                <p>Join our eco-friendly telemedicine platform. Connect with environmentally conscious healthcare providers while reducing your carbon footprint.</p>
                
            </div>
            
        </section>

        <section class="features">
            <h2>Eco-Friendly Healthcare Solutions</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-leaf"></i>
                    <h3>Reduce Carbon Footprint</h3>
                    <p>Save the environment by choosing virtual consultations over traditional clinic visits.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-recycle"></i>
                    <h3>Paperless Healthcare</h3>
                    <p>Digital prescriptions and medical records to minimize paper waste.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-solar-panel"></i>
                    <h3>Green Technology</h3>
                    <p>Our platform runs on renewable energy sources to minimize environmental impact.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-heart"></i>
                    <h3>Sustainable Care</h3>
                    <p>Access quality healthcare while contributing to environmental conservation.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-globe-americas"></i>
                    <h3>Global Impact</h3>
                    <p>Join a community committed to both health and environmental sustainability.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>Eco-Conscious Doctors</h3>
                    <p>Connect with healthcare providers who share your environmental values.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeLoginModal()">&times;</span>
        <h2>Welcome Back</h2>
        <form id="loginForm" method="post" autocomplete="off">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login">Login</button>
            <div class="form-footer">
                Not a member? <a href="#" onclick="switchToSignup()">Sign Up</a>
            </div>
        </form>
    </div>
</div>

<!-- Signup Modal -->
<div id="signupModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSignupModal()">&times;</span>
        <h2>Join Our Green Initiative</h2>
        <form id="signupForm" method="post">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" name="signup">Join Now</button>
            <div class="form-footer">
                Already a member? <a href="#" onclick="switchToLogin()">Log In</a>
            </div>
        </form>
    </div>
</div>

    <script>
        // Modal Functions
function openLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
}

function openSignupModal() {
    const modal = document.getElementById('signupModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function closeSignupModal() {
    const modal = document.getElementById('signupModal');
    modal.classList.remove('active');
    setTimeout(() => modal.style.display = 'none', 300);
}

function switchToSignup() {
    closeLoginModal();
    setTimeout(openSignupModal, 300);
}

function switchToLogin() {
    closeSignupModal();
    setTimeout(openLoginModal, 300);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        if (event.target.id === 'loginModal') {
            closeLoginModal();
        } else if (event.target.id === 'signupModal') {
            closeSignupModal();
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('login', '1');

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            sessionStorage.setItem('user_id', data.user_id);
                            window.location.href = 'home.html';
                        } else {
                            alert(data.message || 'Login failed. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }

            // Signup Form Handler
            const signupForm = document.getElementById('signupForm');
            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('signup', '1');

                    // Password validation
                    const password = formData.get('password');
                    const confirmPassword = formData.get('confirm_password');

                    if (password !== confirmPassword) {
                        alert('Passwords do not match!');
                        return;
                    }

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            alert('Registration successful! Please login.');
                            switchToLogin();
                        } else {
                            alert(data.message || 'Registration failed. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }
        });
// Hamburger Menu
const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navLinks.classList.toggle('active');
});

// Close menu when clicking a link
document.querySelectorAll('.nav-links a').forEach(n => n.addEventListener('click', () => {
    hamburger.classList.remove('active');
    navLinks.classList.remove('active');
}));

// Form validation functions
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Simple password validation
function validatePassword(password) {
    return password.length > 0;
}

// Input validation listeners
document.querySelectorAll('input[type="email"]').forEach(input => {
    input.addEventListener('blur', function() {
        if (!validateEmail(this.value)) {
            this.style.borderColor = '#ff4444';
        } else {
            this.style.borderColor = '#3498db';
        }
    });
});

document.querySelectorAll('input[type="password"]').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.getAttribute('placeholder') === 'Confirm Password') return;
        this.style.borderColor = '#3498db';
    });
});

// Handle Consultation Link Click
document.querySelector('a[href="CONS.html"]').addEventListener('click', function(e) {
    e.preventDefault();
    if (!sessionStorage.getItem('user_id')) {
        openSignupModal();
    } else {
        window.location.href = 'CONS.html';
    }
});
    </script>
</body>
</html>