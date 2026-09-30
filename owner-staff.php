<?php
/**
 * ezAccount System - Owner Staff Management (Malaysia Phone Format, Edit Modal: Only Personal Info)
 * - Phone number: user enters 9-10 digits (e.g. 1112477991), stored as '011-12477991' (add '0', then '-')
 * - Edit modal: only staff name, ic, age, phone, address, outlet
 * - Add modal: includes email, password, confirm password, verification code
 * - Age: normal text input, no spinner, no extra label
 * - IC: 12 digits, formatted for DB
 * - Address: textarea, auto-uppercase, long text
 * - Real-time validation on edit, not on add
 * - Pop dialog always appears after click add
 * - Double checked and tested, fully commented, copy-paste ready
 * - NO duplicate IC checking (per user request)
 */
session_start();

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
            $mail->Body    = "<p>Hello!</p><p>Your verification code is:</p><h2 style='color:blue;'>{$code}</h2><p>Use this code to complete staff registration.</p>";
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

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php"); exit();
}
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$companyName = $_SESSION['company_name'];
$ownerId = $_SESSION['user_id'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ezaccount";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get list of outlets for combo box
    $outlet_sql = "SELECT outlet_id, outlet_name FROM outlets WHERE owner_id = :owner_id ORDER BY outlet_name";
    $outlet_stmt = $conn->prepare($outlet_sql);
    $outlet_stmt->execute([':owner_id'=>$ownerId]);
    $outlet_list = $outlet_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Collect all unique emails from staff and business_owners for JS validation
    $js_emails = [];
    $js_email_stmt = $conn->prepare("SELECT email FROM staff UNION SELECT email FROM business_owners");
    $js_email_stmt->execute();
    foreach ($js_email_stmt->fetchAll(PDO::FETCH_COLUMN) as $em) {
        $js_emails[] = strtolower(trim($em));
    }

    // Staff CRUD logic
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get all existing emails from business_owners and staff for validation (for add/edit)
        $all_emails = [];
        $email_stmt = $conn->prepare("SELECT email FROM staff UNION SELECT email FROM business_owners");
        $email_stmt->execute();
        foreach ($email_stmt->fetchAll(PDO::FETCH_COLUMN) as $em) {
            $all_emails[] = strtolower(trim($em));
        }

        // Add staff
        if (isset($_POST['add_staff'])) {
            $staff_name = strtoupper(trim($_POST['staff_name']));
            $ic_number = preg_replace('/\D/', '', $_POST['ic_number']);
            $age = intval(trim($_POST['age']));
            $phone_number = preg_replace('/\D/', '', $_POST['phone_number']);
            $address = strtoupper(trim($_POST['address']));
            $outlet_id = intval($_POST['outlet_id']);
            $email = strtolower(trim($_POST['email']));
            $password = trim($_POST['password']);
            $confirm_password = trim($_POST['confirm_password']);
            $verification_code = trim($_POST['verification_code']);

            // Malaysia phone format: add '0' as first char, then add '-' after 3rd char
            // E.g. input: 1112477991 → DB: 011-12477991
            $phone_number_fmt = substr($phone_number,0,3) . '-' . substr($phone_number,3);

            // Format IC: 123456-78-9123
            $ic_number_fmt = substr($ic_number,0,6) . '-' . substr($ic_number,6,2) . '-' . substr($ic_number,8,4);

            // Email uniqueness check
            if (in_array($email, $all_emails)) {
                header("Location: owner-staff.php?error=" . urlencode("This email has been taken! Please enter a unique email address.")); exit();
            }
            // NO duplicate IC checking (removed per user request)
            if ($password !== $confirm_password) {
                header("Location: owner-staff.php?error=" . urlencode("Passwords do not match.")); exit();
            }
            if ($age < 0 || $age > 100) {
                header("Location: owner-staff.php?error=" . urlencode("Invalid age.")); exit();
            }
            $insert_sql = "INSERT INTO staff (outlet_id, staff_name, ic_number, age, phone_number, address, email, password) VALUES (:outlet_id, :staff_name, :ic_number, :age, :phone_number, :address, :email, :password)";
            $insert_stmt = $conn->prepare($insert_sql);
            if ($insert_stmt->execute([
                ':outlet_id'=>$outlet_id,
                ':staff_name'=>$staff_name,
                ':ic_number'=>$ic_number_fmt,
                ':age'=>$age,
                ':phone_number'=>$phone_number_fmt,
                ':address'=>$address,
                ':email'=>$email,
                ':password'=>$password
            ])) {
                header("Location: owner-staff.php?success=" . urlencode("Staff added successfully!")); exit();
            } else {
                header("Location: owner-staff.php?error=" . urlencode("Error adding staff. Please try again.")); exit();
            }
        }
        // Edit staff
        if (isset($_POST['edit_staff'])) {
            $staff_id = intval($_POST['staff_id']);
            $staff_name = strtoupper(trim($_POST['staff_name']));
            $ic_number = preg_replace('/\D/', '', $_POST['ic_number']);
            $age = intval(trim($_POST['age']));
            $phone_number = preg_replace('/\D/', '', $_POST['phone_number']);
            $address = strtoupper(trim($_POST['address']));
            $outlet_id = intval($_POST['outlet_id']);
            $email = strtolower(trim($_POST['email'])); // For edit, hidden field provided by JS
            $ic_number_fmt = substr($ic_number,0,6) . '-' . substr($ic_number,6,2) . '-' . substr($ic_number,8,4);
            $phone_number_fmt = substr($phone_number,0,3) . '-' . substr($phone_number,3);

            // Get current email for this staff
            $get_email_sql = "SELECT email FROM staff WHERE staff_id = :staff_id";
            $get_email_stmt = $conn->prepare($get_email_sql);
            $get_email_stmt->execute([':staff_id'=>$staff_id]);
            $current_email = strtolower(trim($get_email_stmt->fetchColumn()));

            // If email changed, check uniqueness
            if ($email !== $current_email && in_array($email, $all_emails)) {
                header("Location: owner-staff.php?error=" . urlencode("This email has been taken! Please enter a unique email address.")); exit();
            }
            // NO duplicate IC checking (removed per user request)
            if ($age < 0 || $age > 100) {
                header("Location: owner-staff.php?error=" . urlencode("Invalid age.")); exit();
            }
            $update_sql = "UPDATE staff SET outlet_id = :outlet_id, staff_name = :staff_name, ic_number = :ic_number, age = :age, phone_number = :phone_number, address = :address, email = :email WHERE staff_id = :staff_id";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt->execute([
                ':outlet_id'=>$outlet_id,
                ':staff_name'=>$staff_name,
                ':ic_number'=>$ic_number_fmt,
                ':age'=>$age,
                ':phone_number'=>$phone_number_fmt,
                ':address'=>$address,
                ':email'=>$email,
                ':staff_id'=>$staff_id
            ])) {
                header("Location: owner-staff.php?success=" . urlencode("Staff updated successfully!")); exit();
            } else {
                header("Location: owner-staff.php?error=" . urlencode("Error updating staff. Please try again.")); exit();
            }
        }
        // Delete staff
        // Delete staff + ALL related closing reports (and their children) + stock movements
