const saved = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const isDark = saved === 'dark' || (!saved && prefersDark);
if (isDark) {
    document.documentElement.classList.add('dark');
}

const updateBackgroundColors = () => {
    const dark = document.documentElement.classList.contains('dark');

    document.getElementById('bg-overlay').style.background = dark ?
        'radial-gradient(circle at 30% 40%, rgba(55, 65, 81, 0.3), transparent 70%)' :
        'radial-gradient(circle at 70% 30%, rgba(200, 200, 220, 0.5), transparent 70%)';

    const blobColor1 = dark ? 'radial-gradient(circle at 30% 40%, rgba(130, 100, 220, 0.45), rgba(50, 80, 170, 0.25))' : 'radial-gradient(circle at 40% 60%, rgba(200, 140, 240, 0.55), rgba(130, 180, 255, 0.3))';
    document.getElementById('blob-1').style.background = blobColor1;
    document.getElementById('blob-3').style.background = blobColor1;
    document.getElementById('blob-5').style.background = blobColor1;
    document.getElementById('blob-6').style.background = blobColor1;

    document.getElementById('blob-2').style.background = dark ? 'radial-gradient(circle at 70% 30%, rgba(190, 90, 150, 0.4), rgba(100, 70, 160, 0.2))' : 'radial-gradient(circle at 70% 30%, rgba(255, 170, 140, 0.6), rgba(255, 120, 180, 0.3))';
    document.getElementById('blob-4').style.background = dark ? 'radial-gradient(circle at 30% 60%, rgba(70, 130, 210, 0.35), rgba(40, 60, 150, 0.2))' : 'radial-gradient(circle at 30% 60%, rgba(150, 210, 255, 0.6), rgba(100, 140, 230, 0.3))';

    document.getElementById('bg-pattern').style.backgroundImage = dark ?
        'url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIxLjUiIGZpbGw9IndoaXRlIiBmaWxsLW9wYWNpdHk9IjAuMTUiLz48Y2lyY2xlIGN4PSI1IiBjeT0iMzAiIHI9IjEiIGZpbGw9IndoaXRlIiBmaWxsLW9wYWNpdHk9IjAuMTUiLz48Y2lyY2xlIGN4PSIzNSIgY3k9IjEwIiByPSIxIiBmaWxsPSJ3aGl0ZSIgZmlsbC1vcGFjaXR5PSIwLjEiLz48L3N2Zz4=")' :
        'url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIxLjUiIGZpbGw9ImJsYWNrIiBmaWxsLW9wYWNpdHk9IjAuMTUiLz48Y2lyY2xlIGN4PSI1IiBjeT0iMzAiIHI9IjEiIGZpbGw9ImJsYWNrIiBmaWxsLW9wYWNpdHk9IjAuMTUiLz48Y2lyY2xlIGN4PSIzNSIgY3k9IjEwIiByPSIxIiBmaWxsPSJibGFjayIgZmlsbC1vcGFjaXR5PSIwLjEiLz48L3N2Zz4=")';
};
updateBackgroundColors();

// ─── Parallax Movement Logic ──────────────────────────────────────────
const layers = document.querySelectorAll('.paralexx-layer');
let currentX = 0,
    currentY = 0;
let targetX = 0,
    targetY = 0;

document.addEventListener('mousemove', (e) => {
    targetX = (e.clientX / window.innerWidth) * 2 - 1;
    targetY = (e.clientY / window.innerHeight) * 2 - 1;

    // Subtle mouse parallax effect on left illustration
    const x = (e.clientX / window.innerWidth - 0.5) * 20;
    const y = (e.clientY / window.innerHeight - 0.5) * 20;
    const svg = document.querySelector('.svg-float');
    if (svg) {
        svg.style.transform = `translateX(${x}px) translateY(${y}px)`;
    }
});

const updateParallax = () => {
    currentX += (targetX - currentX) * 0.04;
    currentY += (targetY - currentY) * 0.04;

    layers.forEach(layer => {
        const speed = parseFloat(layer.getAttribute('data-speed') || '0.1');
        layer.style.transform = `translate(${currentX * 45 * speed}px, ${currentY * 45 * speed}px)`;
    });
    requestAnimationFrame(updateParallax);
};
updateParallax();

// ─── Show/Hide Password ───────────────────────────────────────────────
const toggleBtn = document.getElementById('toggle-password');
const passwordInput = document.getElementById('password-input');
const eyeIcon = document.getElementById('eye-icon');
const eyeOffIcon = document.getElementById('eye-off-icon');

toggleBtn.addEventListener('click', () => {
    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';
    eyeIcon.classList.toggle('hidden', isPassword);
    eyeOffIcon.classList.toggle('hidden', !isPassword);
});

// ─── Form Validation & Submission ─────────────────────────────────────
const form = document.getElementById('login-form');
const emailInput = document.getElementById('email-input');
const emailError = document.getElementById('email-error');
const passwordError = document.getElementById('password-error');
const formError = document.getElementById('form-error');
const formErrorText = document.getElementById('form-error-text');
const submitBtn = document.getElementById('submit-btn');

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

emailInput.addEventListener('focus', () => clearInputError(emailInput, emailError));
passwordInput.addEventListener('focus', () => clearInputError(passwordInput, passwordError));

const setLoading = (loading) => {
    if (loading) {
        submitBtn.disabled = true;
        submitBtn.classList.replace('bg-blue-800', 'bg-blue-700');
        submitBtn.classList.add('cursor-not-allowed');
        submitBtn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Signing in...
                `;
    } else {
        submitBtn.disabled = false;
        submitBtn.classList.replace('bg-blue-700', 'bg-blue-800');
        submitBtn.classList.remove('cursor-not-allowed');
        submitBtn.innerHTML = 'Sign In';
    }
};

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    formError.classList.add('hidden');

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

    setLoading(true);

    // Simulate mock login (matches React mock behavior)
    await new Promise(r => setTimeout(r, 800));

    const mockUsers = [{
        email: 'admin@example.com',
        name: 'Admin User',
        user_role: 0,
        user_status: 1
    },
    {
        email: 'manager@example.com',
        name: 'Manager User',
        user_role: 2,
        user_status: 1
    },
    ];

    const mockUser = mockUsers.find(u => u.email === email && password === 'password');

    if (mockUser) {
        const userInfo = {
            name: mockUser.name,
            email: mockUser.email,
            user_role: mockUser.user_role,
        };
        localStorage.setItem('user', JSON.stringify(userInfo));
        // Redirect to dashboard
        window.location.href = 'index.php';
    } else {
        setLoading(false);
        formErrorText.textContent = 'Invalid credentials. Try admin@example.com / password';
        formError.classList.remove('hidden');
    }
});