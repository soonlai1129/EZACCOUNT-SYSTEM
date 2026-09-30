<?php
/**
 * ezAccount System - View Operating Expense
 * Displays complete operating expense details from database
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
    die("Invalid record ID");
}

$recordId = intval($_GET['id']);

// Fetch main operating expense record data
$sql = "
    SELECT oer.*, o.outlet_name
    FROM operating_expense_records oer
    LEFT JOIN outlets o ON oer.outlet_id = o.outlet_id
    WHERE oer.record_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Operating expense record not found!");
}

// Fetch salary expenses
$salary_sql = "
    SELECT se.*, s.staff_name 
    FROM salary_expenses se 
    LEFT JOIN staff s ON se.staff_id = s.staff_id 
    WHERE se.record_id = ?
";
$salary_stmt = $conn->prepare($salary_sql);
$salary_stmt->bind_param("i", $recordId);
$salary_stmt->execute();
$salary_result = $salary_stmt->get_result();
$salary_expenses = $salary_result->fetch_all(MYSQLI_ASSOC);

// Fetch rental expenses
$rental_sql = "SELECT * FROM rental_expenses WHERE record_id = ?";
$rental_stmt = $conn->prepare($rental_sql);
$rental_stmt->bind_param("i", $recordId);
$rental_stmt->execute();
$rental_result = $rental_stmt->get_result();
$rental_expenses = $rental_result->fetch_all(MYSQLI_ASSOC);

// Fetch utilities expenses
$utilities_sql = "SELECT * FROM utilities_expenses WHERE record_id = ?";
$utilities_stmt = $conn->prepare($utilities_sql);
$utilities_stmt->bind_param("i", $recordId);
$utilities_stmt->execute();
$utilities_result = $utilities_stmt->get_result();
$utilities_expenses = $utilities_result->fetch_all(MYSQLI_ASSOC);

// Fetch advertisement expenses
$advertisement_sql = "SELECT * FROM advertisement_expenses WHERE record_id = ?";
$advertisement_stmt = $conn->prepare($advertisement_sql);
$advertisement_stmt->bind_param("i", $recordId);
$advertisement_stmt->execute();
$advertisement_result = $advertisement_stmt->get_result();
$advertisement_expenses = $advertisement_result->fetch_all(MYSQLI_ASSOC);

// Fetch others expenses
$others_sql = "SELECT * FROM others_expenses WHERE record_id = ?";
$others_stmt = $conn->prepare($others_sql);
$others_stmt->bind_param("i", $recordId);
$others_stmt->execute();
$others_result = $others_stmt->get_result();
$others_expenses = $others_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - View Operating Expense</title>

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
        .expense-table th {
            background-color: #4361ee;
            color: white;
        }
        /* Consistent two-column layout for all expense tables */
.table-responsive table.expense-table {
  table-layout: fixed !important;  /* override the auto layout in your media queries */
  width: 100%;
}

/* Define stable column widths: 70% (name) / 30% (amount) */
.expense-table th:first-child,
.expense-table td:first-child { width: 70%; }

.expense-table th:last-child,
.expense-table td:last-child  { width: 30%; }

/* Nice-to-have: align amounts and prevent ugly wrapping */
.expense-table th:last-child,
.expense-table td:last-child  {
  text-align: left;
  white-space: nowrap;
}



/* ===========================================
   Smart horizontal scroll for ALL .table-responsive
   (includes Basic Information, Expense Details)
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


/* Mobile/Tablet: horizontal scroll + no wrapping for expense tables */
@media (max-width: 1024px) {
  .table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
  }

  .table-responsive > table {
    display: table !important;       /* behave like a real table */
    width: max-content !important;   /* expand to fit content */
    min-width: 100% !important;      /* never smaller than viewport */
    table-layout: auto !important;   /* let columns size naturally */
    border-collapse: collapse;
  }

  /* KEY: prevent wrapping for expense tables */
  .expense-table th,
  .expense-table td {
    white-space: nowrap !important;  /* don't wrap to next line */
    word-break: keep-all;            /* avoid breaking words */
    vertical-align: middle;
    padding: 8px 10px;
  }
}

/* Optional: align amounts nicely */
.expense-table th:last-child,
.expense-table td:last-child {
  text-align: left;
}

/* Prevent breaking of words like "Advertisement" in expense summary on phones */
@media (max-width: 768px) {
  .financial-highlight .fw-bold {
    white-space: nowrap;
    word-break: keep-all;
  }
}

@media (max-width: 576px) {
  .financial-highlight .fw-bold {
    font-size: 0.9rem;
  }
}

