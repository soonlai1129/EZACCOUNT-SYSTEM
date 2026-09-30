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
            $mail->Body    = "<p>Hello!</p><p>Your verification code is:</p><h2 style='color:blue;'>{$code}</h2><p>Use this code to reset your password.</p>";
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
    <title>ezAccount System - Forgot Password</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS for responsive design -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS for this page -->
    <link rel="stylesheet" href="CSS/forgot-password-page.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-key me-2"></i>Reset Your Password</h3>
                    </div>
                    <div class="card-body">
                        <p class="instruction-text">Enter your email address to receive a verification code to reset your password.</p>
                        <form id="forgotPasswordForm" method="post" action="">
                            <!-- Email Field -->
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <div class="form-floating position-relative">
                                    <input type="email" class="form-control" id="resetEmail" name="resetEmail" 
                                           placeholder="name@example.com" required 
                                           value="<?php echo isset($_POST['resetEmail']) ? htmlspecialchars($_POST['resetEmail']) : ''; ?>">
                                    <label for="resetEmail">Email address</label>
                                    <!-- Validation icon container positioned outside the input field -->
                                    <span class="validation-icon-container" id="emailValidationIcon"></span>
                                </div>
                            </div>
                            <div class="error-message" id="emailError"></div>
                            
                            <!-- Verification Code Field with Send Button -->
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                                <div class="form-floating position-relative">
                                    <input type="text" class="form-control" id="verificationCode" name="verificationCode" 
                                           placeholder="Verification Code" maxlength="6" required 
                                           value="<?php echo isset($_POST['verificationCode']) ? htmlspecialchars($_POST['verificationCode']) : ''; ?>">
                                    <label for="verificationCode">Verification Code</label>
                                    <!-- Validation icon container positioned outside the input field -->
                                    <span class="validation-icon-container" id="codeValidationIcon"></span>
                                </div>
                                <button class="btn btn-send-code" type="button" id="sendCodeBtn">Send Code</button>
                                <!-- Hidden field to store the generated code -->
<input type="hidden" name="generatedCode" value="<?php echo $verificationCode; ?>">
                            </div>
                            <div class="error-message" id="codeError"></div>
                            
                            <!-- Timer Display -->
                            <div id="timerContainer" class="text-center mb-3" style="display: none;">
                                <p class="timer-text">Resend code in: <span id="countdown">60</span> seconds</p>
                            </div>
                            
                            <!-- New Password Field -->
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="newPassword" name="newPassword" 
                                           placeholder="New Password" autocomplete="new-password" required 
                                           value="<?php echo isset($_POST['newPassword']) ? htmlspecialchars($_POST['newPassword']) : ''; ?>">
                                    <label for="newPassword">New Password</label>
                                    <!-- Validation icon container positioned outside the input field -->
                                    <span class="validation-icon-container" id="newPasswordValidationIcon"></span>
                                </div>
                                <!-- Password toggle icon -->
                                <span class="input-group-text toggle-password" id="toggleNewPassword" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="error-message" id="newPasswordError"></div>
                            
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
                            
                            <!-- Confirm Password Field -->
                            <div class="input-group mb-4">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" 
                                           placeholder="Confirm Password" required 
                                           value="<?php echo isset($_POST['confirmPassword']) ? htmlspecialchars($_POST['confirmPassword']) : ''; ?>">
                                    <label for="confirmPassword">Confirm Password</label>
                                    <!-- Validation icon container positioned outside the input field -->
                                    <span class="validation-icon-container" id="confirmPasswordValidationIcon"></span>
                                </div>
                                <!-- Password toggle icon -->
                                <span class="input-group-text toggle-password" id="toggleConfirmPassword" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="error-message" id="confirmPasswordError"></div>
                            
                            <!-- Submit Button -->
                            <button class="btn btn-reset w-100 py-2 mb-3" type="submit" id="resetBtn" disabled>Change Password</button>
                            
                            <!-- Back to Login Link -->
                            <div class="text-center">
                                <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
                            </div>
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
    
    <!-- Custom JavaScript for Validation and Timer -->
    <script src="JAVASCRIPT/forgot-password-page.js"></script>

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
            
            // Get form data
            $email = $_POST['resetEmail'];
            $newPassword = $_POST['newPassword'];
            $verificationCode = $_POST['verificationCode'];
            
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
            
            if ($check_owner_stmt->rowCount() > 0) {
                // Email exists in business_owners table, update password
                $update_sql = "UPDATE business_owners SET password = :password WHERE email = :email";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(':password', $newPassword);
                $update_stmt->bindParam(':email', $email);
                
                if ($update_stmt->execute()) {
                    // Password reset successful
                    echo "<script>
                        showNotification('success', 'Password Reset Successful', 'Your password has been reset successfully. You can now log in with your new password.', 'Back to Login');
                    </script>";
                } else {
                    // Password reset failed
                    echo "<script>
                        showNotification('error', 'Password Reset Failed', 'There was an error resetting your password. Please try again later.', 'OK');
                    </script>";
                }
            } 
            else if ($check_staff_stmt->rowCount() > 0) {
                // Email exists in staff table, update password
                $update_sql = "UPDATE staff SET password = :password WHERE email = :email";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bindParam(':password', $newPassword);
                $update_stmt->bindParam(':email', $email);
                
                if ($update_stmt->execute()) {
                    // Password reset successful
                    echo "<script>
                        showNotification('success', 'Password Reset Successful', 'Your password has been reset successfully. You can now log in with your new password.', 'Back to Login');
                    </script>";
                } else {
                    // Password reset failed
                    echo "<script>
                        showNotification('error', 'Password Reset Failed', 'There was an error resetting your password. Please try again later.', 'OK');
                    </script>";
                }
            }
            else {
                // Email doesn't exist in either table
                echo "<script>
                    showNotification('error', 'Email Not Found', 'This email address is not registered in our system. Please check your email or register for a new account.', 'OK');
                </script>";
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