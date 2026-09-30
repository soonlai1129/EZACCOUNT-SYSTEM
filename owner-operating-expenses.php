<?php
/**
 * ezAccount System - Owner Operating Expenses
 * Dynamic operating expenses tracking system with categorized expenses
 */

session_start();
include('connection.php');

// Redirect if not business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit(); 
}

// User session data
$userID       = $_SESSION['user_id'];
$userName     = $_SESSION['user_name'];
$userEmail    = $_SESSION['user_email'];
$companyName  = $_SESSION['company_name'];

// Initialize notification variable
$notification = null;

// Handle form submission
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
            if (empty($staff_id) && !empty($_POST['salary_amount'][$index])) {
                $errors[] = "Staff selection is required for all salary entries";
                break;
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

        // Insert into operating_expense_records table
        $sql = "INSERT INTO operating_expense_records 
                (outlet_id, record_date, total_operating_expense)
                VALUES ($outlet_id, '$record_date', $total_expense)";
        
        if ($conn->query($sql) === TRUE) {
            $record_id = $conn->insert_id;
            
            // Save salary expenses
            if (!empty($_POST['salary_staff_id']) && !empty($_POST['salary_amount'])) {
                foreach ($_POST['salary_staff_id'] as $i => $staff_id) {
                    $amount = floatval($_POST['salary_amount'][$i]);
                    if (!empty($staff_id) && $amount > 0) {
                        $salary_sql = "INSERT INTO salary_expenses (record_id, staff_id, amount) 
                                        VALUES ($record_id, $staff_id, $amount)";
                        $conn->query($salary_sql);
                    }
                }
            }
            
            // Save rental expenses
            if (!empty($_POST['rental_name']) && !empty($_POST['rental_amount'])) {
                foreach ($_POST['rental_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['rental_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $rental_sql = "INSERT INTO rental_expenses (record_id, expense_name, amount) 
                                        VALUES ($record_id, '$name', $amount)";
                        $conn->query($rental_sql);
                    }
                }
            }
            
            // Save utilities expenses
            if (!empty($_POST['utilities_name']) && !empty($_POST['utilities_amount'])) {
                foreach ($_POST['utilities_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['utilities_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $utilities_sql = "INSERT INTO utilities_expenses (record_id, expense_name, amount) 
                                        VALUES ($record_id, '$name', $amount)";
                        $conn->query($utilities_sql);
                    }
                }
            }
            
            // Save advertisement expenses
            if (!empty($_POST['advertisement_name']) && !empty($_POST['advertisement_amount'])) {
                foreach ($_POST['advertisement_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['advertisement_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $advertisement_sql = "INSERT INTO advertisement_expenses (record_id, expense_name, amount) 
                                        VALUES ($record_id, '$name', $amount)";
                        $conn->query($advertisement_sql);
                    }
                }
            }
            
            // Save others expenses
            if (!empty($_POST['others_name']) && !empty($_POST['others_amount'])) {
                foreach ($_POST['others_name'] as $i => $name) {
                    $name = trim($name);
                    $amount = floatval($_POST['others_amount'][$i]);
                    if (!empty($name) && $amount > 0) {
                        $others_sql = "INSERT INTO others_expenses (record_id, expense_name, amount) 
                                        VALUES ($record_id, '$name', $amount)";
                        $conn->query($others_sql);
                    }
                }
            }

            // Success notification
            $notification = [
                'type' => 'success',
                'title' => 'Success',
                'message' => 'Operating expenses have been successfully saved!',
                'buttonText' => 'OK'
            ];
        } else {
            // Database error notification
            $notification = [
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'There was a problem saving the expenses. Please try again.',
                'buttonText' => 'OK'
            ];
        }
    }
}

// Fetch dropdown data
$outlets = $conn->query("SELECT outlet_id, outlet_name FROM outlets WHERE owner_id = $userID");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Operating Expenses</title>

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
   Center the "Submit Expenses" button
   =============================== */