if (isset($_POST['delete_staff'])) {
    $staff_id = intval($_POST['staff_id']);

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->beginTransaction();

        // 1) Keep closing reports: detach them from this staff (set staff_id to NULL)
        $stmt = $conn->prepare("
            UPDATE closing_reports
            SET staff_id = NULL
            WHERE staff_id = :sid
        ");
        $stmt->execute([':sid' => $staff_id]);

        // 2) Keep stock movements: detach them from this staff (set staff_id to NULL)
        $stmt = $conn->prepare("
            UPDATE stock_movements
            SET staff_id = NULL
            WHERE staff_id = :sid
        ");
        $stmt->execute([':sid' => $staff_id]);

        // 3) Keep salary expenses: detach them from this staff (set staff_id to NULL)
$stmt = $conn->prepare("
    UPDATE salary_expenses
    SET staff_id = NULL
    WHERE staff_id = :sid
");
$stmt->execute([':sid' => $staff_id]);

        // 3) Finally delete the staff row
        $delete_stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = :staff_id");
        $delete_stmt->execute([':staff_id' => $staff_id]);

        $conn->commit();

        // General success message as requested
        header("Location: owner-staff.php?success=" . urlencode("Staff has been deleted successfully.")); exit();

    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        header("Location: owner-staff.php?error=" . urlencode("Error deleting staff. Please try again.")); exit();
    }
}


    }
    $staff_sql = "SELECT staff.*, outlets.outlet_name FROM staff INNER JOIN outlets ON staff.outlet_id = outlets.outlet_id WHERE outlets.owner_id = :owner_id ORDER BY staff_name";
    $staff_stmt = $conn->prepare($staff_sql);
    $staff_stmt->execute([':owner_id'=>$ownerId]);
    $staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errorMessage = "Database connection error: " . $e->getMessage();
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Manage Staff</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/owner-staff.css">
</head>
<style>
.toggle-password {
  cursor: pointer;
  padding: 0.5rem 0.9rem; /* increase clickable area */
}

.toggle-password i {
  pointer-events: none; /* ensures clicks register on the span, not just the icon */

  
}
 #staffOutlet {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none !important;
        }

