/**
 * Gate Management System - Main JavaScript
 * University of Rwanda College of Education, Rukara Campus
 */

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Gate Management System JS loaded');
    initializeApp();
});

// Initialize Application
function initializeApp() {
    console.log('Initializing app...');
    setupSidebar();
    setupForms();
    setupTables();
    setupModals();
    setupNotifications();
    setupSearch();
    setupPagination();
}

// Sidebar Toggle Functionality
function setupSidebar() {
    console.log('Setting up sidebar...');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    console.log('Elements found:', { sidebarToggle, sidebar, mainContent });
    
    // Check if sidebar state is stored in localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (sidebar && mainContent) {
        // Apply initial state
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            // Update toggle button icon
            if (sidebarToggle) {
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            }
        }
    }

    if (sidebarToggle && sidebar && mainContent) {
        console.log('Adding click event to toggle button');
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Toggle button clicked!');
            toggleSidebar();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    if (sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                }
            }
        });
    }

    // Set active menu item based on current page
    setActiveMenuItem();
}

// Toggle Sidebar Function
function toggleSidebar() {
    console.log('toggleSidebar function called');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    
    console.log('Elements in toggleSidebar:', { sidebar, mainContent, sidebarToggle });
    
    if (sidebar && mainContent) {
        const isCollapsed = sidebar.classList.contains('collapsed');
        console.log('Sidebar collapsed state:', isCollapsed);
        
        if (isCollapsed) {
            // Expand sidebar
            console.log('Expanding sidebar...');
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            localStorage.setItem('sidebarCollapsed', 'false');
            
            // Update toggle button icon
            if (sidebarToggle) {
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        } else {
            // Collapse sidebar
            console.log('Collapsing sidebar...');
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            localStorage.setItem('sidebarCollapsed', 'true');
            
            // Update toggle button icon
            if (sidebarToggle) {
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            }
        }
    } else {
        console.error('Sidebar or main content not found!');
    }
}

// Set Active Menu Item
function setActiveMenuItem() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        }
    });
}

// Form Handling
function setupForms() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                showNotification('Please correct the errors in the form.', 'error');
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(input);
            });
        });
    });
}

// Form Validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Field Validation
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';

    // Remove existing error styling
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required.';
    }

    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        }
    }

    // Registration number and ID number validation
    if ((fieldName === 'registration_number' || fieldName === 'id_number') && value) {
        if (!/^\d{1,15}$/.test(value)) {
            isValid = false;
            errorMessage = 'Must be numeric and less than 16 digits.';
        }
    }
    // Phone validation (override previous if present)
    if ((fieldName === 'phone' || fieldName === 'emergency_phone' || fieldName === 'telephone') && value) {
        if (!/^\d{10,}$/.test(value)) {
            isValid = false;
            errorMessage = 'Must be numeric and at least 10 digits.';
        }
    }

    // Serial number validation
    if (fieldName === 'serial_number' && value) {
        if (value.length < 5) {
            isValid = false;
            errorMessage = 'Serial number must be at least 5 characters.';
        }
    }

    // Show error if validation fails
    if (!isValid) {
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = errorMessage;
        field.parentNode.appendChild(errorDiv);
    }

    return isValid;
}

// Table Functionality
function setupTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // Add row hover effects
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(52, 152, 219, 0.05)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Setup action buttons
        setupTableActions(table);
    });
}

// Table Actions
function setupTableActions(table) {
    const actionButtons = table.querySelectorAll('.btn-action');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const id = this.dataset.id;
            
            if (action === 'delete') {
                if (confirm('Are you sure you want to delete this item?')) {
                    deleteItem(id);
                }
            } else if (action === 'edit') {
                editItem(id);
            } else if (action === 'view') {
                viewItem(id);
            }
        });
    });
}

// Modal Functionality
function setupModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const modalCloses = document.querySelectorAll('.modal-close, .modal-overlay');

    // Open modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Close modal
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            if (activeModal) {
                activeModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
}

// Notification System
function setupNotifications() {
    // Auto-hide notifications after 5 seconds
    const notifications = document.querySelectorAll('.alert');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    });
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    const container = document.querySelector('.content-wrapper') || document.body;
    container.insertBefore(notification, container.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Search Functionality
function setupSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.card').querySelector('.table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    });
}

// Pagination
function setupPagination() {
    const paginationLinks = document.querySelectorAll('.pagination a');
    
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.dataset.page;
            if (page) {
                loadPage(page);
            }
        });
    });
}

// Load Page (AJAX)
function loadPage(page) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('page', page);
    
    // Show loading spinner
    showLoading();
    
    fetch(currentUrl.toString())
        .then(response => response.text())
        .then(html => {
            // Update content (you'll need to implement this based on your structure)
            hideLoading();
        })
        .catch(error => {
            hideLoading();
            showNotification('Error loading page: ' + error.message, 'error');
        });
}

// Loading Functions
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// CRUD Operations
function deleteItem(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        showLoading();
        
        fetch(`/api/delete.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification('Item deleted successfully.', 'success');
                // Reload the page or remove the row
                location.reload();
            } else {
                showNotification(data.message || 'Error deleting item.', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('Error deleting item: ' + error.message, 'error');
        });
    }
}

function editItem(id) {
    // Redirect to edit page or open modal
    window.location.href = `/edit.php?id=${id}`;
}

function viewItem(id) {
    // Redirect to view page or open modal
    window.location.href = `/view.php?id=${id}`;
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'RWF'
    }).format(amount);
}

// Export functions for global use
window.GateManagement = {
    showNotification,
    showLoading,
    hideLoading,
    formatDate,
    formatCurrency,
    deleteItem,
    editItem,
    viewItem,
    toggleSidebar
};

// Make toggleSidebar globally accessible
window.toggleSidebar = toggleSidebar; 