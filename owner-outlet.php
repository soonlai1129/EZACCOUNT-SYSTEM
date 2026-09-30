<?php
/**
 * ezAccount System - Owner Outlet Management (Add Button Large, Position Safe, Responsive, Clean UI/UX)
 * - Add button is larger on all devices, with increased margin from corners
 * - Add button never overlays card container
 * - Cards have moderate border radius and spacing
 * - Validation icon is small and inside input/textarea
 * - Success/Error dialog icons styled like forgot-password page
 * - Fully responsive, ready for copy-paste
 */
session_start();

// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Add outlet
        if (isset($_POST['add_outlet'])) {
            $outletName = strtoupper(trim($_POST['outlet_name']));
            $outletAddress = strtoupper(trim($_POST['outlet_address']));
            $check_sql = "SELECT outlet_id FROM outlets WHERE owner_id = :owner_id AND TRIM(outlet_name) = :outlet_name";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([':owner_id'=>$ownerId, ':outlet_name'=>$outletName]);
            if ($check_stmt->rowCount() > 0) {
                header("Location: owner-outlet.php?error=" . urlencode("Outlet name already exists. Please choose a different name.")); exit();
            } else {
                $insert_sql = "INSERT INTO outlets (owner_id, outlet_name, outlet_address) VALUES (:owner_id, :outlet_name, :outlet_address)";
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt->execute([
                    ':owner_id'=>$ownerId,
                    ':outlet_name'=>$outletName,
                    ':outlet_address'=>$outletAddress
                ])) {
                    header("Location: owner-outlet.php?success=" . urlencode("Outlet added successfully!")); exit();
                } else {
                    header("Location: owner-outlet.php?error=" . urlencode("Error adding outlet. Please try again.")); exit();
                }
            }
        }
        // Edit outlet
        if (isset($_POST['edit_outlet'])) {
            $outletId = $_POST['outlet_id'];
            $outletName = strtoupper(trim($_POST['outlet_name']));
            $outletAddress = strtoupper(trim($_POST['outlet_address']));
            $check_sql = "SELECT outlet_id FROM outlets WHERE owner_id = :owner_id AND TRIM(outlet_name) = :outlet_name AND outlet_id != :outlet_id";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([
                ':owner_id'=>$ownerId,
                ':outlet_name'=>$outletName,
                ':outlet_id'=>$outletId
            ]);
            if ($check_stmt->rowCount() > 0) {
                header("Location: owner-outlet.php?error=" . urlencode("Outlet name already exists. Please choose a different name.")); exit();
            } else {
                $update_sql = "UPDATE outlets SET outlet_name = :outlet_name, outlet_address = :outlet_address WHERE outlet_id = :outlet_id AND owner_id = :owner_id";
                $update_stmt = $conn->prepare($update_sql);
                if ($update_stmt->execute([
                    ':outlet_name'=>$outletName,
                    ':outlet_address'=>$outletAddress,
                    ':outlet_id'=>$outletId,
                    ':owner_id'=>$ownerId
                ])) {
                    header("Location: owner-outlet.php?success=" . urlencode("Outlet updated successfully!")); exit();
                } else {
                    header("Location: owner-outlet.php?error=" . urlencode("Error updating outlet. Please try again.")); exit();
                }
            }
        }
        // Delete outlet + all related records (no schema changes)