@media (max-width: 768px) {
  .topbar .page-title h1 { 
    font-size: 1.1rem !important;  /* smaller on phone/tablet */
  }
}

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
                        <button id="sidebarToggle" class="btn toggle-btn">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div class="page-title">
                            <h1>View Operating Expense</h1>
                            
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
                                    <h3 class="mb-1">Operating Expense Details</h3>
                                    <p class="mb-0">Recorded on: <?= date('F j, Y', strtotime($record['record_date'])) ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-light text-dark fs-6">Total: RM <?= number_format($record['total_operating_expense'], 2) ?></span>
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
                                            <td><?= date('F j, Y', strtotime($record['record_date'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Outlet</th>
                                            <td><?= htmlspecialchars($record['outlet_name'] ?? 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Total Operating Expense</th>
                                            <td class="fw-bold">RM <?= number_format($record['total_operating_expense'], 2) ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>💰 Expense Summary</h5>
                                    <div class="financial-highlight">
                                        <div class="row text-center">
                                            <?php
                                            // Calculate totals for each expense type
                                            $total_salary = 0;
                                            $total_rental = 0;
                                            $total_utilities = 0;
                                            $total_advertisement = 0;
                                            $total_others = 0;
                                            
                                            foreach($salary_expenses as $expense) {
                                                $total_salary += $expense['amount'];
                                            }
                                            
                                            foreach($rental_expenses as $expense) {
                                                $total_rental += $expense['amount'];
                                            }
                                            
                                            foreach($utilities_expenses as $expense) {
                                                $total_utilities += $expense['amount'];
                                            }
                                            
                                            foreach($advertisement_expenses as $expense) {
                                                $total_advertisement += $expense['amount'];
                                            }
                                            
                                            foreach($others_expenses as $expense) {
                                                $total_others += $expense['amount'];
                                            }
                                            ?>
                                            <div class="col-6 mb-3">
                                                <div class="fw-bold text-primary">Salary</div>
                                                <div class="fs-5">RM <?= number_format($total_salary, 2) ?></div>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="fw-bold text-success">Rental</div>
                                                <div class="fs-5">RM <?= number_format($total_rental, 2) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="fw-bold text-info">Utilities</div>
                                                <div class="fs-5">RM <?= number_format($total_utilities, 2) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="fw-bold text-warning">Advertisement</div>
                                                <div class="fs-5">RM <?= number_format($total_advertisement, 2) ?></div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <div class="fw-bold text-danger">Others</div>
                                                <div class="fs-5">RM <?= number_format($total_others, 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Salary Expenses -->
                            <?php if (!empty($salary_expenses)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>💼 Salary Expenses</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped expense-table">
                                            <thead>
                                                <tr>
                                                    <th>Staff Name</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($salary_expenses as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['staff_name'] ?? 'N/A') ?></td>
                                                    <td>RM <?= number_format($expense['amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <td><strong>Total Salary</strong></td>
                                                    <td><strong>RM <?= number_format($total_salary, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Rental Expenses -->
                            <?php if (!empty($rental_expenses)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>🏢 Rental Expenses</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped expense-table">
                                            <thead>
                                                <tr>
                                                    <th>Expense Name</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($rental_expenses as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['expense_name']) ?></td>
                                                    <td>RM <?= number_format($expense['amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <td><strong>Total Rental</strong></td>
                                                    <td><strong>RM <?= number_format($total_rental, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Utilities Expenses -->
                            <?php if (!empty($utilities_expenses)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>⚡ Utilities Expenses</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped expense-table">
                                            <thead>
                                                <tr>
                                                    <th>Expense Name</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($utilities_expenses as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['expense_name']) ?></td>
                                                    <td>RM <?= number_format($expense['amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <td><strong>Total Utilities</strong></td>
                                                    <td><strong>RM <?= number_format($total_utilities, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Advertisement Expenses -->
                            <?php if (!empty($advertisement_expenses)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>📢 Advertisement Expenses</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped expense-table">
                                            <thead>
                                                <tr>
                                                    <th>Expense Name</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($advertisement_expenses as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['expense_name']) ?></td>
                                                    <td>RM <?= number_format($expense['amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <td><strong>Total Advertisement</strong></td>
                                                    <td><strong>RM <?= number_format($total_advertisement, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Others Expenses -->
                            <?php if (!empty($others_expenses)): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5>📝 Other Expenses</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped expense-table">
                                            <thead>
                                                <tr>
                                                    <th>Expense Name</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($others_expenses as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['expense_name']) ?></td>
                                                    <td>RM <?= number_format($expense['amount'], 2) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <td><strong>Total Others</strong></td>
                                                    <td><strong>RM <?= number_format($total_others, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Grand Total Section -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="text-center p-4 bg-primary text-white rounded">
                                        <h4 class="mb-2">Grand Total Operating Expense</h4>
                                        <h2 class="mb-0">
                                            RM <?= number_format($record['total_operating_expense'], 2) ?>
                                        </h2>
                                        <p class="mb-0 mt-2">
                                            <small>
                                                This is the total operating expense for <?= htmlspecialchars($record['outlet_name'] ?? 'N/A') ?> on <?= date('F j, Y', strtotime($record['record_date'])) ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="operating-expenses-table.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-list me-2"></i>Back to Operating Expenses
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