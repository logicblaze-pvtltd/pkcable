const form = document.getElementById('login-form');
const emailInput = document.getElementById('email-input');
const passwordInput = document.getElementById('password-input');

const emailError = document.getElementById('email-error');
const passwordError = document.getElementById('password-error');

const formError = document.getElementById('form-error');
const formErrorText = document.getElementById('form-error-text');

const submitBtn = document.getElementById('submit-btn');
const withButtonLoading = window.AppButtonLoading?.withButtonLoading;

const API_URL = `${window.APP_URL || ''}/controller/auth/login.php`; // adjust if needed

// â”€â”€â”€ Error Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const setInputError = (input, errorEl, msg) => {
    input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    input.classList.remove('focus:border-blue-400', 'focus:ring-blue-400');
    errorEl.textContent = msg;
    errorEl.classList.remove('hidden');
};

const clearInputError = (input, errorEl) => {
    input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    input.classList.add('focus:border-blue-400', 'focus:ring-blue-400');
    errorEl.classList.add('hidden');
};

emailInput.addEventListener('input', () => clearInputError(emailInput, emailError));
passwordInput.addEventListener('input', () => clearInputError(passwordInput, passwordError));

// â”€â”€â”€ Global Error â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showFormError(msg) {
    formErrorText.textContent = msg;
    formError.classList.remove('hidden');
}

function hideFormError() {
    formError.classList.add('hidden');
    formErrorText.textContent = '';
}

// â”€â”€â”€ Submit Handler â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideFormError();

    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();

    let valid = true;

    if (!email) {
        setInputError(emailInput, emailError, 'Email is required');
        valid = false;
    }

    if (!password) {
        setInputError(passwordInput, passwordError, 'Password is required');
        valid = false;
    }

    if (!valid) return;

    const submitLogin = async () => {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email,
                password
            })
        });

        const data = await res.json();

        if (!res.ok || !data.status) {
            throw new Error(data.message || 'Login failed');
        }

        localStorage.setItem('user', JSON.stringify(data.data.user));

        if (data.data.token) {
            localStorage.setItem('token', data.data.token);
        }

        window.location.href = `${window.APP_URL || ''}/index.php`;
    };

    try {
        if (typeof withButtonLoading === 'function') {
            await withButtonLoading(submitBtn, submitLogin, { label: 'Signing in...' });
        } else {
            await submitLogin();
        }
    } catch (err) {
        showFormError(err.message);
    }
});
