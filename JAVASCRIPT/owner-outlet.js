/**
 * Owner Outlet JS - validation icon inside field, all uppercase, notification dialog icon, add button disables on modal open
 * Enhancement: immediate validation when edit outlet (show validation icon/errors on modal open)
 */
document.addEventListener('DOMContentLoaded', function () {
    const addOutletBtn = document.getElementById('addOutletBtn');
    const outletModal = new bootstrap.Modal(document.getElementById('outletModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const outletForm = document.getElementById('outletForm');
    const saveOutletBtn = document.getElementById('saveOutletBtn');
    const outletNameInput = document.getElementById('outletName');
    const outletAddressInput = document.getElementById('outletAddress');
    const nameValidationIcon = document.getElementById('nameValidationIcon');
    const addressValidationIcon = document.getElementById('addressValidationIcon');
    const nameError = document.getElementById('nameError');
    const addressError = document.getElementById('addressError');
    const modalTitleText = document.getElementById('modalTitleText');
    const outletIdField = document.getElementById('outletId');
    const deleteForm = document.getElementById('deleteForm');
    const deleteOutletId = document.getElementById('deleteOutletId');
    const deleteMessage = document.getElementById('deleteMessage');

    // Always convert to uppercase, allow spaces, keep cursor
    outletNameInput.addEventListener('input', function () {
        let cursorPos = outletNameInput.selectionStart;
        outletNameInput.value = outletNameInput.value.toUpperCase();
        outletNameInput.setSelectionRange(cursorPos, cursorPos);
        let value = outletNameInput.value.trim();
        if (value.length === 0) {
            setValidation('name', false, 'Outlet name is required');
        } else {
            let isDuplicate = false;
            let isEditMode = !!outletIdField.value;
            let valueUpper = value.toUpperCase();
            if (!isEditMode) {
                isDuplicate = existingOutletNames.includes(valueUpper);
            } else {
                let currentId = outletIdField.value;
                let currentName = null;
                document.querySelectorAll('.edit-outlet').forEach(btn => {
                    if (btn.getAttribute('data-outlet-id') === currentId) {
                        currentName = btn.getAttribute('data-outlet-name').trim().toUpperCase();
                    }
                });
                isDuplicate = (existingOutletNames.includes(valueUpper) && valueUpper !== currentName);
            }
            if (isDuplicate) {
                setValidation('name', false, 'Outlet name already exists. Please choose a different name.');
            } else {
                setValidation('name', true);
            }
        }
    });
    outletNameInput.addEventListener('blur', function () { outletNameInput.dispatchEvent(new Event('input')); });

    outletAddressInput.addEventListener('input', function () {
        let cursorPos = outletAddressInput.selectionStart;
        outletAddressInput.value = outletAddressInput.value.toUpperCase();
        outletAddressInput.setSelectionRange(cursorPos, cursorPos);
        let value = outletAddressInput.value.trim();
        if (value.length === 0) {
            setValidation('address', false, 'Outlet address is required');
        } else {
            setValidation('address', true);
        }
    });
    outletAddressInput.addEventListener('blur', function () { outletAddressInput.dispatchEvent(new Event('input')); });

    function setValidation(field, isValid, message = "") {
        const icon = field === 'name' ? nameValidationIcon : addressValidationIcon;
        const error = field === 'name' ? nameError : addressError;
        if (isValid) {
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            error.textContent = '';
        } else {
            icon.innerHTML = '<i class="fas fa-times-circle"></i>';
            error.textContent = message;
        }
        if (field === 'name') validName = isValid;
        if (field === 'address') validAddress = isValid;
        saveOutletBtn.disabled = !(validName && validAddress);
    }

    function disableAddBtn(disable) {
        if (addOutletBtn) addOutletBtn.disabled = disable;
    }
    ['outletModal', 'deleteModal'].forEach(function(modalId) {
        const modalEl = document.getElementById(modalId);
        modalEl.addEventListener('show.bs.modal', function() { disableAddBtn(true); });
        modalEl.addEventListener('hidden.bs.modal', function() { disableAddBtn(false); });
    });

    // List of all outlet names (uppercased, trimmed!) for duplicate check
    let existingOutletNames = [];
    function reloadOutletNames() {
        existingOutletNames = [];
        document.querySelectorAll('.outlet-card-title').forEach(td => {
            let name = td.textContent.replace(/^\W+/, '').trim().toUpperCase();
            existingOutletNames.push(name);
        });
    }
    reloadOutletNames();
    let validName = false;
    let validAddress = false;

    addOutletBtn.addEventListener('click', function () {
        modalTitleText.textContent = "Add New Outlet";
        outletForm.setAttribute('method', 'post');
        outletForm.setAttribute('action', '');
        outletForm.querySelector('[name="add_outlet"]')?.remove();
        let addInput = document.createElement('input');
        addInput.type = 'hidden';
        addInput.name = 'add_outlet';
        addInput.value = '1';
        outletForm.appendChild(addInput);
        outletIdField.value = '';
        outletNameInput.value = '';
        outletAddressInput.value = '';
        nameValidationIcon.innerHTML = '';
        addressValidationIcon.innerHTML = '';
        nameError.textContent = '';
        addressError.textContent = '';
        validName = false;
        validAddress = false;
        saveOutletBtn.disabled = true;
        outletModal.show();
    });

    document.querySelectorAll('.edit-outlet').forEach(btn => {
        btn.addEventListener('click', function () {
            modalTitleText.textContent = "Edit Outlet";
            outletForm.setAttribute('method', 'post');
            outletForm.setAttribute('action', '');
            outletForm.querySelector('[name="edit_outlet"]')?.remove();
            let editInput = document.createElement('input');
            editInput.type = 'hidden';
            editInput.name = 'edit_outlet';
            editInput.value = '1';
            outletForm.appendChild(editInput);
            outletIdField.value = btn.getAttribute('data-outlet-id');
            outletNameInput.value = btn.getAttribute('data-outlet-name');
            outletAddressInput.value = btn.getAttribute('data-outlet-address');
            nameValidationIcon.innerHTML = '';
            addressValidationIcon.innerHTML = '';
            nameError.textContent = '';
            addressError.textContent = '';
            validName = false;
            validAddress = false;
            // --- Immediate validation for edit mode ---
            outletNameInput.dispatchEvent(new Event('input'));
            outletAddressInput.dispatchEvent(new Event('input'));
            saveOutletBtn.disabled = !(validName && validAddress);
            outletModal.show();
        });
    });

    document.querySelectorAll('.delete-outlet').forEach(btn => {
        btn.addEventListener('click', function () {
            deleteOutletId.value = btn.getAttribute('data-outlet-id');
            deleteMessage.textContent = `Are you sure you want to delete the outlet "${btn.getAttribute('data-outlet-name')}"? This action cannot be undone.`;
            deleteForm.querySelector('[name="delete_outlet"]')?.remove();
            let delInput = document.createElement('input');
            delInput.type = 'hidden';
            delInput.name = 'delete_outlet';
            delInput.value = '1';
            deleteForm.appendChild(delInput);
            deleteModal.show();
        });
    });

    document.getElementById('outletModal').addEventListener('hidden.bs.modal', function () {
        outletForm.reset();
        nameValidationIcon.innerHTML = '';
        addressValidationIcon.innerHTML = '';
        nameError.textContent = '';
        addressError.textContent = '';
        validName = false;
        validAddress = false;
        saveOutletBtn.disabled = true;
        outletForm.querySelector('[name="add_outlet"]')?.remove();
        outletForm.querySelector('[name="edit_outlet"]')?.remove();
        reloadOutletNames();
    });

    outletForm.addEventListener('submit', function (e) {
        outletNameInput.dispatchEvent(new Event('input'));
        outletAddressInput.dispatchEvent(new Event('input'));
        if (!(validName && validAddress)) {
            e.preventDefault();
            window.showNotification('error', 'Validation Error', 'Please fix the errors before saving.', 'OK');
            return;
        }
        saveOutletBtn.disabled = true;
        saveOutletBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    });

    deleteForm.addEventListener('submit', function () {});
    document.addEventListener('table-refresh', reloadOutletNames);
});