.expenses-form .submit-expenses-btn {
    display: inline-flex !important;      /* shrink to content */
    justify-content: center;
    align-items: center;
    width: auto !important;               /* override any width:100% on mobile */
    max-width: 100%;
    margin: 20px auto 0 auto !important;   /* center horizontally with some top margin */
    text-align: center;
}

        /* ===== Center the OK button in the Notification Modal (mobile + all sizes) ===== */
#notificationModal .modal-body {
  text-align: center !important;            /* center inline/inline-block content */
}

#notificationModal .notification-button {
  display: inline-flex !important;           /* ensure it sizes to content */
  justify-content: center;
  align-items: center;
  width: auto !important;                    /* override any width:100% on small screens */
  max-width: 100%;                           /* still safe in tiny viewports */
  margin: 0 auto !important;                 /* center if it's treated as block anywhere */
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
    width: 95% !important;        /* almost full width on mobile */
    max-width: 100vw !important;   /* keep nice margins on mobile */
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

.submit-expenses-btn {
  background-color: #6366F1; /* solid indigo */
  color: #fff !important;
  font-weight: 600;
  border: none;
  border-radius: 12px;
  padding: 0.85rem 1.6rem;
  font-size: 1.05rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.25s ease;
  box-shadow: 0 4px 10px rgba(99, 102, 241, 0.25);
}

/* Hover: richer tone, soft glow, slight lift */
.submit-expenses-btn:hover {
  background-color: #4F46E5; /* darker indigo for depth */
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: 
    0 8px 20px rgba(99, 102, 241, 0.35), /* soft outer glow */
    0 0 12px rgba(99, 102, 241, 0.25) inset; /* faint inner light */
}

