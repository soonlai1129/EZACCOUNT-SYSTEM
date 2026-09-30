/**
 * Manage Stock JavaScript
 * Handles dynamic functionality for the stock management interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Modal instances
    const addCategoryModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const editCategoryModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    const addItemModal = new bootstrap.Modal(document.getElementById('addItemModal'));
    const editItemModal = new bootstrap.Modal(document.getElementById('editItemModal'));
    const deleteConfirmationModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));

    // Set up Add Item button to pre-select category
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-item-btn')) {
            const button = e.target.closest('.add-item-btn');
            const categoryId = button.getAttribute('data-category-id');
            const categoryName = button.getAttribute('data-category-name');
            
            document.getElementById('modalCategoryId').value = categoryId;
            document.getElementById('modalCategoryInput').value = categoryName;
            
            // Reset and validate the form
            const form = document.getElementById('addItemForm');
            form.reset();
            validateAddItemForm();
        }
    });

    // Handle Edit Category button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-category-btn')) {
            const button = e.target.closest('.edit-category-btn');
            const categoryId = button.getAttribute('data-category-id');
            const categoryName = button.getAttribute('data-category-name');
            
            document.getElementById('editCategoryId').value = categoryId;
            document.getElementById('editCategoryName').value = categoryName;
            
            // Reset validation
            resetValidation('editCategoryForm');
            validateEditCategoryForm();
            
            editCategoryModal.show();
        }
    });

    // Handle Delete Category button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-category-btn')) {
            const button = e.target.closest('.delete-category-btn');
            const categoryId = button.getAttribute('data-category-id');
            const categoryName = button.getAttribute('data-category-name');
            
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteItemId').value = '';
            document.getElementById('deleteConfirmationMessage').textContent = 
                `Are you sure you want to delete the category "${categoryName}"? This will also delete all items in this category. This action cannot be undone.`;
            
            deleteConfirmationModal.show();
        }
    });

    // Handle Edit Item button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-item-btn')) {
            const button = e.target.closest('.edit-item-btn');
            const itemId = button.getAttribute('data-item-id');
            const categoryId = button.getAttribute('data-category-id');
            const itemName = button.getAttribute('data-item-name');
            const basePrice = button.getAttribute('data-base-price');
            const remainingQuantity = button.getAttribute('data-remaining-quantity');
            const image = button.getAttribute('data-image');
            
            // Populate the edit form
            document.getElementById('editItemId').value = itemId;
            document.getElementById('editCategoryId').value = categoryId;
            document.getElementById('editItemName').value = itemName;
            document.getElementById('editBasePrice').value = basePrice;
            document.getElementById('editRemainingQuantity').value = remainingQuantity;
            document.getElementById('editExistingImage').value = image;
            
            // Show image preview if exists
            const imagePreview = document.getElementById('editImagePreview');
            if (image && image !== 'null') {
                imagePreview.innerHTML = `<img src="uploads/${image}" alt="${itemName}" class="img-thumbnail" style="max-height: 150px;">`;
            } else {
                imagePreview.innerHTML = '<p class="text-muted"><i class="fas fa-image me-1"></i>No image uploaded</p>';
            }
            
            // Reset validation
            resetValidation('editItemForm');
            validateEditItemForm();
            
            editItemModal.show();
        }
    });

    // Handle Delete Item button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-item-btn')) {
            const button = e.target.closest('.delete-item-btn');
            const itemId = button.getAttribute('data-item-id');
            const itemName = button.getAttribute('data-item-name');
            
            document.getElementById('deleteItemId').value = itemId;
            document.getElementById('deleteCategoryId').value = '';
            document.getElementById('deleteConfirmationMessage').textContent = 
                `Are you sure you want to delete the item "${itemName}"? This action cannot be undone.`;
            
            deleteConfirmationModal.show();
        }
    });

    // Confirm Delete Button
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        const itemId = document.getElementById('deleteItemId').value;
        const categoryId = document.getElementById('deleteCategoryId').value;
        
        if (itemId) {
            deleteItem(itemId);
        } else if (categoryId) {
            deleteCategory(categoryId);
        }
        
        deleteConfirmationModal.hide();
    });

    // Form Validation Functions
    function validateAddCategoryForm() {
        const form = document.getElementById('addCategoryForm');
        const categoryName = document.getElementById('categoryName').value.trim();
        const errorElement = document.getElementById('categoryNameError');
        let isValid = true;

        if (categoryName.length < 2) {
            showError('categoryName', 'Category name must be at least 2 characters long');
            isValid = false;
        } else if (categoryName.length > 50) {
            showError('categoryName', 'Category name cannot exceed 50 characters');
            isValid = false;
        } else {
            clearError('categoryName');
        }

        return isValid;
    }

    function validateEditCategoryForm() {
        const categoryName = document.getElementById('editCategoryName').value.trim();
        const errorElement = document.getElementById('editCategoryNameError');
        let isValid = true;

        if (categoryName.length < 2) {
            showError('editCategoryName', 'Category name must be at least 2 characters long');
            isValid = false;
        } else if (categoryName.length > 50) {
            showError('editCategoryName', 'Category name cannot exceed 50 characters');
            isValid = false;
        } else {
            clearError('editCategoryName');
        }

        return isValid;
    }

    function validateAddItemForm() {
        const itemName = document.getElementById('itemName').value.trim();
        const basePrice = document.getElementById('basePrice').value;
        let isValid = true;

        // Validate item name
        if (itemName.length < 2) {
            showError('itemName', 'Item name must be at least 2 characters long');
            isValid = false;
        } else if (itemName.length > 100) {
            showError('itemName', 'Item name cannot exceed 100 characters');
            isValid = false;
        } else {
            clearError('itemName');
        }

        // Validate base price
        if (!basePrice || basePrice < 0) {
            showError('basePrice', 'Base price must be a positive number');
            isValid = false;
        } else {
            clearError('basePrice');
        }

        return isValid;
    }

    function validateEditItemForm() {
        const itemName = document.getElementById('editItemName').value.trim();
        const basePrice = document.getElementById('editBasePrice').value;
        const categoryId = document.getElementById('editCategoryId').value;
        const remainingQuantity = document.getElementById('editRemainingQuantity').value;
        let isValid = true;

        // Validate item name
        if (itemName.length < 2) {
            showError('editItemName', 'Item name must be at least 2 characters long');
            isValid = false;
        } else if (itemName.length > 100) {
            showError('editItemName', 'Item name cannot exceed 100 characters');
            isValid = false;
        } else {
            clearError('editItemName');
        }

        // Validate base price
        if (!basePrice || basePrice < 0) {
            showError('editBasePrice', 'Base price must be a positive number');
            isValid = false;
        } else {
            clearError('editBasePrice');
        }

        // Validate category
        if (!categoryId) {
            showError('editCategoryId', 'Please select a category');
            isValid = false;
        } else {
            clearError('editCategoryId');
        }

        // Validate quantity
        if (!remainingQuantity || remainingQuantity < 0) {
            showError('editRemainingQuantity', 'Quantity must be a positive number');
            isValid = false;
        } else {
            clearError('editRemainingQuantity');
        }

        return isValid;
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + 'Error');
        
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + 'Error');
        
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        if (errorElement) {
            errorElement.textContent = '';
        }
    }

    function resetValidation(formId) {
        const form = document.getElementById(formId);
        const inputs = form.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
            const errorElement = document.getElementById(input.id + 'Error');
            if (errorElement) {
                errorElement.textContent = '';
            }
        });
    }

    // Real-time validation
    document.getElementById('categoryName')?.addEventListener('input', validateAddCategoryForm);
    document.getElementById('editCategoryName')?.addEventListener('input', validateEditCategoryForm);
    document.getElementById('itemName')?.addEventListener('input', validateAddItemForm);
    document.getElementById('basePrice')?.addEventListener('input', validateAddItemForm);
    document.getElementById('editItemName')?.addEventListener('input', validateEditItemForm);
    document.getElementById('editBasePrice')?.addEventListener('input', validateEditItemForm);
    document.getElementById('editCategoryId')?.addEventListener('change', validateEditItemForm);
    document.getElementById('editRemainingQuantity')?.addEventListener('input', validateEditItemForm);

    // Form submission validation
    document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
        if (!validateAddCategoryForm()) {
            e.preventDefault();
            showMessage('Please fix the validation errors before submitting.', 'error');
        }
    });

    document.getElementById('editCategoryForm')?.addEventListener('submit', function(e) {
        if (!validateEditCategoryForm()) {
            e.preventDefault();
            showMessage('Please fix the validation errors before submitting.', 'error');
        }
    });

    document.getElementById('addItemForm')?.addEventListener('submit', function(e) {
        if (!validateAddItemForm()) {
            e.preventDefault();
            showMessage('Please fix the validation errors before submitting.', 'error');
        }
    });

    document.getElementById('editItemForm')?.addEventListener('submit', function(e) {
        if (!validateEditItemForm()) {
            e.preventDefault();
            showMessage('Please fix the validation errors before submitting.', 'error');
        }
    });

    // Function to delete an item via AJAX
    function deleteItem(itemId) {
        const button = document.querySelector(`.delete-item-btn[data-item-id="${itemId}"]`);
        const originalHTML = button.innerHTML;
        button.innerHTML = '<span class="loading"></span>';
        button.disabled = true;
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'manage-stock.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the item card from the DOM
                        const itemCard = button.closest('.col-xl-3');
                        if (itemCard) {
                            itemCard.remove();
                            showMessage(response.message, 'success');
                            
                            // Check if category is now empty
                            const categorySection = button.closest('.category-section');
                            const items = categorySection.querySelectorAll('.col-xl-3');
                            if (items.length === 0) {
                                const emptyDiv = document.createElement('div');
                                emptyDiv.className = 'col-12';
                                emptyDiv.innerHTML = `
                                    <div class="empty-category text-center py-4">
                                        <i class="fas fa-box-open fa-2x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No items in this category yet.</p>
                                    </div>
                                `;
                                categorySection.querySelector('.row').appendChild(emptyDiv);
                            }
                        }
                    } else {
                        showMessage(response.message, 'error');
                    }
                } catch (e) {
                    showMessage('Error processing response', 'error');
                }
            } else {
                showMessage('Server error occurred', 'error');
            }
            
            button.innerHTML = originalHTML;
            button.disabled = false;
        };
        
        xhr.onerror = function() {
            showMessage('Network error occurred', 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        };
        
        xhr.send('delete_item_id=' + encodeURIComponent(itemId));
    }

    // Function to delete a category via AJAX
    function deleteCategory(categoryId) {
        const button = document.querySelector(`.delete-category-btn[data-category-id="${categoryId}"]`);
        const originalHTML = button.innerHTML;
        button.innerHTML = '<span class="loading"></span>';
        button.disabled = true;
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'manage-stock.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the category section from the DOM
                        const categorySection = button.closest('.category-section');
                        categorySection.remove();
                        showMessage(response.message, 'success');
                    } else {
                        showMessage(response.message, 'error');
                    button.innerHTML = originalHTML;
                        button.disabled = false;
                    }
                } catch (e) {
                    showMessage('Error processing response', 'error');
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            } else {
                showMessage('Server error occurred', 'error');
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        };
        
        xhr.onerror = function() {
            showMessage('Network error occurred', 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        };
        
        xhr.send('delete_category_id=' + encodeURIComponent(categoryId));
    }

    // Function to show status messages
    function showMessage(message, type) {
        // Remove any existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}`;
        
        // Insert at the top of the content container
        const contentContainer = document.querySelector('.content-container .container');
        if (contentContainer) {
            contentContainer.insertBefore(messageDiv, contentContainer.firstChild);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    // Image preview for edit modal
    const editImageInput = document.getElementById('editItemImage');
    if (editImageInput) {
        editImageInput.addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('editImagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-height: 150px;">`;
                };
                reader.readAsDataURL(file);
            } else {
                const existingImage = document.getElementById('editExistingImage').value;
                if (existingImage && existingImage !== 'null') {
                    preview.innerHTML = `<img src="uploads/${existingImage}" alt="Preview" class="img-thumbnail" style="max-height: 150px;">`;
                } else {
                    preview.innerHTML = '<p class="text-muted"><i class="fas fa-image me-1"></i>No image uploaded</p>';
                }
            }
        });
    }

    // Prevent form submission on Enter key in certain inputs
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName === 'INPUT' && 
            !e.target.closest('.modal-content') && 
            e.target.type !== 'submit') {
            e.preventDefault();
        }
    });

    // Responsive adjustments for mobile
    function handleResponsive() {
        const isMobile = window.innerWidth < 768;
        
        // Adjust card layout for mobile
        const stockCards = document.querySelectorAll('.stock-item-card');
        stockCards.forEach(card => {
            if (isMobile) {
                card.style.marginBottom = '15px';
            } else {
                card.style.marginBottom = '';
            }
        });
        
        // Adjust floating button position for mobile
        const floatingButtons = document.querySelectorAll('.floating-btn');
        floatingButtons.forEach(btn => {
            if (isMobile) {
                btn.style.bottom = '20px';
                btn.style.right = '20px';
            } else {
                btn.style.bottom = '30px';
                btn.style.right = '30px';
            }
        });
    }

    // Initial responsive setup
    handleResponsive();
    
    // Update on window resize
    window.addEventListener('resize', handleResponsive);

    // Logout functionality
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        logoutModal.show();
    });

    document.getElementById('confirmLogout')?.addEventListener('click', function() {
        window.location.href = 'logout.php';
    });

    // Modal hidden events to reset forms
    document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addCategoryForm').reset();
        resetValidation('addCategoryForm');
    });

    document.getElementById('editCategoryModal').addEventListener('hidden.bs.modal', function() {
        resetValidation('editCategoryForm');
    });

    document.getElementById('addItemModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addItemForm').reset();
        resetValidation('addItemForm');
    });

    document.getElementById('editItemModal').addEventListener('hidden.bs.modal', function() {
        resetValidation('editItemForm');
    });

    console.log('Manage Stock JavaScript loaded successfully');
});