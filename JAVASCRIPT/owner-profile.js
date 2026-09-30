// Profile Customization JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Modal instances
    const changeEmailModal = new bootstrap.Modal(document.getElementById('changeEmailModal'));
    const changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));

    // Clear email modal validation when hidden
document.getElementById('changeEmailModal').addEventListener('hidden.bs.modal', function() {
    // Clear error messages
    document.getElementById('currentPasswordEmailError').textContent = '';
    document.getElementById('newEmailError').textContent = '';
    document.getElementById('emailCodeError').textContent = '';
    
    // Clear validation icons (use null to clear completely)
    updateValidationIcon(document.getElementById('currentPasswordEmailValidationIcon'), null);
    updateValidationIcon(document.getElementById('newEmailValidationIcon'), null);
    updateValidationIcon(document.getElementById('emailCodeValidationIcon'), null);
    
    // Reset validation status
    emailValidationStatus.currentPasswordEmail = false;
    emailValidationStatus.newEmail = false;
    emailValidationStatus.emailCode = false;
    
    // Reset timer and enable send code button
    clearInterval(emailCountdownTimer);
    emailTimerContainer.style.display = 'none';
    sendEmailCodeBtn.disabled = false;
    sendEmailCodeBtn.innerHTML = 'Send Code';
    
    // Re-enable email field
    newEmailInput.readOnly = false;
    newEmailInput.style.backgroundColor = '';
    newEmailInput.style.cursor = '';
    
    // Reset password toggle to default (password hidden, eye icon)
    const currentPasswordEmailInput = document.getElementById('currentPasswordEmail');
    const currentPasswordEmailToggle = document.querySelector('[data-target="currentPasswordEmail"]');
    
    if (currentPasswordEmailInput && currentPasswordEmailToggle) {
        currentPasswordEmailInput.type = 'password';
        const eyeIcon = currentPasswordEmailToggle.querySelector('i');
        if (eyeIcon) {
            eyeIcon.className = 'fas fa-eye';
        }
    }
    
    // Update button state
    checkEmailFormValidity();
});


