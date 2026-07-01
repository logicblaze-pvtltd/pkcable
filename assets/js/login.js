const form = document.getElementById('login-form');
const usernameInput = document.getElementById('username-input');
const passwordInput = document.getElementById('password-input');

const usernameError = document.getElementById('username-error');
const passwordError = document.getElementById('password-error');

const formError = document.getElementById('form-error');
const formErrorText = document.getElementById('form-error-text');

const submitBtn = document.getElementById('submit-btn');
const withButtonLoading = window.AppButtonLoading?.withButtonLoading;

const API_URL = `${window.APP_URL || ''}/controller/auth/login.php`; // adjust if needed

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

usernameInput.addEventListener('input', () => clearInputError(usernameInput, usernameError));
passwordInput.addEventListener('input', () => clearInputError(passwordInput, passwordError));

function showFormError(msg) {
    formErrorText.textContent = msg;
    formError.classList.remove('hidden');
}

function hideFormError() {
    formError.classList.add('hidden');
    formErrorText.textContent = '';
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideFormError();

    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();

    let valid = true;

    if (!username) {
        setInputError(usernameInput, usernameError, 'Email or Mobile Number is required');
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
                username,
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
