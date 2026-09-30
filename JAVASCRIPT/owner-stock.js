/**
 * Owner Stock Management JavaScript
 * Handles stock movement functionality and AJAX operations
 * Enhanced with real-time validation similar to owner-staff
 * FIXED: Validation issues, form clearing, and responsive problems
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize stock management functionality
    initStockManagement();
    
    // Initialize real-time validation
    initRealTimeValidation();
    
    // Initialize modal event handlers
    initModalHandlers();
    
    // Initialize logout handling without alerts
    initLogoutHandling();
});

/**
 * Initialize stock management functionality
 */
function initStockManagement() {
    console.log('Initializing stock management...');
    
    // Set default filter values
    setDefaultFilters();
    
    // Initialize tooltips
    initTooltips();
}

/**
 * Set default filter values to "all"
 */
function setDefaultFilters() {
    // Filters are set to "all" by default in PHP
    console.log('Default filters set: All outlets, all types, all dates');
}

/**
 * Initialize real-time validation
 */
function initRealTimeValidation() {
    // Add Movement Modal Validation
    initAddModalValidation();
    
    // Edit Movement Modal Validation  
    initEditModalValidation();
}

/**
 * Initialize add modal validation
 */
function initAddModalValidation() {
    const dateInput = document.getElementById('movementDate');
    const outletSelect = document.getElementById('movementOutlet');
    const itemSelect = document.getElementById('movementItem');
    const typeSelect = document.getElementById('movementType');
    const quantityInput = document.getElementById('movementQuantity');
    const submitBtn = document.getElementById('submitMovement');
    
    // Immediate validation for date field in add modal
    if (dateInput) {
        validateDate();
        dateInput.addEventListener('input', validateDate);
        dateInput.addEventListener('change', validateDate);
    }
    
    // Outlet change - load items and validate
    if (outletSelect) {
        outletSelect.addEventListener('change', function() {
            loadItemsForOutlet(this.value);
            validateOutlet();
            checkAddFormValidity();
        });
    }
    
    // Item change
    if (itemSelect) {
        itemSelect.addEventListener('change', function() {
            validateItem();
            checkAddFormValidity();
        });
    }
    
    // Type change
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            validateType();
            checkAddFormValidity();
        });
    }
    
    // Quantity input - simplified validation (only check for minimum 1)
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            validateQuantity();
            checkAddFormValidity();
        });
    }
    
    // Add modal show event - validate date immediately and reset form
    const addModal = document.getElementById('addMovementModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function() {
            // Reset form and set current date
            resetAddForm();
            
            // Trigger date validation immediately
            setTimeout(() => {
                validateDate();
                checkAddFormValidity();
            }, 100);
        });
        
        addModal.addEventListener('hidden.bs.modal', function() {
            resetAddFormValidation();
        });
    }
}

/**
 * Reset add form completely
 */
function resetAddForm() {
    const form = document.getElementById('movementForm');
    if (form) {
        form.reset();
    }
    
    // Set current date
    const dateInput = document.getElementById('movementDate');
    if (dateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.value = `${yyyy}-${mm}-${dd}`;
    }
    
    // Reset item select
    const itemSelect = document.getElementById('movementItem');
    if (itemSelect) {
        itemSelect.innerHTML = '<option value="" selected disabled>--- Select Item ---</option>';
        itemSelect.disabled = true;
    }
    
    // Reset validation states
    validDate = validOutlet = validItem = validType = validQuantity = false;
    
    // Reset validation UI
    const validationIcons = document.querySelectorAll('#addMovementModal .validation-icon-outside');
    const errorMessages = document.querySelectorAll('#addMovementModal .error-message');
    const inputs = document.querySelectorAll('#addMovementModal .form-control, #addMovementModal .form-select');
    
    validationIcons.forEach(icon => {
        icon.style.display = 'none';
        icon.classList.remove('show', 'valid', 'invalid');
    });
    
    errorMessages.forEach(message => {
        message.classList.remove('show');
    });
    
    inputs.forEach(input => {
        input.classList.remove('is-valid', 'is-invalid');
    });
    
    // Disable submit button
    const submitBtn = document.getElementById('submitMovement');
    if (submitBtn) {
        submitBtn.disabled = true;
    }
}