// Clear password modal validation when hidden
document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
    // Clear error messages
    document.getElementById('currentPasswordError').textContent = '';
    document.getElementById('newPasswordError').textContent = '';
    document.getElementById('confirmPasswordError').textContent = '';
    
    // Clear validation icons (use null to clear completely)
    updateValidationIcon(document.getElementById('currentPasswordValidationIcon'), null);
    updateValidationIcon(document.getElementById('newPasswordValidationIcon'), null);
    updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), null);
    
    // Reset validation status
    passwordValidationStatus.currentPassword = false;
    passwordValidationStatus.newPassword = false;
    passwordValidationStatus.confirmPassword = false;
    
    // Reset password requirements indicators
    updateRequirementIndicator('req-length', false);
    updateRequirementIndicator('req-uppercase', false);
    updateRequirementIndicator('req-lowercase', false);
    updateRequirementIndicator('req-number', false);
    updateRequirementIndicator('req-special', false);
    
    // Reset all password toggles to default (password hidden, eye icon)
    const passwordFields = ['currentPassword', 'newPassword', 'confirmNewPassword'];
    
    passwordFields.forEach(fieldId => {
        const passwordInput = document.getElementById(fieldId);
        const passwordToggle = document.querySelector(`[data-target="${fieldId}"]`);
        
        if (passwordInput && passwordToggle) {
            passwordInput.type = 'password';
            const eyeIcon = passwordToggle.querySelector('i');
            if (eyeIcon) {
                eyeIcon.className = 'fas fa-eye';
            }
        }
    });
    
    // Update button state
    checkPasswordFormValidity();
});
    
    
    // Get form elements
    const profileForm = document.getElementById('profileForm');
    const fullNameInput = document.getElementById('fullName');
    const phoneNumberInput = document.getElementById('phoneNumber');
    const companyNameInput = document.getElementById('companyName');
    const updateBtn = document.getElementById('updateBtn');
    
    // Get modal buttons
    const changeEmailBtn = document.getElementById('changeEmailBtn');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    
    // Validation icon containers
    const nameValidationIcon = document.getElementById('nameValidationIcon');
    const phoneValidationIcon = document.getElementById('phoneValidationIcon');
    const companyValidationIcon = document.getElementById('companyValidationIcon');
    
    // Error message elements
    const nameError = document.getElementById('nameError');
    const phoneError = document.getElementById('phoneError');
    const companyError = document.getElementById('companyError');
    
    // Email modal elements
    const currentPasswordEmailInput = document.getElementById('currentPasswordEmail');
    const newEmailInput = document.getElementById('newEmail');
    const emailVerificationCodeInput = document.getElementById('emailVerificationCode');
    const sendEmailCodeBtn = document.getElementById('sendEmailCodeBtn');
    const updateEmailBtn = document.getElementById('updateEmailBtn');
    
    // Password modal elements
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
    const updatePasswordBtn = document.getElementById('updatePasswordBtn');
    
    // Timer elements
    const emailTimerContainer = document.getElementById('emailTimerContainer');
    const emailCountdown = document.getElementById('emailCountdown');
    
    let emailCountdownTimer;
    let emailTimerValue = 60;
    let emailAttemptCount = 0;
    
    // Validation status
    const validationStatus = {
        name: true,
        phone: true,
        company: true
    };
    
    const emailValidationStatus = {
        currentPasswordEmail: false,
        newEmail: false,
        emailCode: false
    };
    
    const passwordValidationStatus = {
        currentPassword: false,
        newPassword: false,
        confirmPassword: false
    };
    
    // Name validation function
    function validateName(name) {
        const trimmedName = name.trim();
        return /^[a-zA-Z\s.'-]+$/.test(trimmedName) && trimmedName.length >= 1;
    }
    
    // Phone validation function
    function validatePhone(phone) {
        return /^\d{9,10}$/.test(phone);
    }
    
    // Company name validation function
    function validateCompany(company) {
        const trimmedCompany = company.trim();
        return trimmedCompany.length >= 1;
    }
    
    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
   // Password validation function - REAL TIME
function validatePassword(password) {
    // Always evaluate all requirements based on current password
    const requirements = {
        length: password.length >= 8 && password.length <= 20,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };

    // Update ALL indicators in real-time
    updateRequirementIndicator('req-length', requirements.length);
    updateRequirementIndicator('req-uppercase', requirements.uppercase);
    updateRequirementIndicator('req-lowercase', requirements.lowercase);
    updateRequirementIndicator('req-number', requirements.number);
    updateRequirementIndicator('req-special', requirements.special);

    // Return overall validity
    return requirements.length && requirements.uppercase && 
           requirements.lowercase && requirements.number && requirements.special;
}

// Update requirement indicator - SIMPLIFIED
function updateRequirementIndicator(elementId, isValid) {
    const element = document.getElementById(elementId);
    if (element && element.querySelector('i')) {
        const icon = element.querySelector('i');
        if (isValid) {
            icon.className = 'fas fa-check-circle';
            icon.style.color = '#28a745';
        } else {
            icon.className = 'fas fa-times-circle';
            icon.style.color = '#dc3545';
        }
    }
}
    
// Enhanced updateValidationIcon function with clear option
function updateValidationIcon(iconElement, isValid) {
    if (iconElement) {
        if (isValid === true) {
            iconElement.innerHTML = '<i class="fas fa-check-circle"></i>';
            iconElement.style.color = '#28a745';
        } else if (isValid === false) {
            iconElement.innerHTML = '<i class="fas fa-times-circle"></i>';
            iconElement.style.color = '#dc3545';
        } else {
            // Clear the icon completely
            iconElement.innerHTML = '';
            iconElement.style.color = '';
        }
    }
}
    
    // Check if all fields are valid
    function checkFormValidity() {
        const allValid = Object.values(validationStatus).every(status => status === true);
        updateBtn.disabled = !allValid;
    }
    
    function checkEmailFormValidity() {
        const allValid = Object.values(emailValidationStatus).every(status => status === true);
        updateEmailBtn.disabled = !allValid;
    }
    
    function checkPasswordFormValidity() {
        const allValid = Object.values(passwordValidationStatus).every(status => status === true);
        updatePasswordBtn.disabled = !allValid;
    }
    
    // Show notification modal
    function showNotification(type, title, message, buttonText) {
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationTitle = document.getElementById('notificationTitle');
        const notificationMessage = document.getElementById('notificationMessage');
        const notificationButton = document.getElementById('notificationButton');
        
        // Set icon based on notification type
        notificationIcon.className = 'notification-icon';
        if (type === 'success') {
            notificationIcon.classList.add('success');
            notificationIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
        } else if (type === 'error') {
            notificationIcon.classList.add('error');
            notificationIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
        }
        
        // Set title and message
        notificationTitle.textContent = title;
        notificationMessage.textContent = message;
        
        // Set button text
        notificationButton.textContent = buttonText;
        
        // Remove any existing click events
        notificationButton.replaceWith(notificationButton.cloneNode(true));
        const newButton = document.getElementById('notificationButton');
        
        // Add event listener
        newButton.addEventListener('click', function() {
            notificationModal.hide();
        });
        
        // Show the modal
        notificationModal.show();
    }
    
    // Send verification code
    function sendVerificationCode(email, code, button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        // Make email field read-only
        newEmailInput.readOnly = true;
        newEmailInput.style.backgroundColor = '#f8f9fa';
        newEmailInput.style.cursor = 'not-allowed';
        
        const formData = new FormData();
        formData.append('action', 'send_code');
        formData.append('email', email);
        formData.append('code', code);
        
        fetch('owner-profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                startEmailTimer();
            } else {
                showNotification('error', 'Failed to Send', 'Failed to send verification code. Please try again.', 'OK');
                // Re-enable email field on failure
                newEmailInput.readOnly = false;
                newEmailInput.style.backgroundColor = '';
                newEmailInput.style.cursor = '';
            }
            button.innerHTML = 'Send Code';
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error', 'An error occurred while sending the code.', 'OK');
            button.innerHTML = 'Send Code';
            button.disabled = false;
            // Re-enable email field on error
            newEmailInput.readOnly = false;
            newEmailInput.style.backgroundColor = '';
            newEmailInput.style.cursor = '';
        });
    }
    
    // Start email timer
    function startEmailTimer() {
        emailTimerContainer.style.display = 'block';
        emailTimerValue = 60 + (emailAttemptCount * 60);
        emailCountdown.textContent = emailTimerValue;
        
        clearInterval(emailCountdownTimer);
        emailCountdownTimer = setInterval(function() {
            emailTimerValue--;
            emailCountdown.textContent = emailTimerValue;
            
            if (emailTimerValue <= 0) {
                clearInterval(emailCountdownTimer);
                emailTimerContainer.style.display = 'none';
                sendEmailCodeBtn.disabled = false;
                emailAttemptCount++;
                
                // Re-enable email field when timer expires
                newEmailInput.readOnly = false;
                newEmailInput.style.backgroundColor = '';
                newEmailInput.style.cursor = '';
            }
        }, 1000);
        
        sendEmailCodeBtn.disabled = true;
    }
    
    // Initialize validation on page load
    function initializeValidation() {
        // Trigger validation for all fields
        fullNameInput.dispatchEvent(new Event('input'));
        phoneNumberInput.dispatchEvent(new Event('input'));
        companyNameInput.dispatchEvent(new Event('input'));
    }
    
    // Name validation - prevent numbers
    fullNameInput.addEventListener('input', function() {
        // Remove numbers from the input
        this.value = this.value.replace(/[0-9]/g, '');
        
        // Remove leading and trailing spaces for validation
        const name = this.value.trim();
        
        if (name === '') {
            nameError.textContent = 'Full name is required';
            updateValidationIcon(nameValidationIcon, false);
            validationStatus.name = false;
        } else if (!validateName(name)) {
            nameError.textContent = 'Name should only contain letters and spaces (no numbers allowed)';
            updateValidationIcon(nameValidationIcon, false);
            validationStatus.name = false;
        } else {
            nameError.textContent = '';
            updateValidationIcon(nameValidationIcon, true);
            validationStatus.name = true;
        }
        
        checkFormValidity();
    });
    
    // Force uppercase for full name
    fullNameInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Phone number validation - only allow numbers
    phoneNumberInput.addEventListener('input', function() {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
        
        const phone = this.value;
        
        if (phone === '') {
            phoneError.textContent = 'Phone number is required';
            updateValidationIcon(phoneValidationIcon, false);
            validationStatus.phone = false;
        } else if (!validatePhone(phone)) {
            phoneError.textContent = 'Please enter a valid Malaysian phone number (9-10 digits)';
            updateValidationIcon(phoneValidationIcon, false);
            validationStatus.phone = false;
        } else {
            phoneError.textContent = '';
            updateValidationIcon(phoneValidationIcon, true);
            validationStatus.phone = true;
        }
        
        checkFormValidity();
    });
    
    // Company name validation - prevent empty
    companyNameInput.addEventListener('input', function() {
        // Remove leading and trailing spaces for validation
        const company = this.value.trim();
        
        if (company === '') {
            companyError.textContent = 'Company name is required';
            updateValidationIcon(companyValidationIcon, false);
            validationStatus.company = false;
        } else {
            companyError.textContent = '';
            updateValidationIcon(companyValidationIcon, true);
            validationStatus.company = true;
        }
        
        checkFormValidity();
    });
    
   // Change Email Button Click Handler - Enhanced
