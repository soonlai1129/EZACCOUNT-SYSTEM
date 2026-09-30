<?php
/**
 * ezAccount System - Operating Expenses Table View
 * Displays all operating expenses records in a table with filtering capabilities
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
$userName     = $_SESSION['user_name'];
$userEmail    = $_SESSION['user_email'];
$companyName  = $_SESSION['company_name'];

// Check for success and deletion messages
$success_msg = "";
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_msg = "✅ Operating expense record saved successfully!";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_msg = "✅ Operating expense record deleted successfully!";
}

// Get filter parameters from GET request
$outletFilter = isset($_GET['outlet']) ? $_GET['outlet'] : 'all';
$yearFilter = isset($_GET['year']) ? $_GET['year'] : 'all';
$monthFilter = isset($_GET['month']) ? $_GET['month'] : 'all';
$dayFilter = isset($_GET['day']) ? $_GET['day'] : 'all';

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Build the base query for operating expenses - COUNT for total records
$count_query = "
    SELECT COUNT(DISTINCT oer.record_id) as total_records
    FROM operating_expense_records oer
    LEFT JOIN outlets o ON oer.outlet_id = o.outlet_id
    WHERE o.owner_id = {$userID}
";

// Add filters to count query
if ($outletFilter !== 'all') {
    $count_query .= " AND oer.outlet_id = " . intval($outletFilter);
}
if ($yearFilter !== 'all') {
    $count_query .= " AND YEAR(oer.record_date) = " . intval($yearFilter);
}
if ($monthFilter !== 'all') {
    $count_query .= " AND MONTH(oer.record_date) = " . intval($monthFilter);
}
if ($dayFilter !== 'all') {
    $count_query .= " AND DAY(oer.record_date) = " . intval($dayFilter);
}

// Get total records count
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total_records'];
$total_pages = ceil($total_records / $records_per_page);

// Build the main query with LIMIT for pagination
$query = "
    SELECT oer.*, o.outlet_name,
           COALESCE(se.total_salary, 0) as total_salary,
           COALESCE(re.total_rental, 0) as total_rental,
           COALESCE(ue.total_utilities, 0) as total_utilities,
           COALESCE(ae.total_advertisement, 0) as total_advertisement,
           COALESCE(oe.total_others, 0) as total_others
    FROM operating_expense_records oer
    LEFT JOIN outlets o ON oer.outlet_id = o.outlet_id
    LEFT JOIN (SELECT record_id, SUM(amount) as total_salary FROM salary_expenses GROUP BY record_id) se ON oer.record_id = se.record_id
    LEFT JOIN (SELECT record_id, SUM(amount) as total_rental FROM rental_expenses GROUP BY record_id) re ON oer.record_id = re.record_id
    LEFT JOIN (SELECT record_id, SUM(amount) as total_utilities FROM utilities_expenses GROUP BY record_id) ue ON oer.record_id = ue.record_id
    LEFT JOIN (SELECT record_id, SUM(amount) as total_advertisement FROM advertisement_expenses GROUP BY record_id) ae ON oer.record_id = ae.record_id
    LEFT JOIN (SELECT record_id, SUM(amount) as total_others FROM others_expenses GROUP BY record_id) oe ON oer.record_id = oe.record_id
    WHERE o.owner_id = {$userID}
";

// Add filters to the query if they're not set to 'all'
if ($outletFilter !== 'all') {
    $query .= " AND oer.outlet_id = " . intval($outletFilter);
}
if ($yearFilter !== 'all') {
    $query .= " AND YEAR(oer.record_date) = " . intval($yearFilter);
}
if ($monthFilter !== 'all') {
    $query .= " AND MONTH(oer.record_date) = " . intval($monthFilter);
}
if ($dayFilter !== 'all') {
    $query .= " AND DAY(oer.record_date) = " . intval($dayFilter);
}

// Complete the query with grouping, ordering and pagination
$query .= " GROUP BY oer.record_id 
            ORDER BY oer.record_date DESC, oer.record_id DESC 
            LIMIT $offset, $records_per_page";

// Fetch filtered operating expense records
$records = $conn->query($query);

// Fetch distinct outlets for filter dropdown
$outlets = $conn->query("SELECT outlet_id, outlet_name FROM outlets WHERE owner_id = {$userID} ORDER BY outlet_name");

// Get distinct years from records for year filter
$years = $conn->query("SELECT DISTINCT YEAR(oer.record_date) as year FROM operating_expense_records oer INNER JOIN outlets o ON oer.outlet_id = o.outlet_id WHERE o.owner_id = {$userID} ORDER BY year DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Manage Operating Expenses</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/manage-closing-report.css">

    <style>
        /* Filter Section Styles */
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .filter-section h5 {
            margin-bottom: 1rem;
            color: #495057;
            font-weight: 600;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }
        
        .filter-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            background-color: white;
            font-size: 0.9rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .table thead tr {
            background-color: #343a40;
            color: #fff;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
        }

        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        
        /* Custom styles for the new button design */
        .btn-icon-danger {
            color: #dc3545;
            background-color: transparent;
            border: 1px solid transparent;
            padding: .25rem .5rem; /* Reduced padding */
            border-radius: .25rem;
            transition: all .2s ease-in-out;
        }

        .btn-icon-danger:hover {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-icon-danger:focus,
        .btn-icon-danger:active {
            box-shadow: 0 0 0 .25rem rgba(220, 53, 69, .25);
            background-color: #c82333;
            border-color: #bd2130;
        }

        /* Pagination Styles */
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .pagination .page-link {
            color: #495057;
            border: 1px solid #dee2e6;
            margin: 0 2px;
            border-radius: 6px;
        }

        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #f8f9fa;
        }

        .pagination-info, .pagination-jump {
            min-width: 150px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            /* Filter Section Responsive */
            .filter-row {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .filter-buttons {
                justify-content: space-between;
                width: 100%;
            }
            
            /* Table Responsive - Improved for mobile */
            .table-container {
                border: none;
                box-shadow: none;
            }

            /* Hide the regular table header on mobile */
            .table thead {
                display: none;
            }

            /* Convert table to block elements for mobile */
            .table, .table tbody, .table tr {
                display: block;
                width: 100%;
            }

            .table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                padding: 0.5rem;
            }
            
            /* Style table cells for mobile */
            .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 0.5rem;
                border: none;
                border-bottom: 1px solid #dee2e6;
            }
            
            /* Remove border from last cell in each row */
            .table td:last-child {
                border-bottom: none;
            }
            
            /* Add labels before each cell content for mobile */
            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #495057;
                text-align: left;
                flex: 0 0 40%;
            }
            
            /* Style the cell content */
            .table td > *:not(:first-child) {
                flex: 0 0 60%;
                text-align: right;
            }
            
            /* Special handling for action buttons on mobile */
            .table td[data-label="Actions"] {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .table td[data-label="Actions"]::before {
                margin-bottom: 0.5rem;
            }
            
            .table td[data-label="Actions"] .btn {
                width: 100%;
                text-align: center;
                margin: 0.1rem 0;
            }
            
            /* Ensure buttons are properly aligned */
            .table td[data-label="Actions"] .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            /* Pagination responsive */
            .pagination-info, .pagination-jump {
                min-width: auto;
                text-align: center;
                margin-bottom: 0.5rem;
            }
        }
        
        /* Extra small devices (phones) */
        @media (max-width: 576px) {
            .filter-section {
                padding: 1rem;
            }
            
            .table td::before {
                flex: 0 0 45%;
                font-size: 0.9rem;
            }
            
            .table td > *:not(:first-child) {
                flex: 0 0 55%;
                font-size: 0.9rem;
            }
            
            /* Adjust button sizes for very small screens */
            .table td[data-label="Actions"] .btn {
                padding: 0.4rem 0.5rem;
                font-size: 0.85rem;
            }
        }
        
        /* Medium devices (tablets) */
        @media (min-width: 769px) and (max-width: 992px) {
            .filter-group {
                min-width: 120px;
            }
            
            /* Ensure table doesn't overflow on tablets */
            .table-container {
                font-size: 0.9rem;
            }
            
            .table th, .table td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Operating expenses table – action buttons */
        td[data-label="Actions"] .btn-group .btn {
            border-radius: 8px !important;   /* same rounded corners for all buttons */
            margin-right: 8px;               /* horizontal gap */
        }

        /* Remove the last button's extra right margin */
        td[data-label="Actions"] .btn-group .btn:last-child {
            margin-right: 0;
        }

        /* Optional: keep them nicely spaced when wrapping on mobile */
        @media (max-width: 768px) {
            td[data-label="Actions"] .btn-group {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;                     /* clean gap in both directions */
                justify-content: center;      /* center inside the cell */
            }
            td[data-label="Actions"] .btn-group .btn {
                margin-right: 0;              /* rely on gap for spacing */
            }
        }

        /* Improved Pagination Styles */
.pagination {
    flex-wrap: wrap;
    gap: 0.25rem;
}

.pagination .page-link {
    border-radius: 6px;
    min-width: 40px;
    text-align: center;
    padding: 0.375rem 0.75rem;
}

/* Mobile-specific pagination */
@media (max-width: 768px) {
    .pagination {
        justify-content: center !important;
    }
    
    .pagination .page-item {
        margin: 0.1rem;
    }
    
    .pagination .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    /* Hide page numbers on mobile, show only prev/next and current page */
    .pagination .page-item.d-md-none {
        display: block !important;
    }
    
    .pagination .page-item.d-none.d-md-block {
        display: none !important;
    }
}

/* Small mobile devices */
@media (max-width: 576px) {
    .pagination .page-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
        min-width: 36px;
    }
    
    .pagination-info, .pagination-jump {
        width: 100%;
        text-align: center;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 768px) {
  .topbar .page-title h1 { 
    font-size: 1.1rem !important;  /* smaller on phone/tablet */
  }
}

/* ===== Enhanced Delete Confirmation Modal ===== */

/* Modal Dialog — adds soft elevation and rounded shape */
#deleteConfirmationModal .modal-dialog {
  max-width: 420px; /* slightly narrower for a focused look */
}

