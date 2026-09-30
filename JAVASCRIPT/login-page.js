/**
 * ezAccount Login Page JavaScript
 * Handles form validation, password visibility toggle, and notifications
 */

document.addEventListener('DOMContentLoaded', function () {
    // Get email and password input fields
    const emailInput = document.getElementById('floatingInput');
    const passwordInput = document.getElementById('floatingPassword');
    const togglePassword = document.querySelector('.toggle-password i');
    const loginForm = document.getElementById('loginForm');

    // Prevent spacebar from working in email field
    emailInput.addEventListener('keydown', function (e) {
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    // Remove any spaces that might have been pasted for email field
    emailInput.addEventListener('input', function () {
        if (/\s/.test(this.value)) {
            this.value = this.value.replace(/\s/g, '');
        }
    });

    // Function to force lowercase for email field
    emailInput.addEventListener('input', function () {
        this.value = this.value.toLowerCase();
    });

    // Prevent spacebar from working in password field
    passwordInput.addEventListener('keydown', function (e) {
        if (e.key === ' ') {
            e.preventDefault();
        }
    });

    // Remove any spaces that might have been pasted for password field
    passwordInput.addEventListener('input', function () {
        if (/\s/.test(this.value)) {
            this.value = this.value.replace(/\s/g, '');
        }
    });

    // Toggle password visibility
    document.querySelector('.toggle-password').addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.classList.toggle('fa-eye');
        togglePassword.classList.toggle('fa-eye-slash');
    });

    // Form submission validation
    loginForm.addEventListener('submit', function (e) {
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        if (email === '' || password === '') {
            e.preventDefault();
            // This will be handled by HTML5 validation, but we can add custom message if needed
        }
    });
});

/**
 * Global function to show notification (called from PHP)
 * @param {string} type - Type of notification ('success' or 'error')
 * @param {string} title - Title of the notification
 * @param {string} message - Message content
 * @param {string} buttonText - Text for the button
 */
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

    // Clear previous classes and set new ones
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

    // Remove old event listeners by replacing the button
    const newButton = notificationButton.cloneNode(true);
    notificationButton.parentNode.replaceChild(newButton, notificationButton);

    // Add event listener to the new button
    newButton.addEventListener('click', function () {
        notificationModal.hide();

        // Reset the form fields only for error notifications
        if (type === 'error') {
            document.getElementById('floatingInput').value = '';
            document.getElementById('floatingPassword').value = '';
            document.getElementById('floatingInput').focus();
        }
    });

    // Show the modal
    notificationModal.show();
}