changeEmailBtn.addEventListener('click', function() {
    // Reset email form
    const emailForm = document.getElementById('changeEmailForm');
    emailForm.reset();
    
    // Force clear all error messages
    document.getElementById('currentPasswordEmailError').textContent = '';
    document.getElementById('newEmailError').textContent = '';
    document.getElementById('emailCodeError').textContent = '';
    
    // Force clear all validation icons
    updateValidationIcon(document.getElementById('currentPasswordEmailValidationIcon'), null);
    updateValidationIcon(document.getElementById('newEmailValidationIcon'), null);
    updateValidationIcon(document.getElementById('emailCodeValidationIcon'), null);
    
    // Reset validation status
    emailValidationStatus.currentPasswordEmail = false;
    emailValidationStatus.newEmail = false;
    emailValidationStatus.emailCode = false;
    
    // Reset timer and email field
    clearInterval(emailCountdownTimer);
    emailTimerContainer.style.display = 'none';
    sendEmailCodeBtn.disabled = false;
    sendEmailCodeBtn.innerHTML = 'Send Code';
    newEmailInput.readOnly = false;
    newEmailInput.style.backgroundColor = '';
    newEmailInput.style.cursor = '';
    
    // Reset password toggle to default
    const currentPasswordEmailInput = document.getElementById('currentPasswordEmail');
    const currentPasswordEmailToggle = document.querySelector('[data-target="currentPasswordEmail"]');
    
    if (currentPasswordEmailInput && currentPasswordEmailToggle) {
        currentPasswordEmailInput.type = 'password';
        const eyeIcon = currentPasswordEmailToggle.querySelector('i');
        if (eyeIcon) {
            eyeIcon.className = 'fas fa-eye';
        }
    }
    
    // Update button state
    checkEmailFormValidity();
    
    changeEmailModal.show();
});

