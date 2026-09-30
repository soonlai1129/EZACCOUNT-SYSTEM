<?php
/**
 * ezAccount System - Owner Profile
 * 
 * Profile management interface for business owners with responsive design
 * and comprehensive validation.
 */

// Start session to access user data
session_start();
include("connection.php");

// Redirect to login if user is not authenticated as business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

// Get user data from session for personalized display
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$companyName = $_SESSION['company_name'];
$ownerId = $_SESSION['user_id']; 

// Fetch owner info from business_owner table
$queryOwner = "SELECT owner_name, company_name, phone_number, password FROM business_owners WHERE owner_id = ?";
$stmt = mysqli_prepare($conn, $queryOwner);
mysqli_stmt_bind_param($stmt, "i", $ownerId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $ownerName, $companyName, $phoneNumber, $ownerPassword);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

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
            $mail->Body    = "<p>Hello!</p><p>Your verification code is:</p><h2 style='color:blue;'>{$code}</h2><p>Use this code to verify your email address.</p>";
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

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $ownerName = trim($_POST['fullName']);
    $companyName = trim($_POST['companyName']);
    $phoneNumber = $_POST['phoneNumber'];
    
    // Format phone number
    $formatted_phone = '0' . substr($phoneNumber, 0, 2) . '-' . substr($phoneNumber, 2);
    
    $updateQuery = "UPDATE business_owners SET owner_name = ?, company_name = ?, phone_number = ? WHERE owner_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssi", $ownerName, $companyName, $formatted_phone, $ownerId);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $ownerName;
        $_SESSION['company_name'] = $companyName;
        $successMessage = "Profile updated successfully!";
        $messageType = "success";
    } else {
        $errorMessage = "Failed to update profile. Please try again.";
        $messageType = "error";
    }
    $stmt->close();
}

// Handle email update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_email'])) {
    $newEmail = $_POST['new_email'];
    $currentPassword = $_POST['current_password_email'];
    $verificationCode = $_POST['email_verification_code'];
    $generatedCode = $_POST['email_generated_code'];
    
    // Verify current password
    if ($currentPassword !== $ownerPassword) {
        $errorMessage = "Current password is incorrect!";
        $messageType = "error";
    } elseif ($verificationCode !== $generatedCode) {
        $errorMessage = "Invalid verification code!";
        $messageType = "error";
    } else {
        // Check if email exists in business_owners or staff tables
        $checkOwnerQuery = "SELECT owner_id FROM business_owners WHERE email = ? AND owner_id != ?";
        $stmt = $conn->prepare($checkOwnerQuery);
        $stmt->bind_param("si", $newEmail, $ownerId);
        $stmt->execute();
        $stmt->store_result();
        
        $checkStaffQuery = "SELECT staff_id FROM staff WHERE email = ?";
        $stmt2 = $conn->prepare($checkStaffQuery);
        $stmt2->bind_param("s", $newEmail);
        $stmt2->execute();
        $stmt2->store_result();
        
        if ($stmt->num_rows > 0 || $stmt2->num_rows > 0) {
            $errorMessage = "This email address is already registered. Please use a different email.";
            $messageType = "error";
        } else {
            $updateEmailQuery = "UPDATE business_owners SET email = ? WHERE owner_id = ?";
            $stmt3 = $conn->prepare($updateEmailQuery);
            $stmt3->bind_param("si", $newEmail, $ownerId);
            
            if ($stmt3->execute()) {
                $_SESSION['user_email'] = $newEmail;
                $successMessage = "Email updated successfully!";
                $messageType = "success";
            } else {
                $errorMessage = "Failed to update email. Please try again.";
                $messageType = "error";
            }
            $stmt3->close();
        }
        $stmt->close();
        $stmt2->close();
    }
}

