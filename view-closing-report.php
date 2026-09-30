<?php
/**
 * ezAccount System - View Closing Report
 * Displays complete closing report details from database
 */
session_start();
include('connection.php');

// Redirect if not business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

// User session data
$userName     = $_SESSION['user_name'];
$userEmail    = $_SESSION['user_email'];
$companyName  = $_SESSION['company_name'];

if (!isset($_GET['id'])) {
    die("Invalid report ID");
}

$reportId = intval($_GET['id']);

// Fetch main report data
$sql = "
    SELECT cr.*, o.outlet_name, s.staff_name
    FROM closing_reports cr
    LEFT JOIN outlets o ON cr.outlet_id=o.outlet_id
    LEFT JOIN staff s ON cr.staff_id=s.staff_id
    WHERE cr.report_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reportId);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die("Report not found!");
}

// Fetch payment details
$payments_sql = "SELECT * FROM closing_report_payments WHERE report_id = ?";
$payments_stmt = $conn->prepare($payments_sql);
$payments_stmt->bind_param("i", $reportId);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
$payments = $payments_result->fetch_all(MYSQLI_ASSOC);

// Fetch cash breakdown details
$cash_sql = "SELECT * FROM closing_report_cash_breakdown WHERE report_id = ? ORDER BY denomination DESC";
$cash_stmt = $conn->prepare($cash_sql);
$cash_stmt->bind_param("i", $reportId);
$cash_stmt->execute();
$cash_result = $cash_stmt->get_result();
$cash_breakdown = $cash_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - View Closing Sales</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .user-actions {
            margin-left: auto;
            padding: 0 15px;
        }
        .report-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }
        .report-header {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .financial-highlight {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }
        .positive {
            color: #28a745;
            font-weight: bold;
        }
        .negative {
            color: #dc3545;
            font-weight: bold;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .denomination-table th {
            background-color: #4361ee;
            color: white;
        }

/* ===========================================
   Smart horizontal scroll for ALL .table-responsive
   (includes Basic Information, Cash Details, Payment, Breakdown)
   =========================================== */
@media (max-width: 1024px) {
  .table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
  }

  .table-responsive > table {
    display: table !important;       /* behave like a normal table */
    width: max-content !important;   /* table expands only to fit content */
    min-width: 100% !important;      /* but never shrink smaller than viewport */
    table-layout: auto !important;   /* natural column widths */
    border-collapse: collapse;
  }

  .table-responsive th,
  .table-responsive td {
    white-space: normal !important;  /* wrap text when it fits */
    word-break: break-word;
    vertical-align: middle;
    padding: 8px 10px;
  }
}

/* Optional: prevent rubber-band overscroll on some browsers */
.table-responsive {
  overscroll-behavior-x: contain;
}


/* Stop overscrolling: table width = content width, but never smaller than viewport */
@media (max-width: 1024px) {
  .table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
  }

  .table-responsive > table {
    /* override earlier rules */
    display: table !important;       /* avoid block quirks that add blank width */
    width: max-content !important;   /* size to actual content width */
    min-width: 100% !important;      /* but don’t be narrower than the viewport */
    table-layout: auto !important;   /* natural column widths */
  }

  /* allow wrapping so table doesn’t get wider than necessary */
  .table-responsive th,
  .table-responsive td {
    white-space: normal !important;
    word-break: break-word;
    vertical-align: middle;
  }
}

/* Optional: prevent rubber-band over-scroll on some browsers */
.table-responsive {
  overscroll-behavior-x: contain;
}


/* (Optional) nicer thin scrollbar on desktop too */
.table-responsive { scrollbar-width: thin; }
.table-responsive::-webkit-scrollbar { height: 8px; }


    </style>