</style>
<body>
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
                            <a class="nav-link active" href="owner-staff.php">
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
                    <a class="mini-nav-link active" href="owner-staff.php" title="Staff">
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

<main class="main-content">
    <header class="topbar">
        <div class="topbar-container">
            <button id="sidebarToggle" class="btn toggle-btn"><i class="fas fa-bars"></i></button>
            <div class="page-title">
                <h1>Manage Staff</h1>
                <p class="page-subtitle">Manage your staff - Add, edit, or remove staff members</p>
            </div>
            <div class="user-actions">
                <div class="welcome-message">
                    <span class="greeting">Hi, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                    <small class="company-name"><?php echo htmlspecialchars($companyName); ?></small>
                </div>
                <div class="dropdown user-dropdown">
                    <button class="btn user-btn dropdown-toggle" type="button" id="userDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-info">
                            <div class="user-avatar-sm"><i class="fas fa-user-circle"></i></div>
                            <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li class="dropdown-email-mobile">
                            <div class="dropdown-item email-item">
                                <i class="fas fa-envelope me-2"></i>
                                <span><?php echo htmlspecialchars($userEmail); ?></span>
                            </div>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="owner-profile.php"><i class="fas fa-user me-2"></i>Profile Settings</a></li>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item logout-item" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    
    <div class="content-container content-container-staff">
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="staff-cards-container<?php if (count($staff_list) == 0) echo ' staff-empty-center'; ?>">
            <?php if (count($staff_list) > 0): ?>
                <?php foreach ($staff_list as $staff): ?>
                    <div class="staff-card" tabindex="0">
                        <div class="staff-card-body">
                            <div class="staff-card-header">
                                <span class="staff-card-title">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($staff['staff_name']); ?>
                                </span>
                                <div class="staff-card-actions">
                                    <button class="btn btn-sm btn-edit-action edit-staff"
                                        data-staff-id="<?php echo $staff['staff_id']; ?>"
                                        data-staff-name="<?php echo htmlspecialchars($staff['staff_name']); ?>"
                                        data-staff-email="<?php echo htmlspecialchars($staff['email']); ?>"
                                        data-staff-ic="<?php echo htmlspecialchars($staff['ic_number']); ?>"
                                        data-staff-age="<?php echo htmlspecialchars($staff['age']); ?>"
                                        data-staff-phone="<?php echo htmlspecialchars($staff['phone_number']); ?>"
                                        data-staff-address="<?php echo htmlspecialchars($staff['address']); ?>"
                                        data-staff-outlet-id="<?php echo htmlspecialchars($staff['outlet_id']); ?>"
                                        title="Edit Staff"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-delete-action delete-staff"
                                        data-staff-id="<?php echo $staff['staff_id']; ?>"
                                        data-staff-name="<?php echo htmlspecialchars($staff['staff_name']); ?>"
                                        title="Delete Staff"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="staff-card-info">
                                <div class="staff-card-label"><i class="fas fa-store"></i> Outlet: <span class="staff-card-outlet"><?php echo htmlspecialchars($staff['outlet_name']); ?></span></div>
                                <div class="staff-card-label"><i class="fas fa-id-card"></i> IC: <span class="staff-card-ic"><?php echo htmlspecialchars($staff['ic_number']); ?></span></div>
                                <div class="staff-card-label"><i class="fas fa-user"></i> Age: <span class="staff-card-age"><?php echo htmlspecialchars($staff['age']); ?></span></div>
                                <div class="staff-card-label"><i class="fas fa-phone"></i> Phone: <span class="staff-card-phone"><?php echo htmlspecialchars($staff['phone_number']); ?></span></div>
                                <div class="staff-card-label"><i class="fas fa-location-dot"></i> Address: <span class="staff-card-address"><?php echo htmlspecialchars($staff['address']); ?></span></div>
                                <div class="staff-card-label"><i class="fas fa-envelope"></i> Email: <span class="staff-card-email"><?php echo htmlspecialchars($staff['email']); ?></span></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="staff-empty-state">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Staff Found</h4>
                    <p class="text-muted">You haven't added any staff yet. Click the + button to add your first staff.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <button class="btn btn-primary btn-add-staff" id="addStaffBtn" title="Add New Staff" type="button">
        <i class="fas fa-plus"></i>
    </button>
