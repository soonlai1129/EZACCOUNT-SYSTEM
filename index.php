<?php
// Start session at the very beginning
session_start();
// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'business_owner') {
        header("Location: owner-dashboard.php");
        exit();
    } elseif ($_SESSION['user_type'] === 'staff') {
        header("Location: staff-closing-report.php");
        exit();
    }
}

// Function to handle login process
function handleLogin() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
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
            $email = strtolower(trim($_POST['email']));
            $inputPassword = $_POST['password'];
            
            // Check if email exists in business_owners table
            $check_owner_sql = "SELECT * FROM business_owners WHERE email = :email";
            $check_owner_stmt = $conn->prepare($check_owner_sql);
            $check_owner_stmt->bindParam(':email', $email);
            $check_owner_stmt->execute();
            
            // Check if email exists in staff table
            $check_staff_sql = "SELECT * FROM staff WHERE email = :email";
            $check_staff_stmt = $conn->prepare($check_staff_sql);
            $check_staff_stmt->bindParam(':email', $email);
            $check_staff_stmt->execute();
            
            if ($check_owner_stmt->rowCount() > 0) {
                // User exists in business_owners table
                $user = $check_owner_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password (compare plain text as your database stores plain text passwords)
                if ($inputPassword === $user['password']) {
                    // Login successful - business owner
                    $_SESSION['user_id'] = $user['owner_id'];
                    $_SESSION['user_name'] = $user['owner_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = 'business_owner';
                    $_SESSION['company_name'] = $user['company_name'];
                    
                    // Redirect to owner dashboard
                    header("Location: owner-dashboard.php");
                    exit();
                } else {
                    // Invalid password
                    return array(
                        'type' => 'error',
                        'title' => 'Login Failed',
                        'message' => 'Invalid email or password. Please try again.',
                        'buttonText' => 'OK'
                    );
                }
            } 
            else if ($check_staff_stmt->rowCount() > 0) {
                // User exists in staff table
                $user = $check_staff_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password (compare plain text as your database stores plain text passwords)
                if ($inputPassword === $user['password']) {

                    $sql = "SELECT o.outlet_name, bo.company_name 
                            FROM outlets o 
                            JOIN business_owners bo ON o.owner_id = bo.owner_id 
                            WHERE o.outlet_id = :outlet_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':outlet_id', $user['outlet_id']);
                    $stmt->execute();
                    $outletInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Login successful - staff
                    $_SESSION['user_id'] = $user['staff_id'];
                    $_SESSION['user_name'] = $user['staff_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = 'staff';
                    $_SESSION['outlet_id'] = $user['outlet_id'];
                    $_SESSION['outlet_name'] = $outletInfo['outlet_name'];
                    $_SESSION['company_name'] = $outletInfo['company_name'];
                    

                    // Redirect to staff stock in & out page
                    header("Location: staff-stock.php");
                    exit();
                } else {
                    // Invalid password
                    return array(
                        'type' => 'error',
                        'title' => 'Login Failed',
                        'message' => 'Invalid email or password. Please try again.',
                        'buttonText' => 'OK'
                    );
                }
            }
            else {
                // Login failed - user not found
                return array(
                    'type' => 'error',
                    'title' => 'Login Failed',
                    'message' => 'Invalid email or password. Please try again.',
                    'buttonText' => 'OK'
                );
            }
        } catch(PDOException $e) {
            // Database connection or query error
            return array(
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'There was a problem with our system. Please try again later.',
                'buttonText' => 'OK'
            );
        }
        
        // Close connection
        $conn = null;
    }
    return null;
}

// Process login only if form was submitted
$notification = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $notification = handleLogin();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Login</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS for responsive design -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS for this page -->
    <link rel="stylesheet" href="CSS/login-page.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle me-2"></i>ezAccount Login</h3>
                    </div>
                    <div class="card-body">
                        <form id="loginForm" method="post" action="">
                            <!-- Email Field with icon -->
                            <div class="input-group mb-4">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="floatingInput" name="email" 
                                           auto-complete="email" placeholder="name@example.com" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    <label for="floatingInput">Email address</label>
                                </div>
                            </div>

                            <!-- Password Field with icon -->
                            <div class="input-group mb-4">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="floatingPassword" name="password" 
                                           autocomplete="current-password" placeholder="Password" required>
                                    <label for="floatingPassword">Password</label>
                                </div>
                                <!-- Password toggle icon -->
                                <span class="input-group-text toggle-password" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>

                            <!-- Login Button with icon -->
                            <button class="btn btn-login w-100 py-2 mb-4" type="submit" name="login">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>

                            <!-- Links Container -->
                            <div class="links-container">
                                <a href="forgot-password-page.php" class="forgot-link">Forgot Password?</a>
                                <a href="registration-owner-page.php" class="register-link">Register as Business
                                    Owner</a>
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
            <div class="modal-content notification-modal-content">
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

    <!-- Custom JavaScript to prevent spaces in email & password field -->
    <script src="JAVASCRIPT/login-page.js"></script>

    <?php if ($notification): ?>
    <script>
        // Show notification after page loads
        document.addEventListener('DOMContentLoaded', function() {
            showNotification(
                '<?php echo $notification['type']; ?>',
                '<?php echo $notification['title']; ?>',
                '<?php echo $notification['message']; ?>',
                '<?php echo $notification['buttonText']; ?>'
            );
        });
    </script>
    <?php endif; ?>
</body>
</html>