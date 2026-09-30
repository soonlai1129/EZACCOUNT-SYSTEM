<?php
session_start();
include("connection.php");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$companyName = $_SESSION['company_name'];
$ownerId = $_SESSION['user_id'];

$outletsQuery = "SELECT * FROM outlets WHERE owner_id = ? ORDER BY outlet_name";
$outletsStmt = $conn->prepare($outletsQuery);
$outletsStmt->bind_param("i", $ownerId);
$outletsStmt->execute();
$outletsResult = $outletsStmt->get_result();

$selectedOutletId = isset($_GET['outlet_id']) ? intval($_GET['outlet_id']) : null;


// ===============================
// FORM SUBMISSION HANDLING
// ===============================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add Category
    if (isset($_POST['add_category'])) {
        $categoryName = trim($_POST['category_name']);
        $outletId = intval($_POST['outlet_id']);
        if ($categoryName === "" || $outletId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Category name and outlet are required.']);
            exit();
        }
        $checkStmt = $conn->prepare("SELECT category_id FROM stock_category WHERE outlet_id = ? AND category_name = ?");
        $checkStmt->bind_param("is", $outletId, $categoryName);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Category name already exists.']);
            exit();
        }
        $stmt = $conn->prepare("INSERT INTO stock_category (category_name, outlet_id) VALUES (?, ?)");
        $stmt->bind_param("si", $categoryName, $outletId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add category.']);
        }
        exit();
    }

    // Edit Category
    if (isset($_POST['edit_category'])) {
        $categoryId = intval($_POST['category_id']);
        $categoryName = trim($_POST['category_name']);
        if ($categoryName === "") {
            echo json_encode(['success' => false, 'message' => 'Category name required.']);
            exit();
        }
        // Check duplicate name in outlet
        $getOutletStmt = $conn->prepare("SELECT outlet_id FROM stock_category WHERE category_id = ?");
        $getOutletStmt->bind_param("i", $categoryId);
        $getOutletStmt->execute();
        $outletData = $getOutletStmt->get_result()->fetch_assoc();
        $outletId = $outletData ? intval($outletData['outlet_id']) : 0;
        $dupStmt = $conn->prepare("SELECT category_id FROM stock_category WHERE outlet_id = ? AND category_name = ? AND category_id != ?");
        $dupStmt->bind_param("isi", $outletId, $categoryName, $categoryId);
        $dupStmt->execute();
        if ($dupStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Category name already exists.']);
            exit();
        }
        $stmt = $conn->prepare("UPDATE stock_category SET category_name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $categoryName, $categoryId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update category.']);
        }
        exit();
    }

        // Add Item
    if (isset($_POST['add_item'])) {
        $categoryId = intval($_POST['category_id']);
        $itemName = trim($_POST['item_name']);
        $basePrice = floatval($_POST['base_price']);
        $quantity = intval($_POST['remaining_quantity']);

        if ($itemName === "" || $basePrice < 0) {
            echo json_encode(['success' => false, 'message' => 'All fields are required and must be valid.']);
            exit();
        }

        // Check for duplicate item name within the same category
        $checkStmt = $conn->prepare("SELECT item_id FROM stock_items WHERE category_id = ? AND item_name = ?");
        $checkStmt->bind_param("is", $categoryId, $itemName);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Item name already exists in this category.']);
            exit();
        }
        $checkStmt->close(); // Close statement after use

        $stmt = $conn->prepare("INSERT INTO stock_items (category_id, item_name, base_price, remaining_quantity, total_stock_value) VALUES (?, ?, ?, ?, ?)");
        $totalValue = $basePrice * $quantity;
        $stmt->bind_param("isdid", $categoryId, $itemName, $basePrice, $quantity, $totalValue);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add item.']);
        }
        $stmt->close();
        exit();
    }

    // Edit Item
    if (isset($_POST['edit_item'])) {
        $itemId = intval($_POST['item_id']);
        $categoryId = intval($_POST['category_id']);
        $itemName = trim($_POST['item_name']);
        $basePrice = floatval($_POST['base_price']);
        $quantity = intval($_POST['remaining_quantity']);
        $totalValue = $basePrice * $quantity;

        if ($itemName === "" || $basePrice < 0) {
            echo json_encode(['success' => false, 'message' => 'All fields are required and must be valid.']);
            exit();
        }

        // Check for duplicate item name within the same category, excluding the current item
        $dupStmt = $conn->prepare("SELECT item_id FROM stock_items WHERE category_id = ? AND item_name = ? AND item_id != ?");
        $dupStmt->bind_param("isi", $categoryId, $itemName, $itemId);
        $dupStmt->execute();

        if ($dupStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Item name already exists in this category.']);
            exit();
        }
        $dupStmt->close(); // Close statement after use

        $stmt = $conn->prepare("UPDATE stock_items SET category_id=?, item_name=?, base_price=?, remaining_quantity=?, total_stock_value=? WHERE item_id=?");
        $stmt->bind_param("isdidi", $categoryId, $itemName, $basePrice, $quantity, $totalValue, $itemId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update item.']);
        }
        $stmt->close();
        exit();
    }


    
