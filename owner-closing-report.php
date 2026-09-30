<?php
/**
 * ezAccount System - Owner Dashboard (All-in-One Page)
 * Dynamic closing report system with outlets, staff, purchases, and cash breakdown
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
    if (empty($_POST['report_date'])) {
        $errors[] = "Date is required";
    } elseif (strtotime($_POST['report_date']) > time()) {
        $errors[] = "Date cannot be in the future";
    }
    
    if (empty($_POST['shift'])) {
        $errors[] = "Shift is required";
    }
    
    // Outlet validation 
    if (empty($_POST['outlet_id'])) {
         $errors[] = "Outlet is required";
    }
    
    if (empty($_POST['cash_float']) || floatval($_POST['cash_float']) <= 0) {
        $errors[] = "Cash Float must be greater than 0";
    }
    
    if (empty($_POST['cash_sale']) || floatval($_POST['cash_sale']) < 0) {
        $errors[] = "Cash Sale is required";
    }
    
    
    
    // Payment validation
    if (!empty($_POST['payment_type'])) {
        foreach ($_POST['payment_type'] as $index => $payment_type) {
            if (empty($payment_type) && !empty($_POST['payment_amount'][$index])) {
                $errors[] = "Payment type is required for all payment entries";
                break;
            }
            
            if (!empty($payment_type) && (empty($_POST['payment_amount'][$index]) || floatval($_POST['payment_amount'][$index]) <= 0)) {
                $errors[] = "Payment amount must be greater than 0 for all payment entries";
                break;
            }
        }
    }
    
    // Cash breakdown validation - at least one denomination must have quantity > 0
    $cashBreakdownFilled = false;
    if (!empty($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $qty) {
            if (intval($qty) > 0) {
                $cashBreakdownFilled = true;
                break;
            }
        }
    }
    if (!$cashBreakdownFilled) {
        $errors[] = "At least one cash denomination must have quantity greater than 0";
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
        $staff_id      = NULL;
        $report_date   = $_POST['report_date'];
        $shift         = $_POST['shift'];
        $cash_float    = floatval($_POST['cash_float']);
        $cash_sale     = floatval($_POST['cash_sale']);
        $qr_sale       = floatval($_POST['qr_sale']);
        $total_cash    = floatval($_POST['total_cash']);
        $total_payment = floatval($_POST['total_payment']);
        $expected_cash = floatval($_POST['expected_cash']);
        $actual_cash   = floatval($_POST['actual_cash']);
        $difference    = floatval($_POST['difference']);
        

        // Insert into closing_reports table first (unchanged as requested)
        $sql = "INSERT INTO closing_reports 
                (outlet_id, staff_id, report_date, shift, cash_float, cash_sale, qr_sale, total_cash, total_payment, expected_cash_in_hand, actual_cash_in_hand, difference)
                VALUES ($outlet_id, NULL, '$report_date', '$shift', $cash_float, $cash_sale, $qr_sale, $total_cash, $total_payment, $expected_cash, $actual_cash, $difference)";
        
        if ($conn->query($sql) === TRUE) {
            $report_id = $conn->insert_id;
            
            // Save payments if any
            if (!empty($_POST['payment_type']) && !empty($_POST['payment_amount'])) {
                foreach ($_POST['payment_type'] as $i => $payment_type) {
                    // Remove leading and trailing spaces
                    $payment_type = trim($payment_type);
                    // Optional: also update the POST array if you need it later
        $_POST['payment_type'][$i] = $payment_type;
                    $amount = floatval($_POST['payment_amount'][$i]);
                    if (!empty($payment_type) && $amount > 0) {
                        $payment_sql = "INSERT INTO closing_report_payments (report_id, payment_type, amount) 
                                        VALUES ($report_id, '$payment_type', $amount)";
                        $conn->query($payment_sql);
                    }
                }
            }

            // Save cash breakdown (only for denominations with quantity > 0)
            if (!empty($_POST['denomination']) && !empty($_POST['quantity'])) {
                foreach ($_POST['denomination'] as $i => $denom) {
                    $qty = intval($_POST['quantity'][$i]);
                    if ($qty > 0) {
                        $total_amount = $denom * $qty;
                        $cash_sql = "INSERT INTO closing_report_cash_breakdown (report_id, denomination, quantity, total_amount) 
                                     VALUES ($report_id, $denom, $qty, $total_amount)";
                        $conn->query($cash_sql);
                    }
                }
            }

            // Success notification
            $notification = [
                'type' => 'success',
                'title' => 'Success',
                'message' => 'Closing sales has been successfully saved!',
                'buttonText' => 'OK'
            ];
        } else {
            // Database error notification
            $notification = [
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'There was a problem saving the closing sales. Please try again.',
                'buttonText' => 'OK'
            ];
        }
    }
}

// Fetch dropdown data
$outlets = $conn->query("SELECT outlet_id, outlet_name FROM outlets WHERE owner_id = $userID");
$staffs  = $conn->query("SELECT staff_id, staff_name FROM staff");

// Fetch existing reports
$reports = $conn->query("
    SELECT cr.*, o.outlet_name, s.staff_name 
    FROM closing_reports cr
    LEFT JOIN outlets o ON cr.outlet_id=o.outlet_id
    LEFT JOIN staff s ON cr.staff_id=s.staff_id
    ORDER BY cr.report_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Closing Sales</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/owner-closing-report.css">
    
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
   Center the "Submit Report" button
   =============================== */