</head>
<body>
    <!-- Main Layout Container -->
    <div class="container-fluid">
        <div class="row">
            
            <!-- Sidebar Navigation -->
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
                    
                    <!-- User Profile Section -->
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
                    <a class="mini-nav-link active" href="owner-dashboard.php" title="Dashboard">
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
                    <a class="mini-nav-link " href="closing-report-table.php" title="Closing Sales">
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
                        <button id="sidebarToggle" class="btn toggle-btn">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div class="page-title">
                            <h1>View Closing Sales</h1>
                            
                        </div>
                        
                        <div class="user-actions">
                            <div class="welcome-message">
                                <span class="greeting">Hi, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                                <small class="company-name"><?php echo htmlspecialchars($companyName); ?></small>
                            </div>
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
                </header>

                <!-- Page Content Container -->
                <div class="container py-4">

                    <!-- Main Report Card -->
                    <div class="card report-card">
                        <div class="report-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="mb-1">Closing Sales Details</h3>
                                    <p class="mb-0">Generated on: <?= date('F j, Y', strtotime($report['report_date'])) ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-light text-dark fs-6"><?= $report['shift'] ?> Shift</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Basic Information Section -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>📋 Basic Information</h5>
                                    <table class="table table-bordered">
                                        
                                        <tr>
                                            <th>Date</th>
                                            <td><?= date('F j, Y', strtotime($report['report_date'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Shift</th>
                                            <td><?= $report['shift'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Outlet</th>
                                            <td><?= htmlspecialchars($report['outlet_name'] ?? 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Staff</th>
                                            <td><?= htmlspecialchars($report['staff_name'] ?? 'N/A') ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>💰 Financial Summary</h5>
                                    <div class="financial-highlight">
                                        <div class="row text-center">
                                            <div class="col-6 mb-3">
                                                <div class="fw-bold text-primary">Cash Float</div>
                                                <div class="fs-5">RM <?= number_format($report['cash_float'], 2) ?></div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="fw-bold text-success">Total Payment</div>
                                                <div class="fs-5">RM <?= number_format($report['total_payment'], 2) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="fw-bold text-info">Cash Sale</div>
                                                <div class="fs-5">RM <?= number_format($report['cash_sale'], 2) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="fw-bold text-warning">QR Sale</div>
                                                <div class="fs-5">RM <?= number_format($report['qr_sale'], 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>💵 Cash Details</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Cash Float</th>
                                                    <th>Cash Sale</th>
                                                    <th>QR Sale</th>
                                                    <th>Total Cash</th>
                                                    <th>Expected Cash</th>
                                                    <th>Actual Cash</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>RM <?= number_format($report['cash_float'], 2) ?></td>
                                                    <td>RM <?= number_format($report['cash_sale'], 2) ?></td>
                                                    <td>RM <?= number_format($report['qr_sale'], 2) ?></td>
                                                    <td>RM <?= number_format($report['total_cash'], 2) ?></td>
                                                    <td>RM <?= number_format($report['expected_cash_in_hand'], 2) ?></td>
                                                    <td>RM <?= number_format($report['actual_cash_in_hand'], 2) ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Details -->
                            <?php if (!empty($payments)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>💳 Payment Details</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>Payment Type</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($payments as $payment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($payment['payment_type']) ?></td>
                                                    <td>RM <?= number_format($payment['amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <td><strong>Total Payment</strong></td>
                                                    <td><strong>RM <?= number_format($report['total_payment'], 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Cash Breakdown -->
                            <?php if (!empty($cash_breakdown)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>🪙 Cash Breakdown</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped denomination-table">
                                            <thead>
                                                <tr>
                                                    <th>Denomination (RM)</th>
                                                    <th>Quantity</th>
                                                    <th>Total Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $grand_total = 0;
                                                foreach($cash_breakdown as $cash): 
                                                    $grand_total += $cash['total_amount'];
                                                ?>
                                                <tr>
                                                    <td>RM <?= number_format($cash['denomination'], 2) ?></td>
                                                    <td><?= $cash['quantity'] ?></td>
                                                    <td>RM <?= number_format($cash['total_amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td colspan="2"><strong>Grand Total</strong></td>
                                                    <td><strong>RM <?= number_format($grand_total, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Difference Section -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-center p-4 <?= $report['difference'] >= 0 ? 'bg-success' : 'bg-danger' ?> text-white rounded">
                                        <h4 class="mb-2">Difference</h4>
                                        <h2 class="mb-0">
                                            <?= $report['difference'] >= 0 ? '➕' : '➖' ?>
                                            RM <?= number_format(abs($report['difference']), 2) ?>
                                            <?= $report['difference'] >= 0 ? '(Profit)' : '(Loss)' ?>
                                        </h2>
                                        <p class="mb-0 mt-2">
                                            <small>
                                                Expected: RM <?= number_format($report['expected_cash_in_hand'], 2) ?> | 
                                                Actual: RM <?= number_format($report['actual_cash_in_hand'], 2) ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Section -->
                            <?php if (!empty($report['note'])): ?>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5>📝 Notes</h5>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <?= nl2br(htmlspecialchars($report['note'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="closing-report-table.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list me-2"></i>Back to Closing Sales
                        </a>
                    </div>
                </div>
            </main>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Dashboard JavaScript -->
    <script src="JAVASCRIPT/owner-dashboard.js"></script>

    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>