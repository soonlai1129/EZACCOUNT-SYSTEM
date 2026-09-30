<?php
/**
 * ezAccount System - Edit Operating Expense
 * Allows editing of existing operating expense records
 */

session_start();
include('connection.php');

// Redirect if not business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

// User session data
$userID = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$companyName = $_SESSION['company_name'];

// Check if record ID is provided
if (!isset($_GET['id'])) {
    header("Location: operating-expenses-table.php");
    exit();
}

$recordId = intval($_GET['id']);

// Fetch the operating expense record data
$recordSql = "SELECT oer.*, o.outlet_name 
              FROM operating_expense_records oer 
              LEFT JOIN outlets o ON oer.outlet_id = o.outlet_id 
              WHERE oer.record_id = ?";
$stmt = $conn->prepare($recordSql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$recordResult = $stmt->get_result();
$record = $recordResult->fetch_assoc();

if (!$record) {
    header("Location: operating-expenses-table.php");
    exit();
}

// Fetch salary expenses for this record
$salarySql = "SELECT se.*, s.staff_name 
              FROM salary_expenses se 
              LEFT JOIN staff s ON se.staff_id = s.staff_id 
              WHERE se.record_id = ?";
$stmt = $conn->prepare($salarySql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$salaryResult = $stmt->get_result();
$salaryExpenses = [];
while ($salary = $salaryResult->fetch_assoc()) {
    $salaryExpenses[] = $salary;
}

// Fetch rental expenses for this record
$rentalSql = "SELECT * FROM rental_expenses WHERE record_id = ?";
$stmt = $conn->prepare($rentalSql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$rentalResult = $stmt->get_result();
$rentalExpenses = [];
while ($rental = $rentalResult->fetch_assoc()) {
    $rentalExpenses[] = $rental;
}

// Fetch utilities expenses for this record
$utilitiesSql = "SELECT * FROM utilities_expenses WHERE record_id = ?";
$stmt = $conn->prepare($utilitiesSql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$utilitiesResult = $stmt->get_result();
$utilitiesExpenses = [];
while ($utility = $utilitiesResult->fetch_assoc()) {
    $utilitiesExpenses[] = $utility;
}

// Fetch advertisement expenses for this record
$advertisementSql = "SELECT * FROM advertisement_expenses WHERE record_id = ?";
$stmt = $conn->prepare($advertisementSql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$advertisementResult = $stmt->get_result();
$advertisementExpenses = [];
while ($advertisement = $advertisementResult->fetch_assoc()) {
    $advertisementExpenses[] = $advertisement;
}

// Fetch others expenses for this record
$othersSql = "SELECT * FROM others_expenses WHERE record_id = ?";
$stmt = $conn->prepare($othersSql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$othersResult = $stmt->get_result();
$othersExpenses = [];
while ($other = $othersResult->fetch_assoc()) {
    $othersExpenses[] = $other;
}

// Initialize notification variable
$notification = null;

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $errors = [];
    
    // Required fields validation
    if (empty($_POST['record_date'])) {
        $errors[] = "Date is required";
    } elseif (strtotime($_POST['record_date']) > time()) {
        $errors[] = "Date cannot be in the future";
    }
    
    // Outlet validation
    if (empty($_POST['outlet_id'])) {
        $errors[] = "Outlet is required";
    }
    
    // Validate salary section - staff selection
    if (!empty($_POST['salary_staff_id'])) {
        foreach ($_POST['salary_staff_id'] as $index => $staff_id) {
            // Skip validation for rows with empty staff_id (these are N/A staff)
            if (empty($staff_id)) {
                continue; // Skip validation for N/A staff
            }
            
            if (!empty($staff_id) && (empty($_POST['salary_amount'][$index]) || floatval($_POST['salary_amount'][$index]) <= 0)) {
                $errors[] = "Salary amount must be greater than 0 for all salary entries";
                break;
            }
        }
    }
    
    // Validate other expense sections
    $otherSections = ['rental', 'utilities', 'advertisement', 'others'];
    foreach ($otherSections as $section) {
        if (!empty($_POST[$section . '_name'])) {
            foreach ($_POST[$section . '_name'] as $index => $name) {
                if (empty(trim($name)) && !empty($_POST[$section . '_amount'][$index])) {
                    $errors[] = ucfirst($section) . " expense name is required for all entries";
                    break;
                }
                
                if (!empty(trim($name)) && (empty($_POST[$section . '_amount'][$index]) || floatval($_POST[$section . '_amount'][$index]) <= 0)) {
                    $errors[] = ucfirst($section) . " amount must be greater than 0 for all entries";
                    break;
                }
            }
        }
    }
    
    // If there are validation errors, show notification
    if (!empty($errors)) {
        $notification = [
            'type' => 'error',
            'title' => 'Validation Error',
            'message' => '', // Empty message for errors as requested
            'buttonText' => 'OK'
        ];
    } else {
        // Get form data
        $outlet_id     = intval($_POST['outlet_id']);
        $record_date   = $_POST['record_date'];
        $total_expense = floatval($_POST['total_operating_expense']);

        // Update the operating_expense_records table
        $sql = "UPDATE operating_expense_records 
                SET outlet_id = ?, record_date = ?, total_operating_expense = ?
                WHERE record_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $outlet_id, $record_date, $total_expense, $recordId);
        
        if ($stmt->execute()) {
            // Delete existing expenses using prepared statements
            $deleteSalarySql = "DELETE FROM salary_expenses WHERE record_id = ?";
            $deleteSalaryStmt = $conn->prepare($deleteSalarySql);
            $deleteSalaryStmt->bind_param("i", $recordId);
            $deleteSalaryStmt->execute();

            $deleteRentalSql = "DELETE FROM rental_expenses WHERE record_id = ?";
            $deleteRentalStmt = $conn->prepare($deleteRentalSql);
            $deleteRentalStmt->bind_param("i", $recordId);
            $deleteRentalStmt->execute();

            $deleteUtilitiesSql = "DELETE FROM utilities_expenses WHERE record_id = ?";
            $deleteUtilitiesStmt = $conn->prepare($deleteUtilitiesSql);
            $deleteUtilitiesStmt->bind_param("i", $recordId);
            $deleteUtilitiesStmt->execute();

            $deleteAdvertisementSql = "DELETE FROM advertisement_expenses WHERE record_id = ?";
            $deleteAdvertisementStmt = $conn->prepare($deleteAdvertisementSql);
            $deleteAdvertisementStmt->bind_param("i", $recordId);
            $deleteAdvertisementStmt->execute();

            $deleteOthersSql = "DELETE FROM others_expenses WHERE record_id = ?";
            $deleteOthersStmt = $conn->prepare($deleteOthersSql);
            $deleteOthersStmt->bind_param("i", $recordId);
            $deleteOthersStmt->execute();
            
            // Save salary expenses if any using prepared statements
            if (!empty($_POST['salary_staff_id']) && !empty($_POST['salary_amount'])) {
                $salary_sql = "INSERT INTO salary_expenses (record_id, staff_id, amount) VALUES (?, ?, ?)";
                $salary_stmt = $conn->prepare($salary_sql);
                
                foreach ($_POST['salary_staff_id'] as $i => $staff_id) {
                    $amount = floatval($_POST['salary_amount'][$i]);
                    // Insert even if staff_id is empty (NULL) - this represents N/A staff
                    if ($amount > 0) {
                        $staff_id_value = !empty($staff_id) ? $staff_id : NULL;
                        $salary_stmt->bind_param("iid", $recordId, $staff_id_value, $amount);
                        $salary_stmt->execute();
                    }
                }
            }
            
            // Save rental expenses if any using prepared statements
            if (!empty($_POST['rental_name']) && !empty($_POST['rental_amount'])) {
                $rental_sql = "INSERT INTO rental_expenses (record_id, expense_name, amount) VALUES (?, ?, ?)";
                $rental_stmt = $conn->prepare($rental_sql);
                $rental_stmt->bind_param("isd", $recordId, $name, $amount);
                
                foreach ($_POST['rental_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['rental_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $rental_stmt->execute();
                    }
                }
            }
            
            // Save utilities expenses if any using prepared statements
            if (!empty($_POST['utilities_name']) && !empty($_POST['utilities_amount'])) {
                $utilities_sql = "INSERT INTO utilities_expenses (record_id, expense_name, amount) VALUES (?, ?, ?)";
                $utilities_stmt = $conn->prepare($utilities_sql);
                $utilities_stmt->bind_param("isd", $recordId, $name, $amount);
                
                foreach ($_POST['utilities_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['utilities_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $utilities_stmt->execute();
                    }
                }
            }
            
            // Save advertisement expenses if any using prepared statements
            if (!empty($_POST['advertisement_name']) && !empty($_POST['advertisement_amount'])) {
                $advertisement_sql = "INSERT INTO advertisement_expenses (record_id, expense_name, amount) VALUES (?, ?, ?)";
                $advertisement_stmt = $conn->prepare($advertisement_sql);
                $advertisement_stmt->bind_param("isd", $recordId, $name, $amount);
                
                foreach ($_POST['advertisement_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['advertisement_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $advertisement_stmt->execute();
                    }
                }
            }
            
            // Save others expenses if any using prepared statements
            if (!empty($_POST['others_name']) && !empty($_POST['others_amount'])) {
                $others_sql = "INSERT INTO others_expenses (record_id, expense_name, amount) VALUES (?, ?, ?)";
                $others_stmt = $conn->prepare($others_sql);
                $others_stmt->bind_param("isd", $recordId, $name, $amount);
                
                foreach ($_POST['others_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['others_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $others_stmt->execute();
                    }
                }
            }

            // Success notification
            $notification = [
                'type' => 'success',
                'title' => 'Success',
                'message' => 'Operating expense record has been successfully updated!',
                'buttonText' => 'OK'
            ];
        } else {
            // Database error notification
            $notification = [
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'There was a problem updating the record. Please try again.',
                'buttonText' => 'OK'
            ];
        }
    }
}

// Fetch dropdown data
$outlets = $conn->query("SELECT outlet_id, outlet_name FROM outlets WHERE owner_id = $userID");

// Fetch staff for the current outlet
$currentOutletId = $record['outlet_id'];
$staffs = $conn->query("SELECT staff_id, staff_name FROM staff WHERE outlet_id = $currentOutletId");

// Store staff data for JavaScript
$staffData = [];
while($staff = $staffs->fetch_assoc()) {
    $staffData[] = $staff;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Edit Operating Expense</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/owner-operating-expenses.css">
    
    <style>
        /* Notification Modal Styles (same as login page) */
        .notification-modal-content {
            border-radius: 15px;
            border: 2px solid #4895ef;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            transform: translateY(-20px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .modal.show .notification-modal-content {
            transform: translateY(0);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .modal-body {
            padding: 30px;
        }

        .notification-icon {
            font-size: 70px;
            margin-bottom: 20px;
            display: block;
        }

        .notification-icon.success {
            color: #28a745;
        }

        .notification-icon.error {
            color: #dc3545;
        }

        .notification-title {
            color: #212529;
            margin-bottom: 15px;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .notification-message {
            color: #6c757d;
            margin-bottom: 25px;
            line-height: 1.5;
            font-size: 1rem;
        }

        .notification-button {
            background: #4361ee;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }

        .notification-button:hover {
            background: #3f37c9;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.45);
            color: white;
        }

        .notification-button:active {
            transform: translateY(0);
            box-shadow: 0 3px 6px rgba(67, 97, 238, 0.3);
        }

        /* Modal backdrop */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-backdrop.show {
            opacity: 1;
        }

        /* Prevent closing modal on backdrop click */
        .modal {
            pointer-events: none;
        }

        .modal-dialog {
            pointer-events: auto;
        }

        /* Responsive adjustments for mobile devices */
        @media (max-width: 576px) {
            .modal-body {
                padding: 20px;
            }

            .notification-icon {
                font-size: 60px;
                margin-bottom: 15px;
            }

            .notification-title {
                font-size: 1.3rem;
            }

            .notification-message {
                font-size: 0.9rem;
            }

            .notification-button {
                padding: 10px 25px;
            }
        }
        
        /* Form validation styles */
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        /* ===============================
   Center the buttons
   =============================== */
.expenses-form .submit-expenses-btn {
    display: inline-flex !important;
    justify-content: center;
    align-items: center;
    width: auto !important;
    max-width: 100%;
    margin: 20px 10px 0 10px !important;
    text-align: center;
}

        /* ===== Center the OK button in the Notification Modal (mobile + all sizes) ===== */
#notificationModal .modal-body {
  text-align: center !important;
}

#notificationModal .notification-button {
  display: inline-flex !important;
  justify-content: center;
  align-items: center;
  width: auto !important;
  max-width: 100%;
  margin: 0 auto !important;
}

/* If you also want the Logout modal buttons centered on mobile */
#logoutModal .modal-actions {
  display: flex;
  justify-content: center !important;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
#logoutModal .modal-actions .btn {
  width: auto !important;
}

/* Expense table responsive container */
.expense-table-container {
    width: 100%;
    overflow-x: auto;
}

.expense-table-container .expense-table {
    min-width: 100%;
    margin-bottom: 0;
}

/* Ensure proper width for table columns */
.expense-table th:nth-child(1),
.expense-table td:nth-child(1) {
    width: 60%;
}

.expense-table th:nth-child(2),
.expense-table td:nth-child(2) {
    width: 40%;
}

/* Total operating expenses styling */
.total-operating-expenses {
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    font-weight: 600 !important;
    text-align: center !important;
    font-size: 1.1rem !important;
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    font-weight: 600;
}
        
/* Force the success notification modal to be truly centered on phones/tablets */
@media (max-width: 992px) {
  /* Center the dialog in the viewport, independent of page layout/animations */
  #notificationModal .modal-dialog {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    width: 95% !important;
    max-width: 100vw !important;
  }

  /* Ensure the content fits and can scroll if needed */
  #notificationModal .modal-content {
    max-height: 90dvh;
    overflow: auto;
  }

  /* Kill any custom vertical nudge/animation that can misalign it */
  #notificationModal .notification-modal-content {
    transform: none !important;
  }
}

.na-staff-display {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    color: #6c757d;
    font-style: italic;
}

.loading-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.submit-expenses-btn {
  font-size: 1.05rem;   /* slightly larger text */
  padding: 0.8rem 1.5rem; /* slightly taller & wider */
}

    </style>
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
<a class="mini-nav-link" href="operating-expenses-table.php" title="Operating Expenses">
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
                            <h1>Edit Operating Expense</h1>
                            <p class="page-subtitle">Update your business operating expenses – Track salary, rental, utilities, and more</p>
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
                <div class="container py-4">
    
  <form method="POST" id="operatingExpensesForm" class="expenses-form" novalidate>

    <!-- Section 1: Basic Info -->
<div class="form-section card p-3 mb-3">
    <h4>📋 Basic Info</h4>

    <!-- Date -->
    <div class="mb-3">
        <label>Date</label>
         <input type="date" name="record_date" id="record_date" class="form-control"
         value="<?= htmlspecialchars($record['record_date']) ?>">
         <div class="invalid-feedback">Date is required and cannot be in the future</div>
    </div>

    <!-- Outlet -->
    <div class="mb-3">
        <label>Outlet</label>
        <div class="input-group">
            <select name="outlet_id" id="outletSelect" class="form-control">
                <option value="" disabled selected>-- Select Outlet --</option>
                <?php 
                $outlets->data_seek(0); // Reset pointer
                while($o = $outlets->fetch_assoc()): ?>
                    <option value="<?= $o['outlet_id'] ?>" <?= $record['outlet_id'] == $o['outlet_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($o['outlet_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <div class="loading-spinner" id="outletLoading"></div>
             <div class="invalid-feedback">Outlet is required</div>
        </div>
    </div>
</div>

    <!-- Section 2: Operating Expenses -->
    <div class="form-section card p-3 mb-3">
        <h4>💰 Operating Expenses</h4>

        <!-- Salary Expenses -->
        <div class="expense-section mb-4">
            <h5 class="text-primary">💼 Salary Expenses</h5>
            <div class="expense-table-container">
                <table class="table table-bordered expense-table" id="salaryTable">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody id="salaryTableBody">
                        <?php if (!empty($salaryExpenses)): ?>
                            <?php foreach ($salaryExpenses as $salary): ?>
                                <tr>
                                    <td>
                                        <?php if ($salary['staff_id'] === null): ?>
                                            <!-- For deleted staff (staff_id is NULL), show N/A as display only -->
                                            <div class="na-staff-display">N/A (Deleted Staff)</div>
                                            <input type="hidden" name="salary_staff_id[]" value="">
                                        <?php else: ?>
                                            <!-- Display dropdown for existing staff -->
                                            <select name="salary_staff_id[]" class="form-control staff-select">
                                                <option value="" disabled>-- Please Select --</option>
                                                <?php 
                                                $staffs->data_seek(0); // Reset pointer
                                                while($s = $staffs->fetch_assoc()): ?>
                                                    <option value="<?= $s['staff_id'] ?>" <?= ($salary['staff_id'] == $s['staff_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($s['staff_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        <?php endif; ?>
                                        <div class="invalid-feedback">Staff selection is required</div>
                                    </td>
                                    <td>
                                        <input type="number" name="salary_amount[]" class="form-control salary-amount" step="0.01" min="0.01" placeholder="0.00" value="<?= htmlspecialchars($salary['amount']) ?>">
                                        <div class="invalid-feedback">Salary amount must be greater than 0</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- No salary records - table will be empty, user adds rows themselves -->
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Salary</th>
                            <th><input type="text" id="total_salary" class="form-control expense-total" readonly value="<?= number_format(array_sum(array_column($salaryExpenses, 'amount')), 2) ?>"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Buttons side by side -->
            <div class="d-flex gap-2 mb-3">
                <button type="button" id="addSalaryRow" class="btn btn-outline-primary">+ Add Row</button>
                <button type="button" id="deleteSalaryRow" class="btn btn-outline-danger">- Delete Row</button>
            </div>
        </div>

        <!-- Rental Expenses -->
        <div class="expense-section mb-4">
            <h5 class="text-primary">🏢 Rental Expenses</h5>
            <div class="expense-table-container">
                <table class="table table-bordered expense-table" id="rentalTable">
                    <thead>
                        <tr>
                            <th>Expense Name</th>
                            <th>Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody id="rentalTableBody">
                        <?php if (!empty($rentalExpenses)): ?>
                            <?php foreach ($rentalExpenses as $rental): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="rental_name[]" class="form-control" placeholder="Enter rental expense name" value="<?= htmlspecialchars($rental['expense_name']) ?>">
                                        <div class="invalid-feedback">Rental expense name is required</div>
                                    </td>
                                    <td>
                                        <input type="number" name="rental_amount[]" class="form-control rental-amount" step="0.01" min="0.01" placeholder="0.00" value="<?= htmlspecialchars($rental['amount']) ?>">
                                        <div class="invalid-feedback">Rental amount must be greater than 0</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Rental</th>
                            <th><input type="text" id="total_rental" class="form-control expense-total" readonly value="<?= number_format(array_sum(array_column($rentalExpenses, 'amount')), 2) ?>"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Buttons side by side -->
            <div class="d-flex gap-2 mb-3">
                <button type="button" id="addRentalRow" class="btn btn-outline-primary">+ Add Row</button>
                <button type="button" id="deleteRentalRow" class="btn btn-outline-danger">- Delete Row</button>
            </div>
        </div>

        <!-- Utilities Expenses -->
        <div class="expense-section mb-4">
            <h5 class="text-primary">⚡ Utilities Expenses</h5>
            <div class="expense-table-container">
                <table class="table table-bordered expense-table" id="utilitiesTable">
                    <thead>
                        <tr>
                            <th>Expense Name</th>
                            <th>Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody id="utilitiesTableBody">
                        <?php if (!empty($utilitiesExpenses)): ?>
                            <?php foreach ($utilitiesExpenses as $utility): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="utilities_name[]" class="form-control" placeholder="Enter utilities expense name" value="<?= htmlspecialchars($utility['expense_name']) ?>">
                                        <div class="invalid-feedback">Utilities expense name is required</div>
                                    </td>
                                    <td>
                                        <input type="number" name="utilities_amount[]" class="form-control utilities-amount" step="0.01" min="0.01" placeholder="0.00" value="<?= htmlspecialchars($utility['amount']) ?>">
                                        <div class="invalid-feedback">Utilities amount must be greater than 0</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Utilities</th>
                            <th><input type="text" id="total_utilities" class="form-control expense-total" readonly value="<?= number_format(array_sum(array_column($utilitiesExpenses, 'amount')), 2) ?>"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Buttons side by side -->
            <div class="d-flex gap-2 mb-3">
                <button type="button" id="addUtilitiesRow" class="btn btn-outline-primary">+ Add Row</button>
                <button type="button" id="deleteUtilitiesRow" class="btn btn-outline-danger">- Delete Row</button>
            </div>
        </div>

        <!-- Advertisement Expenses -->
        <div class="expense-section mb-4">
            <h5 class="text-primary">📢 Advertisement Expenses</h5>
            <div class="expense-table-container">
                <table class="table table-bordered expense-table" id="advertisementTable">
                    <thead>
                        <tr>
                            <th>Expense Name</th>
                            <th>Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody id="advertisementTableBody">
                        <?php if (!empty($advertisementExpenses)): ?>
                            <?php foreach ($advertisementExpenses as $advertisement): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="advertisement_name[]" class="form-control" placeholder="Enter advertisement expense name" value="<?= htmlspecialchars($advertisement['expense_name']) ?>">
                                        <div class="invalid-feedback">Advertisement expense name is required</div>
                                    </td>
                                    <td>
                                        <input type="number" name="advertisement_amount[]" class="form-control advertisement-amount" step="0.01" min="0.01" placeholder="0.00" value="<?= htmlspecialchars($advertisement['amount']) ?>">
                                        <div class="invalid-feedback">Advertisement amount must be greater than 0</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Advertisement</th>
                            <th><input type="text" id="total_advertisement" class="form-control expense-total" readonly value="<?= number_format(array_sum(array_column($advertisementExpenses, 'amount')), 2) ?>"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Buttons side by side -->
            <div class="d-flex gap-2 mb-3">
                <button type="button" id="addAdvertisementRow" class="btn btn-outline-primary">+ Add Row</button>
                <button type="button" id="deleteAdvertisementRow" class="btn btn-outline-danger">- Delete Row</button>
            </div>
        </div>

        <!-- Others Expenses -->
        <div class="expense-section mb-4">
            <h5 class="text-primary">📝 Others Expenses</h5>
            <div class="expense-table-container">
                <table class="table table-bordered expense-table" id="othersTable">
                    <thead>
                        <tr>
                            <th>Expense Name</th>
                            <th>Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody id="othersTableBody">
                        <?php if (!empty($othersExpenses)): ?>
                            <?php foreach ($othersExpenses as $other): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="others_name[]" class="form-control" placeholder="Enter other expense name" value="<?= htmlspecialchars($other['expense_name']) ?>">
                                        <div class="invalid-feedback">Other expense name is required</div>
                                    </td>
                                    <td>
                                        <input type="number" name="others_amount[]" class="form-control others-amount" step="0.01" min="0.01" placeholder="0.00" value="<?= htmlspecialchars($other['amount']) ?>">
                                        <div class="invalid-feedback">Other amount must be greater than 0</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Others</th>
                            <th><input type="text" id="total_others" class="form-control expense-total" readonly value="<?= number_format(array_sum(array_column($othersExpenses, 'amount')), 2) ?>"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Buttons side by side -->
            <div class="d-flex gap-2 mb-3">
                <button type="button" id="addOthersRow" class="btn btn-outline-primary">+ Add Row</button>
                <button type="button" id="deleteOthersRow" class="btn btn-outline-danger">- Delete Row</button>
            </div>
        </div>

        <!-- Total Operating Expenses -->
        <div class="row mt-4">
            <div class="col-md-6 offset-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">💰 Total Operating Expenses</h5>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="text" id="total_operating_expense" name="total_operating_expense" class="form-control total-operating-expenses" readonly value="<?= number_format($record['total_operating_expense'], 2) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="text-center mt-4">
        <a href="operating-expenses-table.php" class="btn btn-secondary submit-expenses-btn">
            <i class="fas fa-arrow-left me-2"></i>Back to Operating Expenses
        </a>
        <button type="submit" class="btn btn-warning submit-expenses-btn">
            <i class="fas fa-edit me-2"></i>Update Operating Expenses
        </button>
    </div>
</form>
</div>

            </main>
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

    <!-- Bootstrap JavaScript with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Dashboard JavaScript -->
    <script src="JAVASCRIPT/owner-dashboard.js"></script>
    
    
    <script>
        // Store staff data globally for add row functionality
        let currentStaffData = <?php echo json_encode($staffData); ?>;

        // Function to load staff for selected outlet
        function loadStaffForOutlet(outletId) {
            if (!outletId) return;
            
            // Show loading spinner
            const loadingSpinner = document.getElementById('outletLoading');
            loadingSpinner.style.display = 'inline-block';
            
            // Clear all salary rows when changing outlet
            document.getElementById('salaryTableBody').innerHTML = '';
            
            // Create AJAX request to fetch staff data
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'get-staff-by-outlet.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                loadingSpinner.style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        // Store staff data globally
                        currentStaffData = response.data || [];
                        
                    } catch (e) {
                        console.error('Error parsing staff data:', e);
                        currentStaffData = [];
                    }
                } else {
                    currentStaffData = [];
                }
                
                // Reset salary total
                document.getElementById('total_salary').value = '0.00';
                calculateTotals();
            };
            
            xhr.onerror = function() {
                loadingSpinner.style.display = 'none';
                currentStaffData = [];
                document.getElementById('total_salary').value = '0.00';
                calculateTotals();
            };
            
            xhr.send('outlet_id=' + encodeURIComponent(outletId));
        }

        // Helper function to add salary row with staff data
        function addSalaryRowWithStaff() {
            const tableBody = document.getElementById('salaryTableBody');
            const newRow = document.createElement('tr');
            
            let staffOptions = '<option value="" disabled selected>-- Please Select --</option>';
            if (currentStaffData && currentStaffData.length > 0) {
                staffOptions += currentStaffData.map(staff => `<option value="${staff.staff_id}">${staff.staff_name}</option>`).join('');
            } else {
                staffOptions = '<option value="" disabled selected>-- No Staff Available --</option>';
            }
            
            newRow.innerHTML = `
                <td>
                    <select name="salary_staff_id[]" class="form-control staff-select">
                        ${staffOptions}
                    </select>
                    <div class="invalid-feedback">Staff selection is required</div>
                </td>
                <td>
                    <input type="number" name="salary_amount[]" class="form-control salary-amount" step="0.01" min="0.01" placeholder="0.00">
                    <div class="invalid-feedback">Salary amount must be greater than 0</div>
                </td>
            `;
            tableBody.appendChild(newRow);
        }

        // Event listener for outlet change
        document.getElementById('outletSelect').addEventListener('change', function() {
            const outletId = this.value;
            loadStaffForOutlet(outletId);
        });

        // Calculate totals function
        function calculateTotals() {
            // Calculate salary total
            let salaryTotal = 0;
            document.querySelectorAll('.salary-amount').forEach(input => {
                const val = parseFloat(input.value) || 0;
                salaryTotal += val;
            });
            document.getElementById('total_salary').value = salaryTotal.toFixed(2);

            // Calculate rental total
            let rentalTotal = 0;
            document.querySelectorAll('.rental-amount').forEach(input => {
                const val = parseFloat(input.value) || 0;
                rentalTotal += val;
            });
            document.getElementById('total_rental').value = rentalTotal.toFixed(2);

            // Calculate utilities total
            let utilitiesTotal = 0;
            document.querySelectorAll('.utilities-amount').forEach(input => {
                const val = parseFloat(input.value) || 0;
                utilitiesTotal += val;
            });
            document.getElementById('total_utilities').value = utilitiesTotal.toFixed(2);

            // Calculate advertisement total
            let advertisementTotal = 0;
            document.querySelectorAll('.advertisement-amount').forEach(input => {
                const val = parseFloat(input.value) || 0;
                advertisementTotal += val;
            });
            document.getElementById('total_advertisement').value = advertisementTotal.toFixed(2);

            // Calculate others total
            let othersTotal = 0;
            document.querySelectorAll('.others-amount').forEach(input => {
                const val = parseFloat(input.value) || 0;
                othersTotal += val;
            });
            document.getElementById('total_others').value = othersTotal.toFixed(2);

            // Calculate grand total
            const grandTotal = salaryTotal + rentalTotal + utilitiesTotal + advertisementTotal + othersTotal;
            document.getElementById('total_operating_expense').value = grandTotal.toFixed(2);
        }

        // Add event listeners for amount inputs to calculate totals
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('salary-amount') || 
                e.target.classList.contains('rental-amount') ||
                e.target.classList.contains('utilities-amount') ||
                e.target.classList.contains('advertisement-amount') ||
                e.target.classList.contains('others-amount')) {
                calculateTotals();
            }
        });

        // Add row functionality
        document.getElementById('addSalaryRow').addEventListener('click', function() {
            const currentOutletId = document.getElementById('outletSelect').value;
            
            if (!currentOutletId) {
                alert('Please select an outlet first');
                return;
            }
            
            // Use the stored staff data to add a row
            addSalaryRowWithStaff();
        });

        // Add similar functionality for other expense types
        document.getElementById('addRentalRow').addEventListener('click', function() {
            const tableBody = document.getElementById('rentalTableBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="rental_name[]" class="form-control" placeholder="Enter rental expense name">
                    <div class="invalid-feedback">Rental expense name is required</div>
                </td>
                <td>
                    <input type="number" name="rental_amount[]" class="form-control rental-amount" step="0.01" min="0.01" placeholder="0.00">
                    <div class="invalid-feedback">Rental amount must be greater than 0</div>
                </td>
            `;
            tableBody.appendChild(newRow);
        });

        document.getElementById('addUtilitiesRow').addEventListener('click', function() {
            const tableBody = document.getElementById('utilitiesTableBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="utilities_name[]" class="form-control" placeholder="Enter utilities expense name">
                    <div class="invalid-feedback">Utilities expense name is required</div>
                </td>
                <td>
                    <input type="number" name="utilities_amount[]" class="form-control utilities-amount" step="0.01" min="0.01" placeholder="0.00">
                    <div class="invalid-feedback">Utilities amount must be greater than 0</div>
                </td>
            `;
            tableBody.appendChild(newRow);
        });

        document.getElementById('addAdvertisementRow').addEventListener('click', function() {
            const tableBody = document.getElementById('advertisementTableBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="advertisement_name[]" class="form-control" placeholder="Enter advertisement expense name">
                    <div class="invalid-feedback">Advertisement expense name is required</div>
                </td>
                <td>
                    <input type="number" name="advertisement_amount[]" class="form-control advertisement-amount" step="0.01" min="0.01" placeholder="0.00">
                    <div class="invalid-feedback">Advertisement amount must be greater than 0</div>
                </td>
            `;
            tableBody.appendChild(newRow);
        });

        document.getElementById('addOthersRow').addEventListener('click', function() {
            const tableBody = document.getElementById('othersTableBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" name="others_name[]" class="form-control" placeholder="Enter other expense name">
                    <div class="invalid-feedback">Other expense name is required</div>
                </td>
                <td>
                    <input type="number" name="others_amount[]" class="form-control others-amount" step="0.01" min="0.01" placeholder="0.00">
                    <div class="invalid-feedback">Other amount must be greater than 0</div>
                </td>
            `;
            tableBody.appendChild(newRow);
        });

        // Delete row functionality - allow complete deletion of all rows
        document.getElementById('deleteSalaryRow').addEventListener('click', function() {
            const tableBody = document.getElementById('salaryTableBody');
            if (tableBody.children.length > 0) {
                tableBody.removeChild(tableBody.lastChild);
                calculateTotals();
            }
        });

        document.getElementById('deleteRentalRow').addEventListener('click', function() {
            const tableBody = document.getElementById('rentalTableBody');
            if (tableBody.children.length > 0) {
                tableBody.removeChild(tableBody.lastChild);
                calculateTotals();
            }
        });

        document.getElementById('deleteUtilitiesRow').addEventListener('click', function() {
            const tableBody = document.getElementById('utilitiesTableBody');
            if (tableBody.children.length > 0) {
                tableBody.removeChild(tableBody.lastChild);
                calculateTotals();
            }
        });

        document.getElementById('deleteAdvertisementRow').addEventListener('click', function() {
            const tableBody = document.getElementById('advertisementTableBody');
            if (tableBody.children.length > 0) {
                tableBody.removeChild(tableBody.lastChild);
                calculateTotals();
            }
        });

        document.getElementById('deleteOthersRow').addEventListener('click', function() {
            const tableBody = document.getElementById('othersTableBody');
            if (tableBody.children.length > 0) {
                tableBody.removeChild(tableBody.lastChild);
                calculateTotals();
            }
        });

        // Notification script
        function showNotification(type, title, message, buttonText) {
            const notificationModalElement = document.getElementById('notificationModal');
            const notificationModal = new bootstrap.Modal(notificationModalElement, {
                backdrop: 'static',
                keyboard: false
            });
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationTitle = document.getElementById('notificationTitle');
            const notificationMessage = document.getElementById('notificationMessage');
            const notificationButton = document.getElementById('notificationButton');

            // Clear previous classes and set new ones
            notificationIcon.className = 'notification-icon';
            if (type === 'success') {
                notificationIcon.classList.add('success');
                notificationIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                notificationMessage.style.display = 'block';
            } else if (type === 'error') {
                notificationIcon.classList.add('error');
                notificationIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
                notificationMessage.style.display = 'none';
            }

            notificationTitle.textContent = title;
            notificationMessage.innerHTML = message;
            notificationButton.textContent = buttonText;

            // Remove old event listeners by replacing the button
            const newButton = notificationButton.cloneNode(true);
            notificationButton.parentNode.replaceChild(newButton, notificationButton);

            // Add event listener to the new button
            newButton.addEventListener('click', function() {
                notificationModal.hide();
                <?php if (isset($notification) && $notification['type'] === 'success'): ?>
                    window.location.href = 'operating-expenses-table.php';
                <?php endif; ?>
            });

            notificationModal.show();
        }

        // Form validation - Fixed to prevent duplicate error messages
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('operatingExpensesForm');
            
            form.addEventListener('submit', function(e) {
                // Clear previous validation
                const invalidInputs = form.querySelectorAll('.is-invalid');
                invalidInputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
                
                // Clear all error messages
                const errorMessages = form.querySelectorAll('.invalid-feedback');
                errorMessages.forEach(msg => {
                    msg.style.display = 'none';
                });
                
                let isValid = true;
                
                // Validate date
                const recordDate = document.getElementById('record_date');
                const recordDateFeedback = recordDate.nextElementSibling;
                if (!recordDate.value) {
                    recordDate.classList.add('is-invalid');
                    if (recordDateFeedback) recordDateFeedback.style.display = 'block';
                    isValid = false;
                } else if (new Date(recordDate.value) > new Date()) {
                    recordDate.classList.add('is-invalid');
                    if (recordDateFeedback) recordDateFeedback.style.display = 'block';
                    isValid = false;
                } else {
                    if (recordDateFeedback) recordDateFeedback.style.display = 'none';
                }
                
                // Validate outlet
                const outlet = document.getElementById('outletSelect');
                const outletFeedback = outlet.nextElementSibling;
                if (!outlet.value) {
                    outlet.classList.add('is-invalid');
                    if (outletFeedback) outletFeedback.style.display = 'block';
                    isValid = false;
                } else {
                    outlet.classList.remove('is-invalid');
                    if (outletFeedback) outletFeedback.style.display = 'none';
                }

                // Validate salary section - skip validation for N/A staff (empty staff_id)
                if (!validateSalarySection()) {
                    isValid = false;
                }

                // Validate other expense sections
                const otherSections = ['rental', 'utilities', 'advertisement', 'others'];
                otherSections.forEach(section => {
                    if (!validateGenericExpenseSection(section)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Show notification with errors
                    showNotification(
                        'error',
                        'Validation Error',
                        '',
                        'OK'
                    );
                }
            });

            function validateSalarySection() {
                const salaryTable = document.getElementById('salaryTable');
                const staffSelects = salaryTable.querySelectorAll('select[name="salary_staff_id[]"]');
                const amountInputs = salaryTable.querySelectorAll('input[name="salary_amount[]"]');
                let isValid = true;
                
                for (let i = 0; i < staffSelects.length; i++) {
                    const staffSelect = staffSelects[i];
                    const amountInput = amountInputs[i];
                    const staffFeedback = staffSelect.nextElementSibling;
                    const amountFeedback = amountInput.nextElementSibling;
                    
                    // Clear previous validation
                    staffSelect.classList.remove('is-invalid');
                    amountInput.classList.remove('is-invalid');
                    if (staffFeedback) staffFeedback.style.display = 'none';
                    if (amountFeedback) amountFeedback.style.display = 'none';
                    
                    // Skip validation for N/A staff (these have empty staff_id)
                    if (!staffSelect.value) {
                        continue; // Skip validation for N/A staff rows
                    }
                    
                    // If staff is selected but amount is empty or zero
                    if (staffSelect.value && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
                        staffSelect.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        if (amountFeedback) amountFeedback.style.display = 'block';
                        isValid = false;
                    }
                    
                    // If amount is provided but staff is not selected (should not happen for N/A staff)
                    if (amountInput.value && parseFloat(amountInput.value) > 0 && !staffSelect.value) {
                        staffSelect.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        if (staffFeedback) staffFeedback.style.display = 'block';
                        isValid = false;
                    }
                }
                
                return isValid;
            }

            function validateGenericExpenseSection(type) {
                const table = document.getElementById(`${type}Table`);
                const nameInputs = table.querySelectorAll(`input[name="${type}_name[]"]`);
                const amountInputs = table.querySelectorAll(`input[name="${type}_amount[]"]`);
                let isValid = true;
                
                for (let i = 0; i < nameInputs.length; i++) {
                    const nameInput = nameInputs[i];
                    const amountInput = amountInputs[i];
                    const nameFeedback = nameInput.nextElementSibling;
                    const amountFeedback = amountInput.nextElementSibling;
                    
                    // Clear previous validation
                    nameInput.classList.remove('is-invalid');
                    amountInput.classList.remove('is-invalid');
                    if (nameFeedback) nameFeedback.style.display = 'none';
                    if (amountFeedback) amountFeedback.style.display = 'none';
                    
                    // If name is provided but amount is empty or zero
                    if (nameInput.value.trim() && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
                        nameInput.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        if (amountFeedback) amountFeedback.style.display = 'block';
                        isValid = false;
                    }
                    
                    // If amount is provided but name is empty
                    if (amountInput.value && parseFloat(amountInput.value) > 0 && !nameInput.value.trim()) {
                        nameInput.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        if (nameFeedback) nameFeedback.style.display = 'block';
                        isValid = false;
                    }
                }
                
                return isValid;
            }
        });

        // Show notification if set by PHP
        <?php if ($notification): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification(
                '<?php echo $notification['type']; ?>',
                '<?php echo $notification['title']; ?>',
                '<?php echo $notification['message']; ?>',
                '<?php echo $notification['buttonText']; ?>'
            );
        });
        <?php endif; ?>
    </script>
</body>
</html>