/**
 * Initialize edit modal validation - FIXED: Proper validation when changing outlet
 */
function initEditModalValidation() {
    const editDateInput = document.getElementById('editMovementDate');
    const editOutletSelect = document.getElementById('editMovementOutlet');
    const editItemSelect = document.getElementById('editMovementItem');
    const editTypeSelect = document.getElementById('editMovementType');
    const editQuantityInput = document.getElementById('editMovementQuantity');
    
    // Edit modal show - validate all fields immediately
    const editModal = document.getElementById('editMovementModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const movementId = button.getAttribute('data-movement-id');
            const movementDate = button.getAttribute('data-movement-date');
            const movementType = button.getAttribute('data-movement-type');
            const movementQuantity = button.getAttribute('data-movement-quantity');
            const outletId = button.getAttribute('data-outlet-id');
            const itemId = button.getAttribute('data-item-id');
            const currentStock = button.getAttribute('data-current-stock');
            
            document.getElementById('editMovementId').value = movementId;
            document.getElementById('editMovementDate').value = movementDate;
            document.getElementById('editMovementType').value = movementType;
            document.getElementById('editMovementQuantity').value = movementQuantity;
            
            // Store original values for rollback logic
            document.getElementById('originalItemId').value = itemId;
            document.getElementById('originalMovementType').value = movementType;
            document.getElementById('originalQuantity').value = movementQuantity;
            
            // Set outlet and load items for that outlet
            const outletSelect = document.getElementById('editMovementOutlet');
            outletSelect.value = outletId;
            
            // Load items for the selected outlet and then select the correct item
            loadItemsForEditOutlet(outletId, itemId, currentStock);
            
            // Validate all fields immediately in edit modal
            setTimeout(() => {
                validateEditDate();
                validateEditOutlet();
                validateEditItem();
                validateEditType();
                validateEditQuantity();
                checkEditFormValidity();
            });
        });
        
        editModal.addEventListener('hidden.bs.modal', function() {
            resetEditFormValidation();
        });
    }
    
    // Edit outlet change - FIXED: Reset item validation when outlet changes
    if (editOutletSelect) {
        editOutletSelect.addEventListener('change', function() {
            const outletId = this.value;
            // Reset item validation when outlet changes
            validEditItem = false;
            const itemValidationIcon = document.getElementById('editItemValidationIcon');
            const itemErrorMessage = document.getElementById('editItemError');
            const itemSelect = document.getElementById('editMovementItem');
            
            showValidationError(itemSelect, itemValidationIcon, itemErrorMessage, 'Please select an item');
            
            loadItemsForEditOutlet(outletId, '', '');
            validateEditOutlet();
            checkEditFormValidity();
        });
    }
    
    // Edit item change
    if (editItemSelect) {
        editItemSelect.addEventListener('change', function() {
            validateEditItem();
            checkEditFormValidity();
        });
    }
    
    // Edit type change
    if (editTypeSelect) {
        editTypeSelect.addEventListener('change', function() {
            validateEditType();
            checkEditFormValidity();
        });
    }
    
    // Edit quantity input
    if (editQuantityInput) {
        editQuantityInput.addEventListener('input', function() {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            validateEditQuantity();
            checkEditFormValidity();
        });
    }
    
    // Edit date input
    if (editDateInput) {
        editDateInput.addEventListener('input', validateEditDate);
        editDateInput.addEventListener('change', validateEditDate);
    }
}

/**
 * Initialize modal event handlers
 */
function initModalHandlers() {
    // Delete modal handler
    const deleteModal = document.getElementById('deleteMovementModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const movementId = button.getAttribute('data-movement-id');
            const itemName = button.getAttribute('data-item-name');
            
            document.getElementById('deleteItemName').textContent = itemName;
            document.getElementById('confirmDeleteBtn').href = `owner-stock.php?delete_movement=${movementId}`;
        });
    }
}

