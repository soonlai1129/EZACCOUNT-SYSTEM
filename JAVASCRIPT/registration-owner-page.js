document.addEventListener('DOMContentLoaded', function () {
    // Get all input elements
    const fullNameInput = document.getElementById('fullName');
    const phoneNumberInput = document.getElementById('phoneNumber');
    const registerEmailInput = document.getElementById('registerEmail');
    const verificationCodeInput = document.getElementById('verificationCode');
    const registerPasswordInput = document.getElementById('registerPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const companyNameInput = document.getElementById('companyName');
    const registerBtn = document.getElementById('registerBtn');
    const sendCodeBtn = document.getElementById('sendCodeBtn');
    const timerContainer = document.getElementById('timerContainer');
    const countdownElement = document.getElementById('countdown');

    // Modal elements
    const notificationModalElement = document.getElementById('notificationModal');
    const notificationModal = new bootstrap.Modal(notificationModalElement, {
        backdrop: 'static',
        keyboard: false
    });
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationTitle = document.getElementById('notificationTitle');
    const notificationMessage = document.getElementById('notificationMessage');
    const notificationButton = document.getElementById('notificationButton');

    // Validation icon containers
    const nameValidationIcon = document.getElementById('nameValidationIcon');
    const phoneValidationIcon = document.getElementById('phoneValidationIcon');
    const emailValidationIcon = document.getElementById('emailValidationIcon');
    const codeValidationIcon = document.getElementById('codeValidationIcon');
    const passwordValidationIcon = document.getElementById('passwordValidationIcon');
    const confirmPasswordValidationIcon = document.getElementById('confirmPasswordValidationIcon');
    const companyValidationIcon = document.getElementById('companyValidationIcon');

    // Error message elements
    const nameError = document.getElementById('nameError');
    const phoneError = document.getElementById('phoneError');
    const emailError = document.getElementById('emailError');
    const codeError = document.getElementById('codeError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const companyError = document.getElementById('companyError');

    // Password requirement elements
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

    let countdown;
    let timerValue = 60;
    let attemptCount = 0;

    // Validation status
    const validationStatus = {
        name: false,
        phone: false,
        email: false,
        code: false,
        password: false,
        confirmPassword: false,
        company: false
    };

    // Function to show notification modal
    function showNotification(type, title, message, buttonText) {
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

        // Add appropriate event listener based on button text
        if (buttonText === 'Back to Login') {
            newButton.addEventListener('click', function () {
                window.location.href = 'index.php';
            });
        } else {
            newButton.addEventListener('click', function () {
                notificationModal.hide();

                // If it's a database error, reset the form
                if (message.includes('problem with our system') || message.includes('error creating your account')) {
                    resetForm();
                }

                // If it's an error about email already existing, reset email and verification fields
                if (message.includes('email address is already registered')) {
                    registerEmailInput.value = '';
                    verificationCodeInput.value = '';
                    registerEmailInput.readOnly = false;
                    registerEmailInput.style.backgroundColor = '';
                    registerEmailInput.style.cursor = '';
                    validationStatus.email = false;
                    validationStatus.code = false;
                    updateValidationIcon(emailValidationIcon, false);
                    updateValidationIcon(codeValidationIcon, false);
                    checkFormValidity();
                }
            });
        }

        // Show the modal
        notificationModal.show();

        // If registration was successful, disable the form
        if (type === 'success') {
            disableForm();
        }
    }

    // Function to reset the form
    function resetForm() {
        document.getElementById('registrationForm').reset();

        // Reset validation status
        for (let key in validationStatus) {
            validationStatus[key] = false;
        }

        // Reset validation icons
        const validationIcons = document.querySelectorAll('.validation-icon-container');
        validationIcons.forEach(icon => {
            icon.innerHTML = '';
        });

        // Reset error messages
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => {
            error.textContent = '';
        });

        // Reset password requirements
        const requirementIcons = document.querySelectorAll('.requirement-list i');
        requirementIcons.forEach(icon => {
            icon.className = 'fas fa-times-circle';
            icon.style.color = '#dc3545';
        });

        // Enable register button
        registerBtn.disabled = true;

        // Re-enable email field if it was disabled
        registerEmailInput.readOnly = false;
        registerEmailInput.style.backgroundColor = '';
        registerEmailInput.style.cursor = '';
    }

    // Function to disable form after successful registration
    function disableForm() {
        // Disable all form inputs
        const formInputs = document.querySelectorAll('input, button');
        formInputs.forEach(input => {
            input.disabled = true;
        });

        // Change register button text and style
        registerBtn.textContent = 'Registration Complete';
        registerBtn.style.backgroundColor = '#28a745';
    }

    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Name validation function (no numbers, at least 1 character)
    function validateName(name) {
        // Remove leading and trailing spaces
        const trimmedName = name.trim();
        // Check if name contains only letters, spaces, and allowed special characters
        return /^[a-zA-Z\s.'-]+$/.test(trimmedName) && trimmedName.length >= 1;
    }

    // Phone validation function (Malaysian format)
    function validatePhone(phone) {
        return /^\d{9,10}$/.test(phone);
    }

    // Company name validation function (at least 1 character)
    function validateCompany(company) {
        // Remove leading and trailing spaces
        const trimmedCompany = company.trim();
        return trimmedCompany.length >= 1;
    }

    // Password validation function
    function validatePassword(password) {
        const hasMinLength = password.length >= 8 && password.length <= 20;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        // Update requirement indicators
        updateRequirementIndicator(reqLength, hasMinLength);
        updateRequirementIndicator(reqUppercase, hasUppercase);
        updateRequirementIndicator(reqLowercase, hasLowercase);
        updateRequirementIndicator(reqNumber, hasNumber);
        updateRequirementIndicator(reqSpecial, hasSpecial);

        return hasMinLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
    }

    // Update requirement indicator
    function updateRequirementIndicator(element, isValid) {
        const icon = element.querySelector('i');
        if (isValid) {
            icon.className = 'fas fa-check-circle';
            icon.style.color = '#28a745';
        } else {
            icon.className = 'fas fa-times-circle';
            icon.style.color = '#dc3545';
        }
    }

    // Update validation icon
    function updateValidationIcon(iconElement, isValid) {
        if (isValid) {
            iconElement.innerHTML = '<i class="fas fa-check-circle"></i>';
        } else {
            iconElement.innerHTML = '<i class="fas fa-times-circle"></i>';
        }
    }

    // Check if all fields are valid
    function checkFormValidity() {
        const allValid = Object.values(validationStatus).every(status => status === true);
        registerBtn.disabled = !allValid;
    }

    // Name validation - prevent numbers
    fullNameInput.addEventListener('input', function () {
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

    // Phone number validation - only allow numbers
    phoneNumberInput.addEventListener('input', function () {
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

    // Email validation - prevent spaces and convert to lowercase
    registerEmailInput.addEventListener('keydown', function (e) {
        // Prevent spacebar from working in email field
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    // Function to force uppercase for full name
    fullNameInput.addEventListener('input', function () {
        this.value = this.value.toUpperCase();
    });

    registerEmailInput.addEventListener('input', function () {
        // Remove any spaces that might have been pasted
        if (/\s/.test(this.value)) {
            this.value = this.value.replace(/\s/g, '');
        }

        // Convert to lowercase
        this.value = this.value.toLowerCase();

        // Remove leading and trailing spaces for validation
        const email = this.value.trim();

        if (email === '') {
            emailError.textContent = 'Email is required';
            updateValidationIcon(emailValidationIcon, false);
            validationStatus.email = false;
        } else if (!validateEmail(email)) {
            emailError.textContent = 'Please enter a valid email address (e.g., user@example.com)';
            updateValidationIcon(emailValidationIcon, false);
            validationStatus.email = false;
        } else {
            emailError.textContent = '';
            updateValidationIcon(emailValidationIcon, true);
            validationStatus.email = true;
        }

        checkFormValidity();
    });

        // Verification code validation - real-time matching with generated code
    verificationCodeInput.addEventListener('input', function () {
        const code = this.value.trim();
        const generatedCode = document.querySelector('input[name="generatedCode"]').value;

        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');

        if (code === '') {
            codeError.textContent = 'Verification code is required';
            updateValidationIcon(codeValidationIcon, false);
            validationStatus.code = false;
        } else if (code.length !== 6) {
            codeError.textContent = 'Verification code must be 6 digits';
            updateValidationIcon(codeValidationIcon, false);
            validationStatus.code = false;
        } else if (code !== generatedCode) {
            codeError.textContent = 'Invalid verification code. Please check and try again.';
            updateValidationIcon(codeValidationIcon, false);
            validationStatus.code = false;
        } else {
            codeError.textContent = '';
            updateValidationIcon(codeValidationIcon, true);
            validationStatus.code = true;
        }

        checkFormValidity();
    });

        // Send code button functionality - with actual email sending
    sendCodeBtn.addEventListener('click', function () {
        const email = registerEmailInput.value.trim();
        const generatedCode = document.querySelector('input[name="generatedCode"]').value;

        if (email === '' || !validateEmail(email)) {
            emailError.textContent = 'Please enter a valid email first';
            registerEmailInput.focus();
            return;
        }

        // Make email field read-only after sending code
        registerEmailInput.readOnly = true;
        registerEmailInput.style.backgroundColor = '#f8f9fa';
        registerEmailInput.style.cursor = 'not-allowed';

        // Send code via AJAX
        sendCodeBtn.disabled = true;
        sendCodeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        // Use FormData to send the request
        const formData = new FormData();
        formData.append('action', 'send_code');
        formData.append('email', email);
        formData.append('code', generatedCode);

        fetch('registration-owner-page.php', {
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

                // Show success message
                //showNotification('success', 'Code Sent', 'Verification code //has been sent to your email.', 'OK');
            } else {
                // Failed to send
                //showNotification('error', 'Failed to Send', 'Failed to send verification code. Please try again.', 'OK');
                sendCodeBtn.disabled = false;
                sendCodeBtn.innerHTML = 'Send Code';
                
                // Re-enable email field on failure
                registerEmailInput.readOnly = false;
                registerEmailInput.style.backgroundColor = '';
                registerEmailInput.style.cursor = '';
            }
        })
        .catch(error => {
            //console.error('Error:', error);
           // showNotification('error', 'Error', 'An error occurred while sending the code.', 'OK');
            sendCodeBtn.disabled = false;
            sendCodeBtn.innerHTML = 'Send Code';
            
            // Re-enable email field on error
            registerEmailInput.readOnly = false;
            registerEmailInput.style.backgroundColor = '';
            registerEmailInput.style.cursor = '';
        });
    });

    // Password validation - prevent spaces
    registerPasswordInput.addEventListener('keydown', function (e) {
        // Prevent spacebar from working in password field
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    registerPasswordInput.addEventListener('input', function () {
        const password = this.value;

        // Remove any spaces that might have been pasted
        if (/\s/.test(password)) {
            this.value = password.replace(/\s/g, '');
        }

        if (password === '') {
            passwordError.textContent = 'Password is required';
            updateValidationIcon(passwordValidationIcon, false);
            validationStatus.password = false;

            // Reset all password requirement indicators
            updateRequirementIndicator(reqLength, false);
            updateRequirementIndicator(reqUppercase, false);
            updateRequirementIndicator(reqLowercase, false);
            updateRequirementIndicator(reqNumber, false);
            updateRequirementIndicator(reqSpecial, false);
        } else {
            const isValid = validatePassword(password);
            passwordError.textContent = isValid ? '' : 'Password does not meet all requirements';
            updateValidationIcon(passwordValidationIcon, isValid);
            validationStatus.password = isValid;
        }

        // Also validate confirm password if it has value
        if (confirmPasswordInput.value !== '') {
            confirmPasswordInput.dispatchEvent(new Event('input'));
        }

        checkFormValidity();
    });

    // Confirm password validation - prevent spaces
    confirmPasswordInput.addEventListener('keydown', function (e) {
        // Prevent spacebar from working in password field
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    confirmPasswordInput.addEventListener('input', function () {
        const confirmPassword = this.value;
        const password = registerPasswordInput.value;

        // Remove any spaces that might have been pasted
        if (/\s/.test(confirmPassword)) {
            this.value = confirmPassword.replace(/\s/g, '');
        }

        if (confirmPassword === '') {
            confirmPasswordError.textContent = 'Please confirm your password';
            updateValidationIcon(confirmPasswordValidationIcon, false);
            validationStatus.confirmPassword = false;
        } else if (confirmPassword !== password) {
            confirmPasswordError.textContent = 'Passwords do not match';
            updateValidationIcon(confirmPasswordValidationIcon, false);
            validationStatus.confirmPassword = false;
        } else {
            confirmPasswordError.textContent = '';
            updateValidationIcon(confirmPasswordValidationIcon, true);
            validationStatus.confirmPassword = true;
        }

        checkFormValidity();
    });

    // Company name validation - prevent empty
    companyNameInput.addEventListener('input', function () {


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

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
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


    // Form submission
    document.getElementById('registrationForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Check if all fields are valid
        if (Object.values(validationStatus).every(status => status === true)) {
            // If all valid, submit the form
            this.submit();
        }
    });
});

// Global function to show notification (called from PHP)
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

    if (buttonText === 'Back to Login') {
        newButton.addEventListener('click', function () {
            window.location.href = 'index.php';
        });
    } else {
        newButton.addEventListener('click', function () {
            notificationModal.hide();

            // Reset the form when occurs DB error or failed registration
            // Reset all fields;
            document.getElementById('registrationForm').reset();
            document.getElementById('fullName').value = '';
            document.getElementById('phoneNumber').value = '';
            document.getElementById('registerEmail').value = '';
            document.getElementById('verificationCode').value = '';
            document.getElementById('registerPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('companyName').value = '';

            // Reset validation icons + errors
            document.querySelectorAll('.validation-icon-container').forEach(icon => (icon.innerHTML = ''));
            document.querySelectorAll('.error-message').forEach(error => (error.textContent = ''));
            document.querySelectorAll('.requirement-list i').forEach(icon => {
                icon.className = 'fas fa-times-circle';
                icon.style.color = '#dc3545';
            });

            if (typeof validationStatus !== 'undefined') {
                for (let key in validationStatus) validationStatus[key] = false;
            }

            const registerBtn = document.getElementById('registerBtn');
            if (registerBtn) registerBtn.disabled = true;

            const registerEmailInput = document.getElementById('registerEmail');
            if (registerEmailInput) {
                registerEmailInput.readOnly = false;
                registerEmailInput.style.backgroundColor = '';
                registerEmailInput.style.cursor = '';
            }

        });
    }

    notificationModal.show();

    if (type === 'success') {

        const registerBtn = document.getElementById('registerBtn');
        if (registerBtn) {
            registerBtn.textContent = 'Registration Complete';
            registerBtn.style.backgroundColor = '#28a745';
        }
    }
}