// Change Password Button Click Handler - Enhanced
changePasswordBtn.addEventListener('click', function() {
    // Reset password form
    const passwordForm = document.getElementById('changePasswordForm');
    passwordForm.reset();
    
    // Force clear all error messages
    document.getElementById('currentPasswordError').textContent = '';
    document.getElementById('newPasswordError').textContent = '';
    document.getElementById('confirmPasswordError').textContent = '';
    
    // Force clear all validation icons
    updateValidationIcon(document.getElementById('currentPasswordValidationIcon'), null);
    updateValidationIcon(document.getElementById('newPasswordValidationIcon'), null);
    updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), null);
    
    // Reset validation status
    passwordValidationStatus.currentPassword = false;
    passwordValidationStatus.newPassword = false;
    passwordValidationStatus.confirmPassword = false;
    
    // Reset password requirements
    updateRequirementIndicator('req-length', false);
    updateRequirementIndicator('req-uppercase', false);
    updateRequirementIndicator('req-lowercase', false);
    updateRequirementIndicator('req-number', false);
    updateRequirementIndicator('req-special', false);
    
    // Reset all password toggles to default
    const passwordFields = ['currentPassword', 'newPassword', 'confirmNewPassword'];
    
    passwordFields.forEach(fieldId => {
        const passwordInput = document.getElementById(fieldId);
        const passwordToggle = document.querySelector(`[data-target="${fieldId}"]`);
        
        if (passwordInput && passwordToggle) {
            passwordInput.type = 'password';
            const eyeIcon = passwordToggle.querySelector('i');
            if (eyeIcon) {
                eyeIcon.className = 'fas fa-eye';
            }
        }
    });
    
    // Update button state
    checkPasswordFormValidity();
    
    changePasswordModal.show();
});
    
    // Current Password Email validation
    currentPasswordEmailInput.addEventListener('input', function() {
        const password = this.value;

        if (password === '') {
            document.getElementById('currentPasswordEmailError').textContent = 'Current password is required';
            updateValidationIcon(document.getElementById('currentPasswordEmailValidationIcon'), false);
            emailValidationStatus.currentPasswordEmail = false;
        } else {
            document.getElementById('currentPasswordEmailError').textContent = '';
            updateValidationIcon(document.getElementById('currentPasswordEmailValidationIcon'), true);
            emailValidationStatus.currentPasswordEmail = true;
        }
        
        checkEmailFormValidity();
    });
    
    // New Email validation
    newEmailInput.addEventListener('input', function() {
        // Remove any spaces that might have been pasted
        if (/\s/.test(this.value)) {
            this.value = this.value.replace(/\s/g, '');
        }

        // Convert to lowercase
        this.value = this.value.toLowerCase();

        // Remove leading and trailing spaces for validation
        const email = this.value.trim();

        if (email === '') {
            document.getElementById('newEmailError').textContent = 'Email is required';
            updateValidationIcon(document.getElementById('newEmailValidationIcon'), false);
            emailValidationStatus.newEmail = false;
        } else if (!validateEmail(email)) {
            document.getElementById('newEmailError').textContent = 'Please enter a valid email address';
            updateValidationIcon(document.getElementById('newEmailValidationIcon'), false);
            emailValidationStatus.newEmail = false;
        } else {
            document.getElementById('newEmailError').textContent = '';
            updateValidationIcon(document.getElementById('newEmailValidationIcon'), true);
            emailValidationStatus.newEmail = true;
        }
        
        checkEmailFormValidity();
    });
    
    // Email Code validation
    emailVerificationCodeInput.addEventListener('input', function() {
        const code = this.value.trim();
        this.value = this.value.replace(/[^0-9]/g, '');

        if (code === '') {
            document.getElementById('emailCodeError').textContent = 'Verification code is required';
            updateValidationIcon(document.getElementById('emailCodeValidationIcon'), false);
            emailValidationStatus.emailCode = false;
        } else if (code.length !== 6) {
            document.getElementById('emailCodeError').textContent = 'Verification code must be 6 digits';
            updateValidationIcon(document.getElementById('emailCodeValidationIcon'), false);
            emailValidationStatus.emailCode = false;
        } else {
            document.getElementById('emailCodeError').textContent = '';
            updateValidationIcon(document.getElementById('emailCodeValidationIcon'), true);
            emailValidationStatus.emailCode = true;
        }
        
        checkEmailFormValidity();
    });
    
    // Send Email Code
    sendEmailCodeBtn.addEventListener('click', function() {
        const newEmail = newEmailInput.value.trim();
        const currentPassword = currentPasswordEmailInput.value;
        const generatedCode = document.querySelector('input[name="email_generated_code"]').value;

        if (!emailValidationStatus.newEmail) {
            document.getElementById('newEmailError').textContent = 'Please enter a valid email address first';
            newEmailInput.focus();
            return;
        }

        if (!emailValidationStatus.currentPasswordEmail) {
            document.getElementById('currentPasswordEmailError').textContent = 'Please enter your current password first';
            currentPasswordEmailInput.focus();
            return;
        }

        sendVerificationCode(newEmail, generatedCode, this);
    });
    
    // Password validation
    currentPasswordInput.addEventListener('input', function() {
        const password = this.value;

        if (password === '') {
            document.getElementById('currentPasswordError').textContent = 'Current password is required';
            updateValidationIcon(document.getElementById('currentPasswordValidationIcon'), false);
            passwordValidationStatus.currentPassword = false;
        } else {
            document.getElementById('currentPasswordError').textContent = '';
            updateValidationIcon(document.getElementById('currentPasswordValidationIcon'), true);
            passwordValidationStatus.currentPassword = true;
        }
        
        checkPasswordFormValidity();
    });
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;

        // Remove any spaces that might have been pasted
        if (/\s/.test(password)) {
            this.value = password.replace(/\s/g, '');
        }

        if (password === '') {
            document.getElementById('newPasswordError').textContent = 'New password is required';
            updateValidationIcon(document.getElementById('newPasswordValidationIcon'), false);
            passwordValidationStatus.newPassword = false;
        } else {
            const isValid = validatePassword(password);
            document.getElementById('newPasswordError').textContent = isValid ? '' : 'Password does not meet all requirements';
            updateValidationIcon(document.getElementById('newPasswordValidationIcon'), isValid);
            passwordValidationStatus.newPassword = isValid;
        }

        // Also validate confirm password if it has value
        if (confirmNewPasswordInput.value !== '') {
            confirmNewPasswordInput.dispatchEvent(new Event('input'));
        }
        
        checkPasswordFormValidity();
    });
    
    confirmNewPasswordInput.addEventListener('input', function() {
        const confirmPassword = this.value;
        const password = newPasswordInput.value;

        // Remove any spaces that might have been pasted
        if (/\s/.test(confirmPassword)) {
            this.value = confirmPassword.replace(/\s/g, '');
        }

        if (confirmPassword === '') {
            document.getElementById('confirmPasswordError').textContent = 'Please confirm your password';
            updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), false);
            passwordValidationStatus.confirmPassword = false;
        } else if (confirmPassword !== password) {
            document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
            updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), false);
            passwordValidationStatus.confirmPassword = false;
        } else {
            document.getElementById('confirmPasswordError').textContent = '';
            updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), true);
            passwordValidationStatus.confirmPassword = true;
        }
        
        checkPasswordFormValidity();
    });
    
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                targetInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });
    
    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logoutModal.show();
    });
    
    document.getElementById('confirmLogout').addEventListener('click', function() {
        window.location.href = 'logout.php';
    });
    
    // Form submission handlers
    profileForm.addEventListener('submit', function(e) {
        if (!Object.values(validationStatus).every(status => status === true)) {
            e.preventDefault();
            showNotification('error', 'Validation Error', 'Please fix validation errors before submitting.', 'OK');
        }
    });
    
    document.getElementById('changeEmailForm').addEventListener('submit', function(e) {
        if (!Object.values(emailValidationStatus).every(status => status === true)) {
            e.preventDefault();
            showNotification('error', 'Validation Error', 'Please fix validation errors before submitting.', 'OK');
        }
    });
    
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        if (!Object.values(passwordValidationStatus).every(status => status === true)) {
            e.preventDefault();
            showNotification('error', 'Validation Error', 'Please fix validation errors before submitting.', 'OK');
        }
    });

   // Current Password Email validation - Prevent spaces using keydown
