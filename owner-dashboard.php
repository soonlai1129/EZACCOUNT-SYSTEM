<?php
/**
 * ezAccount System - Owner Dashboard
 * 
 * Main dashboard interface for business owners with responsive sidebar navigation
 * and session-based authentication.
 */

// Start session to access user data
session_start();
// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

// Redirect to login if user is not authenticated as business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

// Get user data from session for personalized display
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$companyName = $_SESSION['company_name'];

$owner_id = (int)$_SESSION['user_id'];

// ==== Database connection ====
$host = "localhost";
$user = "root";      // <-- change to your DB user
$pass = "";          // <-- change to your DB password
$db   = "ezaccount";

$conn = new mysqli($host, $user, $pass, $db);
$outlets = [];
if (!$conn->connect_error) {
  $q = $conn->prepare("SELECT outlet_id, outlet_name FROM outlets WHERE owner_id = ? ORDER BY outlet_name");
  $q->bind_param('i', $owner_id);
  $q->execute();
  $r = $q->get_result();
  while ($row = $r->fetch_assoc()) $outlets[] = $row;
  $q->close();
  $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ezAccount System - Owner Dashboard</title>

    <link rel="icon" type="image/png" sizes="32x32" href="LOGO/ezaccount_favicon_192.png">
    
    <!-- Bootstrap 5 CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">   
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard Styles -->
    <link rel="stylesheet" href="CSS/owner-dashboard.css">

    <!-- Graph Section -->
<link rel="stylesheet" href="CSS/graph.css">

<style>
/* If your global variables exist, these are just fallbacks */
:root {
  --content-padding: 16px;
  --content-bg: #f8fafc;
  --card-radius: 14px;
  --primary-color: #4361ee;
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
                            <a class="nav-link active" href="owner-dashboard.php">
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
                            <h1>Dashboard Overview</h1>
                            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($userName); ?>! Here's your business overview.</p>
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
                <div class="content-container">
                   
                   <div class="graph-wrapper">
    <h2 class="graph-title">Net Earnings Trend</h2>

    <div class="graph-controls">
        <label for="granularity">View by:</label>
        <select id="granularity">
            <option value="day">Daily</option>
            <option value="week">Weekly</option>
            <option value="month" selected>Monthly</option>
            <option value="quarter">Quarterly</option>  
            <option value="year">Yearly</option>
        </select>
        <label for="outletFilter">Outlet:</label>
        <select id="outletFilter">
  <option value="">All outlets</option>
  <?php foreach ($outlets as $o): ?>
    <option value="<?= (int)$o['outlet_id'] ?>"><?= htmlspecialchars($o['outlet_name']) ?></option>
  <?php endforeach; ?>
</select>


    </div>

    <div class="chart-container">
        <canvas id="earningsChart"></canvas>
    </div>
</div>

<!-- ==== Total Stock Value by Outlet (matches Net Earnings styling) ==== -->
<div class="graph-wrapper" id="stockvalue-wrapper">
  <h2 class="graph-title">Total Stock Value by Outlet</h2>

  <!-- ✅ Add legend note here -->
  <div class="graph-legend-note mb-2" style="text-align:left; ">
    <span style="color:#10B981; font-weight:600;">● Green</span> = Available stock value &nbsp;&nbsp;
    <span style="color:#EF4444; font-weight:600;">● Red</span> = Shortage of stock
  </div>

  <div class="graph-controls">
    
    <label for="svSort">Sort:</label>
    <select id="svSort">
      <option value="desc" selected>High → Low</option>
      <option value="asc">Low → High</option>
    </select>
    
     

    <!-- pager (aligned to the right within your existing controls row) -->
  <span></span>
  <button id="svPrev" class="btn btn-light" type="button">◀</button>
  <span id="svCounter" class="text-muted" style="min-width:90px; text-align:center;">0–0 of 0</span>
  <button id="svNext" class="btn btn-light" type="button">▶</button>

  </div>

  <div class="chart-container">
    <canvas id="stockValueChart"></canvas>
  </div>
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

    <!-- Chart.js and graph logic -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="JAVASCRIPT/graph.js"></script>

<script>
(() => {
  const canvas = document.getElementById('stockValueChart');
  if (!canvas) return;
  if (typeof Chart === 'undefined') { console.error('[StockValue] Chart.js not loaded.'); return; }

  // Controls
  const sortSel   = document.getElementById('svSort');
  const prevBtn   = document.getElementById('svPrev');
  const nextBtn   = document.getElementById('svNext');
  const counterEl = document.getElementById('svCounter');

  // State
  let chart;
  let ALL_LABELS = [], ALL_VALUES = [], ALL_PERCENTS = [];
  const PAGE_SIZE = (window.innerWidth <= 576) ? 4 : 7; // phone shows 4, larger screens 7
  let start = 0; // current page start index

  const rm = n => 'RM' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 });

  function renderPage() {
   if (!ALL_LABELS.length) {
    // If no outlets, still render empty chart frame
    if (chart) chart.destroy();
    chart = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: [''],   // no x label
        datasets: [{
          label: 'Total Stock Value (RM)',
          data: [0],
          backgroundColor: 'rgba(0,0,0,0)' // invisible bar
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { display: false } // hide x-axis ticks since no data
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { callback: val => 'RM' + val }
          }
        }
      }
    });

    if (counterEl) counterEl.textContent = '0 of 0'; // pager update
    return;
  }

    // Clamp start
    if (start < 0) start = 0;
    if (start >= ALL_LABELS.length) start = Math.max(0, ALL_LABELS.length - PAGE_SIZE);

    const end = Math.min(start + PAGE_SIZE, ALL_LABELS.length);
    const labels   = ALL_LABELS.slice(start, end);
    const values   = ALL_VALUES.slice(start, end);
    const percents = ALL_PERCENTS.slice(start, end);
    const colors   = values.map(v => v >= 0 ? '#10B981' : '#EF4444'); // match your green/red

    if (chart) chart.destroy();
    chart = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Total Stock Value (RM)',
          data: values,
          backgroundColor: colors,
          borderWidth: 0,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,           // keep normal responsive look of your card
        maintainAspectRatio: false, // height controlled by your .chart-container
        animation: { duration: 250 },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: items => (items && items.length ? items[0].label : ''),
              label: ctx => {
                const i = ctx.dataIndex;
                const v = ctx.parsed.y;
                const p = (typeof percents[i] !== 'undefined') ? ` (${percents[i]}%)` : '';
                return `${rm(v)}${p}`;
              }
            }
          }
        },
        scales: {
  x: {
    grid: { display: false },
    ticks: {
      autoSkip: false,
      maxRotation: 48,   // rotate labels up to 48 degrees
      minRotation: 28,   // minimum rotation (slanted)
      align: 'end'       // align labels so they don’t clip
    }
  },
  y: {
    beginAtZero: true,
    grid: { color: 'rgba(0,0,0,0.06)' },
    ticks: { callback: val => rm(val) }
  }
}
      }
    });

    // Update pager UI
    if (counterEl) counterEl.textContent = `${start + 1}–${end} of ${ALL_LABELS.length}`;
    if (prevBtn) prevBtn.disabled = (start === 0);
    if (nextBtn) nextBtn.disabled = (end >= ALL_LABELS.length);
  }

  async function load(sort = 'desc') {
    try {
      const res = await fetch(`stock_value_aggregate.php?sort=${sort}`, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (json.error) throw new Error(json.error);

      ALL_LABELS  = json.labels   || [];
      ALL_VALUES  = json.values   || [];
      ALL_PERCENTS= json.percents || [];
      start = 0;                   // reset to first page on new load
      renderPage();
    } catch (e) {
      console.error('[StockValue] load error:', e);
    }
  }

  // Wire up controls
  sortSel?.addEventListener('change', () => load(sortSel.value));
  prevBtn?.addEventListener('click', () => { start = Math.max(0, start - PAGE_SIZE); renderPage(); });
  nextBtn?.addEventListener('click', () => { start = Math.min(ALL_LABELS.length - 1, start + PAGE_SIZE); renderPage(); });

  // Initial
  load(sortSel?.value || 'desc');
})();
</script>





    <!-- Bootstrap JavaScript with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <!-- Custom Dashboard JavaScript -->
    <script src="JAVASCRIPT/owner-dashboard.js"></script>

    

</body>
</html>