/**
 * Load stock items for selected outlet
 */
function loadItemsForOutlet(outletId) {
    const itemSelect = document.getElementById('movementItem');
    
    if (!outletId) {
        itemSelect.innerHTML = '<option value="" selected disabled>--- Select Item ---</option>';
        itemSelect.disabled = true;
        
        // Reset item validation when outlet is cleared
        validItem = false;
        const validationIcon = document.getElementById('itemValidationIcon');
        const errorMessage = document.getElementById('itemError');
        showValidationError(itemSelect, validationIcon, errorMessage, 'Please select an outlet first');
        checkAddFormValidity();
        return;
    }
    
    // Show loading state
    itemSelect.innerHTML = '<option value="">Loading items...</option>';
    itemSelect.disabled = true;
    
    // Reset item validation state
    validItem = false;
    const validationIcon = document.getElementById('itemValidationIcon');
    const errorMessage = document.getElementById('itemError');
    showValidationError(itemSelect, validationIcon, errorMessage, 'Please select an item');
    
    // Fetch items via AJAX
    fetch(`get_items.php?outlet_id=${outletId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.length > 0) {
                populateItemSelect(itemSelect, data);
            } else {
                itemSelect.innerHTML = '<option value="" selected disabled>--- No items available for this outlet ---</option>';
            }
        })
        .catch(error => {
            console.error('Error loading items:', error);
            itemSelect.innerHTML = '<option value="" selected disabled>--- Error loading items ---</option>';
        })
        .finally(() => {
            itemSelect.disabled = false;
            checkAddFormValidity();
        });
}

/**
 * Load stock items for selected outlet in edit modal
 */
function loadItemsForEditOutlet(outletId, itemId, currentStock) {
    const itemSelect = document.getElementById('editMovementItem');
    
    if (!outletId) {
        itemSelect.innerHTML = '<option value="" selected disabled>--- Select Item ---</option>';
        itemSelect.disabled = true;
         validEditItem = false;
    const icon = document.getElementById('editItemValidationIcon');
    const err  = document.getElementById('editItemError');
    showValidationError(itemSelect, icon, err, 'Please select an item');
    checkEditFormValidity();
    return;
    }
    
    // Show loading state
    itemSelect.innerHTML = '<option value="">Loading items...</option>';
    itemSelect.disabled = true;
    
    // Fetch items via AJAX
    return fetch(`get_items.php?outlet_id=${outletId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.length > 0) {
                populateEditItemSelect(itemSelect, data, itemId, currentStock);
            } else {
                itemSelect.innerHTML = '<option value="" selected disabled>--- No items available for this outlet ---</option>';
            }
        })
        .catch(error => {
            console.error('Error loading items:', error);
            itemSelect.innerHTML = '<option value="" selected disabled>--- Error loading items ---</option>';
        })
        .finally(() => {
            itemSelect.disabled = false;
            validateEditItem();
      checkEditFormValidity();
        });
}

/**
 * Populate item select dropdown with data
 */
function populateItemSelect(selectElement, items) {
    if (!items || items.length === 0) {
        selectElement.innerHTML = '<option value="" selected disabled>--- No items available for this outlet ---</option>';
        return;
    }
    
    let options = '<option value="" selected disabled>--- Select Item ---</option>';
    
    items.forEach(item => {
        const currentStock = item.remaining_quantity || 0;
        options += `<option value="${item.item_id}">
            ${item.item_name} (${item.category_name}) - Stock: ${currentStock}
        </option>`;
    });
    
    selectElement.innerHTML = options;
}

/**
 * Populate edit item select dropdown with data and select the correct item
 */