currentPasswordEmailInput.addEventListener('keydown', function(e) {
    // Prevent space key
    if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        return false;
    }
});

currentPasswordEmailInput.addEventListener('input', function() {
    // Remove any spaces that might have been pasted (fallback)
    if (/\s/.test(this.value)) {
        const cursorPosition = this.selectionStart;
        const newValue = this.value.replace(/\s/g, '');
        this.value = newValue;
        this.setSelectionRange(cursorPosition, cursorPosition);
    }

    const password = this.value;

    if (password === '') {
        document.getElementById('currentPasswordEmailError').textContent = 'Current password is required';
        updateValidationIcon(document.getElementById('currentPasswordEmailValidationIcon'), false);
        emailValidationStatus.currentPasswordEmail = false;
    } else {
        document.getElementById('currentPasswordEmailError').textContent = '';
        updateValidationIcon(document.getElementById('currentPasswordEmailValidationIcon'), true);
        emailValidationStatus.currentPasswordEmail = true;
    }
    
    checkEmailFormValidity();
});

// New Email validation - Prevent spaces using keydown
newEmailInput.addEventListener('keydown', function(e) {
    // Prevent space key
    if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        return false;
    }
});

newEmailInput.addEventListener('input', function() {
    // Remove any spaces that might have been pasted (fallback)
    if (/\s/.test(this.value)) {
        const cursorPosition = this.selectionStart;
        const newValue = this.value.replace(/\s/g, '');
        this.value = newValue;
        this.setSelectionRange(cursorPosition, cursorPosition);
    }

    // Convert to lowercase
    this.value = this.value.toLowerCase();

    // Remove leading and trailing spaces for validation
    const email = this.value.trim();

    if (email === '') {
        document.getElementById('newEmailError').textContent = 'Email is required';
        updateValidationIcon(document.getElementById('newEmailValidationIcon'), false);
        emailValidationStatus.newEmail = false;
    } else if (!validateEmail(email)) {
        document.getElementById('newEmailError').textContent = 'Please enter a valid email address';
        updateValidationIcon(document.getElementById('newEmailValidationIcon'), false);
        emailValidationStatus.newEmail = false;
    } else {
        document.getElementById('newEmailError').textContent = '';
        updateValidationIcon(document.getElementById('newEmailValidationIcon'), true);
        emailValidationStatus.newEmail = true;
    }
    
    checkEmailFormValidity();
});