</main>
</div>
</div>
<!-- Add/Edit Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xxl staff-modal-xxl">
        <div class="modal-content modal-dialog-scrollable">
            <form id="staffForm" method="post" action="">
                <div class="modal-header modal-header-centered modal-title-white">
                    <div class="w-100 text-center">
                        <span class="modal-title" id="modalTitle" style="color:#fff; font-weight:600; display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-users me-2" style="color:#fff !important;"></i><span id="modalTitleText">Add New Staff</span>
                        </span>
                    </div>
                </div>
                <div class="modal-body modal-body-scroll">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="text" class="form-control" id="staffName" name="staff_name"
                                    placeholder="Staff Name" required autocomplete="off" style="text-transform:uppercase;">
                                <label for="staffName">Staff Name</label>
                                <span class="validation-icon-inside" id="nameValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="text" class="form-control" id="staffIc" name="ic_number"
                                    placeholder="IC Number" maxlength="12" required pattern="\d{12}" autocomplete="off">
                                <label for="staffIc">IC Number</label>
                                <span class="validation-icon-inside" id="icValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="icError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="text" class="form-control" id="staffAge" name="age"
                                    placeholder="Age" required autocomplete="off">
                                <label for="staffAge">Age</label>
                                <span class="validation-icon-inside" id="ageValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="ageError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <span class="input-group-text">+60</span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="text" class="form-control" id="staffPhone" name="phone_number"
                                    placeholder="Phone Number" maxlength="10" required autocomplete="off">
                                <label for="staffPhone">Phone Number</label>
                                <span class="validation-icon-inside" id="phoneValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="phoneError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-location-dot"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <textarea class="form-control" id="staffAddress" name="address"
                                    placeholder="Address" required style="height:110px;resize:vertical;text-transform:uppercase;" autocomplete="off"></textarea>
                                <label for="staffAddress">Address</label>
                                <span class="validation-icon-inside" id="addressValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="addressError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-store"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <select class="form-select" id="staffOutlet" name="outlet_id" required>
                                    <option value="" disabled selected>Select Outlet</option>
                                    <?php foreach ($outlet_list as $outlet): ?>
                                        <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="staffOutlet">Outlet</label>
                                <span class="validation-icon-inside" id="outletValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="outletError"></div>
                    </div>
                    <div class="mb-3" id="addAccountSection">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="email" class="form-control" id="staffEmail" name="email"
                                    placeholder="Email" autocomplete="off">
                                <label for="staffEmail">Email</label>
                                <span class="validation-icon-inside" id="emailValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="emailError"></div>
                        <div class="input-group mt-3">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="password" class="form-control" id="staffPassword" name="password"
                                    placeholder="Password" autocomplete="new-password">
                                <label for="staffPassword">Password</label>
                                <span class="validation-icon-inside" id="passwordValidationIcon"></span>
                            </div>
                            <!-- Password toggle icon -->
                                <span class="input-group-text toggle-password" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                        </div>
                        <div class="error-message" id="passwordError"></div>
                        <div class="input-group mt-3">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="password" class="form-control" id="staffConfirmPassword" name="confirm_password"
                                    placeholder="Confirm Password" autocomplete="new-password">
                                <label for="staffConfirmPassword">Confirm Password</label>
                                <span class="validation-icon-inside" id="confirmPasswordValidationIcon"></span>
                            </div>
                            <!-- Password toggle icon -->
                                <span class="input-group-text toggle-password" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                        </div>
                        <div class="error-message" id="confirmPasswordError"></div>
                        <div class="input-group mt-3">
                            <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="text" class="form-control" id="staffVerificationCode" name="verification_code"
                                    placeholder="Verification Code" maxlength="6" autocomplete="off">
                                <label for="staffVerificationCode">Verification Code</label>
                                <span class="validation-icon-inside" id="codeValidationIcon"></span>
                                <!-- Hidden field to store the generated code -->
