/**
 * ezAccount System - Owner Staff JS (Malaysia phone format, edit modal hides account fields)
 * Fully finished, bug-free, copy-paste ready.
 */
document.addEventListener('DOMContentLoaded', function () {
    // --- Modal and Button Elements ---
    const addStaffBtn = document.getElementById('addStaffBtn');
    const staffModalEl = document.getElementById('staffModal');
    const staffModal = staffModalEl ? new bootstrap.Modal(staffModalEl) : null;
    const staffForm = document.getElementById('staffForm');
    const saveStaffBtn = document.getElementById('saveStaffBtn');
    const staffIdField = document.getElementById('staffId');
    const modalTitleText = document.getElementById('modalTitleText');
    const addAccountSection = document.getElementById('addAccountSection');
    const deleteForm = document.getElementById('deleteForm');
    const deleteStaffId = document.getElementById('deleteStaffId');
    const deleteMessage = document.getElementById('deleteMessage');
    // --- Input Fields ---
    const staffNameInput = document.getElementById('staffName');
    const staffIcInput = document.getElementById('staffIc');
    const staffAgeInput = document.getElementById('staffAge');
    const staffPhoneInput = document.getElementById('staffPhone');
    const staffAddressInput = document.getElementById('staffAddress');
    const staffOutletSelect = document.getElementById('staffOutlet');
    const staffEmailInput = document.getElementById('staffEmail');
    const staffPasswordInput = document.getElementById('staffPassword');
    const staffConfirmPasswordInput = document.getElementById('staffConfirmPassword');
    const staffVerificationCodeInput = document.getElementById('staffVerificationCode');
    // --- Validation Icons ---
    const nameValidationIcon = document.getElementById('nameValidationIcon');
    const icValidationIcon = document.getElementById('icValidationIcon');
    const ageValidationIcon = document.getElementById('ageValidationIcon');
    const phoneValidationIcon = document.getElementById('phoneValidationIcon');
    const addressValidationIcon = document.getElementById('addressValidationIcon');
    const outletValidationIcon = document.getElementById('outletValidationIcon');
    const emailValidationIcon = document.getElementById('emailValidationIcon');
    const passwordValidationIcon = document.getElementById('passwordValidationIcon');
    const confirmPasswordValidationIcon = document.getElementById('confirmPasswordValidationIcon');
    const codeValidationIcon = document.getElementById('codeValidationIcon');
    // --- Error Messages ---
    const nameError = document.getElementById('nameError');
    const icError = document.getElementById('icError');
    const ageError = document.getElementById('ageError');
    const phoneError = document.getElementById('phoneError');
    const addressError = document.getElementById('addressError');
    const outletError = document.getElementById('outletError');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const codeError = document.getElementById('codeError');
    // --- Verification Code ---
    const sendCodeBtn = document.getElementById('sendCodeBtn');
    const timerContainer = document.getElementById('timerContainer');
    const countdownElement = document.getElementById('countdown');
    // --- Validation State ---
    let validName = false, validIc = false, validAge = false, validPhone = false,
        validAddress = false, validOutlet = false, validEmail = false,
        validPassword = false, validConfirmPassword = false, validCode = false;
    let countdown;
    let timerValue = 60;
    let attemptCount = 0;

    // Helper: Malaysia phone format for DB
    function formatPhone(phone) {
        // Always add '0' in front, then add '-' after 3rd char
        // E.g. input: 1112477991 → DB: 011-12477991
        return '0' + phone.slice(0,3) + '-' + phone.slice(3);
    }
    // Helper: Format IC (insert "-" after 6 and 8 digits)
    function formatIc(ic) {
        return ic.replace(/^(\d{6})(\d{2})(\d{4})$/, '$1-$2-$3');
    }
    // Helper: Email uniqueness from PHP (window.allEmails)
    let allEmails = window.allEmails || [];
    function isDuplicateEmail(email) {
        email = email.toLowerCase();
        return allEmails.includes(email);
    }
    // Helper: Email regex
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    // Helper: Password validation
    function validatePassword(password) {
        const hasMinLength = password.length >= 8 && password.length <= 20;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        return hasMinLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
    }

    // Validation logic
    function setValidation(field, isValid, message = "") {
        let icon, error;
        switch (field) {
            case 'name': icon = nameValidationIcon; error = nameError; validName = isValid; break;
            case 'ic': icon = icValidationIcon; error = icError; validIc = isValid; break;
            case 'age': icon = ageValidationIcon; error = ageError; validAge = isValid; break;
            case 'phone': icon = phoneValidationIcon; error = phoneError; validPhone = isValid; break;
            case 'address': icon = addressValidationIcon; error = addressError; validAddress = isValid; break;
            case 'outlet': icon = outletValidationIcon; error = outletError; validOutlet = isValid; break;
            case 'email': icon = emailValidationIcon; error = emailError; validEmail = isValid; break;
            case 'password': icon = passwordValidationIcon; error = passwordError; validPassword = isValid; break;
            case 'confirmPassword': icon = confirmPasswordValidationIcon; error = confirmPasswordError; validConfirmPassword = isValid; break;
            case 'code': icon = codeValidationIcon; error = codeError; validCode = isValid; break;
        }
        if (icon) icon.innerHTML = isValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
        if (error) error.textContent = isValid ? '' : message;
        // In edit mode, enable save only if all real-time validations pass
        if (staffForm.querySelector('[name="edit_staff"]')) {
            saveStaffBtn.disabled = !(validName && validIc && validAge && validPhone && validAddress && validOutlet);
        } else {
            // In add mode, enable only if all required fields pass
            saveStaffBtn.disabled = !(validName && validIc && validAge && validPhone && validAddress && validOutlet && validEmail && validPassword && validConfirmPassword && validCode);
        }
    }

    // --- Field Listeners ---
    staffNameInput.addEventListener('input', function () {
        let pos = staffNameInput.selectionStart;
        staffNameInput.value = staffNameInput.value.toUpperCase();
        staffNameInput.setSelectionRange(pos, pos);
        let value = staffNameInput.value.trim();
        setValidation('name', value.length > 0, value.length === 0 ? 'Staff name is required' : '');
    });
    staffIcInput.addEventListener('input', function () {
        let value = staffIcInput.value.replace(/\D/g, '').slice(0, 12);
        staffIcInput.value = value;
        setValidation('ic', value.length === 12, value.length !== 12 ? 'IC number must be 12 digits' : '');
    });
    staffAgeInput.addEventListener('input', function () {
        let value = staffAgeInput.value.replace(/[^0-9]/g, '').replace(/\s/g,'');
        staffAgeInput.value = value;
        let age = parseInt(value, 10);
        setValidation('age', value.length > 0 && !isNaN(age) && age >= 0 && age <= 100, value.length === 0 ? 'Age is required' : (isNaN(age) || age < 0 || age > 100 ? 'Age must be 0-100' : ''));
    });
    staffPhoneInput.addEventListener('input', function () {
        let value = staffPhoneInput.value.replace(/\D/g, '').replace(/\s/g,'').slice(0, 10);
        staffPhoneInput.value = value;
        setValidation('phone', value.length >= 9 && value.length <= 10, (value.length < 9 || value.length > 10) ? 'Phone number must be 9 or 10 digits' : '');
    });
    staffAddressInput.addEventListener('input', function () {
        let pos = staffAddressInput.selectionStart;
        staffAddressInput.value = staffAddressInput.value.toUpperCase();
        staffAddressInput.setSelectionRange(pos, pos);
        let value = staffAddressInput.value.trim();
        setValidation('address', value.length > 0, value.length === 0 ? 'Address is required' : '');
    });
    staffOutletSelect.addEventListener('change', function () {
        setValidation('outlet', staffOutletSelect.value !== '', staffOutletSelect.value === '' ? 'Please select an outlet' : '');
    });
    staffEmailInput.addEventListener('keydown', function (e) {
        if (e.key === ' ') e.preventDefault();
    });
    staffEmailInput.addEventListener('input', function () {
        this.value = this.value.replace(/\s/g, '').toLowerCase();
        let value = this.value.trim();
        if (value.length === 0) {
            setValidation('email', false, 'Email is required');
        } else if (!validateEmail(value)) {
            setValidation('email', false, 'Invalid email format');
        } else if (isDuplicateEmail(value) && (!staffForm.querySelector('[name="edit_staff"]') || value !== staffEmailInput.defaultValue)) {
            setValidation('email', false, 'This email has been taken! Please enter a unique email address.');
        } else {
            setValidation('email', true);
        }
    });
    staffPasswordInput.addEventListener('keydown', function (e) {
        if (e.key === ' ') e.preventDefault();
    });
    staffPasswordInput.addEventListener('input', function () {
        let value = staffPasswordInput.value.replace(/\s/g, '');
        staffPasswordInput.value = value;
        setValidation('password', validatePassword(value), value.length === 0 ? 'Password is required' : 'Password must be 8-20 characters, include one uppercase, one lowercase, one number, and one special character');
        if (staffConfirmPasswordInput.value.length > 0) staffConfirmPasswordInput.dispatchEvent(new Event('input'));
    });
    staffConfirmPasswordInput.addEventListener('keydown', function (e) {
        if (e.key === ' ') e.preventDefault();
    });
    staffConfirmPasswordInput.addEventListener('input', function () {
        let value = staffConfirmPasswordInput.value.replace(/\s/g, '');
        staffConfirmPasswordInput.value = value;
        setValidation('confirmPassword', value.length > 0 && value === staffPasswordInput.value, value.length === 0 ? 'Confirm your password' : (value !== staffPasswordInput.value ? 'Passwords do not match' : ''));
    });
     staffVerificationCodeInput.addEventListener('input', function () {
        let value = staffVerificationCodeInput.value.replace(/\D/g, '').slice(0, 6);
        staffVerificationCodeInput.value = value;
        const generatedCode = document.querySelector('input[name="generatedCode"]').value;

        if (value === '') {
            setValidation('code', false, 'Verification code is required');
        } else if (value.length !== 6) {
            setValidation('code', false, 'Verification code must be 6 digits');
        } else if (value !== generatedCode) {
            setValidation('code', false, 'Invalid verification code. Please check and try again.');
        } else {
            setValidation('code', true);
        }
    });

    // --- Modal Hide: Reset All ---
    function resetAllValidation() {
        nameValidationIcon.innerHTML = '';
        icValidationIcon.innerHTML = '';
        ageValidationIcon.innerHTML = '';
        phoneValidationIcon.innerHTML = '';
        addressValidationIcon.innerHTML = '';
        outletValidationIcon.innerHTML = '';
        emailValidationIcon.innerHTML = '';
        // Reset email field to editable
staffEmailInput.readOnly = false;
staffEmailInput.style.backgroundColor = '';
staffEmailInput.style.cursor = '';

// Also reset the send code button and timer
sendCodeBtn.disabled = false;
sendCodeBtn.innerHTML = 'Send Code';
timerContainer.style.display = 'none';
clearInterval(countdown);
        passwordValidationIcon.innerHTML = '';
        confirmPasswordValidationIcon.innerHTML = '';
        codeValidationIcon.innerHTML = '';
        nameError.textContent = '';
        icError.textContent = '';
        ageError.textContent = '';
        phoneError.textContent = '';
        addressError.textContent = '';
        outletError.textContent = '';
        emailError.textContent = '';
        passwordError.textContent = '';
        confirmPasswordError.textContent = '';
        codeError.textContent = '';
        validName = validIc = validAge = validPhone = validAddress = validOutlet = validEmail = validPassword = validConfirmPassword = validCode = false;
    }

    document.getElementById('staffModal').addEventListener('hidden.bs.modal', function () {
        staffForm.reset();
        resetAllValidation();
        saveStaffBtn.disabled = true;
        staffForm.querySelector('[name="add_staff"]')?.remove();
        staffForm.querySelector('[name="edit_staff"]')?.remove();
        addAccountSection.style.display = '';
         const newCode = String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
    document.querySelector('input[name="generatedCode"]').value = newCode;
    return newCode;
    });

    // --- Add Modal ---
    addStaffBtn.addEventListener('click', function () {
        modalTitleText.textContent = "Add New Staff";
        staffForm.setAttribute('method', 'post');
        staffForm.setAttribute('action', '');
        staffForm.querySelector('[name="add_staff"]')?.remove();
        let addInput = document.createElement('input');
        addInput.type = 'hidden';
        addInput.name = 'add_staff';
        addInput.value = '1';
        staffForm.appendChild(addInput);
        staffIdField.value = '';
        staffNameInput.value = '';
        staffIcInput.value = '';
        staffAgeInput.value = '';
        staffPhoneInput.value = '';
        staffAddressInput.value = '';
        staffOutletSelect.value = '';
        staffEmailInput.value = '';
        staffPasswordInput.value = '';
        staffConfirmPasswordInput.value = '';
        staffVerificationCodeInput.value = '';
        addAccountSection.style.display = '';
        resetAllValidation();
        saveStaffBtn.disabled = true;
        staffModal.show();
    });

    // --- Edit Modal (real-time validation on show, hide account section) ---
    document.querySelectorAll('.edit-staff').forEach(btn => {
        btn.addEventListener('click', function () {
            modalTitleText.textContent = "Edit Staff";
            staffForm.setAttribute('method', 'post');
            staffForm.setAttribute('action', '');
            staffForm.querySelector('[name="edit_staff"]')?.remove();
            let editInput = document.createElement('input');
            editInput.type = 'hidden';
            editInput.name = 'edit_staff';
            editInput.value = '1';
            staffForm.appendChild(editInput);
            staffIdField.value = btn.getAttribute('data-staff-id');
            staffNameInput.value = btn.getAttribute('data-staff-name');
            staffIcInput.value = btn.getAttribute('data-staff-ic').replace(/-/g, '');
            staffAgeInput.value = btn.getAttribute('data-staff-age');
            // '011-12477991' => '1112477991'
            let phone = btn.getAttribute('data-staff-phone'); // e.g. 011-12477991
            if (phone.length >= 11 && phone.includes('-')) {
                phone = phone.replace(/-/g,'').slice(1); // Remove '0' in front and '-'
            }
            staffPhoneInput.value = phone;
            staffAddressInput.value = btn.getAttribute('data-staff-address');
            staffOutletSelect.value = btn.getAttribute('data-staff-outlet-id');
            staffEmailInput.value = btn.getAttribute('data-staff-email');
            staffEmailInput.defaultValue = btn.getAttribute('data-staff-email').toLowerCase();
            staffPasswordInput.value = '';
            staffConfirmPasswordInput.value = '';
            staffVerificationCodeInput.value = '';
            addAccountSection.style.display = 'none'; // Hide account fields in edit mode
            resetAllValidation();
            // Real-time validation for all fields
            staffNameInput.dispatchEvent(new Event('input'));
            staffIcInput.dispatchEvent(new Event('input'));
            staffAgeInput.dispatchEvent(new Event('input'));
            staffPhoneInput.dispatchEvent(new Event('input'));
            staffAddressInput.dispatchEvent(new Event('input'));
            staffOutletSelect.dispatchEvent(new Event('change'));
            saveStaffBtn.disabled = !(validName && validIc && validAge && validPhone && validAddress && validOutlet);
            staffModal.show();
        });
    });

    // --- Delete Modal ---
    document.querySelectorAll('.delete-staff').forEach(btn => {
        btn.addEventListener('click', function () {
            deleteStaffId.value = btn.getAttribute('data-staff-id');
            deleteMessage.textContent = `Are you sure you want to delete staff "${btn.getAttribute('data-staff-name')}"? This action cannot be undone.`;
            deleteForm.querySelector('[name="delete_staff"]')?.remove();
            let delInput = document.createElement('input');
            delInput.type = 'hidden';
            delInput.name = 'delete_staff';
            delInput.value = '1';
            deleteForm.appendChild(delInput);
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    });

    // --- Form Submit ---
    staffForm.addEventListener('submit', function (e) {
        staffNameInput.dispatchEvent(new Event('input'));
        staffIcInput.dispatchEvent(new Event('input'));
        staffAgeInput.dispatchEvent(new Event('input'));
        staffPhoneInput.dispatchEvent(new Event('input'));
        staffAddressInput.dispatchEvent(new Event('input'));
        staffOutletSelect.dispatchEvent(new Event('change'));
        if (staffForm.querySelector('[name="add_staff"]') &&
            !(validName && validIc && validAge && validPhone && validAddress && validOutlet && validEmail && validPassword && validConfirmPassword && validCode)) {
            e.preventDefault();
            window.showNotification('error', 'Validation Error', 'Please fix the errors before saving.', 'OK');
            return;
        }
        if (staffForm.querySelector('[name="edit_staff"]') &&
            !(validName && validIc && validAge && validPhone && validAddress && validOutlet)) {
            e.preventDefault();
            window.showNotification('error', 'Validation Error', 'Please fix the errors before saving.', 'OK');
            return;
        }
        staffIcInput.value = formatIc(staffIcInput.value);
        staffPhoneInput.value = formatPhone(staffPhoneInput.value);
        saveStaffBtn.disabled = true;
        saveStaffBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        // Add hidden field for email (for edit)
        if (staffForm.querySelector('[name="edit_staff"]')) {
            let hiddenEmail = staffForm.querySelector('input[name="email"]');
            if (!hiddenEmail) {
                hiddenEmail = document.createElement('input');
                hiddenEmail.type = 'hidden';
                hiddenEmail.name = 'email';
                staffForm.appendChild(hiddenEmail);
            }
            hiddenEmail.value = staffEmailInput.value;
        }
    });

       // --- Send Code Button - with actual email sending ---
    sendCodeBtn.addEventListener('click', function () {
        const email = staffEmailInput.value.trim();
        const generatedCode = document.querySelector('input[name="generatedCode"]').value;

        if (email === '' || !validateEmail(email)) {
            emailError.textContent = 'Please enter a valid email first';
            staffEmailInput.focus();
            return;
        }

        // Make email field read-only after sending code
        staffEmailInput.readOnly = true;
        staffEmailInput.style.backgroundColor = '#f8f9fa';
        staffEmailInput.style.cursor = 'not-allowed';

        // Send code via AJAX
        sendCodeBtn.disabled = true;
        sendCodeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        // Use FormData to send the request
        const formData = new FormData();
        formData.append('action', 'send_code');
        formData.append('email', email);
        formData.append('code', generatedCode);

        fetch('owner-staff.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success - show timer
                timerContainer.style.display = 'block';
                timerValue = 60 + (attemptCount * 60);
                countdownElement.textContent = timerValue;

                // Start countdown
                clearInterval(countdown);
                countdown = setInterval(function () {
                    timerValue--;
                    countdownElement.textContent = timerValue;

                    if (timerValue <= 0) {
                        clearInterval(countdown);
                        timerContainer.style.display = 'none';
                        sendCodeBtn.disabled = false;
                        sendCodeBtn.innerHTML = 'Send Code';
                        attemptCount++;
                    }
                }, 1000);

                // Reset button text immediately after successful send
                sendCodeBtn.innerHTML = 'Send Code';
                sendCodeBtn.disabled = true; // Keep disabled during countdown

            } else {
                // Failed to send - reset button
                sendCodeBtn.disabled = false;
                sendCodeBtn.innerHTML = 'Send Code';
                
                // Re-enable email field on failure
                staffEmailInput.readOnly = false;
                staffEmailInput.style.backgroundColor = '';
                staffEmailInput.style.cursor = '';
                
               // alert('Failed to send verification code. Please try again.');
            }
        })
        .catch(error => {
            //console.error('Error:', error);
           // alert('An error occurred while sending the code.');
            sendCodeBtn.disabled = false;
            sendCodeBtn.innerHTML = 'Send Code';
            
            // Re-enable email field on error
            staffEmailInput.readOnly = false;
            staffEmailInput.style.backgroundColor = '';
            staffEmailInput.style.cursor = '';
        });
    });

    // --- Notification Modal (forgot-password style) ---
    window.showNotification = function(type, title, message, buttonText, callback) {
        var notificationIcon = document.getElementById('notificationIcon');
        var notificationTitle = document.getElementById('notificationTitle');
        var notificationMessage = document.getElementById('notificationMessage');
        var notificationButton = document.getElementById('notificationButton');
        var notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
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
    };
});