// Handle password update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    
    if ($currentPassword !== $ownerPassword) {
        $errorMessage = "Current password is incorrect!";
        $messageType = "error";
    } else {
        $updatePasswordQuery = "UPDATE business_owners SET password = ? WHERE owner_id = ?";
        $stmt = $conn->prepare($updatePasswordQuery);
        $stmt->bind_param("si", $newPassword, $ownerId);
        
        if ($stmt->execute()) {
            $successMessage = "Password updated successfully!";
            $messageType = "success";
        } else {
            $errorMessage = "Failed to update password. Please try again.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Generate verification codes
$emailVerificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Profile Settings</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">   
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/owner-profile.css">
</head>
<body>
    <!-- Main Layout Container -->
    <div class="container-fluid">
        <div class="row">
            
            <!-- Sidebar Navigation - Fixed Position -->
            <nav id="sidebar" class="sidebar">
                <div class="sidebar-sticky">
                    
                    <!-- Brand Logo Section -->
                    <div class="sidebar-header">
                        <div class="brand-logo">
                            <i class="fas fa-chart-pie"></i>
                            <span class="brand-text">ezAccount</span>
                        </div>
                        <button id="sidebarCollapse" class="collapse-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>
                    
                    <!-- Main Navigation Menu -->
                    <ul class="nav flex-column">
                        
                        <!-- Dashboard Menu Item -->
                        <li class="nav-item menu-item">
                            <a class="nav-link" href="owner-dashboard.php">
                                <div class="menu-icon">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <span class="menu-text">Dashboard</span>
                            </a>
                        </li>

                        <!-- Outlet Menu Item -->
                        <li class="nav-item menu-item">
                            <a class="nav-link" href="owner-outlet.php">
                                <div class="menu-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <span class="menu-text">Outlet</span>
                            </a>
                        </li>
                        
                        <!-- Staff Menu Item -->
                        <li class="nav-item menu-item">
                            <a class="nav-link" href="owner-staff.php">
                                <div class="menu-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <span class="menu-text">Staff</span>
                            </a>
                        </li>
                        
                        <!-- Stock Menu Dropdown -->
                        <li class="nav-item dropdown stock-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="stockDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="menu-icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <span class="menu-text">Stock</span>
                            </a>
                            
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="stockDropdown">
                                <li><a class="dropdown-item" href="owner-stock.php">Stock In & Out</a></li>
                                <li><a class="dropdown-item" href="manage-stock.php">Manage Stock</a></li>
                            </ul>
                            
                        </li>

                        <!-- Operating Expenses Dropdown Menu -->
                        <li class="nav-item dropdown operating-expenses-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center"
                            href="#" id="operatingExpensesDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="menu-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <span class="menu-text">Operating Expenses</span>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="operatingExpensesDropdown">
                                <li><a class="dropdown-item" href="owner-operating-expenses.php">New Operating Expenses</a></li>
                                <li><a class="dropdown-item" href="operating-expenses-table.php">Manage Operating Expenses</a></li>
                            </ul>
                        </li>

                        <!-- Closing Report Dropdown Menu -->
                        <li class="nav-item dropdown closing-report-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center"
                            href="#" id="closingReportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="menu-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <span class="menu-text">Closing Sales</span>
                            </a>
                            
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="closingReportDropdown">
                                <li><a class="dropdown-item" href="owner-closing-report.php">New Closing Sales</a></li>
                                <li><a class="dropdown-item" href="closing-report-table.php">Manage Closing Sales</a></li>
                            </ul>
                        </li>

                        
                    </ul>
                    
                    <!-- User Profile Section in Sidebar Footer -->
                    <div class="sidebar-footer">
                        <div class="user-profile">
                            <div class="user-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="user-details">
                                <h6 class="user-name"><?php echo htmlspecialchars($userName); ?></h6>
                                <small class="user-role">Business Owner</small>
                                <small class="user-company"><?php echo htmlspecialchars($companyName); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Mini Sidebar for Collapsed State -->
            <nav id="mini-sidebar" class="mini-sidebar">
                <div class="mini-sidebar-sticky">
                    <!-- Dashboard Menu Item -->
                    <a class="mini-nav-link" href="owner-dashboard.php" title="Dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                    </a>

                    <!-- Outlet Menu Item -->
                    <a class="mini-nav-link" href="owner-outlet.php" title="Outlet">
                        <i class="fas fa-store"></i>
                    </a>
                    
                    <!-- Staff Menu Item -->
                    <a class="mini-nav-link" href="owner-staff.php" title="Staff">
                        <i class="fas fa-users"></i>
                    </a>
                    
                    <!-- Stock Menu Item -->
                    <a class="mini-nav-link" href="owner-stock.php" title="Stock">
                        <i class="fas fa-boxes"></i>
                    </a>

                    <!-- Operating Expenses Menu Item -->
                    <a class="mini-nav-link" href="owner-operating-expenses.php" title="Operating Expenses">
                        <i class="fas fa-money-bill-wave"></i>
                    </a>
                    
                    <!-- Closing Report Menu Item -->
                    <a class="mini-nav-link" href="owner-closing-report.php" title="Closing Sales">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </a>

                    
                    
                    <!-- Expand Button -->
                    <button id="sidebarExpand" class="expand-btn" title="Expand Menu">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="main-content">
                
                <!-- Top Navigation Bar -->
                <header class="topbar">
                    <div class="topbar-container">
                        
                        <!-- Toggle Button for Mobile -->
                        <button id="sidebarToggle" class="btn toggle-btn">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <!-- Page Title Section -->
                        <div class="page-title">
                            <h1>Profile Settings</h1>
                            <p class="page-subtitle">Manage your personal and business information</p>
                        </div>
                        
                        <!-- User Actions Section -->
                        <div class="user-actions">
                            
                            <!-- Welcome Message -->
                            <div class="welcome-message">
                                <span class="greeting">Hi, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                                <small class="company-name"><?php echo htmlspecialchars($companyName); ?></small>
                            </div>
                            
                            <!-- User Dropdown Menu -->
                            <div class="dropdown user-dropdown">
                                <button class="btn user-btn dropdown-toggle" type="button" id="userDropdown" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-info">
                                        <div class="user-avatar-sm">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                                    </div>
                                </button>
                                
                                <!-- Dropdown Menu Items -->
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <!-- Email display for mobile -->
                                    <li class="dropdown-email-mobile">
                                        <div class="dropdown-item email-item">
                                            <i class="fas fa-envelope me-2"></i>
                                            <span><?php echo htmlspecialchars($userEmail); ?></span>
                                        </div>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="owner-profile.php">
                                            <i class="fas fa-user me-2"></i>Profile Settings
                                        </a>
                                    </li>
                                   
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item logout-item" href="#" id="logoutBtn">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content Container -->
                <div class="content-container">
                    <div class="row">
                        <!-- Profile Information Section -->
                        <div class="col-md-8">
                            <div class="profile-section">
                                <h4 class="section-title">Profile Information</h4>
                                
                                <form id="profileForm" method="post">
                                    <input type="hidden" name="update_profile" value="1">
                                    <!-- Full Name Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="text" class="form-control" id="fullName" name="fullName"
                                                placeholder="Your Full Name" required 
                                                value="<?php echo htmlspecialchars($ownerName); ?>">
                                            <label for="fullName">Full Name</label>
                                            <span class="validation-icon-container" id="nameValidationIcon"></span>
                                        </div>
                                    </div>
                                    <div class="error-message" id="nameError"></div>

                                    <!-- Company Name Field -->
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="fas fa-building"></i>
                                        </span>
                                        <div class="form-floating position-relative">
                                            <input type="text" class="form-control" id="companyName" name="companyName" 
                                                placeholder="Company Name" required 
                                                value="<?php echo htmlspecialchars($companyName); ?>">
                                            <label for="companyName">Company Name</label>
                                            <span class="validation-icon-container" id="companyValidationIcon"></span>
                                        </div>
                                    </div>
                                    <div class="error-message" id="companyError"></div>

                                    <!-- Phone Number Field -->
                                    <div class="input-group mb-4">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <span class="input-group-text">+60</span>
                                        <div class="form-floating position-relative">
                                            <?php
                                            // Format phone number for display (remove 0 and -)
                                           $displayPhone = preg_replace('/^0|-/','', $phoneNumber);
                                            ?>
                                            <input type="text" class="form-control" id="phoneNumber" name="phoneNumber"
                                                placeholder="Phone Number" maxlength="10" required 
                                                value="<?php echo htmlspecialchars($displayPhone); ?>">
                                            <label for="phoneNumber">Phone Number</label>
                                            <span class="validation-icon-container" id="phoneValidationIcon"></span>
                                        </div>
                                    </div>
                                    <div class="error-message" id="phoneError"></div>

                                    <!-- Update Button -->
                                    <button class="btn btn-update w-100 py-2 mb-3" type="submit" id="updateBtn">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Account Security Section -->
                        <div class="col-md-4">
                            <div class="security-section">
                                <h4 class="section-title">Account Security</h4>
                                
                                <div class="security-actions">
                                    <!-- Change Email Button -->
                                    <div class="action-item mb-4">
                                        <div class="action-info">
                                            <h5>Email Address</h5>
                                            <p class="current-value"><?php echo htmlspecialchars($userEmail); ?></p>
                                        </div>
                                        <button class="btn btn-action" type="button" id="changeEmailBtn">
                                            <i class="fas fa-envelope me-2"></i>Change Email
                                        </button>
                                    </div>

                                    <!-- Change Password Button -->
                                    <div class="action-item">
                                        <div class="action-info">
                                            <h5>Password</h5>
                                            <p class="current-value">••••••••</p>
                                        </div>
                                        <button class="btn btn-action" type="button" id="changePasswordBtn">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Change Email Modal -->
    <div class="modal fade" id="changeEmailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center w-100 text-white"><i class="fas fa-envelope me-2"></i>Change Email Address</h5>
                </div>
                <div class="modal-body">
                    <form id="changeEmailForm" method="post">
                        <input type="hidden" name="update_email" value="1">
                        <input type="hidden" name="email_generated_code" value="<?php echo $emailVerificationCode; ?>">
                        
                        <!-- Current Password Field -->
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="currentPasswordEmail" name="current_password_email"
                                    placeholder="Current Password" required>
                                <label for="currentPasswordEmail">Current Password</label>
                                <span class="validation-icon-container" id="currentPasswordEmailValidationIcon"></span>
                            </div>
                            <span class="input-group-text toggle-password" data-target="currentPasswordEmail">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="error-message" id="currentPasswordEmailError"></div>

                        <!-- New Email Field -->
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <div class="form-floating position-relative">
                                <input type="email" class="form-control" id="newEmail" name="new_email"
                                    placeholder="New Email Address" required>
                                <label for="newEmail">New Email Address</label>
                                <span class="validation-icon-container" id="newEmailValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="newEmailError"></div>

                        <!-- Verification Code -->
                        <div class="input-group mb-4">
                            <span class="input-group-text">
                                <i class="fas fa-shield-alt"></i>
                            </span>
                            <div class="form-floating position-relative">
                                <input type="text" class="form-control" id="emailVerificationCode" name="email_verification_code"
                                    placeholder="Verification Code" maxlength="6"  autocomplete="off"   autocorrect="off"
            spellcheck="false"
            autofill="false"
            inputmode="numeric" required>
                                <label for="emailVerificationCode">Verification Code</label>
                                <span class="validation-icon-container" id="emailCodeValidationIcon"></span>
                            </div>
                            <button class="btn btn-send-code" type="button" id="sendEmailCodeBtn">
                                Send Code
                            </button>
                        </div>
                        <div class="error-message" id="emailCodeError"></div>

                        <!-- Timer Display -->
                        <div id="emailTimerContainer" class="text-center mb-3" style="display: none;">
                            <p class="timer-text">Resend code in: <span id="emailCountdown">60</span> seconds</p>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-cancel w-50" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-update w-50" id="updateEmailBtn" disabled>
                                <i class="fas fa-envelope me-2"></i>Update Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center w-100 text-white"><i class="fas fa-key me-2"></i>Change Password</h5>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" method="post">
                        <input type="hidden" name="update_password" value="1">
                        
                        <!-- Current Password Field -->
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="currentPassword" name="current_password"
                                    placeholder="Current Password" required>
                                <label for="currentPassword">Current Password</label>
                                <span class="validation-icon-container" id="currentPasswordValidationIcon"></span>
                            </div>
                            <span class="input-group-text toggle-password" data-target="currentPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="error-message" id="currentPasswordError"></div>

                        <!-- New Password Field -->
                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="newPassword" name="new_password"
                                    placeholder="New Password" required>
                                <label for="newPassword">New Password</label>
                                <span class="validation-icon-container" id="newPasswordValidationIcon"></span>
                            </div>
                            <span class="input-group-text toggle-password" data-target="newPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="error-message" id="newPasswordError"></div>

                        <!-- Confirm New Password Field -->
                        <div class="input-group mb-4">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="confirmNewPassword" name="confirm_password"
                                    placeholder="Confirm New Password" required>
                                <label for="confirmNewPassword">Confirm New Password</label>
                                <span class="validation-icon-container" id="confirmPasswordValidationIcon"></span>
                            </div>
                            <span class="input-group-text toggle-password" data-target="confirmNewPassword">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>

                        <!-- Password Requirements -->
                        <div class="password-requirements">
                            <p class="requirement-title text-start">Password must include:</p>
                            <ul class="requirement-list">
                                <li id="req-length"><i class="fas fa-times-circle"></i> 8-20 characters</li>
                                <li id="req-uppercase"><i class="fas fa-times-circle"></i> One uppercase letter (A-Z)</li>
                                <li id="req-lowercase"><i class="fas fa-times-circle"></i> One lowercase letter (a-z)</li>
                                <li id="req-number"><i class="fas fa-times-circle"></i> One number (0-9)</li>
                                <li id="req-special"><i class="fas fa-times-circle"></i> One special character (!@#$%^&* etc.)</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-cancel w-50" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-update w-50" id="updatePasswordBtn" disabled>
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <span id="notificationIcon" class="notification-icon mb-3 d-block"></span>
                    <h4 id="notificationTitle" class="notification-title mb-2 text-white"></h4>
                    <p id="notificationMessage" class="notification-message mb-3"></p>
                    <button type="button" id="notificationButton" class="btn btn-primary notification-button"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <!-- Modal Icon -->
                    <div class="modal-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    
                    <!-- Modal Title -->
                    <h3 class="modal-title">Confirm Logout</h3>
                    
                    <!-- Modal Message -->
                    <p class="modal-message">Are you sure you want to logout from your account?</p>
                    
                    <!-- Action Buttons -->
                    <div class="modal-actions">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-logout" id="confirmLogout">
                            <i class="fas fa-check me-1"></i>Yes, Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Dashboard JavaScript -->
    <script src="JAVASCRIPT/owner-dashboard.js"></script>
    
    <!-- Profile Customization JavaScript -->
    <script src="JAVASCRIPT/owner-profile.js"></script>

    <script>
    // Show notification modal from PHP
    <?php if (isset($successMessage)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('success', 'Success', '<?php echo $successMessage; ?>', 'OK');
        });
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('error', 'Error', '<?php echo $errorMessage; ?>', 'OK');
        });
    <?php endif; ?>
    </script>
</body>
</html>