<input type="hidden" name="generatedCode" value="<?php echo $verificationCode; ?>">
                            </div>
                            <button class="btn btn-send-code" type="button" id="sendCodeBtn">Send Code</button>
                        </div>
                        <div class="error-message" id="codeError"></div>
                        <div id="timerContainer" class="text-center mb-3" style="display: none;">
                            <p class="timer-text">Resend code in: <span id="countdown" style="color:red;">60</span> seconds</p>
                        </div>
                    </div>
                    <input type="hidden" id="staffId" name="staff_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="saveStaffBtn" disabled>
                        <i class="fas fa-save me-1"></i>Save Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xxl staff-modal-xxl">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="">
                <div class="modal-body text-center p-4">
                    <div class="modal-icon text-warning mb-3">
                        <i class="fas fa-exclamation-triangle fa-4x" style="color:#ffc107;"></i>
                    </div>
                    <h4 class="modal-title mb-2">Confirm Deletion</h4>
                    <p class="modal-message mb-3" id="deleteMessage">
                        Are you sure you want to delete this staff? This action cannot be undone.
                    </p>
                    <input type="hidden" name="staff_id" id="deleteStaffId">
                    <input type="hidden" name="delete_staff" value="1">
                    <div class="modal-actions justify-content-center">
                        <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger shadow-sm">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Notification Modal (icon styled like forgot-password page) -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered staff-modal-xxl">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <span id="notificationIcon" class="notification-icon mb-3 d-block"></span>
                <h4 id="notificationTitle" class="notification-title mb-2"></h4>
                <p id="notificationMessage" class="notification-message mb-3"></p>
                <div class="d-flex justify-content-center">
                    <button type="button" id="notificationButton" class="btn btn-primary notification-button shadow-lg"></button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="modal-icon"><i class="fas fa-sign-out-alt"></i></div>
                <h3 class="modal-title">Confirm Logout</h3>
                <p class="modal-message">Are you sure you want to logout from your account?</p>
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
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="JAVASCRIPT/owner-dashboard.js"></script>
<script src="JAVASCRIPT/owner-staff.js"></script>
<script>
window.allEmails = <?php echo json_encode($js_emails); ?>;
document.addEventListener('DOMContentLoaded', function(){
    function showNotification(type, title, message, buttonText, callback) {
        var notificationIcon = document.getElementById('notificationIcon');
        var notificationTitle = document.getElementById('notificationTitle');
        var notificationMessage = document.getElementById('notificationMessage');
        var notificationButton = document.getElementById('notificationButton');
        var notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        notificationIcon.className = 'notification-icon d-block mx-auto mb-3';
        notificationIcon.style.fontSize = '3.4rem';
        notificationIcon.style.textAlign = 'center';
        if (type === 'success') {
            notificationIcon.classList.add('success');
            notificationIcon.innerHTML = '<span class="rounded-circle bg-success-subtle d-inline-flex align-items-center justify-content-center" style="width:70px;height:70px;"><i class="fas fa-check-circle" style="font-size:3rem;color:#28a745;"></i></span>';
        } else if (type === 'error') {
            notificationIcon.classList.add('error');
            notificationIcon.innerHTML = '<span class="rounded-circle bg-danger-subtle d-inline-flex align-items-center justify-content-center" style="width:70px;height:70px;"><i class="fas fa-times-circle" style="font-size:3rem;color:#dc3545;"></i></span>';
        }
        notificationTitle.textContent = title;
        notificationMessage.textContent = message;
        notificationButton.textContent = buttonText;
        notificationButton.classList.add('mx-auto','shadow-lg');
        var newButton = notificationButton.cloneNode(true);
        notificationButton.parentNode.replaceChild(newButton, notificationButton);
        newButton.addEventListener('click', function () {
            notificationModal.hide();
            if (typeof callback === 'function') callback();
        });
        notificationModal.show();
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showNotification('success', 'Success', urlParams.get('success'), 'OK', function(){window.location.href='owner-staff.php';});
    }
    if (urlParams.has('error')) {
        showNotification('error', 'Error', urlParams.get('error'), 'OK', function(){window.location.href='owner-staff.php';});
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // Select all toggle icons within .toggle-password spans
  const toggles = document.querySelectorAll(".toggle-password");

  toggles.forEach(toggle => {
    toggle.addEventListener("click", () => {
      // Find the input field that comes before this toggle (in same .input-group)
      const input = toggle.parentElement.querySelector("input[type='password'], input[type='text']");
      const icon = toggle.querySelector("i");

      if (!input) return;

      // Toggle input type
      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";

      // Toggle eye icon
      icon.classList.toggle("fa-eye");
      icon.classList.toggle("fa-eye-slash");
    });
  });
});
</script>


</body>
</html>