#deleteConfirmationModal .modal-content {
  border: none;
  border-radius: 18px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.25);
  background: #ffffff;
  overflow: hidden;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

/* Small lift animation on show */
#deleteConfirmationModal.show .modal-content {
  transform: translateY(-3px);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.3);
}

/* Header */
#deleteConfirmationModal .modal-header {
  border-bottom: none;
  padding: 1.25rem 1.5rem 0.5rem;
  text-align: center;
  justify-content: center;
}

#deleteConfirmationModal .modal-title {
  font-weight: 700;
  color: #111827;
  text-align: center;
}

/* Body */
#deleteConfirmationModal .modal-body {
  color: #374151;
  font-size: 0.96rem;
  line-height: 1.5;
}

#deleteConfirmationModal .modal-body h4 {
  font-weight: 700;
  margin-bottom: 0.4rem;
  color: #dc2626; /* strong red for warning */
}

#deleteConfirmationModal .modal-icon i {
  color: #ef4444;
  animation: pulseAlert 1.5s ease-in-out infinite;
}

/* Button area */
#deleteConfirmationModal .modal-footer {
  border-top: none;
  padding-bottom: 1.4rem;
  gap: 0.6rem;
}

#deleteConfirmationModal .btn {
  font-weight: 600;
  padding: 0.7rem 1.4rem;
  border-radius: 10px;
  transition: all 0.2s ease;
}

