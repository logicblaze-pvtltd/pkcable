const togglePassword = document.getElementById('toggle-password');
const passwordInputField = document.getElementById('password-input');
const eyeIcon = document.getElementById('eye-icon');
const eyeOffIcon = document.getElementById('eye-off-icon');

togglePassword.addEventListener('click', () => {
    const isPassword = passwordInputField.getAttribute('type') === 'password';

    // Toggle input type
    passwordInputField.setAttribute(
        'type',
        isPassword ? 'text' : 'password'
    );

    // Toggle icons
    eyeIcon.classList.toggle('hidden', isPassword);
    eyeOffIcon.classList.toggle('hidden', !isPassword);
});