function populateEditItemSelect(selectElement, items, selectedItemId, currentStock) {
    if (!items || items.length === 0) {
        selectElement.innerHTML = '<option value="" selected disabled>--- No items available for this outlet ---</option>';
        return;
    }
    
    let options = '<option value="" selected disabled>--- Select Item ---</option>';
    
    items.forEach(item => {
        const isSelected = item.item_id == selectedItemId;
        options += `<option value="${item.item_id}" ${isSelected ? 'selected' : ''}>
            ${item.item_name} (${item.category_name}) - Stock: ${item.remaining_quantity}
        </option>`;
    });
    
    selectElement.innerHTML = options;
}

// VALIDATION FUNCTIONS

/**
 * Validation state tracking
 */
let validDate = false, validOutlet = false, validItem = false, validType = false, validQuantity = false;
let validEditDate = false, validEditOutlet = false, validEditItem = false, validEditType = false, validEditQuantity = false;

/**
 * Validate date field (add modal)
 */
function validateDate() {
    const dateInput = document.getElementById('movementDate');
    const validationIcon = document.getElementById('dateValidationIcon');
    const errorMessage = document.getElementById('dateError');
    
    if (!dateInput.value) {
        showValidationError(dateInput, validationIcon, errorMessage, 'Date is required');
        validDate = false;
        checkAddFormValidity();
        return false;
    }
    
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(23, 59, 59, 999);
    
    if (selectedDate > today) {
        showValidationError(dateInput, validationIcon, errorMessage, 'Date cannot be in the future');
        validDate = false;
        checkAddFormValidity();
        return false;
    }
    
    showValidationSuccess(dateInput, validationIcon, errorMessage);
    validDate = true;
    checkAddFormValidity();
    return true;
}

/**
 * Validate outlet field (add modal)
 */
function validateOutlet() {
    const outletSelect = document.getElementById('movementOutlet');
    const validationIcon = document.getElementById('outletValidationIcon');
    const errorMessage = document.getElementById('outletError');
    
    if (!outletSelect.value) {
        showValidationError(outletSelect, validationIcon, errorMessage, 'Outlet is required');
        validOutlet = false;
        checkAddFormValidity();
        return false;
    }
    
    showValidationSuccess(outletSelect, validationIcon, errorMessage);
    validOutlet = true;
    checkAddFormValidity();
    return true;
}

/**
 * Validate item field (add modal)
 */
function validateItem() {
    const itemSelect = document.getElementById('movementItem');
    const validationIcon = document.getElementById('itemValidationIcon');
    const errorMessage = document.getElementById('itemError');
    
    if (!itemSelect.value || itemSelect.disabled) {
        showValidationError(itemSelect, validationIcon, errorMessage, 'Item is required');
        validItem = false;
        checkAddFormValidity();
        return false;
    }
    
    showValidationSuccess(itemSelect, validationIcon, errorMessage);
    validItem = true;
    checkAddFormValidity();
    return true;
}

/**
 * Validate type field (add modal)
 */
function validateType() {
    const typeSelect = document.getElementById('movementType');
    const validationIcon = document.getElementById('typeValidationIcon');
    const errorMessage = document.getElementById('typeError');
    
    if (!typeSelect.value) {
        showValidationError(typeSelect, validationIcon, errorMessage, 'Movement type is required');
        validType = false;
        checkAddFormValidity();
        return false;
    }
    
    showValidationSuccess(typeSelect, validationIcon, errorMessage);
    validType = true;
    checkAddFormValidity();
    return true;
}

/**
 * Validate quantity field (add modal) - SIMPLIFIED: only check for minimum 1
 */
function validateQuantity() {
    const quantityInput = document.getElementById('movementQuantity');
    const validationIcon = document.getElementById('quantityValidationIcon');
    const errorMessage = document.getElementById('quantityError');
    
    if (!quantityInput.value || quantityInput.value <= 0) {
        showValidationError(quantityInput, validationIcon, errorMessage, 'Quantity must be at least 1');
        validQuantity = false;
        checkAddFormValidity();
        return false;
    }
    
    // Check if quantity is a valid integer
    if (!Number.isInteger(Number(quantityInput.value))) {
        showValidationError(quantityInput, validationIcon, errorMessage, 'Quantity must be a whole number');
        validQuantity = false;
        checkAddFormValidity();
        return false;
    }
    
    showValidationSuccess(quantityInput, validationIcon, errorMessage);
    validQuantity = true;
    checkAddFormValidity();
    return true;
}