/* Cancel button */
#deleteConfirmationModal .btn-secondary {
  background-color: #e5e7eb;
  color: #1f2937;
}
#deleteConfirmationModal .btn-secondary:hover {
  background-color: #d1d5db;
}

/* Delete button */
#deleteConfirmationModal .btn-danger {
  background-color: #ef4444;
  border: none;
}
#deleteConfirmationModal .btn-danger:hover {
  background-color: #dc2626;
  box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
}

/* Pulsing icon animation */
@keyframes pulseAlert {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.85; }
  100% { transform: scale(1); opacity: 1; }
}


    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            
            <!-- Sidebar Navigation -->
            <nav id="sidebar" class="sidebar">
                <div class="sidebar-sticky">
                    
                    <div class="sidebar-header">
                        <div class="brand-logo">
                            <i class="fas fa-chart-pie"></i>
                            <span class="brand-text">ezAccount</span>
                        </div>
                        <button id="sidebarCollapse" class="collapse-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>
                    
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

            <!-- Mini Sidebar for collapsed state -->
            <nav id="mini-sidebar" class="mini-sidebar">
                <div class="mini-sidebar-sticky">
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
                    
                    <a class="mini-nav-link" href="owner-stock.php" title="Stock">
                        <i class="fas fa-boxes"></i>
                    </a>

                    <!-- Operating Expenses Menu Item -->