// --- Delete Item AJAX (delete its movements first) ---
if (isset($_POST['ajax_delete_item'])) {
    $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item_id']);
        exit();
    }

    $conn->begin_transaction();
    try {
        // 1) Delete movements for this item
        $stmt = $conn->prepare("DELETE FROM stock_movements WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete item movements");
        }
        $stmt->close();

        // 2) Delete the item
        $stmt = $conn->prepare("DELETE FROM stock_items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        if (!$stmt->execute() || $stmt->affected_rows < 1) {
            throw new Exception("Failed to delete item (not found or DB error)");
        }
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item and its movements deleted successfully']);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
    }
    exit();
}

// --- Delete Category AJAX (delete movements of its items, then items, then category) ---
if (isset($_POST['ajax_delete_category'])) {
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if (!$categoryId) {
        echo json_encode(['success' => false, 'message' => 'Invalid category_id']);
        exit();
    }

    $conn->begin_transaction();
    try {
        // 1) Delete movements for ALL items under this category
        //    (Using a join so we don't have to fetch item IDs in PHP)
        $stmt = $conn->prepare("
            DELETE sm
            FROM stock_movements sm
            INNER JOIN stock_items si ON si.item_id = sm.item_id
            WHERE si.category_id = ?
        ");
        $stmt->bind_param("i", $categoryId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete movements under the category");
        }
        $stmt->close();

        // 2) Delete the items under this category
        $stmt = $conn->prepare("DELETE FROM stock_items WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete items under the category");
        }
        $stmt->close();

        // 3) Delete the category itself
        $stmt = $conn->prepare("DELETE FROM stock_category WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);
        if (!$stmt->execute() || $stmt->affected_rows < 1) {
            throw new Exception("Failed to delete category (not found or DB error)");
        }
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Category and all related items & movements deleted successfully']);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
    }
    exit();
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Manage Stock</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/owner-dashboard.css">
    <link rel="stylesheet" href="CSS/manage-stock.css?v=4">
    <style>
/* Floating Add (+) Button — match Owner Staff FAB */
.floating-btn {
     position: fixed;
    bottom: 30px;
    right: 40px;
    width: 84px;
    height: 84px;
    border-radius: 50%;
    font-size: 2.6rem;
    display: flex;
    align-items: center;
    justify-content: center;

    /* visual style */
    background: linear-gradient(135deg, #4895ef, #4361ee);
    color: #fff;
    border: 4px solid #fff;
    box-shadow: 0 10px 28px rgba(67,97,238,0.55);
    z-index: 3000;

    /* smooth interactions */
    transition: all 0.3s;

    /* keep corner anchor so it doesn't "jump" when scaling */
    transform-origin: bottom right;
}
        </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
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
                                <div class="menu-icon"><i class="fas fa-tachometer-alt"></i></div>
                                <span class="menu-text">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item menu-item">
                            <a class="nav-link" href="owner-outlet.php">
                                <div class="menu-icon"><i class="fas fa-store"></i></div>
                                <span class="menu-text">Outlet</span>
                            </a>
                        </li>
                        <li class="nav-item menu-item">
                            <a class="nav-link" href="owner-staff.php">
                                <div class="menu-icon"><i class="fas fa-users"></i></div>
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
                            <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
                            <div class="user-details">
                                <h6 class="user-name"><?php echo htmlspecialchars($userName); ?></h6>
                                <small class="user-role">Business Owner</small>
                                <small class="user-company"><?php echo htmlspecialchars($companyName); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- Mini Sidebar -->
            <nav id="mini-sidebar" class="mini-sidebar">
                <div class="mini-sidebar-sticky">
                    <a class="mini-nav-link" href="owner-dashboard.php" title="Dashboard"><i class="fas fa-tachometer-alt"></i></a>
                    <a class="mini-nav-link" href="owner-outlet.php" title="Outlet"><i class="fas fa-store"></i></a>
                    <a class="mini-nav-link" href="owner-staff.php" title="Staff"><i class="fas fa-users"></i></a>
                    <a class="mini-nav-link active" href="manage-stock.php" title="Stock"><i class="fas fa-boxes"></i></a>
                    <!-- Operating Expenses Menu Item -->
<a class="mini-nav-link" href="owner-operating-expenses.php" title="Operating Expenses">
    <i class="fas fa-money-bill-wave"></i>
</a>
                    <a class="mini-nav-link" href="owner-closing-report.php" title="Closing Sales"><i class="fas fa-file-invoice-dollar"></i></a>
                    <button id="sidebarExpand" class="expand-btn" title="Expand Menu"><i class="fas fa-chevron-right"></i></button>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="main-content">
                <header class="topbar">
                    <div class="topbar-container">
                        <button id="sidebarToggle" class="btn toggle-btn"><i class="fas fa-bars"></i></button>
                        <div class="page-title"><h1>Manage Stock</h1>
                         <p class="page-subtitle">Manage your stock – Add, edit, or remove categories and items</p></div>
                        <div class="user-actions">
                            <div class="welcome-message">
                                <span class="greeting">Hi, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                                <small class="company-name"><?php echo htmlspecialchars($companyName); ?></small>
                            </div>
                            <div class="dropdown user-dropdown">
                                <button class="btn user-btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                    <div class="user-info">
                                        <div class="user-avatar-sm"><i class="fas fa-user-circle"></i></div>
                                        <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                                    </div>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
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
                <div class="content-container">
                    <div class="container my-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-store me-2"></i>Select Outlet</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" id="outletForm">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-6">
                                            <label class="form-label">Choose Outlet</label>
                                            <select class="form-select" name="outlet_id" onchange="this.form.submit()">
                                                <option value="" disabled selected>-- Select Outlet --</option>
                                                <?php
                                                $outletsStmt->execute();
                                                $outletsResult = $outletsStmt->get_result();
                                                while($outlet = $outletsResult->fetch_assoc()): ?>
                                                    <option value="<?php echo $outlet['outlet_id']; ?>" <?php echo $selectedOutletId == $outlet['outlet_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if($selectedOutletId): ?>
                                                <?php
                                                $outletsStmt->execute();
                                                $outletsResult = $outletsStmt->get_result();
                                                $currentOutletName = '';
                                                while($outlet = $outletsResult->fetch_assoc()) {
                                                    if($outlet['outlet_id'] == $selectedOutletId) {
                                                        $currentOutletName = $outlet['outlet_name'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-check-circle me-2 text-success"></i>
                                                    <strong>Currently managing:</strong> <?php echo htmlspecialchars($currentOutletName); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>Please select an outlet
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Content based on outlet selection -->
                        <?php if($selectedOutletId): ?>
                            <?php
                            $categoriesQuery = "SELECT * FROM stock_category WHERE outlet_id = ? ORDER BY category_name";
                            $categoriesStmt = $conn->prepare($categoriesQuery);
                            $categoriesStmt->bind_param("i", $selectedOutletId);
                            $categoriesStmt->execute();
                            $categories = $categoriesStmt->get_result();
                            if ($categories->num_rows == 0): ?>
                                <div class="card text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">No Categories Found</h4>
                                    <p class="text-muted">This outlet doesn't have any stock categories yet.</p>
                                    
                                </div>
                            <?php else: ?>
                                <?php while($category = $categories->fetch_assoc()): ?>
                                    <div class="category-section mb-4" data-category-id="<?php echo $category['category_id']; ?>">
                                        <div class="category-header d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-folder me-3 text-primary fa-lg"></i>
                                                <div>
                                                    <h4 class="mb-1"><?php echo htmlspecialchars($category['category_name']); ?></h4>
                                                    <small class="text-muted">Category</small>
                                                </div>
                                            </div>
                                            <div class="category-actions">
                                                <button class="btn btn-outline-primary btn-sm me-2 edit-category-btn"
                                                        data-category-id="<?php echo $category['category_id']; ?>"
                                                        data-category-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm me-2 delete-category-btn"
                                                        data-category-id="<?php echo $category['category_id']; ?>"
                                                        data-category-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                                <button class="btn btn-primary btn-sm add-item-btn"
                                                        data-category-id="<?php echo $category['category_id']; ?>"
                                                        data-category-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                                                    <i class="fas fa-plus me-1"></i>Add Item
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <?php
                                            $itemsQuery = "SELECT * FROM stock_items WHERE category_id = ? ORDER BY item_name";
                                            $itemsStmt = $conn->prepare($itemsQuery);
                                            $itemsStmt->bind_param("i", $category['category_id']);
                                            $itemsStmt->execute();
                                            $items = $itemsStmt->get_result();
                                            if($items->num_rows == 0): ?>
                                                <div class="col-12">
                                                    <div class="text-center py-4 text-muted">
                                                        <i class="fas fa-box-open fa-2x mb-2"></i>
                                                        <p>No items in this category yet.</p>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <?php while($item = $items->fetch_assoc()): ?>
                                                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                                        <div class="card stock-item-card h-100">
                                                            <div class="card-actions">
                                                                <button class="btn btn-sm btn-danger delete-item-btn"
                                                                        data-item-id="<?php echo $item['item_id']; ?>"
                                                                        data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-primary edit-item-btn"
                                                                        data-item-id="<?php echo $item['item_id']; ?>"
                                                                        data-category-id="<?php echo $item['category_id']; ?>"
                                                                        data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                                        data-base-price="<?php echo $item['base_price']; ?>"
                                                                        data-remaining-quantity="<?php echo $item['remaining_quantity']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            </div>
                                                            <div class="card-body">
                                                                <h5 class="card-title">
                                                                    <i class="fas fa-cube me-2 text-primary"></i>
                                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                                </h5>
                                                                <div class="item-details">
                                                                    <p class="mb-1"><i class="fas fa-tag me-2 text-muted"></i>Price per Unit: RM <?php echo number_format($item['base_price'], 2); ?></p>
                                                                    <p class="mb-1"><i class="fas fa-boxes me-2 text-muted"></i>Remaining Quantity: <?php echo number_format($item['remaining_quantity']); ?></p>
                                                                    <p class="mb-0"><i class="fas fa-chart-line me-2 text-muted"></i>Total Stock Value: RM <?php echo number_format($item['base_price'] * $item['remaining_quantity'], 2); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="card text-center py-5">
                                <i class="fas fa-warehouse fa-4x text-primary mb-3"></i>
                                <h3>Welcome to Stock Management</h3>
                                <p class="text-muted">Select an outlet from the dropdown above to get started.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Floating Add Category Button -->
                    <?php if($selectedOutletId): ?>
                        <button class="btn btn-primary floating-btn floating-btn-category" id="floatingAddCategory">
                            <i class="fas fa-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <!-- Modals Section -->
    <div id="modals-container">
        <!-- Add/Edit Category Modal -->
        <div class="modal fade" id="categoryModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-xxl">
                <div class="modal-content modal-dialog-scrollable">
                    <form id="categoryForm" autocomplete="off">
                        <div class="modal-header modal-header-centered modal-title-white">
                            <div class="w-100 text-center">
                                <span class="modal-title" id="categoryModalTitle" style="color:#fff; font-weight:600; display:inline-flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-folder-plus me-2"></i><span id="categoryModalTitleText">Add Category</span>
                                </span>
                            </div>
                        </div>
                        <div class="modal-body modal-body-scroll">
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                    <div class="form-floating position-relative flex-grow-1">
                                        <input type="text" class="form-control" id="categoryName" name="category_name" placeholder="Category Name" autocomplete="off">
                                        <label for="categoryName">Category Name</label>
                                        <span class="validation-icon-inside" id="categoryNameIcon"></span>
                                    </div>
                                </div>
                                <div class="error-message" id="categoryNameError"></div>
                            </div>
                            <input type="hidden" id="categoryId" name="category_id">
                            <input type="hidden" id="categoryAction" name="action">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary shadow-sm" id="categorySubmitBtn" disabled>
                                <i class="fas fa-save me-1"></i><span id="categorySubmitText">Add Category</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Add/Edit Item Modal -->
        <div class="modal fade" id="itemModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-xxl">
                <div class="modal-content modal-dialog-scrollable">
                    <form id="itemForm" autocomplete="off">
                        <div class="modal-header modal-header-centered modal-title-white">
                            <div class="w-100 text-center">
                                <span class="modal-title" id="itemModalTitle" style="color:#fff; font-weight:600; display:inline-flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-cube me-2"></i><span id="itemModalTitleText">Add Item</span>
                                </span>
                            </div>
                        </div>
                        <div class="modal-body modal-body-scroll">
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                    <div class="form-floating position-relative flex-grow-1">
                                        <input type="text" class="form-control" id="itemCategoryName" readonly>
                                        <label for="itemCategoryName">Category</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-cube"></i></span>
                                    <div class="form-floating position-relative flex-grow-1">
                                        <input type="text" class="form-control" id="itemName" name="item_name" placeholder="Item Name" autocomplete="off">
                                        <label for="itemName">Item Name</label>
                                        <span class="validation-icon-inside" id="itemNameIcon"></span>
                                    </div>
                                </div>
                                <div class="error-message" id="itemNameError"></div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <div class="form-floating position-relative flex-grow-1">
                                        <input type="text" class="form-control" id="itemBasePrice" name="base_price" placeholder="Base Price" autocomplete="off">
                                        <label for="itemBasePrice">Price per Unit (RM)</label>
                                        <span class="validation-icon-inside" id="itemBasePriceIcon"></span>
                                    </div>
                                </div>
                                <div class="error-message" id="itemBasePriceError"></div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                                    <div class="form-floating position-relative flex-grow-1">
                                        <input type="text" class="form-control" id="itemQuantity" name="remaining_quantity" placeholder="Quantity" autocomplete="off">
                                        <label for="itemQuantity">Remaining Quantity (Optional)</label>
                                        <span class="validation-icon-inside" id="itemQuantityIcon"></span>
                                    </div>
                                </div>
                                <div class="error-message" id="itemQuantityError"></div>
                            </div>
                            <input type="hidden" id="itemCategoryId" name="category_id">
                            <input type="hidden" id="itemId" name="item_id">
                            <input type="hidden" id="itemAction" name="action">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary shadow-sm" id="itemSubmitBtn" disabled>
                                <i class="fas fa-save me-1"></i><span id="itemSubmitText">Add Item</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Confirm Delete Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-xxl">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="modal-icon text-warning mb-3">
                            <i class="fas fa-exclamation-triangle fa-4x" style="color:#ffc107;"></i>
                        </div>
                        <h4 class="modal-title mb-2" id="deleteModalTitle">Confirm Delete</h4>
                        <p class="modal-message mb-3" id="deleteModalMsg"></p>
                        <input type="hidden" id="deleteTargetId">
                        <input type="hidden" id="deleteTargetType">
                        <div class="modal-actions justify-content-center">
                            <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-danger shadow-sm" id="confirmDeleteBtn">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Success/Error Notification Modal -->
        <div class="modal fade" id="notificationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-xxl">
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
    </div>

          <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="fas fa-sign-out-alt fa-3x text-primary mb-3"></i>
                    <h4>Confirm Logout</h4>
                    <p>Are you sure you want to logout?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="window.location.href='logout.php'">Logout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JAVASCRIPT/owner-dashboard.js"></script>
    <script>
    // --- JS for Category/Item Modal and Delete Modal (owner-staff style) ---
    document.addEventListener('DOMContentLoaded', function() {
        // Modals
        const categoryModalEl = document.getElementById('categoryModal');
        const categoryModal = new bootstrap.Modal(categoryModalEl);
        const itemModalEl = document.getElementById('itemModal');
        const itemModal = new bootstrap.Modal(itemModalEl);
        const deleteModalEl = document.getElementById('deleteModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        const notificationModalEl = document.getElementById('notificationModal');
        const notificationModal = new bootstrap.Modal(notificationModalEl);
        
        // --- Add Category Button (floating) ---
        document.getElementById('floatingAddCategory').addEventListener('click', function(e) {
            e.preventDefault();
            showCategoryModal('add');
        });
        
        // --- Show Category Modal ---
        function showCategoryModal(mode, catId = '', catName = '') {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = catId;
            document.getElementById('categoryAction').value = mode;
            document.getElementById('categoryModalTitleText').textContent = mode === 'add' ? "Add Category" : "Edit Category";
            document.getElementById('categorySubmitText').textContent = mode === 'add' ? "Add Category" : "Update Category";
            document.getElementById('categoryName').value = catName;
            document.getElementById('categoryNameIcon').innerHTML = '';
            document.getElementById('categoryNameError').textContent = '';
            document.getElementById('categorySubmitBtn').disabled = true;
            categoryModal.show();
            
            // Trigger validation for edit mode
            if (mode === 'edit') {
                validateCategoryName();
            }
        }
        
        // --- Edit Category ---
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-category-btn')) {
                const btn = e.target.closest('.edit-category-btn');
                showCategoryModal('edit', btn.getAttribute('data-category-id'), btn.getAttribute('data-category-name'));
            }
        });
        
        // --- Add Item ---
        function showItemModal(mode, catId, catName, item = null) {
            document.getElementById('itemForm').reset();
            document.getElementById('itemCategoryId').value = catId;
            document.getElementById('itemCategoryName').value = catName;
            document.getElementById('itemId').value = item ? item.item_id : '';
            document.getElementById('itemAction').value = mode;
            document.getElementById('itemModalTitleText').textContent = mode === 'add' ? "Add Item" : "Edit Item";
            document.getElementById('itemSubmitText').textContent = mode === 'add' ? "Add Item" : "Update Item";
            document.getElementById('itemName').value = item ? item.item_name : '';
            document.getElementById('itemBasePrice').value = item ? item.base_price : '';
            document.getElementById('itemQuantity').value = item ? item.remaining_quantity : '';
            document.getElementById('itemNameIcon').innerHTML = '';
            document.getElementById('itemBasePriceIcon').innerHTML = '';
            document.getElementById('itemQuantityIcon').innerHTML = '';
            document.getElementById('itemNameError').textContent = '';
            document.getElementById('itemBasePriceError').textContent = '';
            document.getElementById('itemQuantityError').textContent = '';
            document.getElementById('itemSubmitBtn').disabled = true;
            itemModal.show();
            
            // Trigger validation for edit mode
            if (mode === 'edit') {
                validateItemForm();
            }
        }
        
        // --- Add Item Button ---
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-item-btn')) {
                const btn = e.target.closest('.add-item-btn');
                showItemModal('add', btn.getAttribute('data-category-id'), btn.getAttribute('data-category-name'));
            }
        });
        
        // --- Edit Item ---
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-item-btn')) {
                const btn = e.target.closest('.edit-item-btn');
                showItemModal('edit', btn.getAttribute('data-category-id'), document.querySelector(`.category-section[data-category-id="${btn.getAttribute('data-category-id')}"] h4`).textContent.trim(), {
                    item_id: btn.getAttribute('data-item-id'),
                    item_name: btn.getAttribute('data-item-name'),
                    base_price: btn.getAttribute('data-base-price'),
                    remaining_quantity: btn.getAttribute('data-remaining-quantity')
                });
            }
        });
        
        // --- Delete Category/Item Button ---
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-item-btn')) {
                const btn = e.target.closest('.delete-item-btn');
                document.getElementById('deleteTargetId').value = btn.getAttribute('data-item-id');
                document.getElementById('deleteTargetType').value = 'item';
                document.getElementById('deleteModalTitle').textContent = "Confirm Delete";
                document.getElementById('deleteModalMsg').textContent = `Are you sure you want to delete the item "${btn.getAttribute('data-item-name')}"?`;
                deleteModal.show();
            }
            if (e.target.closest('.delete-category-btn')) {
                const btn = e.target.closest('.delete-category-btn');
                document.getElementById('deleteTargetId').value = btn.getAttribute('data-category-id');
                document.getElementById('deleteTargetType').value = 'category';
                document.getElementById('deleteModalTitle').textContent = "Confirm Delete";
                document.getElementById('deleteModalMsg').textContent = `Are you sure you want to delete the category "${btn.getAttribute('data-category-name')}"? This will delete all items in this category.`;
                deleteModal.show();
            }
        });
        
        // --- Confirm Delete ---
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const id = document.getElementById('deleteTargetId').value;
            const type = document.getElementById('deleteTargetType').value;
            if (type === 'item') {
                fetch('manage-stock.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ajax_delete_item=1&item_id=' + id
                })
                .then(r=>r.json()).then(data=>{
                    deleteModal.hide();
                    showNotification(data.success ? 'success':'error', data.success?'Success':'Error', data.message, 'OK', ()=>window.location.reload());
                });
            } else if (type === 'category') {
                fetch('manage-stock.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'ajax_delete_category=1&category_id=' + id
                })
                .then(r=>r.json()).then(data=>{
                    deleteModal.hide();
                    showNotification(data.success ? 'success':'error', data.success?'Success':'Error', data.message, 'OK', ()=>window.location.reload());
                });
            }
        });
        
        // --- Real-time Validation: Category Modal ---
        function validateCategoryName() {
            const value = document.getElementById('categoryName').value.trim();
            if (value.length > 0) {
                document.getElementById('categoryNameIcon').innerHTML = '<i class="fas fa-check-circle" style="color:#28a745;"></i>';
                document.getElementById('categoryNameError').textContent = '';
                document.getElementById('categorySubmitBtn').disabled = false;
            } else {
                document.getElementById('categoryNameIcon').innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i>';
                document.getElementById('categoryNameError').textContent = 'Category name is required.';
                document.getElementById('categorySubmitBtn').disabled = true;
            }
        }
        
        document.getElementById('categoryName').addEventListener('input', validateCategoryName);
        
        // --- Real-time Validation: Item Modal ---
        function validateItemForm() {
            let valid = true;
            
            // Item Name
            const nameVal = document.getElementById('itemName').value.trim();
            if (nameVal.length>0) {
                document.getElementById('itemNameIcon').innerHTML = '<i class="fas fa-check-circle" style="color:#28a745;"></i>';
                document.getElementById('itemNameError').textContent = '';
            } else {
                document.getElementById('itemNameIcon').innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i>';
                document.getElementById('itemNameError').textContent = 'Item name is required.';
                valid = false;
            }
            
            // Base Price
            const priceVal = document.getElementById('itemBasePrice').value;
            const priceRegex = /^\d*\.?\d*$/;
            if (priceVal !== '' && priceRegex.test(priceVal) && parseFloat(priceVal) >= 0) {
                document.getElementById('itemBasePriceIcon').innerHTML = '<i class="fas fa-check-circle" style="color:#28a745;"></i>';
                document.getElementById('itemBasePriceError').textContent = '';
            } else {
                document.getElementById('itemBasePriceIcon').innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i>';
                document.getElementById('itemBasePriceError').textContent = 'Base price must be 0 or above.';
                valid = false;
            }
            
           // Quantity (optional; if entered, must be an integer — negative allowed)
const qtyEl   = document.getElementById('itemQuantity');
const qtyIcon = document.getElementById('itemQuantityIcon');
const qtyErr  = document.getElementById('itemQuantityError');

const qtyVal   = qtyEl.value.trim();
const qtyRegex = /^-?\d+$/;  // allows: -5, 0, 12

if (qtyVal === '' || qtyRegex.test(qtyVal)) {
  qtyIcon.innerHTML = qtyVal === '' ? '' : '<i class="fas fa-check-circle" style="color:#28a745;"></i>';
  qtyErr.textContent = '';
} else {
  qtyIcon.innerHTML = '<i class="fas fa-times-circle" style="color:#dc3545;"></i>';
  qtyErr.textContent = 'Quantity must be an integer (e.g., -5, 0, 12).';
  valid = false;
}
            
            document.getElementById('itemSubmitBtn').disabled = !valid;
        }
        
        // Input restrictions for price field (only numbers and decimal point)
        document.getElementById('itemBasePrice').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9.]/g, '');
            // Ensure only one decimal point
            if ((this.value.match(/\./g) || []).length > 1) {
                this.value = this.value.substring(0, this.value.lastIndexOf('.'));
            }
            validateItemForm();
        });
        
        // Input restrictions for quantity field (allow negative numbers)