/**
 * Edit form validation functions
 */
function validateEditDate() {
    const dateInput = document.getElementById('editMovementDate');
    const validationIcon = document.getElementById('editDateValidationIcon');
    const errorMessage = document.getElementById('editDateError');
    
    if (!dateInput.value) {
        showValidationError(dateInput, validationIcon, errorMessage, 'Date is required');
        validEditDate = false;
        checkEditFormValidity();
        return false;
    }
    
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(23, 59, 59, 999);
    
    if (selectedDate > today) {
        showValidationError(dateInput, validationIcon, errorMessage, 'Date cannot be in the future');
        validEditDate = false;
        checkEditFormValidity();
        return false;
    }
    
    showValidationSuccess(dateInput, validationIcon, errorMessage);
    validEditDate = true;
    checkEditFormValidity();
    return true;
}

function validateEditOutlet() {
    const outletSelect = document.getElementById('editMovementOutlet');
    const validationIcon = document.getElementById('editOutletValidationIcon');
    const errorMessage = document.getElementById('editOutletError');
    
    if (!outletSelect.value) {
        showValidationError(outletSelect, validationIcon, errorMessage, 'Outlet is required');
        validEditOutlet = false;
        checkEditFormValidity();
        return false;
    }
    
    showValidationSuccess(outletSelect, validationIcon, errorMessage);
    validEditOutlet = true;
    checkEditFormValidity();
    return true;
}

function validateEditItem() {
    const itemSelect = document.getElementById('editMovementItem');
    const validationIcon = document.getElementById('editItemValidationIcon');
    const errorMessage = document.getElementById('editItemError');
    
    if (!itemSelect.value) {
        showValidationError(itemSelect, validationIcon, errorMessage, 'Item is required');
        validEditItem = false;
        checkEditFormValidity();
        return false;
    }
    
    showValidationSuccess(itemSelect, validationIcon, errorMessage);
    validEditItem = true;
    checkEditFormValidity();
    return true;
}

function validateEditType() {
    const typeSelect = document.getElementById('editMovementType');
    const validationIcon = document.getElementById('editTypeValidationIcon');
    const errorMessage = document.getElementById('editTypeError');
    
    if (!typeSelect.value) {
        showValidationError(typeSelect, validationIcon, errorMessage, 'Movement type is required');
        validEditType = false;
        checkEditFormValidity();
        return false;
    }
    
    showValidationSuccess(typeSelect, validationIcon, errorMessage);
    validEditType = true;
    checkEditFormValidity();
    return true;
}

function validateEditQuantity() {
    const quantityInput = document.getElementById('editMovementQuantity');
    const validationIcon = document.getElementById('editQuantityValidationIcon');
    const errorMessage = document.getElementById('editQuantityError');
    
    if (!quantityInput.value || quantityInput.value <= 0) {
        showValidationError(quantityInput, validationIcon, errorMessage, 'Quantity must be at least 1');
        validEditQuantity = false;
        checkEditFormValidity();
        return false;
    }
    
    // Check if quantity is a valid integer
    if (!Number.isInteger(Number(quantityInput.value))) {
        showValidationError(quantityInput, validationIcon, errorMessage, 'Quantity must be a whole number');
        validEditQuantity = false;
        checkEditFormValidity();
        return false;
    }
    
    showValidationSuccess(quantityInput, validationIcon, errorMessage);
    validEditQuantity = true;
    checkEditFormValidity();
    return true;
}

/**
 * Show validation error
 */
function showValidationError(inputElement, validationIcon, errorMessage, message) {
    inputElement.classList.add('is-invalid');
    inputElement.classList.remove('is-valid');
    
    if (validationIcon) {
        validationIcon.style.display = 'flex';
        validationIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
        validationIcon.classList.add('show', 'invalid');
        validationIcon.classList.remove('valid');
    }
    
    if (errorMessage) {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    }
}

