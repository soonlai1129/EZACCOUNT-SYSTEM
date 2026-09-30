document.addEventListener('DOMContentLoaded', function () {
    // Get all elements
    const sendCodeBtn = document.getElementById('sendCodeBtn');
    const timerContainer = document.getElementById('timerContainer');
    const countdownElement = document.getElementById('countdown');
    const resetEmailInput = document.getElementById('resetEmail');
    const verificationCodeInput = document.getElementById('verificationCode');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const resetBtn = document.getElementById('resetBtn');

    // Validation icon containers
    const emailValidationIcon = document.getElementById('emailValidationIcon');
    const codeValidationIcon = document.getElementById('codeValidationIcon');
    const newPasswordValidationIcon = document.getElementById('newPasswordValidationIcon');
    const confirmPasswordValidationIcon = document.getElementById('confirmPasswordValidationIcon');

    // Toggle password icons
    const toggleNewPassword = document.querySelector('#toggleNewPassword i');
    const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword i');

    // Error message elements
    const emailError = document.getElementById('emailError');
    const codeError = document.getElementById('codeError');
    const newPasswordError = document.getElementById('newPasswordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    // Password requirement elements
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

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

    let countdown;
    let timerValue = 60;
    let attemptCount = 0;

    // Validation status
    const validationStatus = {
        email: false,
        code: false,
        newPassword: false,
        confirmPassword: false
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

                // Reset the form when occurs DB error or email not found
                document.getElementById('forgotPasswordForm').reset();

                // Reset all fields;
                document.getElementById('resetEmail').value = '';
                document.getElementById('verificationCode').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';

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

                // Enable reset button
                resetBtn.disabled = true;

                // Re-enable email field if it was disabled
                resetEmailInput.readOnly = false;
                resetEmailInput.style.backgroundColor = '';
                resetEmailInput.style.cursor = '';

            });
        }

        // Show the modal
        notificationModal.show();

        // If password reset was successful, disable the form
        if (type === 'success') {
            disableForm();
        }
    }

    // Function to disable form after successful password reset
    function disableForm() {
        // Disable all form inputs
        const formInputs = document.querySelectorAll('input, button');
        formInputs.forEach(input => {
            input.disabled = true;
        });

        // Change reset button text and style
        resetBtn.textContent = 'Password Reset Complete';
        resetBtn.style.backgroundColor = '#28a745';
    }

    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
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
        resetBtn.disabled = !allValid;
    }

    // Email validation - prevent spaces and convert to lowercase
    resetEmailInput.addEventListener('keydown', function (e) {
        // Prevent spacebar from working in email field
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    resetEmailInput.addEventListener('input', function () {
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

    // New password validation - prevent spaces
    newPasswordInput.addEventListener('keydown', function (e) {
        // Prevent spacebar from working in password field
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    newPasswordInput.addEventListener('input', function () {
        const password = this.value;

        // Remove any spaces that might have been pasted
        if (/\s/.test(password)) {
            this.value = password.replace(/\s/g, '');
        }

        if (password === '') {
            newPasswordError.textContent = 'Password is required';
            updateValidationIcon(newPasswordValidationIcon, false);
            validationStatus.newPassword = false;

            // Reset all password requirement indicators
            updateRequirementIndicator(reqLength, false);
            updateRequirementIndicator(reqUppercase, false);
            updateRequirementIndicator(reqLowercase, false);
            updateRequirementIndicator(reqNumber, false);
            updateRequirementIndicator(reqSpecial, false);
        } else {
            const isValid = validatePassword(password);
            newPasswordError.textContent = isValid ? '' : 'Password does not meet all requirements';
            updateValidationIcon(newPasswordValidationIcon, isValid);
            validationStatus.newPassword = isValid;
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
        const password = newPasswordInput.value;

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

    // Toggle password visibility - New Password
    document.getElementById('toggleNewPassword').addEventListener('click', function () {
        const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        newPasswordInput.setAttribute('type', type);
        toggleNewPassword.classList.toggle('fa-eye');
        toggleNewPassword.classList.toggle('fa-eye-slash');
    });

    // Toggle password visibility - Confirm Password
    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        toggleConfirmPassword.classList.toggle('fa-eye');
        toggleConfirmPassword.classList.toggle('fa-eye-slash');
    });

    // Send code button functionality - with actual email sending
    sendCodeBtn.addEventListener('click', function () {
        const email = resetEmailInput.value.trim();
        const generatedCode = document.querySelector('input[name="generatedCode"]').value;

        if (email === '' || !validateEmail(email)) {
            emailError.textContent = 'Please enter a valid email first';
            resetEmailInput.focus();
            return;
        }

        // Make email field read-only after sending code
        resetEmailInput.readOnly = true;
        resetEmailInput.style.backgroundColor = '#f8f9fa';
        resetEmailInput.style.cursor = 'not-allowed';

        // Send code via AJAX
        sendCodeBtn.disabled = true;
        sendCodeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        // Use FormData to send the request
        const formData = new FormData();
        formData.append('action', 'send_code');
        formData.append('email', email);
        formData.append('code', generatedCode);

        fetch('forgot-password-page.php', {
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
                resetEmailInput.readOnly = false;
                resetEmailInput.style.backgroundColor = '';
                resetEmailInput.style.cursor = '';
                
              //  alert('Failed to send verification code. Please try again.');
            }
        })
        .catch(error => {
           // console.error('Error:', error);
          //  alert('An error occurred while sending the code.');
            sendCodeBtn.disabled = false;
            sendCodeBtn.innerHTML = 'Send Code';
            
            // Re-enable email field on error
            resetEmailInput.readOnly = false;
            resetEmailInput.style.backgroundColor = '';
            resetEmailInput.style.cursor = '';
        });
    });

    // Form submission
    document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
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

            // Reset the form when occurs DB error or email not found
            document.getElementById('forgotPasswordForm').reset();

            // Reset all fields;
            document.getElementById('resetEmail').value = '';
            document.getElementById('verificationCode').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';

            // Reset all validation icons and errors
            document.querySelectorAll('.validation-icon-container').forEach(icon => (icon.innerHTML = ''));
            document.querySelectorAll('.error-message').forEach(error => (error.textContent = ''));
            document.querySelectorAll('.requirement-list i').forEach(icon => {
                icon.className = 'fas fa-times-circle';
                icon.style.color = '#dc3545';
            });

            // Reset validation status if it exists
            if (typeof validationStatus !== 'undefined') {
                for (let key in validationStatus) validationStatus[key] = false;
            }

            // Enable reset button
            const resetBtn = document.getElementById('resetBtn');
            if (resetBtn) resetBtn.disabled = true;

            // Re-enable email field
            const resetEmailInput = document.getElementById('resetEmail');
            if (resetEmailInput) {
                resetEmailInput.readOnly = false;
                resetEmailInput.style.backgroundColor = '';
                resetEmailInput.style.cursor = '';
            }
        });
    }

    notificationModal.show();

    if (type === 'success') {
        // Change reset button text and style if password reset was successful
        const resetBtn = document.getElementById('resetBtn');
        if (resetBtn) {
            resetBtn.textContent = 'Password Reset Complete';
            resetBtn.style.backgroundColor = '#28a745';
        }
    }
}