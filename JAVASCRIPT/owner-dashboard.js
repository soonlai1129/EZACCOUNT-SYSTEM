/**
 * ezAccount System - Owner Dashboard JavaScript
 * 
 * Handles responsive sidebar functionality, collapse/expand features,
 * logout confirmation, and overall user interface interactions
 */

// Wait for DOM to be fully loaded before executing scripts
document.addEventListener('DOMContentLoaded', function () {
    /**
     * DOM Element References
     * Cache all frequently accessed elements for better performance
     */
    const sidebar = document.getElementById('sidebar');
    const miniSidebar = document.getElementById('mini-sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebarExpand = document.getElementById('sidebarExpand');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const logoutBtn = document.getElementById('logoutBtn');
    const confirmLogout = document.getElementById('confirmLogout');

    // Initialize Bootstrap modal instance for logout confirmation
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
        backdrop: 'static',
        keyboard: false
    });

    /**
     * Sidebar State Management
     * Tracks the current state of the sidebar
     */
    let sidebarState = {
        isCollapsed: false,
        isMobile: window.innerWidth < 768,
        isSidebarVisible: false
    };

    /**
     * Initialize the sidebar based on screen size
     */
    function initializeSidebar() {
        if (sidebarState.isMobile) {
            // Mobile view - show mini sidebar by default, hide main sidebar
            sidebar.classList.add('collapsed');
            miniSidebar.classList.add('expanded');
            sidebar.classList.remove('mobile-visible');
            hideOverlay();

            // Show toggle button
            if (sidebarToggle) sidebarToggle.style.display = 'block';

            // Reset content layout
            updateContentLayout();
        } else {
            // Desktop view - show full sidebar by default
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('mobile-visible');
            miniSidebar.classList.remove('expanded');
            hideOverlay();

            // Hide toggle button
            if (sidebarToggle) sidebarToggle.style.display = 'none';

            // Set appropriate content layout
            updateContentLayout();
        }
    }

    /**
     * Sidebar Collapse/Expand Functionality
     * Handles the transition between full sidebar and mini sidebar
     */
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function (e) {
            e.stopPropagation();
            collapseSidebar();
        });
    }

    if (sidebarExpand) {
        sidebarExpand.addEventListener('click', function (e) {
            e.stopPropagation();
            expandSidebar();
        });
    }

    /**
     * Collapse Sidebar Function
     */
    function collapseSidebar() {
        if (sidebarState.isMobile) {
            sidebar.classList.remove('mobile-visible');
            hideOverlay();
            sidebarState.isSidebarVisible = false;
        } else {
            sidebar.classList.add('collapsed');
            miniSidebar.classList.add('expanded');
            sidebarState.isCollapsed = true;
        }

        // Update content area
        updateContentLayout();
    }

    /**
     * Expand Sidebar Function
     */
    function expandSidebar() {
        if (sidebarState.isMobile) {
            sidebar.classList.add('mobile-visible');
            showOverlay();
            sidebarState.isSidebarVisible = true;
        } else {
            sidebar.classList.remove('collapsed');
            miniSidebar.classList.remove('expanded');
            sidebarState.isCollapsed = false;
        }

        // Update content area
        updateContentLayout();
    }

    /**
     * Update Content Layout Based on Sidebar State
     */
    function updateContentLayout() {
        const mainContent = document.querySelector('.main-content');

        if (sidebarState.isMobile) {
            // Mobile layout
            if (sidebarState.isSidebarVisible) {
                // When sidebar is visible on mobile, shift content to the right
                mainContent.style.transform = `translateX(${sidebar.offsetWidth}px)`;
                mainContent.style.marginLeft = '0';
                mainContent.style.width = '100%';
            } else {
                // When sidebar is hidden on mobile, content takes full width
                mainContent.style.transform = 'translateX(0)';
                mainContent.style.marginLeft = '0';
                mainContent.style.width = '100%';

                // If mini sidebar is expanded, adjust for it
                if (miniSidebar.classList.contains('expanded')) {
                    mainContent.style.marginLeft = `${miniSidebar.offsetWidth}px`;
                    mainContent.style.width = `calc(100% - ${miniSidebar.offsetWidth}px)`;
                }
            }
        } else {
            // Desktop layout
            if (sidebarState.isCollapsed) {
                mainContent.style.marginLeft = `${miniSidebar.offsetWidth}px`;
                mainContent.style.width = `calc(100% - ${miniSidebar.offsetWidth}px)`;
                mainContent.style.transform = 'translateX(0)';
            } else {
                mainContent.style.marginLeft = `${sidebar.offsetWidth}px`;
                mainContent.style.width = `calc(100% - ${sidebar.offsetWidth}px)`;
                mainContent.style.transform = 'translateX(0)';
            }
        }
    }

    /**
     * Mobile Sidebar Toggle Functionality
     * Handles showing/hiding the sidebar on mobile devices
     */
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleMobileSidebar();
        });
    }

    /**
     * Toggle Mobile Sidebar
     */
    function toggleMobileSidebar() {
        if (sidebarState.isSidebarVisible) {
            collapseSidebar();
        } else {
            expandSidebar();
        }
    }
    /**
     * Create Overlay for Mobile Sidebar
     * Adds a semi-transparent overlay when sidebar is open on mobile
     */
    function showOverlay() {
        let overlay = document.getElementById('sidebarOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'sidebarOverlay';
            overlay.classList.add('visible');
            document.body.appendChild(overlay);

            overlay.addEventListener('click', function (e) {
                e.stopPropagation();
                collapseSidebar();
            });
        } else {
            overlay.classList.add('visible');
        }

        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide Overlay
     * Removes the overlay when sidebar is closed
     */
    function hideOverlay() {
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay) {
            overlay.classList.remove('visible');
        }
        document.body.style.overflow = '';
    }

    /**
     * Logout Confirmation Modal
     * Handles the logout process with user confirmation
     */
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            // Show logout confirmation modal
            logoutModal.show();
        });
    }

    // Handle logout confirmation
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function () {
            // Redirect to logout script
            window.location.href = 'logout.php';
        });
    }

    /**
     * Responsive Sidebar Handling
     * Manages sidebar behavior on different screen sizes
     */
    function handleResponsiveSidebar() {
        const wasMobile = sidebarState.isMobile;
        sidebarState.isMobile = window.innerWidth < 768;

        if (sidebarState.isMobile !== wasMobile) {
            // Screen size category changed
            initializeSidebar();
        } else {
            // Just a resize within the same category
            updateContentLayout();
        }
    }

    // Initial responsive setup
    initializeSidebar();

    // Update on window resize with debounce for performance
    let resizeTimeout;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            handleResponsiveSidebar();
        }, 250);
    });

    /**
     * Enhanced Menu Item Animations
     * Adds staggered animation to menu items for better visual appeal
     */
    function enhanceMenuAnimations() {
        const menuItems = document.querySelectorAll('.nav-link, .mini-nav-link');

        menuItems.forEach((item, index) => {
            // Add delay based on item position for staggered effect
            item.style.transitionDelay = `${0.1 + (index * 0.05)}s`;

            // Add hover effect with transformation
            item.addEventListener('mouseenter', function () {
                if (window.innerWidth > 768) { // Only on desktop
                    this.style.transform = this.classList.contains('nav-link') ?
                        'translateX(8px)' : 'scale(1.2)';
                    this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                }
            });

            item.addEventListener('mouseleave', function () {
                if (this.classList.contains('nav-link')) {
                    this.style.transform = this.classList.contains('active') ? 'translateX(5px)' : '';
                } else {
                    this.style.transform = this.classList.contains('active') ? 'scale(1.1)' : '';
                }
            });
        });
    }

    // Initialize menu animations
    enhanceMenuAnimations();

    /**
     * Active Menu Item Highlighting
     * Updates active state based on current page
     */
    function updateActiveMenu() {
        const currentPage = window.location.pathname.split('/').pop();
        const menuItems = document.querySelectorAll('.nav-link, .mini-nav-link');

        menuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Initialize active menu highlighting
    updateActiveMenu();

    /**
     * Keyboard Shortcuts
     * Enhance productivity with keyboard navigation
     */
    document.addEventListener('keydown', function (e) {
        // Escape key closes modals and sidebar
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            if (openModals.length > 0) {
                bootstrap.Modal.getInstance(openModals[0]).hide();
            }

            if (sidebarState.isMobile && sidebarState.isSidebarVisible) {
                collapseSidebar();
            }
        }

        // Alt+S toggles sidebar (accessibility feature)
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            if (sidebarState.isMobile) {
                toggleMobileSidebar();
            } else {
                if (sidebarState.isCollapsed) {
                    expandSidebar();
                } else {
                    collapseSidebar();
                }
            }
        }
    });

    /**
     * Initialize Animations on Page Load
     * Adds subtle entrance animations to page elements
     */
    function initializeAnimations() {
        // Add fade-in animation to main content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) mainContent.classList.add('fade-in');

        // Add staggered animation to sidebar items
        const sidebarItems = document.querySelectorAll('.nav-item');
        sidebarItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('slide-in-left');
        });

        // Animate topbar elements
        const topbarElements = document.querySelectorAll('.topbar > *');
        topbarElements.forEach((element, index) => {
            element.style.animationDelay = `${0.5 + (index * 0.1)}s`;
            element.classList.add('slide-in-right');
        });
    }

    // Initialize animations after short delay
    setTimeout(initializeAnimations, 100);

    /**
     * Touch Device Detection
     * Add specific styles for touch devices
     */
    function detectTouchDevice() {
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            document.body.classList.add('touch-device');

            // Increase tap target sizes for touch devices
            const interactiveElements = document.querySelectorAll('button, a, .nav-link, .dropdown-item');
            interactiveElements.forEach(el => {
                el.style.minHeight = '44px';
                el.style.minWidth = '44px';
                el.style.display = 'flex';
                el.style.alignItems = 'center';
                el.style.justifyContent = 'center';
            });
        } else {
            document.body.classList.add('non-touch-device');
        }
    }

    // Detect touch device
    detectTouchDevice();

    console.log('Dashboard initialized successfully');
});

/**
 * Global Notification Function
 * Consistent with login page for unified user experience
 */
function showNotification(type, title, message, buttonText) {
    // Implementation consistent with login page functionality
    console.log('Notification system would show:', { type, title, message, buttonText });
    // Actual implementation would mirror the login page's notification system
}

const stockDropdownEl = document.getElementById('stockDropdown');
const stockDropdown = new bootstrap.Dropdown(stockDropdownEl, {
    popperConfig: {
        modifiers: [
            {
                name: 'preventOverflow',
                options: {
                    altBoundary: true, // allow it to escape sidebar
                    tether: false,
                },
            },
        ],
    },
});