/**
 * Show validation success
 */
function showValidationSuccess(inputElement, validationIcon, errorMessage) {
    inputElement.classList.add('is-valid');
    inputElement.classList.remove('is-invalid');
    
    if (validationIcon) {
        validationIcon.style.display = 'flex';
        validationIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
        validationIcon.classList.add('show', 'valid');
        validationIcon.classList.remove('invalid');
    }
    
    if (errorMessage) {
        errorMessage.classList.remove('show');
    }
}

/**
 * Check if add form is valid and update button state
 */
function checkAddFormValidity() {
    const submitBtn = document.getElementById('submitMovement');
    const isValid = validDate && validOutlet && validItem && validType && validQuantity;
    
    if (submitBtn) {
        submitBtn.disabled = !isValid;
    }
    
    return isValid;
}

/**
 * Check if edit form is valid and update button state
 */
function checkEditFormValidity() {
    const submitBtn = document.getElementById('submitEditMovement');
    const isValid = validEditDate && validEditOutlet && validEditItem && validEditType && validEditQuantity;
    
    if (submitBtn) {
        submitBtn.disabled = !isValid;
    }
    
    return isValid;
}

/**
 * Reset add form validation
 */
function resetAddFormValidation() {
    validDate = validOutlet = validItem = validType = validQuantity = false;
    
    const validationIcons = document.querySelectorAll('#addMovementModal .validation-icon-outside');
    const errorMessages = document.querySelectorAll('#addMovementModal .error-message');
    const inputs = document.querySelectorAll('#addMovementModal .form-control, #addMovementModal .form-select');
    
    validationIcons.forEach(icon => {
        icon.style.display = 'none';
        icon.classList.remove('show', 'valid', 'invalid');
    });
    
    errorMessages.forEach(message => {
        message.classList.remove('show');
    });
    
    inputs.forEach(input => {
        input.classList.remove('is-valid', 'is-invalid');
    });
    
    const submitBtn = document.getElementById('submitMovement');
    if (submitBtn) {
        submitBtn.disabled = true;
    }
}

/**
 * Reset edit form validation
 */
function resetEditFormValidation() {
    validEditDate = validEditOutlet = validEditItem = validEditType = validEditQuantity = false;
    
    const validationIcons = document.querySelectorAll('#editMovementModal .validation-icon-outside');
    const errorMessages = document.querySelectorAll('#editMovementModal .error-message');
    const inputs = document.querySelectorAll('#editMovementModal .form-control, #editMovementModal .form-select');
    
    validationIcons.forEach(icon => {
        icon.style.display = 'none';
        icon.classList.remove('show', 'valid', 'invalid');
    });
    
    errorMessages.forEach(message => {
        message.classList.remove('show');
    });
    
    inputs.forEach(input => {
        input.classList.remove('is-valid', 'is-invalid');
    });
    
    const submitBtn = document.getElementById('submitEditMovement');
    if (submitBtn) {
        submitBtn.disabled = true;
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize logout handling without alerts
 */
function initLogoutHandling() {
   
    
    const confirmLogout = document.getElementById('confirmLogout');
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function() {
            window.location.href = 'logout.php';
        });
    }
}

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    const movementForm = document.getElementById('movementForm');
    const editMovementForm = document.getElementById('editMovementForm');
    
    if (movementForm) {
        movementForm.addEventListener('submit', function(e) {
            if (!checkAddFormValidity()) {
                e.preventDefault();
                // Validate all fields to show errors
                validateDate();
                validateOutlet();
                validateItem();
                validateType();
                validateQuantity();
            }
        });
    }
    
    if (editMovementForm) {
        editMovementForm.addEventListener('submit', function(e) {
            if (!checkEditFormValidity()) {
                e.preventDefault();
                // Validate all fields to show errors
                validateEditDate();
                validateEditOutlet();
                validateEditItem();
                validateEditType();
                validateEditQuantity();
            }
        });
    }
});