document.getElementById('itemQuantity').addEventListener('input', function () {
  this.value = this.value
    .replace(/[^\d-]/g, '')   // keep only digits and '-'
    .replace(/(?!^)-/g, '');  // drop any '-' that's not the first char
  validateItemForm();
});
        
        document.getElementById('itemName').addEventListener('input', validateItemForm);
        
        // --- Submit Category Modal (AJAX) ---
        document.getElementById('categoryForm').addEventListener('submit', function(e){
            e.preventDefault();
            const mode = document.getElementById('categoryAction').value;
            const data = new URLSearchParams();
            if (mode==='add') {
                data.append('add_category',1);
                data.append('category_name', document.getElementById('categoryName').value.trim());
                data.append('outlet_id', '<?php echo $selectedOutletId; ?>');
            } else {
                data.append('edit_category',1);
                data.append('category_id',document.getElementById('categoryId').value);
                data.append('category_name',document.getElementById('categoryName').value.trim());
            }
            fetch('manage-stock.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:data.toString()
            }).then(r=>r.json()).then(data=>{
                categoryModal.hide();
                showNotification(data.success ? 'success':'error', data.success?'Success':'Error', data.message, 'OK', ()=>window.location.reload());
            });
        });
        
        // --- Submit Item Modal (AJAX) ---
        document.getElementById('itemForm').addEventListener('submit', function(e){
            e.preventDefault();
            const mode = document.getElementById('itemAction').value;
            const data = new URLSearchParams();
            if (mode==='add') {
                data.append('add_item',1);
                data.append('category_id', document.getElementById('itemCategoryId').value);
                data.append('item_name', document.getElementById('itemName').value.trim());
                data.append('base_price', document.getElementById('itemBasePrice').value || '0');
                data.append('remaining_quantity', document.getElementById('itemQuantity').value || '0');
            } else {
                data.append('edit_item',1);
                data.append('item_id', document.getElementById('itemId').value);
                data.append('category_id', document.getElementById('itemCategoryId').value);
                data.append('item_name', document.getElementById('itemName').value.trim());
                data.append('base_price', document.getElementById('itemBasePrice').value || '0');
                data.append('remaining_quantity', document.getElementById('itemQuantity').value || '0');
            }
            fetch('manage-stock.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:data.toString()
            }).then(r=>r.json()).then(data=>{
                itemModal.hide();
                showNotification(data.success ? 'success':'error', data.success?'Success':'Error', data.message, 'OK', ()=>window.location.reload());
            });
        });
        
        // --- Notification Modal Function (owner-staff style) ---
        function showNotification(type, title, message, buttonText, callback) {
            var notificationIcon = document.getElementById('notificationIcon');
            var notificationTitle = document.getElementById('notificationTitle');
            var notificationMessage = document.getElementById('notificationMessage');
            var notificationButton = document.getElementById('notificationButton');
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
        
      
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>