// Current Password validation - Prevent spaces using keydown
currentPasswordInput.addEventListener('keydown', function(e) {
    // Prevent space key
    if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        return false;
    }
});

currentPasswordInput.addEventListener('input', function() {
    // Remove any spaces that might have been pasted (fallback)
    if (/\s/.test(this.value)) {
        const cursorPosition = this.selectionStart;
        const newValue = this.value.replace(/\s/g, '');
        this.value = newValue;
        this.setSelectionRange(cursorPosition, cursorPosition);
    }

    const password = this.value;

    if (password === '') {
        document.getElementById('currentPasswordError').textContent = 'Current password is required';
        updateValidationIcon(document.getElementById('currentPasswordValidationIcon'), false);
        passwordValidationStatus.currentPassword = false;
    } else {
        document.getElementById('currentPasswordError').textContent = '';
        updateValidationIcon(document.getElementById('currentPasswordValidationIcon'), true);
        passwordValidationStatus.currentPassword = true;
    }
    
    checkPasswordFormValidity();
});

// New Password validation - Prevent spaces using keydown
newPasswordInput.addEventListener('keydown', function(e) {
    // Prevent space key
    if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        return false;
    }
});

newPasswordInput.addEventListener('input', function() {
    const password = this.value;

    // Remove any spaces that might have been pasted
    if (/\s/.test(password)) {
        this.value = password.replace(/\s/g, '');
    }

    // ALWAYS validate password on every input (even empty)
    const isValid = validatePassword(this.value);
    
    if (this.value === '') {
        document.getElementById('newPasswordError').textContent = 'New password is required';
        updateValidationIcon(document.getElementById('newPasswordValidationIcon'), false);
        passwordValidationStatus.newPassword = false;
    } else {
        document.getElementById('newPasswordError').textContent = isValid ? '' : 'Password does not meet all requirements';
        updateValidationIcon(document.getElementById('newPasswordValidationIcon'), isValid);
        passwordValidationStatus.newPassword = isValid;
    }

    // Also validate confirm password if it has value
    if (confirmNewPasswordInput.value !== '') {
        confirmNewPasswordInput.dispatchEvent(new Event('input'));
    }
    
    checkPasswordFormValidity();
});