if (isset($_POST['delete_outlet'])) {
    $outletId = (int)$_POST['outlet_id'];

    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->beginTransaction();

        // 0) Verify ownership and lock the outlet row
        $chk = $conn->prepare("
            SELECT 1 FROM outlets
            WHERE outlet_id = :outlet_id AND owner_id = :owner_id
            FOR UPDATE
        ");
        $chk->execute([':outlet_id' => $outletId, ':owner_id' => $ownerId]);
        if (!$chk->fetchColumn()) {
            $conn->rollBack();
            header("Location: owner-outlet.php?error=" . urlencode("Outlet not found or not yours.")); exit();
        }

        // 1) Delete closing report children (cash breakdown, payments) using joins
        $stmt = $conn->prepare("
            DELETE cbd
            FROM closing_report_cash_breakdown AS cbd
            JOIN closing_reports AS cr ON cr.report_id = cbd.report_id
            WHERE cr.outlet_id = :outlet_id
        ");
        $stmt->execute([':outlet_id' => $outletId]);

        $stmt = $conn->prepare("
            DELETE pay
            FROM closing_report_payments AS pay
            JOIN closing_reports AS cr ON cr.report_id = pay.report_id
            WHERE cr.outlet_id = :outlet_id
        ");
        $stmt->execute([':outlet_id' => $outletId]);

        // 2) Delete closing reports (for this outlet)
        $stmt = $conn->prepare("DELETE FROM closing_reports WHERE outlet_id = :outlet_id");
        $stmt->execute([':outlet_id' => $outletId]);

        // 2a) Delete Operating Expenses children (for this outlet)
$stmt = $conn->prepare("
    DELETE re
    FROM rental_expenses AS re
    JOIN operating_expense_records AS oer ON oer.record_id = re.record_id
    WHERE oer.outlet_id = :outlet_id
");
$stmt->execute([':outlet_id' => $outletId]);

$stmt = $conn->prepare("
    DELETE se
    FROM salary_expenses AS se
    JOIN operating_expense_records AS oer ON oer.record_id = se.record_id
    WHERE oer.outlet_id = :outlet_id
");
$stmt->execute([':outlet_id' => $outletId]);

$stmt = $conn->prepare("
    DELETE ue
    FROM utilities_expenses AS ue
    JOIN operating_expense_records AS oer ON oer.record_id = ue.record_id
    WHERE oer.outlet_id = :outlet_id
");
$stmt->execute([':outlet_id' => $outletId]);

$stmt = $conn->prepare("
    DELETE ae
    FROM advertisement_expenses AS ae
    JOIN operating_expense_records AS oer ON oer.record_id = ae.record_id
    WHERE oer.outlet_id = :outlet_id
");
$stmt->execute([':outlet_id' => $outletId]);

$stmt = $conn->prepare("
    DELETE oe
    FROM others_expenses AS oe
    JOIN operating_expense_records AS oer ON oer.record_id = oe.record_id
    WHERE oer.outlet_id = :outlet_id
");
$stmt->execute([':outlet_id' => $outletId]);

// 2b) Delete Operating Expense parent records (for this outlet)
$stmt = $conn->prepare("
    DELETE FROM operating_expense_records
    WHERE outlet_id = :outlet_id
");
$stmt->execute([':outlet_id' => $outletId]);


        // 3) Delete stock movements for items under this outlet's categories
        $stmt = $conn->prepare("
            DELETE sm
            FROM stock_movements AS sm
            JOIN stock_items AS si    ON si.item_id = sm.item_id
            JOIN stock_category AS sc ON sc.category_id = si.category_id
            WHERE sc.outlet_id = :outlet_id
        ");
        $stmt->execute([':outlet_id' => $outletId]);

        // 4) Delete stock items under this outlet's categories
        $stmt = $conn->prepare("
            DELETE si
            FROM stock_items AS si
            JOIN stock_category AS sc ON sc.category_id = si.category_id
            WHERE sc.outlet_id = :outlet_id
        ");
        $stmt->execute([':outlet_id' => $outletId]);

        // 5) Delete staff under this outlet
        $stmt = $conn->prepare("DELETE FROM staff WHERE outlet_id = :outlet_id");
        $stmt->execute([':outlet_id' => $outletId]);

        // 6) Delete stock categories under this outlet
        $stmt = $conn->prepare("DELETE FROM stock_category WHERE outlet_id = :outlet_id");
        $stmt->execute([':outlet_id' => $outletId]);

        // 7) Finally delete the outlet (owner check kept)
        $stmt = $conn->prepare("
            DELETE FROM outlets
            WHERE outlet_id = :outlet_id AND owner_id = :owner_id
        ");
        $stmt->execute([':outlet_id' => $outletId, ':owner_id' => $ownerId]);

        $conn->commit();
        header("Location: owner-outlet.php?success=" . urlencode("Outlet deleted successfully!")); exit();
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        // Optional: error_log($e->getMessage());
        header("Location: owner-outlet.php?error=" . urlencode("Error deleting outlet. Please try again.")); exit();
    }
}

    }

    $outlets_sql = "SELECT * FROM outlets WHERE owner_id = :owner_id ORDER BY outlet_name";
    $outlets_stmt = $conn->prepare($outlets_sql);
    $outlets_stmt->execute([':owner_id'=>$ownerId]);
    $outlets = $outlets_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>ezAccount System - Manage Outlets</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Shared Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <!-- Outlet-specific Styles -->
    <link rel="stylesheet" href="CSS/owner-outlet.css">
</head>
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
                            <a class="nav-link active" href="owner-outlet.php">
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
                    <a class="mini-nav-link active" href="owner-outlet.php" title="Outlet">
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
                            <h1>Manage Outlets</h1>
                            <p class="page-subtitle">Manage your business outlets - Add, edit, or remove outlets</p>
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
            <div class="content-container content-container-outlet">
                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($errorMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Responsive Card UI: vertical stack, moderate border radius, safe space for add button -->
                <div class="outlet-cards-container<?php if (count($outlets) == 0) echo ' outlets-empty-center'; ?>">
                    <?php if (count($outlets) > 0): ?>
                        <?php foreach ($outlets as $outlet): ?>
                            <div class="outlet-card" tabindex="0">
                                <div class="outlet-card-body">
                                    <div class="outlet-card-header">
                                        <span class="outlet-card-title">
                                            <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                        </span>
                                        <div class="outlet-card-actions">
                                            <button class="btn btn-sm btn-edit-action edit-outlet"
                                                data-outlet-id="<?php echo $outlet['outlet_id']; ?>"
                                                data-outlet-name="<?php echo htmlspecialchars($outlet['outlet_name']); ?>"
                                                data-outlet-address="<?php echo htmlspecialchars($outlet['outlet_address']); ?>"
                                                title="Edit Outlet"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-delete-action delete-outlet"
                                                data-outlet-id="<?php echo $outlet['outlet_id']; ?>"
                                                data-outlet-name="<?php echo htmlspecialchars($outlet['outlet_name']); ?>"
                                                title="Delete Outlet"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="outlet-card-info">
                                        <div class="outlet-card-label"><i class="fas fa-map-marker-alt me-2 purple-icon"></i>Address:</div>
                                        <div class="outlet-card-address"><?php echo htmlspecialchars($outlet['outlet_address']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="outlets-empty-state">
                            <i class="fas fa-store fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Outlets Found</h4>
                            <p class="text-muted">You haven't added any outlets yet. Click the + button to add your first outlet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Floating Add Button: always visible/fixed, larger, more margin from corners, never overlays cards -->
            <button class="btn btn-primary btn-add-outlet" id="addOutletBtn" title="Add New Outlet">
                <i class="fas fa-plus"></i>
            </button>
        </main>
    </div>
</div>
<!-- Add/Edit Outlet Modal (responsive, validation icon in input/textarea) -->
<div class="modal fade" id="outletModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xxl outlet-modal-xxl">
        <div class="modal-content">
            <form id="outletForm" method="post" action="">
                <div class="modal-header modal-header-centered modal-title-white">
                    <div class="w-100 text-center">
                        <span class="modal-title" id="modalTitle" style="color:#fff; font-weight:600; display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-store me-2" style="color:#fff !important;"></i><span id="modalTitleText">Add New Outlet</span>
                        </span>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-signature purple-icon"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <input type="text" class="form-control" id="outletName" name="outlet_name"
                                       placeholder="Outlet Name" required autocomplete="off" style="text-transform:uppercase;">
                                <label for="outletName">Outlet Name</label>
                                <span class="validation-icon-inside" id="nameValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt purple-icon"></i></span>
                            <div class="form-floating position-relative flex-grow-1">
                                <textarea class="form-control" id="outletAddress" name="outlet_address"
                                          placeholder="Outlet Address" required style="height: 110px; resize: vertical; text-transform:uppercase;"></textarea>
                                <label for="outletAddress">Outlet Address</label>
                                <span class="validation-icon-inside" id="addressValidationIcon"></span>
                            </div>
                        </div>
                        <div class="error-message" id="addressError"></div>
                    </div>
                    <input type="hidden" id="outletId" name="outlet_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm" id="saveOutletBtn" disabled>
                        <i class="fas fa-save me-1"></i>Save Outlet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xxl outlet-modal-xxl">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="">
                <div class="modal-body text-center p-4">
                    <div class="modal-icon text-warning mb-3">
                        <i class="fas fa-exclamation-triangle fa-4x" style="color:#ffc107;"></i>
                    </div>
                    <h4 class="modal-title mb-2">Confirm Deletion</h4>
                    <p class="modal-message mb-3" id="deleteMessage">
                        Are you sure you want to delete this outlet? This action cannot be undone.
                    </p>
                    <input type="hidden" name="outlet_id" id="deleteOutletId">
                    <input type="hidden" name="delete_outlet" value="1">
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
    <div class="modal-dialog modal-dialog-centered outlet-modal-xxl">
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
<!-- Logout Confirmation Modal (from dashboard, unchanged) -->
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
<!-- Shared Dashboard JS: sidebar/topbar/logout -->
<script src="JAVASCRIPT/owner-dashboard.js"></script>
<!-- Outlet-specific JS -->
<script src="JAVASCRIPT/owner-outlet.js"></script>
<script>
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
        // Use forgot-password style: big, rounded, colored icon
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
        showNotification('success', 'Success', urlParams.get('success'), 'OK', function(){window.location.href='owner-outlet.php';});
    }
    if (urlParams.has('error')) {
        showNotification('error', 'Error', urlParams.get('error'), 'OK', function(){window.location.href='owner-outlet.php';});
    }
});
</script>
</body>
</html>