.closing-form .submit-report-btn {
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

/* Optional: give a minimum touch target without forcing full width */
#notificationModal .notification-button {
  min-width: 140px;                          /* tweak as you like */
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
  width: auto !important;                    /* avoid full-width buttons on mobile */
}

        /* ============================
   Cash Breakdown (scoped)
   ============================ */

/* Make ONLY the section that contains #cashTable horizontally scrollable on small screens */
@media (max-width: 1024px) {
  .form-section.card:has(#cashTable) {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch; /* smooth scroll on iOS */
  }
  /* Ensure the table is wide enough inside the scroll area */
  #cashTable {
    min-width: 560px;  /* adjust if you want wider/narrower */
  }
}

/* Improve wrapping and sizing inside the Cash Breakdown table only */
#cashTable {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;        /* prevents columns from expanding and allows wrapping */
}

#cashTable th,
#cashTable td {
  white-space: normal;        /* allow line wrapping */
  word-break: break-word;     /* break long words if needed */
  hyphens: auto;
  vertical-align: middle;
}

/* Keep header labels tidy on very small screens */
@media (max-width: 576px) {
  #cashTable th:nth-child(2),
  #cashTable th:nth-child(3) {
    white-space: nowrap;      /* "Quantity" and "Total" stay on a single line */
  }
}

/* Inputs behave well in narrow cells (Cash Breakdown only) */
#cashTable .form-control {
  width: 100%;
  min-width: 90px;            /* keeps tap targets usable */
  box-sizing: border-box;
}

/* Optional: make the "Grand Total" input not shrink oddly */
#cashTable tfoot #grandTotal.form-control {
  min-width: 120px;
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


.submit-report-btn {
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
.submit-report-btn:hover {
  background-color: #4F46E5; /* darker indigo for depth */
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: 
    0 8px 20px rgba(99, 102, 241, 0.35), /* soft outer glow */
    0 0 12px rgba(99, 102, 241, 0.25) inset; /* faint inner light */
}

/* Active / click-hold: keep same as hover (no flash/white change) */
.submit-report-btn:active,
.submit-report-btn:focus,
.submit-report-btn:focus-visible {
  background-color: #4F46E5 !important;
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: 
    0 8px 20px rgba(99, 102, 241, 0.35),
    0 0 12px rgba(99, 102, 241, 0.25) inset;
  outline: none;
}

/* ============================
   Payment Section - Wider Payment Type Column (Phone Only)
   ============================ */

@media (max-width: 767.98px) {
  /* Make payment type column wider on phones only */
  #paymentTable th:nth-child(1),
  #paymentTable td:nth-child(1) {
    width: 70%; /* Payment type takes 70% of table width on phones */
  }

  #paymentTable th:nth-child(2),
  #paymentTable td:nth-child(2) {
    width: 30%; /* Amount takes 30% of table width on phones */
  }

  /* Ensure inputs fill the available space */
  #paymentTable .payment-type {
    width: 100%;
  }

  #paymentTable .payment-amount {
    width: 100%;
  }
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
<a class="mini-nav-link" href="owner-operating-expenses.php" title="Operating Expenses">
    <i class="fas fa-money-bill-wave"></i>
</a>
                    
                    <!-- Closing Report Menu Item -->
                    <a class="mini-nav-link active" href="owner-closing-report.php" title="Closing Sales">
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
                            <h1>Closing Sales</h1>
                            <p class="page-subtitle">Submit your closing sales – Record daily sales and cash details</p>
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
    
  <form method="POST" id="closingReportForm" class="closing-form" novalidate>

    <!-- Section 1: Basic Info -->