// Confirm New Password validation - Prevent spaces using keydown
confirmNewPasswordInput.addEventListener('keydown', function(e) {
    // Prevent space key
    if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        return false;
    }
});

confirmNewPasswordInput.addEventListener('input', function() {
    // Remove any spaces that might have been pasted (fallback)
    if (/\s/.test(this.value)) {
        const cursorPosition = this.selectionStart;
        const newValue = this.value.replace(/\s/g, '');
        this.value = newValue;
        this.setSelectionRange(cursorPosition, cursorPosition);
    }

    const confirmPassword = this.value;
    const password = newPasswordInput.value;

    if (confirmPassword === '') {
        document.getElementById('confirmPasswordError').textContent = 'Please confirm your password';
        updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), false);
        passwordValidationStatus.confirmPassword = false;
    } else if (confirmPassword !== password) {
        document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
        updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), false);
        passwordValidationStatus.confirmPassword = false;
    } else {
        document.getElementById('confirmPasswordError').textContent = '';
        updateValidationIcon(document.getElementById('confirmPasswordValidationIcon'), true);
        passwordValidationStatus.confirmPassword = true;
    }
    
    checkPasswordFormValidity();
});
    
    // Initialize validation on page load
    initializeValidation();
});

// Global function to show notification
function showNotification(type, title, message, buttonText) {
    const notificationModalElement = document.getElementById('notificationModal');
    const notificationModal = new bootstrap.Modal(notificationModalElement, {
        backdrop: 'static',
        keyboard: false
    });
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationTitle = document.getElementById('notificationTitle');
    const notificationMessage = document.getElementById('notificationMessage');
    const notificationButton = document.getElementById('notificationButton');

    notificationIcon.className = 'notification-icon';
    if (type === 'success') {
        notificationIcon.classList.add('success');
        notificationIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
    } else if (type === 'error') {
        notificationIcon.classList.add('error');
        notificationIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
    }

    notificationTitle.textContent = title;
    notificationMessage.textContent = message;
    notificationButton.textContent = buttonText;

    // Remove old listeners safely
    const newButton = notificationButton.cloneNode(true);
    notificationButton.parentNode.replaceChild(newButton, notificationButton);

    newButton.addEventListener('click', function() {
        notificationModal.hide();
    });

    notificationModal.show();
}