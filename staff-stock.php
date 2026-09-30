<?php
/**
 * ezAccount System - Stock In/Out Management
 * 
 * Interface for staff to manage stock movements within their assigned outlet
 */

session_start();
include("connection.php");

// Redirect to login if user is not authenticated as staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: index.php");
    exit();
}

// Get user data from session for personalized display
$userID = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$companyName  = $_SESSION['company_name'];
$outletName = $_SESSION['outlet_name'];
$outletID = $_SESSION['outlet_id'];

// Filter parameters - REMOVED OUTLET FILTER, SET DEFAULT TO "ALL" FOR OTHERS
$filterMovementType = isset($_GET['movement_type']) ? $_GET['movement_type'] : '';
$filterYear = isset($_GET['year']) ? intval($_GET['year']) : 0; // Changed to 0 for "all"
$filterMonth = isset($_GET['month']) ? intval($_GET['month']) : 0; // Changed to 0 for "all"
$filterDay = isset($_GET['day']) ? intval($_GET['day']) : 0; // Changed to 0 for "all"

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;


// Handle Add Stock Movement
if (isset($_POST['add_movement'])) {
    $itemId = intval($_POST['item_id']);
    $movementDate = $_POST['movement_date'];
    $movementType = $_POST['movement_type'];
    $movementQuantity = intval($_POST['movement_quantity']);
    
    // Get current quantity and base price of the item
    $itemQuery = "SELECT remaining_quantity, base_price FROM stock_items WHERE item_id = ?";
    $itemStmt = $conn->prepare($itemQuery);
    $itemStmt->bind_param("i", $itemId);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();
    
    if ($itemRow = $itemResult->fetch_assoc()) {
        $currentQuantity = $itemRow['remaining_quantity'];
        $basePrice = $itemRow['base_price'];
        $newQuantity = ($movementType === 'IN') ? 
            $currentQuantity + $movementQuantity : 
            $currentQuantity - $movementQuantity;
        
        // Calculate new total stock value
        $newTotalValue = $newQuantity * $basePrice;
        
        // Insert movement record with staff_id
        $insertMovement = $conn->prepare("
            INSERT INTO stock_movements (item_id, staff_id, movement_date, movement_type, movement_quantity) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertMovement->bind_param("iissi", $itemId, $userID, $movementDate, $movementType, $movementQuantity);
        
        // Update item quantity and total stock value
        $updateItem = $conn->prepare("UPDATE stock_items SET remaining_quantity = ?, total_stock_value = ? WHERE item_id = ?");
        $updateItem->bind_param("idi", $newQuantity, $newTotalValue, $itemId);
        
        // Execute both queries in transaction
        $conn->begin_transaction();
        
        try {
            $insertMovement->execute();
            $updateItem->execute();
            $conn->commit();
            
            // Redirect to refresh with success message
            header("Location: staff-stock.php?success=1");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Failed to add stock movement. Please try again.";
        }
    } else {
        $error_msg = "Item not found.";
    }
}

// Handle Edit Stock Movement - FIXED LOGIC
if (isset($_POST['edit_movement'])) {
    $movementId = intval($_POST['movement_id']);
    $originalItemId = intval($_POST['original_item_id']);
    $newItemId = intval($_POST['item_id']);
    $movementDate = $_POST['movement_date'];
    $originalMovementType = $_POST['original_movement_type'];
    $newMovementType = $_POST['movement_type'];
    $originalQuantity = intval($_POST['original_quantity']);
    $newQuantity = intval($_POST['movement_quantity']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Reverse the original movement on the original item
        if ($originalItemId > 0) {
            $originalItemQuery = "SELECT remaining_quantity, base_price FROM stock_items WHERE item_id = ?";
            $originalItemStmt = $conn->prepare($originalItemQuery);
            $originalItemStmt->bind_param("i", $originalItemId);
            $originalItemStmt->execute();
            $originalItemResult = $originalItemStmt->get_result();
            
            if ($originalItemRow = $originalItemResult->fetch_assoc()) {
                $originalCurrentQuantity = $originalItemRow['remaining_quantity'];
                $originalBasePrice = $originalItemRow['base_price'];
                
                // Reverse the original movement
                if ($originalMovementType === 'IN') {
                    $reversedQuantity = $originalCurrentQuantity - $originalQuantity;
                } else {
                    $reversedQuantity = $originalCurrentQuantity + $originalQuantity;
                }
                
                // Update original item
                $reversedTotalValue = $reversedQuantity * $originalBasePrice;
                $updateOriginalItem = $conn->prepare("UPDATE stock_items SET remaining_quantity = ?, total_stock_value = ? WHERE item_id = ?");
                $updateOriginalItem->bind_param("idi", $reversedQuantity, $reversedTotalValue, $originalItemId);
                $updateOriginalItem->execute();
            }
        }
        
        // 2. Apply the new movement to the new item
        $newItemQuery = "SELECT remaining_quantity, base_price FROM stock_items WHERE item_id = ?";
        $newItemStmt = $conn->prepare($newItemQuery);
        $newItemStmt->bind_param("i", $newItemId);
        $newItemStmt->execute();
        $newItemResult = $newItemStmt->get_result();
        
        if ($newItemRow = $newItemResult->fetch_assoc()) {
            $newCurrentQuantity = $newItemRow['remaining_quantity'];
            $newBasePrice = $newItemRow['base_price'];
            
            // Apply new movement - ALLOW NEGATIVE STOCK
            if ($newMovementType === 'IN') {
                $finalQuantity = $newCurrentQuantity + $newQuantity;
            } else {
                $finalQuantity = $newCurrentQuantity - $newQuantity; // Removed max(0, ...) to allow negative
            }
            
            // Update new item
            $finalTotalValue = $finalQuantity * $newBasePrice;
            $updateNewItem = $conn->prepare("UPDATE stock_items SET remaining_quantity = ?, total_stock_value = ? WHERE item_id = ?");
            $updateNewItem->bind_param("idi", $finalQuantity, $finalTotalValue, $newItemId);
            $updateNewItem->execute();
        }
        
        // 3. Update the movement record
        $updateMovement = $conn->prepare("
            UPDATE stock_movements SET item_id = ?, movement_date = ?, movement_type = ?, movement_quantity = ? 
            WHERE movement_id = ?
        ");
        $updateMovement->bind_param("issii", $newItemId, $movementDate, $newMovementType, $newQuantity, $movementId);
        $updateMovement->execute();
        
        $conn->commit();
        
        // Redirect to refresh with success message
        header("Location: staff-stock.php?success=2");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Failed to update stock movement. Please try again.";
    }
}

// Handle Delete Stock Movement
if (isset($_GET['delete_movement'])) {
    $movementId = intval($_GET['delete_movement']);
    
    // Get movement data
    $movementQuery = "SELECT item_id, movement_type, movement_quantity FROM stock_movements WHERE movement_id = ?";
    $movementStmt = $conn->prepare($movementQuery);
    $movementStmt->bind_param("i", $movementId);
    $movementStmt->execute();
    $movementResult = $movementStmt->get_result();
    
    if ($movementRow = $movementResult->fetch_assoc()) {
        $itemId = $movementRow['item_id'];
        $movementType = $movementRow['movement_type'];
        $movementQuantity = $movementRow['movement_quantity'];
        
        // Get current item data
        $itemQuery = "SELECT remaining_quantity, base_price FROM stock_items WHERE item_id = ?";
        $itemStmt = $conn->prepare($itemQuery);
        $itemStmt->bind_param("i", $itemId);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        
        if ($itemRow = $itemResult->fetch_assoc()) {
            $currentQuantity = $itemRow['remaining_quantity'];
            $basePrice = $itemRow['base_price'];
            
            // Reverse the movement
            if ($movementType === 'IN') {
                $newQuantity = $currentQuantity - $movementQuantity;
            } else {
                $newQuantity = $currentQuantity + $movementQuantity;
            }
            
            $newTotalValue = $newQuantity * $basePrice;
            
            // Delete movement record
            $deleteMovement = $conn->prepare("DELETE FROM stock_movements WHERE movement_id = ?");
            $deleteMovement->bind_param("i", $movementId);
            
            // Update item quantity and total stock value
            $updateItem = $conn->prepare("UPDATE stock_items SET remaining_quantity = ?, total_stock_value = ? WHERE item_id = ?");
            $updateItem->bind_param("idi", $newQuantity, $newTotalValue, $itemId);
            
            // Execute both queries in transaction
            $conn->begin_transaction();
            
            try {
                $deleteMovement->execute();
                $updateItem->execute();
                $conn->commit();
                
                // Redirect to refresh with success message
                header("Location: staff-stock.php?success=3");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Failed to delete stock movement. Please try again.";
            }
        }
    }
}

// Build filter conditions for movement history - RESTRICT TO STAFF'S OUTLET AND THEIR MOVEMENTS
$filterConditions = [];
$filterParams = [];
$filterTypes = "";

// Always restrict to staff's outlet and their movements
$filterConditions[] = "o.outlet_id = ?";
$filterParams[] = $outletID;
$filterTypes .= "i";

$filterConditions[] = "sm.staff_id = ?";
$filterParams[] = $userID;
$filterTypes .= "i";

if (!empty($filterMovementType)) {
    $filterConditions[] = "sm.movement_type = ?";
    $filterParams[] = $filterMovementType;
    $filterTypes .= "s";
}

// Date filtering logic - SIMPLIFIED AND FIXED
if ($filterYear > 0) {
    $filterConditions[] = "YEAR(sm.movement_date) = ?";
    $filterParams[] = $filterYear;
    $filterTypes .= "i";
}

if ($filterMonth > 0) {
    $filterConditions[] = "MONTH(sm.movement_date) = ?";
    $filterParams[] = $filterMonth;
    $filterTypes .= "i";
}

if ($filterDay > 0) {
    $filterConditions[] = "DAY(sm.movement_date) = ?";
    $filterParams[] = $filterDay;
    $filterTypes .= "i";
}

// Build the WHERE clause
$whereClause = "";
if (!empty($filterConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $filterConditions);
}

// Get total records count for pagination
$countQuery = "
    SELECT COUNT(*) as total_records
    FROM stock_movements sm
    JOIN stock_items si ON sm.item_id = si.item_id
    JOIN stock_category sc ON si.category_id = sc.category_id
    JOIN outlets o ON sc.outlet_id = o.outlet_id
    LEFT JOIN staff st ON sm.staff_id = st.staff_id
    $whereClause
";
$countStmt = $conn->prepare($countQuery);
if (!empty($filterParams)) {
    $countStmt->bind_param($filterTypes, ...$filterParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total_records = $countResult->fetch_assoc()['total_records'];
$total_pages = ceil($total_records / $records_per_page);


// Get movement history with filters - ONLY SHOW STAFF'S OWN MOVEMENTS
$movementQuery = "
    SELECT sm.*, si.item_name, sc.category_name, o.outlet_name, st.staff_name,
           si.item_id, o.outlet_id, si.remaining_quantity, si.base_price
    FROM stock_movements sm
    JOIN stock_items si ON sm.item_id = si.item_id
    JOIN stock_category sc ON si.category_id = sc.category_id
    JOIN outlets o ON sc.outlet_id = o.outlet_id
    LEFT JOIN staff st ON sm.staff_id = st.staff_id
    $whereClause
    ORDER BY sm.movement_date DESC, sm.movement_id DESC
    LIMIT $offset, $records_per_page
";
$movementStmt = $conn->prepare($movementQuery);
if (!empty($filterParams)) {
    $movementStmt->bind_param($filterTypes, ...$filterParams);
}
$movementStmt->execute();
$movementResult = $movementStmt->get_result();

// Get distinct years from movements for year filter (only staff's movements)
$yearsQuery = "SELECT DISTINCT YEAR(sm.movement_date) as year 
               FROM stock_movements sm
               JOIN stock_items si ON sm.item_id = si.item_id
               JOIN stock_category sc ON si.category_id = sc.category_id
               WHERE sc.outlet_id = ? AND sm.staff_id = ?
               ORDER BY year DESC";
$yearsStmt = $conn->prepare($yearsQuery);
$yearsStmt->bind_param("ii", $outletID, $userID);
$yearsStmt->execute();
$yearsResult = $yearsStmt->get_result();

// Get items for staff's outlet for dropdowns
$itemsQuery = "SELECT si.item_id, si.item_name, sc.category_name, si.remaining_quantity, si.base_price
               FROM stock_items si
               JOIN stock_category sc ON si.category_id = sc.category_id
               WHERE sc.outlet_id = ?
               ORDER BY sc.category_name, si.item_name";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bind_param("i", $outletID);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

// Check for success message
$success_msg = "";
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success_msg = "✅ Stock movement added successfully!";
    } elseif ($_GET['success'] == 2) {
        $success_msg = "✅ Stock movement updated successfully!";
    } elseif ($_GET['success'] == 3) {
        $success_msg = "✅ Stock movement deleted successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Stock In/Out</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">   
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/staff-stock.css">

    <style>
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
                        

                        <!-- Stock Menu Dropdown -->
                        <li class="nav-item dropdown stock-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center active" href="#" id="stockDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="menu-icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <span class="menu-text">Stock</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="stockDropdown">
                                <li><a class="dropdown-item" href="staff-stock.php">Stock In & Out</a></li>
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
                                <li><a class="dropdown-item" href="staff-closing-report.php">New Closing Sales</a></li>
                                <li><a class="dropdown-item" href="staff-closing-report-table.php">Manage Closing Sales</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                    
                    <!-- User Profile -->
                    <div class="sidebar-footer">
                        <div class="user-profile">
                            <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
                            <div class="user-details">
                                <h6 class="user-name"><?php echo htmlspecialchars($userName); ?></h6>
                                <small class="user-role">Staff</small>
                                <small class="user-company"><?php echo htmlspecialchars($companyName); ?></small>
                                <small class="user-company"><?php echo htmlspecialchars($outletName); ?></small>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Mini Sidebar -->
            <nav id="mini-sidebar" class="mini-sidebar">
                <div class="mini-sidebar-sticky">
                    <a class="mini-nav-link active" href="staff-stock.php" title="Stock"><i class="fas fa-boxes"></i></a>
                    <a class="mini-nav-link" href="staff-closing-report.php" title="Closing Sales"><i class="fas fa-file-invoice-dollar"></i></a>
                    <button id="sidebarExpand" class="expand-btn" title="Expand Menu"><i class="fas fa-chevron-right"></i></button>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="main-content">
                
                <!-- Topbar -->
                <header class="topbar">
                    <div class="topbar-container">
                        <button id="sidebarToggle" class="btn toggle-btn"><i class="fas fa-bars"></i></button>
                        <div class="page-title"><h1>Stock In & Out</h1>
                        <p class="page-subtitle">Manage your stock movements – Record, edit, or remove stock in/out activities</p></div>
                        <div class="user-actions">
                            <div class="welcome-message">
                                <span class="greeting">Hi, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                                <small class="company-name"><?php echo htmlspecialchars($companyName). " - "; ?></small>
                                <small class="company-name"><?php echo htmlspecialchars($outletName); ?></small>
                            </div>
                            <div class="dropdown user-dropdown">
                                <button class="btn user-btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-info">
                                        <div class="user-avatar-sm"><i class="fas fa-user-circle"></i></div>
                                        <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                                    </div>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li class="dropdown-email-mobile">
                                        <div class="dropdown-item email-item"><i class="fas fa-envelope me-2"></i><span><?php echo htmlspecialchars($userEmail); ?></span></div>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="staff-profile.php"><i class="fas fa-user me-2"></i>Profile Settings</a></li>
                                    
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item logout-item" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <div class="content-container">
                    <div class="container my-4">
                        
                        <!-- Success Message -->
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                               
                                <?php echo $success_msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Error Message -->
                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                
                                <?php echo $error_msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Filter Section - REMOVED OUTLET FILTER -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card filter-card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Stock Movements</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <!-- Movement Type Filter -->
                                            <div class="col-md-3">
                                                <label for="movementTypeFilter" class="form-label">Movement Type</label>
                                                <select class="form-select" id="movementTypeFilter" name="movement_type">
                                                    <option value="" <?= empty($filterMovementType) ? 'selected' : '' ?>>All Types</option>
                                                    <option value="IN" <?= $filterMovementType === 'IN' ? 'selected' : '' ?>>Stock In</option>
                                                    <option value="OUT" <?= $filterMovementType === 'OUT' ? 'selected' : '' ?>>Stock Out</option>
                                                </select>
                                            </div>
                                            
                                            <!-- Year Filter -->
                                            <div class="col-md-3">
                                                <label for="yearSelect" class="form-label">Year</label>
                                                <select class="form-select" id="yearSelect" name="year">
                                                    <option value="0" <?= $filterYear === 0 ? 'selected' : '' ?>>All Years</option>
                                                    <?php
                                                    $currentYear = date('Y');
                                                    if ($yearsResult->num_rows > 0) {
                                                        $yearsResult->data_seek(0);
                                                        while($year = $yearsResult->fetch_assoc()): 
                                                            if ($year['year'] <= $currentYear): ?>
                                                                <option value="<?= $year['year'] ?>" <?= $filterYear == $year['year'] ? 'selected' : '' ?>>
                                                                    <?= $year['year'] ?>
                                                                </option>
                                                            <?php endif;
                                                        endwhile;
                                                    } else {
                                                        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                                            $selected = $filterYear == $y ? 'selected' : '';
                                                            echo "<option value='$y' $selected>$y</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <!-- Month Filter -->
                                            <div class="col-md-3">
                                                <label for="monthSelect" class="form-label">Month</label>
                                                <select class="form-select" id="monthSelect" name="month">
                                                    <option value="0" <?= $filterMonth === 0 ? 'selected' : '' ?>>All Months</option>
                                                    <?php
                                                    $months = [
                                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                                    ];
                                                    foreach ($months as $num => $name) {
                                                        $selected = $filterMonth == $num ? 'selected' : '';
                                                        echo "<option value='$num' $selected>$name</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <!-- Day Filter -->
                                            <div class="col-md-3">
                                                <label for="daySelect" class="form-label">Day</label>
                                                <select class="form-select" id="daySelect" name="day">
                                                    <option value="0" <?= $filterDay === 0 ? 'selected' : '' ?>>All Days</option>
                                                    <?php
                                                    for ($d = 1; $d <= 31; $d++) {
                                                        $selected = $filterDay == $d ? 'selected' : '';
                                                        echo "<option value='$d' $selected>$d</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="filter-buttons-mobile">
                                                    <button type="submit" class="btn btn-primary me-2 mb-2">
                                                        <i class="fas fa-filter me-1"></i> Apply Filters
                                                    </button>
                                                    <a href="staff-stock.php" class="btn btn-outline-secondary mb-2">
                                                        <i class="fas fa-times me-1"></i> Clear Filters
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <button class="btn btn-success btn-add-movement" data-bs-toggle="modal" data-bs-target="#addMovementModal">
                                    <i class="fas fa-plus-circle me-2"></i>New Stock Movement
                                </button>
                            </div>
                        </div>

                        <!-- Stock Movements Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card table-card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Stock Movement History</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if($movementResult->num_rows > 0): ?>
                                            <div class="table-container">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Outlet</th>
                                                            <th>Staff</th>
                                                            
                                                            <th>Category</th>
                                                            <th>Item</th>
                                                            <th>Type</th>
                                                            <th>Quantity</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while($movement = $movementResult->fetch_assoc()): ?>
                                                            <tr>
                                                                <td data-label="Date"><?php echo date('d/m/Y', strtotime($movement['movement_date'])); ?></td>
                                                                
                                                                <td data-label="Outlet"><?php echo htmlspecialchars($movement['outlet_name']); ?></td>
                                                                <td data-label="Staff"><?php echo htmlspecialchars($movement['staff_name'] ?: 'N/A'); ?></td>
                                                                <td data-label="Category"><?php echo htmlspecialchars($movement['category_name']); ?></td>
                                                                <td data-label="Item"><?php echo htmlspecialchars($movement['item_name']); ?></td>
                                                                <td data-label="Type">
                                                                    <span class="badge <?php echo $movement['movement_type'] === 'IN' ? 'bg-success' : 'bg-danger'; ?>">
                                                                        <i class="fas fa-<?php echo $movement['movement_type'] === 'IN' ? 'arrow-down' : 'arrow-up'; ?> me-1"></i>
                                                                        <?php echo $movement['movement_type']; ?>
                                                                    </span>
                                                                </td>
                                                                <td data-label="Quantity"><?php echo number_format($movement['movement_quantity']); ?></td>
                                                                <td data-label="Actions">
                                                                    <div class="btn-group action-buttons" role="group">
                                                                        <button class="btn btn-warning btn-sm btn-edit" 
                                                                                data-bs-toggle="modal" 
                                                                                data-bs-target="#editMovementModal"
                                                                                data-movement-id="<?= $movement['movement_id'] ?>"
                                                                                data-movement-date="<?= $movement['movement_date'] ?>"
                                                                                data-outlet-id="<?= $movement['outlet_id'] ?>"
                                                                                data-item-id="<?= $movement['item_id'] ?>"
                                                                                data-movement-type="<?= $movement['movement_type'] ?>"
                                                                                data-movement-quantity="<?= $movement['movement_quantity'] ?>"
                                                                                data-current-stock="<?= $movement['remaining_quantity'] ?>">
                                                                            <i class="fas fa-edit"></i> Edit
                                                                        </button>
                                                                        <button class="btn btn-danger btn-sm btn-delete" 
                                                                                data-bs-toggle="modal" 
                                                                                data-bs-target="#deleteMovementModal"
                                                                                data-movement-id="<?= $movement['movement_id'] ?>"
                                                                                data-item-name="<?= htmlspecialchars($movement['item_name']) ?>">
                                                                            <i class="fas fa-trash"></i> Remove
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-state text-center">
                                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                                <h4>No Stock Movements Found</h4>
                                                <p>There are no stock movements matching your current filters.</p>
                                                
                                            </div>
                                        <?php endif; ?>
                                    </div>
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
                    </div>
                </div>

                
            </main>
        </div>
    </div>

    <!-- Add Stock Movement Modal - REMOVED OUTLET FIELD -->
    <div class="modal fade" id="addMovementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-centered">
                    <h5 class="modal-title modal-title-white">
                        <i class="fas fa-plus-circle me-2"></i>New Stock Movement
                    </h5>
                </div>
                <form method="post" id="movementForm">
                    <div class="modal-body">
                        <!-- Date Field -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-calendar purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <input type="date" name="movement_date" class="form-control" id="movementDate" value="<?php echo date('Y-m-d'); ?>" placeholder="Date">
                                    <label for="movementDate">Date</label>
                                </div>
                                <span class="validation-icon-outside" id="dateValidationIcon"></span>
                            </div>
                            <div class="error-message" id="dateError"></div>
                        </div>
                        
                        <!-- Item Field - AUTO-LOAD ITEMS FROM STAFF'S OUTLET -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-box purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <select class="form-select" id="movementItem" name="item_id">
                                        <option value="" selected disabled>--- Select Item ---</option>
                                        <?php
                                        if ($itemsResult->num_rows > 0) {
                                            $itemsResult->data_seek(0);
                                            while($item = $itemsResult->fetch_assoc()) {
                                                echo "<option value='{$item['item_id']}' data-stock='{$item['remaining_quantity']}' data-price='{$item['base_price']}'>
                                                    {$item['item_name']} ({$item['category_name']}) - Stock: {$item['remaining_quantity']}
                                                </option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <label for="movementItem">Item</label>
                                </div>
                                <span class="validation-icon-outside" id="itemValidationIcon"></span>
                            </div>
                            <div class="error-message" id="itemError"></div>
                            <div id="itemDetails" class="item-details mt-2"></div>
                        </div>
                        
                        <!-- Movement Type Field -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-exchange-alt purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <select class="form-select" name="movement_type" id="movementType">
                                        <option value="" selected disabled>--- Select Type ---</option>
                                        <option value="IN">Stock In</option>
                                        <option value="OUT">Stock Out</option>
                                    </select>
                                    <label for="movementType">Movement Type</label>
                                </div>
                                <span class="validation-icon-outside" id="typeValidationIcon"></span>
                            </div>
                            <div class="error-message" id="typeError"></div>
                        </div>
                        
                        <!-- Quantity Field -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-sort-amount-up purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <input type="text" name="movement_quantity" class="form-control" id="movementQuantity" placeholder="Enter quantity">
                                    <label for="movementQuantity">Quantity</label>
                                </div>
                                <span class="validation-icon-outside" id="quantityValidationIcon"></span>
                            </div>
                            <div class="error-message" id="quantityError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" name="add_movement" class="btn btn-primary" id="submitMovement" disabled>
                            <i class="fas fa-check me-1"></i>Add Movement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Stock Movement Modal - REMOVED OUTLET FIELD -->
    <div class="modal fade" id="editMovementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-centered">
                    <h5 class="modal-title modal-title-white">
                        <i class="fas fa-edit me-2"></i>Edit Stock Movement
                    </h5>
                </div>
                <form method="post" id="editMovementForm">
                    <input type="hidden" name="edit_movement" value="1">
                    <input type="hidden" name="movement_id" id="editMovementId">
                    <input type="hidden" name="original_item_id" id="originalItemId">
                    <input type="hidden" name="original_movement_type" id="originalMovementType">
                    <input type="hidden" name="original_quantity" id="originalQuantity">
                    
                    <div class="modal-body">
                        <!-- Date Field -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-calendar purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <input type="date" name="movement_date" class="form-control" id="editMovementDate" placeholder="Date">
                                    <label for="editMovementDate">Date</label>
                                </div>
                                <span class="validation-icon-outside" id="editDateValidationIcon"></span>
                            </div>
                            <div class="error-message" id="editDateError"></div>
                        </div>
                        
                        <!-- Item Field - AUTO-LOAD ITEMS FROM STAFF'S OUTLET -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-box purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <select class="form-select" id="editMovementItem" name="item_id">
                                        <option value="" selected disabled>--- Select Item ---</option>
                                        <?php
                                        // Re-query items for edit modal
                                        $itemsResult->data_seek(0);
                                        if ($itemsResult->num_rows > 0) {
                                            while($item = $itemsResult->fetch_assoc()) {
                                                echo "<option value='{$item['item_id']}' data-stock='{$item['remaining_quantity']}' data-price='{$item['base_price']}'>
                                                    {$item['item_name']} ({$item['category_name']}) - Stock: {$item['remaining_quantity']}
                                                </option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <label for="editMovementItem">Item</label>
                                </div>
                                <span class="validation-icon-outside" id="editItemValidationIcon"></span>
                            </div>
                            <div class="error-message" id="editItemError"></div>
                            <div id="editItemDetails" class="item-details mt-2"></div>
                        </div>
                        
                        <!-- Movement Type Field -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-exchange-alt purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <select class="form-select" name="movement_type" id="editMovementType">
                                        <option value="" selected disabled>--- Select Type ---</option>
                                        <option value="IN">Stock In</option>
                                        <option value="OUT">Stock Out</option>
                                    </select>
                                    <label for="editMovementType">Movement Type</label>
                                </div>
                                <span class="validation-icon-outside" id="editTypeValidationIcon"></span>
                            </div>
                            <div class="error-message" id="editTypeError"></div>
                        </div>
                        
                        <!-- Quantity Field -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-sort-amount-up purple-icon"></i>
                                </span>
                                <div class="form-floating position-relative flex-grow-1">
                                    <input type="text" name="movement_quantity" class="form-control" id="editMovementQuantity" placeholder="Enter quantity">
                                    <label for="editMovementQuantity">Quantity</label>
                                </div>
                                <span class="validation-icon-outside" id="editQuantityValidationIcon"></span>
                            </div>
                            <div class="error-message" id="editQuantityError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitEditMovement" disabled>
                            <i class="fas fa-check me-1"></i>Update Movement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade delete-modal" id="deleteMovementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" style="margin: 0 auto;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="modal-icon text-danger mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h4>Are you sure?</h4>
                    <p>You are about to delete the stock movement for <strong id="deleteItemName"></strong>. This action cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Yes, Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="modal-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <h3 class="modal-title">Confirm Logout</h3>
                    <p class="modal-message">Are you sure you want to logout from your account?</p>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                        <button type="button" class="btn btn-logout" id="confirmLogout"><i class="fas fa-check me-1"></i>Yes, Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="JAVASCRIPT/staff-stock.js"></script>
    <script src="JAVASCRIPT/owner-dashboard.js"></script>
</body>
</html>