<div class="form-section card p-3 mb-3">
    <h4>📋 Basic Info</h4>

    <!-- Date -->
    <div class="mb-3">
        <label>Date</label>
         <input type="date" name="report_date" id="report_date" class="form-control"
         value="<?= date('Y-m-d') ?>">
         <div class="invalid-feedback">Date is required and cannot be in the future</div>
    </div>

    <!-- Shift -->
    <div class="mb-3">
        <label>Shift</label>
        <select name="shift" id="shift" class="form-control">
            <option value="" disabled selected>-- Select Shift --</option>
            <option value="Morning">Morning</option>
            <option value="Evening">Evening</option>
        </select>
        <div class="invalid-feedback">Shift is required</div>
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

    <!-- Section 2: Cash Section -->
    <div class="form-section card p-3 mb-3">
        <h4>💰 Cash Section</h4>
        <div class="row">
            <div class="col-md-3">
                <label>Cash Float</label>
                <input type="number" id="cash_float" name="cash_float" class="form-control" step="0.01" required min="0.01">
                <div class="invalid-feedback">Cash Float must be greater than 0</div>
            </div>
            <div class="col-md-3">
                <label>Cash Sale</label>
                <input type="number" id="cash_sale" name="cash_sale" class="form-control" step="0.01" required min="0">
                <div class="invalid-feedback">Cash Sale is required</div>
            </div>
            <div class="col-md-3">
                <label>Total Cash</label>
                <input type="text" id="total_cash" name="total_cash" class="form-control" readonly>
            </div>
            <div class="col-md-3">
                <label>QR Sale</label>
                <input type="number" id="qr_sale" name="qr_sale" class="form-control" step="0.01" required min="0">
                <!-- <div class="invalid-feedback">QR Sale is required</div> -->
            </div>
        </div>
    </div>

<!-- Section 3: Payment Section -->
<div class="form-section card p-3 mb-3">
    <h4>💳 Payment Section</h4>
    <table class="table table-bordered" id="paymentTable">
        <thead>
            <tr>
                <th>Payment Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <!--<tr>
                <td>
                    <input type="text" name="payment_type[]" class="form-control payment-type" value="Gaji">
                    <div class="invalid-feedback">Payment type is required</div>
                </td>
                <td>
                    <input type="number" name="payment_amount[]" class="form-control payment-amount" step="0.01" min="0.01" >
                    <div class="invalid-feedback">Payment amount must be greater than 0</div>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="text" name="payment_type[]" class="form-control payment-type" value="Ais">
                    <div class="invalid-feedback">Payment type is required</div>
                </td>
                <td>
                    <input type="number" name="payment_amount[]" class="form-control payment-amount" step="0.01" min="0.01">
                    <div class="invalid-feedback">Payment amount must be greater than 0</div>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="text" name="payment_type[]" class="form-control payment-type" value="Serahan">
                    <div class="invalid-feedback">Payment type is required</div>
                </td>
                <td>
                    <input type="number" name="payment_amount[]" class="form-control payment-amount" step="0.01" min="0.01">
                    <div class="invalid-feedback">Payment amount must be greater than 0</div>
                </td>
            </tr> -->
        </tbody>
    </table>
    
    <!-- Buttons side by side -->
    <div class="d-flex gap-2 mb-3">
        <button type="button" id="addPaymentRow" class="btn btn-outline-primary">+ Add Row</button>
        <button type="button" id="deletePaymentRow" class="btn btn-outline-danger">- Delete Row</button>
    </div>

    <div class="mt-3">
        <label>Total Payment</label>
        <input type="text" id="total_payment" name="total_payment" class="form-control" readonly>
    </div>
