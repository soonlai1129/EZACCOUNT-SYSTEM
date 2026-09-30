<?php

// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

// Generate 6-digit verification code when page loads
$verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

// Handle send code request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_code') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';
    
    header('Content-Type: application/json');
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'mail.ezaccount.online';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'admin@ezaccount.online';
            $mail->Password   = 'admin';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('admin@ezaccount.online', 'ezAccount Verification');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code';
            $mail->Body    = "<p>Hello!</p><p>Your verification code is:</p><h2 style='color:blue;'>{$code}</h2><p>Use this code to complete business owner registration.</p>";
            $mail->AltBody = "Your verification code is: {$code}";

            if ($mail->send()) {
                echo json_encode(['success' => true, 'message' => 'Code sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send email']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Business Owner Registration</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS for responsive design -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS for this page -->
    <link rel="stylesheet" href="CSS/registration-owner-page.css">
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-briefcase me-2"></i>Register as Business Owner</h3>
                    </div>
                    <div class="card-body">
                        <form id="registrationForm" method="post" action="">
                            <div class="row">
                                <!-- Personal Information -->
                                <div class="col-md-6">
                                    <h4 class="section-title">Personal Information</h4>

                                    <!-- Full Name Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="text" class="form-control" id="fullName" name="fullName"
                                                placeholder="Your Full Name" required value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
                                            <label for="fullName">Full Name</label>
                                            <!-- Validation icon container positioned outside the input field -->
                                            <span class="validation-icon-container" id="nameValidationIcon"></span>
                                        </div>
                                    </div>
                                    <div class="error-message" id="nameError"></div>

                                    <!-- Phone Number Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <span class="input-group-text">+60</span>
                                        <div class="form-floating position-relative">
                                            <input type="text" class="form-control" id="phoneNumber" name="phoneNumber"
                                                placeholder="Phone Number" maxlength="10" required value="<?php echo isset($_POST['phoneNumber']) ? htmlspecialchars($_POST['phoneNumber']) : ''; ?>">
                                            <label for="phoneNumber">Phone Number</label>
                                            <!-- Validation icon container positioned outside the input field -->
                                            <span class="validation-icon-container" id="phoneValidationIcon"></span>
                                        </div>
                                    </div>
                                    <div class="error-message" id="phoneError"></div>

                                    <!-- Email Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="email" class="form-control" id="registerEmail" name="registerEmail"
                                                placeholder="name@example.com" required value="<?php echo isset($_POST['registerEmail']) ? htmlspecialchars($_POST['registerEmail']) : ''; ?>">
                                            <label for="registerEmail">Email address</label>
                                            <!-- Validation icon container positioned outside the input field -->
                                            <span class="validation-icon-container" id="emailValidationIcon"></span>
                                        </div>
                                    </div>
                                    <div class="error-message" id="emailError"></div>
                                </div>

                                <!-- Account Information -->
                                <div class="col-md-6">
                                    <h4 class="section-title">Account Information</h4>

                                    <!-- Verification Code Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-shield-alt"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="text" class="form-control" id="verificationCode" name="verificationCode"
                                                placeholder="Verification Code" maxlength="6" value="<?php echo isset($_POST['verificationCode']) ? htmlspecialchars($_POST['verificationCode']) : ''; ?>">
                                            <label for="verificationCode">Verification Code</label>
                                            <!-- Validation icon container positioned outside the input field -->
                                            <span class="validation-icon-container" id="codeValidationIcon"></span>
                                        </div>
                                        <button class="btn btn-send-code" type="button" id="sendCodeBtn">Send Code</button>
                                    </div>
                                    <div class="error-message" id="codeError"></div>

                                    <!-- Timer Display -->
                                    <div id="timerContainer" class="text-center mb-3" style="display: none;">
                                        <p class="timer-text">Resend code in: <span id="countdown">60</span> seconds</p>
                                    </div>

                                    <!-- Password Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="password" class="form-control" id="registerPassword" name="registerPassword" autocomplete="new-password"
                                                placeholder="Password" required value="<?php echo isset($_POST['registerPassword']) ? htmlspecialchars($_POST['registerPassword']) : ''; ?>">
                                            <label for="registerPassword">Password</label>
                                            <!-- Validation icon container positioned outside the input field -->
                                            <span class="validation-icon-container" id="passwordValidationIcon"></span>
                                        </div>
                                        <span class="input-group-text toggle-password" data-target="registerPassword">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div class="error-message" id="passwordError"></div>

                                    <!-- Confirm Password Field -->
                                    <div class="input-group mb-4">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                                placeholder="Confirm Password" required value="<?php echo isset($_POST['confirmPassword']) ? htmlspecialchars($_POST['confirmPassword']) : ''; ?>">
                                            <label for="confirmPassword">Confirm Password</label>
                                            <!-- Validation icon container positioned outside the input field -->
                                            <span class="validation-icon-container" id="confirmPasswordValidationIcon"></span>
                                        </div>
                                        <span class="input-group-text toggle-password" data-target="confirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    <div class="error-message" id="confirmPasswordError"></div>
                                </div>
                            </div>

                            <!-- Company Information -->
                            <h4 class="section-title">Company Information</h4>

                            <!-- Company Name Field -->
                            <div class="input-group mb-4">
                                <span class="input-group-text">
                                    <i class="fas fa-building"></i>
                                </span>
                                <div class="form-floating position-relative">
                                    <input type="text" class="form-control" id="companyName" name="companyName" placeholder="Company Name" required value="<?php echo isset($_POST['companyName']) ? htmlspecialchars($_POST['companyName']) : ''; ?>">
                                    <label for="companyName">Company Name</label>
                                    <!-- Validation icon container positioned outside the input field -->
                                    <span class="validation-icon-container" id="companyValidationIcon"></span>
                                </div>
                            </div>
                            <div class="error-message" id="companyError"></div>

                            <!-- Password Requirements -->
                            <div class="password-requirements">
                                <p class="requirement-title">Password must include:</p>
                                <ul class="requirement-list">
                                    <li id="req-length"><i class="fas fa-times-circle"></i> 8-20 characters</li>
                                    <li id="req-uppercase"><i class="fas fa-times-circle"></i> One uppercase letter (A-Z)</li>
                                    <li id="req-lowercase"><i class="fas fa-times-circle"></i> One lowercase letter (a-z)</li>
                                    <li id="req-number"><i class="fas fa-times-circle"></i> One number (0-9)</li>
                                    <li id="req-special"><i class="fas fa-times-circle"></i> One special character (!@#$%^&* etc.)</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <button class="btn btn-register w-100 py-2 mb-3" type="submit" id="registerBtn"
                                disabled>Create Account</button>

                            <!-- Back to Login Link -->
                            <div class="text-center">
                                <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Back to
                                    Login</a>
                            </div>

                            <!-- Hidden field to store the generated code -->
<input type="hidden" name="generatedCode" value="<?php echo $verificationCode; ?>">

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal Dialog for Notifications -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <span id="notificationIcon" class="notification-icon mb-3 d-block"></span>
                    <h4 id="notificationTitle" class="notification-title mb-2"></h4>
                    <p id="notificationMessage" class="notification-message mb-3"></p>
                    <button type="button" id="notificationButton" class="btn btn-primary notification-button"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript for Validation -->
    <script src="JAVASCRIPT/registration-owner-page.js"></script>
    
    <?php
    // PHP code to handle form submission and database operations
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Database connection parameters
        $servername = "localhost";
        $username = "root"; // Replace with your database username
        $password = ""; // Replace with your database password
        $dbname = "ezaccount"; // Replace with your database name
        
        try {
            // Create connection
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            // Set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get form data and trim whitespace from name and company fields
            $owner_name = trim($_POST['fullName']);
            $company_name = trim($_POST['companyName']);
            $email = $_POST['registerEmail'];
            $password = $_POST['registerPassword']; // Store plain text password as requested
            $phone_number = $_POST['phoneNumber'];
            
            // Format phone number: add 0 at front and hyphen after the first 3 digits
            // Example: 1112477991 becomes 011-12477991
            $formatted_phone = '0' . substr($phone_number, 0, 2) . '-' . substr($phone_number, 2);
            
            // Check if email exists in business_owners table
            $check_owner_sql = "SELECT email FROM business_owners WHERE email = :email";
            $check_owner_stmt = $conn->prepare($check_owner_sql);
            $check_owner_stmt->bindParam(':email', $email);
            $check_owner_stmt->execute();
            
            // Check if email exists in staff table
            $check_staff_sql = "SELECT email FROM staff WHERE email = :email";
            $check_staff_stmt = $conn->prepare($check_staff_sql);
            $check_staff_stmt->bindParam(':email', $email);
            $check_staff_stmt->execute();
            
            if ($check_owner_stmt->rowCount() > 0 || $check_staff_stmt->rowCount() > 0) {
                // Email already exists in one of the tables
                echo "<script>
                    showNotification('error', 'Registration Failed', 'This email address is already registered in our system. Please use a different email or try logging in.', 'OK');
                </script>";
            } else {
                // Email doesn't exist, proceed with registration
                $insert_sql = "INSERT INTO business_owners (owner_name, company_name, email, password, phone_number) 
                               VALUES (:owner_name, :company_name, :email, :password, :phone_number)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bindParam(':owner_name', $owner_name);
                $insert_stmt->bindParam(':company_name', $company_name);
                $insert_stmt->bindParam(':email', $email);
                $insert_stmt->bindParam(':password', $password);
                $insert_stmt->bindParam(':phone_number', $formatted_phone);
                
                if ($insert_stmt->execute()) {
                    // Registration successful
                    echo "<script>
                        showNotification('success', 'Registration Successful', 'Your account has been created successfully. You can now log in to your account.', 'Back to Login');
                    </script>";
                } else {
                    // Registration failed
                    echo "<script>
                        showNotification('error', 'Registration Failed', 'There was an error creating your account. Please try again later.', 'OK');
                    </script>";
                }
            }
        } catch(PDOException $e) {
            // Database connection or query error
            echo "<script>
                showNotification('error', 'Database Error', 'There was a problem with our system. Please try again later.', 'OK');
            </script>";
            // For debugging purposes only (remove in production)
            // echo "Error: " . $e->getMessage();
        }
        
        // Close connection
        $conn = null;
    }
    ?>
</body>

</html>