<a class="mini-nav-link active" href="operating-expenses-table.php" title="Operating Expenses">
    <i class="fas fa-money-bill-wave"></i>
</a>

                    
                    <a class="mini-nav-link" href="owner-closing-report.php" title="Closing Sales">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </a>
                    
                    
                    
                    <button id="sidebarExpand" class="expand-btn" title="Expand Menu">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="main-content">
                
                <!-- Top Bar with User Info -->
                <header class="topbar">
                    <div class="topbar-container">
                        
                        <button id="sidebarToggle" class="btn toggle-btn">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div class="page-title">
                            <h1>Manage Operating Expenses</h1>
                            <p class="page-subtitle">View and manage all operating expense records</p>
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
                                        <div class="user-avatar-sm">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
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

                <!-- Main Content Container -->
                <div class="container py-4">
                    <?php if (isset($success_msg)): ?>
                        <!-- Success message alert (commented out as per original code) -->
                        <!--<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>-->
                    <?php endif; ?>

                    <!-- Filter Section -->
                    <div class="card p-4 mb-4">
                        <h4>🔍 Filter Records</h4>
                        <div class="filter-section">
                            <form method="GET" action="">
                                <div class="filter-row">
                                    <!-- Outlet Filter -->
                                    <div class="filter-group">
                                        <label for="outletFilter">Outlet</label>
                                        <select id="outletFilter" name="outlet" class="filter-select">
                                            <option value="all" <?= $outletFilter === 'all' ? 'selected' : '' ?>>All Outlets</option>
                                            <?php while($outlet = $outlets->fetch_assoc()): ?>
                                                <option value="<?= $outlet['outlet_id'] ?>" <?= $outletFilter == $outlet['outlet_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($outlet['outlet_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Year Filter -->
                                    <div class="filter-group">
                                        <label for="yearFilter">Year</label>
                                        <select id="yearFilter" name="year" class="filter-select">
                                            <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All Years</option>
                                            <?php 
                                            $currentYear = date('Y');
                                            while($year = $years->fetch_assoc()): 
                                                if ($year['year'] <= $currentYear): // Only show past and current years ?>
                                                    <option value="<?= $year['year'] ?>" <?= $yearFilter == $year['year'] ? 'selected' : '' ?>>
                                                        <?= $year['year'] ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Month Filter -->
                                    <div class="filter-group">
                                        <label for="monthFilter">Month</label>
                                        <select id="monthFilter" name="month" class="filter-select">
                                            <option value="all" <?= $monthFilter === 'all' ? 'selected' : '' ?>>All Months</option>
                                            <?php 
                                            $months = [
                                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                            ];
                                            foreach($months as $num => $name): ?>
                                                <option value="<?= $num ?>" <?= $monthFilter == $num ? 'selected' : '' ?>>
                                                    <?= $name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Day Filter (1-31) -->
                                    <div class="filter-group">
                                        <label for="dayFilter">Day</label>
                                        <select id="dayFilter" name="day" class="filter-select">
                                            <option value="all" <?= $dayFilter === 'all' ? 'selected' : '' ?>>All Days</option>
                                            <?php for($i = 1; $i <= 31; $i++): ?>
                                                <option value="<?= $i ?>" <?= $dayFilter == $i ? 'selected' : '' ?>>
                                                    <?= $i ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-1"></i> Apply Filters
                                    </button>
                                    <a href="operating-expenses-table.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Clear Filters
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Operating Expenses Table -->
                    <div class="card p-4">
                        <h4>💰 Operating Expenses Records</h4>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Outlet</th>
                                        <th>Salary</th>
                                        <th>Rental</th>
                                        <th>Utilities</th>
                                        <th>Advertisement</th>
                                        <th>Others</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($records->num_rows > 0):
                                        while($r = $records->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td data-label="Date"><?= $r['record_date'] ?></td>
                                        <td data-label="Outlet"><?= htmlspecialchars($r['outlet_name'] ?? 'N/A') ?></td>
                                        <td data-label="Salary">RM <?= number_format($r['total_salary'], 2) ?></td>
                                        <td data-label="Rental">RM <?= number_format($r['total_rental'], 2) ?></td>
                                        <td data-label="Utilities">RM <?= number_format($r['total_utilities'], 2) ?></td>
                                        <td data-label="Advertisement">RM <?= number_format($r['total_advertisement'], 2) ?></td>
                                        <td data-label="Others">RM <?= number_format($r['total_others'], 2) ?></td>
                                        <td data-label="Total" class="fw-bold">RM <?= number_format($r['total_operating_expense'], 2) ?></td>
                                        <td data-label="Actions">
                                            <div class="btn-group" role="group">
                                                <a href="view-operating-expenses.php?id=<?= $r['record_id'] ?>" class="btn btn-primary btn-sm me-1">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="edit-operating-expense.php?id=<?= $r['record_id'] ?>" class="btn btn-warning btn-sm me-1">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-icon-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                    data-bs-id="<?= $r['record_id'] ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else: 
                                    ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No operating expense records found matching your filters.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <?php if ($total_pages > 1): ?>
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="pagination-info">
                                        <small class="text-muted">
                                            Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> records
                                        </small>
                                    </div>
                                    
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination mb-0">
                                            <!-- Previous Button -->
                                            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" 
                                                   href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" 
                                                   aria-label="Previous">
                                                    <i class="fas fa-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                            
                                            <!-- Page Numbers -->
                                            <?php
                                            $start_page = max(1, $current_page - 2);
                                            $end_page = min($total_pages, $current_page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++):
                                            ?>
                                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                                    <a class="page-link" 
                                                       href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Next Button -->
                                            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" 
                                                   href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" 
                                                   aria-label="Next">
                                                    Next <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                    
                                    <div class="pagination-jump">
                                        <small class="text-muted">
                                            Page <?= $current_page ?> of <?= $total_pages ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Deletion</h5>
                        
                    </div>
                    <div class="modal-body text-center p-4">
                        <div class="modal-icon text-danger mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                        </div>
                        <h4>Are you sure?</h4>
                        <p>This action cannot be undone. The selected operating expense record will be permanently deleted.</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Yes, Delete</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout Confirmation Modal -->
        <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-5">
                        <div class="modal-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        
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

        <!-- JavaScript Libraries -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Custom JavaScript -->
        <script src="JAVASCRIPT/owner-dashboard.js"></script>
        
        <!-- JavaScript for Delete Confirmation Modal -->
        <script>
            // JavaScript to handle the delete confirmation modal
            const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
            deleteConfirmationModal.addEventListener('show.bs.modal', function (event) {
                // Button that triggered the modal
                const button = event.relatedTarget;
                // Extract info from data-bs-* attributes
                const recordId = button.getAttribute('data-bs-id');
                // Update the modal's delete button link
                const confirmDeleteBtn = deleteConfirmationModal.querySelector('#confirmDeleteBtn');
                confirmDeleteBtn.href = `remove-operating-expense.php?id=${recordId}`;
            });
        </script>

    </div>
</body>
</html>