</div>

    <!-- Section 4: Cash Breakdown -->
    <div class="form-section card p-3 mb-3" id="table-responsive">
        <h4>💵 Cash Breakdown</h4>
        <table class="table table-bordered" id="cashTable">
            <thead>
                <tr>
                    <th>Denomination (RM)</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $denoms = [100, 50, 20, 10, 5, 1, 0.50, 0.20, 0.10];
                foreach($denoms as $d): ?>
                <tr>
                    <td>
                        <input type="hidden" name="denomination[]" value="<?= $d ?>">
                        RM <?= $d ?>
                    </td>
                    <td>
                        <input type="number" name="quantity[]" class="form-control qty-input" min="0">
                    </td>
                    <td>
                        <input type="text" class="form-control row-total" readonly value="0.00">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-primary">
                    <th colspan="2">Grand Total</th>
                    <th><input type="text" id="grandTotal" class="form-control" readonly></th>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-3">
            <div class="col-md-4">
                <label>Expected Cash in Hand</label>
                <input type="text" id="expected_cash" name="expected_cash" class="form-control" readonly>
            </div>
            <div class="col-md-4">
                <label>Actual Cash in Hand</label>
                <input type="text" id="actual_cash" name="actual_cash" class="form-control" readonly>
            </div>
            <div class="col-md-4">
                <label>Difference (Profit/Loss)</label>
                <input type="text" id="difference" name="difference" class="form-control fw-bold" readonly>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary submit-report-btn"><i class="fas fa-file-invoice-dollar me-2"></i>Submit Closing Sales</button>
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
    <script src="JAVASCRIPT/owner-closing-report.js"></script>
    
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
            newButton.addEventListener('click', function () {
                notificationModal.hide();
                <?php if (isset($notification) && $notification['type'] === 'success'): ?>
                    window.location.href = 'closing-report-table.php';
                <?php endif; ?>
            });

            // Show the modal
            notificationModal.show();
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('closingReportForm');
            
            form.addEventListener('submit', function(e) {
                // Clear previous validation
                const invalidInputs = form.querySelectorAll('.is-invalid');
                invalidInputs.forEach(input => {
                    input.classList.remove('is-invalid');
                });
                
                let isValid = true;
                
                // Validate date
                const reportDate = document.getElementById('report_date');
                if (!reportDate.value) {
                    reportDate.classList.add('is-invalid');
                    isValid = false;
                } else if (new Date(reportDate.value) > new Date()) {
                    reportDate.classList.add('is-invalid');
                    isValid = false;
                }
                
                // Validate shift
                const shift = document.getElementById('shift');
                if (!shift.value) {
                    shift.classList.add('is-invalid');
                    isValid = false;
                }

                // Validate outlet
const outlet = document.getElementById('outletSelect');
const outletFeedback = outlet.nextElementSibling; // the <div class="invalid-feedback">

if (!outlet.value) {
    outlet.classList.add('is-invalid');
    if (outletFeedback) outletFeedback.style.display = 'block';
    isValid = false;
} else {
    outlet.classList.remove('is-invalid');
    if (outletFeedback) outletFeedback.style.display = '';
}

               
                // Validate cash fields
                const cashFloat = document.getElementById('cash_float');
                if (!cashFloat.value || parseFloat(cashFloat.value) <= 0) {
                    cashFloat.classList.add('is-invalid');
                    isValid = false;
                }
                
                const cashSale = document.getElementById('cash_sale');
                if (!cashSale.value || parseFloat(cashSale.value) < 0) {
                    cashSale.classList.add('is-invalid');
                    isValid = false;
                }
                
                //const qrSale = document.getElementById('qr_sale');
                //if (!qrSale.value || parseFloat(qrSale.value) < 0) {
                //    qrSale.classList.add('is-invalid');
                //    isValid = false;
                //}
                
                // Validate payment fields
                const paymentTypes = document.querySelectorAll('.payment-type');
                const paymentAmounts = document.querySelectorAll('.payment-amount');
                
                paymentTypes.forEach((typeInput, index) => {
                    const amountInput = paymentAmounts[index];
                    
                    // If type is empty
                    if (!typeInput.value.trim()) {
                        typeInput.classList.add('is-invalid');
                        isValid = false;
                    }
                    
                    // If amount is empty or invalid
                    if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
                        amountInput.classList.add('is-invalid');
                        isValid = false;
                    }
                });
                
                // Validate cash breakdown - at least one denomination must have quantity > 0
                const quantityInputs = document.querySelectorAll('.qty-input');
                let cashBreakdownFilled = false;
                
                quantityInputs.forEach(input => {
                    if (parseInt(input.value) > 0) {
                        cashBreakdownFilled = true;
                    }
                });
                
                if (!cashBreakdownFilled) {
                    // Highlight all quantity inputs
                    quantityInputs.forEach(input => {
                        input.classList.add('is-invalid');
                    });
                    isValid = false;
                }
                
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
        });
    </script>

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

        // Initialize date to today
    document.getElementById('report_date').valueAsDate = new Date();
    </script>
    <?php endif; ?>
</body>
</html>