/* Active / click-hold: keep same as hover (no flash/white change) */
.submit-expenses-btn:active,
.submit-expenses-btn:focus,
.submit-expenses-btn:focus-visible {
  background-color: #4F46E5 !important;
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: 
    0 8px 20px rgba(99, 102, 241, 0.35),
    0 0 12px rgba(99, 102, 241, 0.25) inset;
  outline: none;
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
                    <a class="mini-nav-link active" href="owner-operating-expenses.php" title="Operating Expenses">
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
                            <h1>Operating Expenses</h1>
                            <p class="page-subtitle">Record your business operating expenses – Track salary, rental, utilities, and more</p>
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
         value="<?= date('Y-m-d') ?>">
         <div class="invalid-feedback">Date is required and cannot be in the future</div>
    </div>

    <!-- Outlet -->
    <div class="mb-3">
        <label>Outlet</label>
        <div class="input-group">
            <select name="outlet_id" id="outletSelect" class="form-control">
                <option value="" disabled selected>-- Select Outlet --</option>
                <?php while($o = $outlets->fetch_assoc()): ?>
                    <option value="<?= $o['outlet_id'] ?>"><?= htmlspecialchars($o['outlet_name']) ?></option>
                <?php endwhile; ?>
            </select>
            
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
                    <tbody>
                        <!-- No static rows by default -->
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Salary</th>
                            <th><input type="text" id="total_salary" class="form-control expense-total" readonly value="0.00"></th>
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
                    <tbody>
                        <!-- No static rows by default -->
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Rental</th>
                            <th><input type="text" id="total_rental" class="form-control expense-total" readonly value="0.00"></th>
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
                    <tbody>
                        <!-- No static rows by default -->
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Utilities</th>
                            <th><input type="text" id="total_utilities" class="form-control expense-total" readonly value="0.00"></th>
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
                    <tbody>
                        <!-- No static rows by default -->
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Advertisement</th>
                            <th><input type="text" id="total_advertisement" class="form-control expense-total" readonly value="0.00"></th>
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
                    <tbody>
                        <!-- No static rows by default -->
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total Others</th>
                            <th><input type="text" id="total_others" class="form-control expense-total" readonly value="0.00"></th>
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
                            <input type="text" id="total_operating_expense" name="total_operating_expense" class="form-control total-operating-expenses" readonly value="0.00">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <div class="text-center mt-4">
  <button type="submit" class="btn submit-expenses-btn">
    <i class="fas fa-money-bill-wave"></i> Submit Operating Expenses
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
    <script src="JAVASCRIPT/owner-operating-expenses.js"></script>
    
    <!-- Notification script -->
    <script>
        /**
         * Global function to show notification (called from PHP)
         * @param {string} type - Type of notification ('success' or 'error')
         * @param {string} title - Title of the notification
         * @param {string} message - Message content
         * @param {string} buttonText - Text for the button
         */
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
                notificationMessage.style.display = 'none'; // Hide message for errors
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
                if (type === 'success') {
                     // Go to the table page after a successful save
        window.location.href = 'operating-expenses-table.php';
                }
            });

            notificationModal.show();
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('operatingExpensesForm');
            
            form.addEventListener('submit', function(e) {
                // Clear previous validation
                const invalidInputs = form.querySelectorAll('.is-invalid');
                invalidInputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
                
                // Clear previous error messages
                const errorMessages = form.querySelectorAll('.error-message');
                errorMessages.forEach(msg => msg.remove());
                
                let isValid = true;
                
                // Validate date
                const recordDate = document.getElementById('record_date');
                if (!recordDate.value) {
                    recordDate.classList.add('is-invalid');
                    isValid = false;
                } else if (new Date(recordDate.value) > new Date()) {
                    recordDate.classList.add('is-invalid');
                    isValid = false;
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
                    if (outletFeedback) outletFeedback.style.display = '';
                }

                // Validate salary section
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
                        '', // Empty message for errors
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
                    
                    // If staff is selected but amount is empty or zero
                    if (staffSelect.value && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
                        staffSelect.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        
                        // Add error message for amount
                        if (!amountInput.nextElementSibling || !amountInput.nextElementSibling.classList.contains('error-message')) {
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message invalid-feedback';
                            errorMsg.textContent = 'Salary amount must be greater than 0';
                            errorMsg.style.display = 'block';
                            amountInput.parentNode.appendChild(errorMsg);
                        }
                        
                        isValid = false;
                    }
                    
                    // If amount is provided but staff is not selected
                    if (amountInput.value && parseFloat(amountInput.value) > 0 && !staffSelect.value) {
                        staffSelect.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        
                        // Add error message for staff
                        if (!staffSelect.nextElementSibling || !staffSelect.nextElementSibling.classList.contains('error-message')) {
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message invalid-feedback';
                            errorMsg.textContent = 'Staff selection is required';
                            errorMsg.style.display = 'block';
                            staffSelect.parentNode.appendChild(errorMsg);
                        }
                        
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
                    
                    // If name is provided but amount is empty or zero
                    if (nameInput.value.trim() && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
                        nameInput.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        
                        // Add error message for amount
                        if (!amountInput.nextElementSibling || !amountInput.nextElementSibling.classList.contains('error-message')) {
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message invalid-feedback';
                            errorMsg.textContent = 'Amount must be greater than 0';
                            errorMsg.style.display = 'block';
                            amountInput.parentNode.appendChild(errorMsg);
                        }
                        
                        isValid = false;
                    }
                    
                    // If amount is provided but name is empty
                    if (amountInput.value && parseFloat(amountInput.value) > 0 && !nameInput.value.trim()) {
                        nameInput.classList.add('is-invalid');
                        amountInput.classList.add('is-invalid');
                        
                        // Add error message for name
                        if (!nameInput.nextElementSibling || !nameInput.nextElementSibling.classList.contains('error-message')) {
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message invalid-feedback';
                            errorMsg.textContent = 'Expense name is required';
                            errorMsg.style.display = 'block';
                            nameInput.parentNode.appendChild(errorMsg);
                        }
                        
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