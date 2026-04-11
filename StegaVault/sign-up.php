<?php
// Start session
session_start();

// Database connection
$conn = mysqli_connect("localhost", "u291912822_stegavault", "OwlOpsCo432!", "u291912822_owlops_sg");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Function to generate a verification token
function generateVerificationToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

// Function to send verification email
function sendVerificationEmail($email, $firstname, $token)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nailarchitect.glamhub@gmail.com';
        $mail->Password = 'xvft ygzc fijz vmth';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('nailarchitect.glamhub@gmail.com', 'Nail Architect');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Nail Architect Account';

        // Create verification link (use your actual domain)
        $verificationLink = 'localhost/nailarchitect/verify.php?email=' . urlencode($email) . '&token=' . $token;

        // HTML Email Body
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e8d7d0; border-radius: 10px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #ae9389; margin: 0;">Nail Architect</h1>
               <div style="text-align: center; margin-bottom: 20px;">
            </div>
            <h2 style="color: #ae9389; text-align: center;">Welcome to Nail Architect!</h2>
            <p>Hello ' . $firstname . ',</p>
            <p>Thank you for creating an account with Nail Architect. To complete your registration, please verify your email address by clicking the button below:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $verificationLink . '" style="background-color: #d9bbb0; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold;">Verify My Email</a>
            </div>
            <p>If the button above doesn\'t work, you can also copy and paste the following link into your browser:</p>
            <p style="background-color: #f2e9e9; padding: 10px; border-radius: 5px; word-break: break-all;">' . $verificationLink . '</p>
            <p>This link will expire in 24 hours for security reasons.</p>
            <p>If you didn\'t create an account with us, you can safely ignore this email.</p>
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e8d7d0; text-align: center; font-size: 12px; color: #666;">
                <p>&copy; ' . date('Y') . ' Nail Architect. All rights reserved.</p>
                <p>123 Nail Street, Beauty District, Marikina City</p>
            </div>
        </div>';

        // Plain text alternative
        $mail->AltBody = "Hello $firstname,\n\nPlease verify your email by clicking this link: $verificationLink\n\nThank you,\nNail Architect Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle signup form submission
if (isset($_POST['signup'])) {
    if (
        isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['password']) &&
        !empty($_POST['first_name']) && !empty($_POST['last_name']) && !empty($_POST['email']) &&
        !empty($_POST['phone']) && !empty($_POST['password'])
    ) {

        $firstname = mysqli_real_escape_string($conn, $_POST['first_name']);
        $lastname = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = $_POST['password'];

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Check if the email already exists
        $check_email_query = "SELECT * FROM users WHERE email = '$email'";
        $email_result = mysqli_query($conn, $check_email_query);

        if (mysqli_num_rows($email_result) > 0) {
            echo "<script>
            if (confirm('Email already exists. Would you like to log in instead?')) {
                window.location.href = 'login.php';
            }
            </script>";
        } else {
            // Generate verification token
            $token = generateVerificationToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Insert data into the users table with verified status = 0 (unverified)
            $query = "INSERT INTO users (first_name, last_name, email, phone, password, verification_token, token_expiry, is_verified) 
                      VALUES ('$firstname', '$lastname', '$email', '$phone', '$hashedPassword', '$token', '$expiry', 0)";
            $result = mysqli_query($conn, $query);

            if ($result) {
                // Send verification email
                if (sendVerificationEmail($email, $firstname, $token)) {
                    // Set session variable to display message on the next page
                    $_SESSION['verification_email_sent'] = true;

                    // Redirect to verification pending page
                    echo "<script>
                        window.location.href = 'verification-pending.php';
                    </script>";
                } else {
                    // Email sending failed
                    echo "<script>
                        alert('Account created but we could not send a verification email. Please contact support.');
                        window.location.href = 'login.php';
                    </script>";
                }
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
    } else {
        echo "<script>alert('Please fill in all fields.');</script>";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="navbar.css">
    <link rel="icon" type="image/png" href="Assets/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Nail Architect - Sign Up</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Poppins, sans-serif;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #F2E9E9;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1500px;
            width: 100%;
            flex: 1;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
        }


        .signup-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            animation: fadeIn 0.6s ease-out forwards;
            padding: 20px 0;
        }

        .signup-form-container {
            background-color: rgb(245, 207, 207);
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            animation: fadeIn 0.7s ease-out forwards;
            border: 1px solid rgba(235, 184, 184, 0.3);
            box-shadow:
                0 4px 16px rgba(0, 0, 0, 0.1),
                0 2px 8px rgba(0, 0, 0, 0.05),
                inset 0 1px 2px rgba(255, 255, 255, 0.3);
        }

        .signup-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: #F2E9E9;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            background-color: #ffffff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            user-select: none;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #333;
        }

        .password-strength,
        .match-status {
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .terms-container {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
            font-size: 13px;
        }

        .terms-container input {
            width: auto;
            margin-top: 4px;
        }

        .signup-button {
            padding: 12px 24px;
            background: linear-gradient(to right, #e6a4a4, #d98d8d);
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            margin-top: 20px;
            font-weight: bold;
        }

        .signup-button:hover {
            background: linear-gradient(to right, #d98d8d, #ce7878);
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
        }

        .login-text {
            cursor: pointer;
            font-weight: bold;
            transition: opacity 0.3s ease;
        }

        .login-text:hover {
            opacity: 0.7;
        }

        .back-button {
            display: inline-block;
            margin-top: 30px;
            font-size: 14px;
            cursor: pointer;
            position: relative;
            animation: fadeIn 0.8s ease-out forwards;
            align-self: center;
        }

        .back-button:after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -2px;
            left: 0;
            background-color: #000;
            transition: width 0.3s ease;
        }

        .back-button:hover:after {
            width: 100%;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Footer styles */
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 20px;
            padding: 0 15px;
        }

        .footer-section h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
            margin-top: 20px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .signup-form-container {
                padding: 30px 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .footer-section {
                flex: 100%;
            }
        }

        .password-strength-meter {
            height: 4px;
            width: 100%;
            background-color: #e0e0e0;
            margin-top: 8px;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-meter-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-weak .password-strength-meter-bar {
            width: 33.33%;
            background-color: #f44336;
            /* Red */
        }

        .strength-medium .password-strength-meter-bar {
            width: 66.66%;
            background-color: #ff9800;
            /* Orange */
        }

        .strength-strong .password-strength-meter-bar {
            width: 100%;
            background-color: #4caf50;
            /* Green */
        }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div class="logo-container">
                <div class="logo">
                    <a href="index.php">
                        <img src="Assets/logo.png" alt="Nail Architect Logo">
                    </a>
                </div>
            </div>
            <div class="nav-links">
                <div class="nav-link">Services</div>
                <div class="book-now">Book Now</div>
                <div class="login-icon"></div>
            </div>
        </header>

        <div class="signup-container">
            <div class="signup-form-container">
                <div class="signup-title">Create Account</div>

                <form id="signup-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{11}" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" required oninput="checkPasswordStrength()">
                            <i id="toggle-password-icon" class="fa fa-eye toggle-password" onclick="togglePasswordVisibility('password', 'toggle-password-icon')"></i>
                        </div>
                        <div id="password-strength-meter" class="password-strength-meter" style="display: none;">
                            <div id="password-strength-meter-bar" class="password-strength-meter-bar"></div>
                        </div>
                        <span id="password-strength" class="strength-text"></span>
                        <div class="password-requirements">
                            Password must be at least 8 characters with uppercase, lowercase, and number
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-input-container">
                            <input type="password" id="confirm-password" name="confirm_password" required oninput="checkPasswordMatch()">
                            <i id="toggle-confirm-password-icon" class="fa fa-eye toggle-password" onclick="togglePasswordVisibility('confirm-password', 'toggle-confirm-password-icon')"></i>
                        </div>
                        <span id="match-status" class="match-status"></span>
                    </div>

                    <div class="terms-container">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms-policy.php">Terms of Service and Privacy Policy</a></label>
                    </div>

                    <button type="submit" name="signup" class="signup-button">Create Account</button>
                </form>

                <div class="login-link">
                    Already have an account? <span class="login-text" onclick="window.location.href='login.php'">Sign In</span>
                </div>
            </div>

            <div class="back-button" onclick="window.location.href='index.php'">← Back to Home</div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('password-strength');
            const meterBar = document.getElementById('password-strength-meter-bar');
            const meterContainer = document.getElementById('password-strength-meter');

            // Remove all strength classes
            meterContainer.classList.remove('strength-weak', 'strength-medium', 'strength-strong');

            if (!password) {
                strengthIndicator.textContent = '';
                meterContainer.style.display = 'none'; // Hide the meter when empty
                return;
            }

            // Show the meter when user is typing
            meterContainer.style.display = 'block';

            let strength = 'Weak';
            let strengthClass = 'strength-weak';
            let textColor = '#f44336'; // Red color for weak

            // Basic checks
            const hasMinLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChars = /[^A-Za-z0-9]/.test(password);

            // Determine strength
            if (hasMinLength && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChars) {
                strength = 'Strong';
                strengthClass = 'strength-strong';
                textColor = '#4caf50'; // Green color for strong
            } else if (hasMinLength &&
                ((hasUpperCase && hasLowerCase && hasNumbers) ||
                    (hasUpperCase && hasLowerCase && hasSpecialChars) ||
                    (hasLowerCase && hasNumbers && hasSpecialChars))) {
                strength = 'Medium';
                strengthClass = 'strength-medium';
                textColor = '#ff9800'; // Orange color for medium
            }

            // Update UI
            strengthIndicator.textContent = `Password strength: ${strength}`;
            strengthIndicator.style.color = textColor; // Set text color
            meterContainer.classList.add(strengthClass);
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const matchStatus = document.getElementById('match-status');

            if (!confirmPassword) {
                matchStatus.textContent = '';
                return;
            }

            if (password === confirmPassword) {
                matchStatus.textContent = 'Passwords match';
                matchStatus.style.color = 'green';
            } else {
                matchStatus.textContent = 'Passwords do not match';
                matchStatus.style.color = 'red';
            }
        }

        document.getElementById('signup-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const terms = document.getElementById('terms').checked;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }

            if (!terms) {
                e.preventDefault();
                alert('You must agree to the Terms of Service');
                return;
            }

            // Password validation
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                alert('Password must be at least 8 characters with uppercase, lowercase, and number');
                return;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const bookNow = document.querySelector('.book-now');
            bookNow.addEventListener('click', function() {
                window.location.href = 'booking.php';
            });

            const servicesLink = document.querySelector('.nav-link');
            servicesLink.addEventListener('click', function() {
                window.location.href = 'services.php';
            });

            // Fixed: Removed PHP conditional that was causing errors
            const loginIcon = document.querySelector('.login-icon');
            if (loginIcon) {
                loginIcon.addEventListener('click', function() {
                    window.location.href = 'login.php';
                });
            }
        });
    </script>
</body>

</html>