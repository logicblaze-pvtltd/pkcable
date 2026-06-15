/* ============================================================
   Forgot Password — JavaScript (3-Step Flow)
   Step 1: Email → Step 2: OTP Verify → Step 3: Reset Password
   ============================================================ */

const BASE_URL = window.APP_URL || '';

// ---------- State ----------
let currentEmail = '';
let otpTimerInterval = null;

// ---------- DOM refs ----------
const steps = {
    1: document.getElementById('step-1'),
    2: document.getElementById('step-2'),
    3: document.getElementById('step-3'),
    success: document.getElementById('step-success'),
};

const dots  = [null, document.getElementById('dot-1'), document.getElementById('dot-2'), document.getElementById('dot-3')];
const lines = [null, document.getElementById('line-1'), document.getElementById('line-2')];
const stepLabel = document.getElementById('step-label');

// ---------- Step Navigation ----------
function goToStep(step) {
    // Hide all panels
    Object.values(steps).forEach(el => el.classList.add('hidden'));

    if (step === 'success') {
        steps.success.classList.remove('hidden');
        document.getElementById('step-indicator').classList.add('hidden');
        return;
    }

    steps[step].classList.remove('hidden');
    updateStepIndicator(step);
}

function updateStepIndicator(activeStep) {
    stepLabel.textContent = `Step ${activeStep} of 3`;

    [1, 2, 3].forEach(i => {
        dots[i].classList.remove('fp-step-active', 'fp-step-done');
        if (i < activeStep) dots[i].classList.add('fp-step-done');
        else if (i === activeStep) dots[i].classList.add('fp-step-active');
    });

    [1, 2].forEach(i => {
        lines[i].classList.remove('fp-line-done');
        if (i < activeStep) lines[i].classList.add('fp-line-done');
    });
}

// ---------- Error helpers ----------
function showError(boxId, textId, msg) {
    document.getElementById(boxId).classList.remove('hidden');
    document.getElementById(textId).textContent = msg;
}

function hideError(boxId) {
    document.getElementById(boxId).classList.add('hidden');
}

function setFieldError(inputEl, errorEl, msg) {
    inputEl.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    inputEl.classList.remove('focus:border-violet-400', 'focus:ring-violet-400');
    errorEl.textContent = msg;
    errorEl.classList.remove('hidden');
}

function clearFieldError(inputEl, errorEl) {
    inputEl.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    inputEl.classList.add('focus:border-violet-400', 'focus:ring-violet-400');
    errorEl.classList.add('hidden');
}

// ---------- Button loading state ----------
function setLoading(btn, loading, label = null) {
    if (loading) {
        btn.disabled = true;
        btn._origHTML = btn.innerHTML;
        const text = label || btn.textContent.trim();
        btn.innerHTML = `<span class="fp-spinner"></span><span>${text}</span>`;
    } else {
        btn.disabled = false;
        if (btn._origHTML) btn.innerHTML = btn._origHTML;
    }
}

// ============================================================
//  STEP 1 — Send OTP
// ============================================================
const s1Form   = document.getElementById('form-step1');
const s1Email  = document.getElementById('s1-email');
const s1EmailErr = document.getElementById('s1-email-error');
const s1Btn    = document.getElementById('s1-btn');

s1Email.addEventListener('input', () => clearFieldError(s1Email, s1EmailErr));

s1Form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideError('s1-error');

    const email = s1Email.value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setFieldError(s1Email, s1EmailErr, 'Please enter a valid email address');
        return;
    }

    setLoading(s1Btn, true, 'Sending Code...');

    try {
        const res  = await fetch(`${BASE_URL}/controller/auth/send-otp.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email }),
        });
        const data = await res.json();

        if (!data.status) throw new Error(data.message || 'Failed to send OTP');

        currentEmail = email;
        document.getElementById('s2-email-display').textContent = email;
        startOtpTimer(600); // 10 minutes
        goToStep(2);
        setTimeout(() => document.querySelector('.otp-box')?.focus(), 150);

    } catch (err) {
        showError('s1-error', 's1-error-text', err.message);
    } finally {
        setLoading(s1Btn, false);
    }
});

// ============================================================
//  OTP Timer
// ============================================================
function startOtpTimer(seconds) {
    clearInterval(otpTimerInterval);
    const timerEl  = document.getElementById('otp-timer');
    const resendBtn = document.getElementById('resend-btn');
    resendBtn.disabled = true;

    let remaining = seconds;

    function tick() {
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        timerEl.textContent = `${m}:${s}`;
        if (remaining <= 0) {
            clearInterval(otpTimerInterval);
            timerEl.textContent = '00:00';
            timerEl.classList.add('text-red-500');
            timerEl.classList.remove('text-violet-600', 'dark:text-violet-400');
            resendBtn.disabled = false;
        }
        remaining--;
    }

    tick();
    otpTimerInterval = setInterval(tick, 1000);
}

// ============================================================
//  OTP Input Boxes behaviour
// ============================================================
const otpBoxes = Array.from(document.querySelectorAll('.otp-box'));

otpBoxes.forEach((box, idx) => {
    box.addEventListener('input', (e) => {
        // Allow only digits
        box.value = box.value.replace(/\D/g, '');
        if (box.value) {
            box.classList.add('otp-filled');
            box.classList.remove('otp-error');
            if (idx < otpBoxes.length - 1) otpBoxes[idx + 1].focus();
        } else {
            box.classList.remove('otp-filled');
        }
    });

    box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && idx > 0) {
            otpBoxes[idx - 1].focus();
            otpBoxes[idx - 1].value = '';
            otpBoxes[idx - 1].classList.remove('otp-filled');
        }
        if (e.key === 'ArrowLeft' && idx > 0) otpBoxes[idx - 1].focus();
        if (e.key === 'ArrowRight' && idx < otpBoxes.length - 1) otpBoxes[idx + 1].focus();
    });

    // Handle paste
    box.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((ch, i) => {
            if (otpBoxes[i]) {
                otpBoxes[i].value = ch;
                otpBoxes[i].classList.add('otp-filled');
            }
        });
        const next = Math.min(pasted.length, otpBoxes.length - 1);
        otpBoxes[next].focus();
    });
});

function getOtpValue() {
    return otpBoxes.map(b => b.value).join('');
}

function clearOtpBoxes(error = false) {
    otpBoxes.forEach(b => {
        b.value = '';
        b.classList.remove('otp-filled');
        if (error) {
            b.classList.add('otp-error');
            setTimeout(() => b.classList.remove('otp-error'), 600);
        }
    });
    otpBoxes[0].focus();
}

// ============================================================
//  STEP 2 — Verify OTP
// ============================================================
const s2Form = document.getElementById('form-step2');
const s2Btn  = document.getElementById('s2-btn');
const s2OtpErr = document.getElementById('s2-otp-error');

s2Form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideError('s2-error');
    s2OtpErr.classList.add('hidden');

    const otp = getOtpValue();
    if (otp.length < 6) {
        s2OtpErr.classList.remove('hidden');
        clearOtpBoxes(true);
        return;
    }

    setLoading(s2Btn, true, 'Verifying...');

    try {
        const res  = await fetch(`${BASE_URL}/controller/auth/verify-otp.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: currentEmail, otp }),
        });
        const data = await res.json();

        if (!data.status) throw new Error(data.message || 'Invalid or expired code');

        clearInterval(otpTimerInterval);
        goToStep(3);

    } catch (err) {
        showError('s2-error', 's2-error-text', err.message);
        clearOtpBoxes(true);
    } finally {
        setLoading(s2Btn, false);
    }
});

// Back button
document.getElementById('s2-back-btn').addEventListener('click', () => {
    clearInterval(otpTimerInterval);
    clearOtpBoxes();
    hideError('s2-error');
    goToStep(1);
});

// Resend OTP
document.getElementById('resend-btn').addEventListener('click', async () => {
    hideError('s2-error');
    const btn = document.getElementById('resend-btn');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    try {
        const res  = await fetch(`${BASE_URL}/controller/auth/send-otp.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: currentEmail }),
        });
        const data = await res.json();
        if (!data.status) throw new Error(data.message || 'Failed to resend');

        clearOtpBoxes();
        document.getElementById('otp-timer').classList.remove('text-red-500');
        document.getElementById('otp-timer').classList.add('text-violet-600');
        startOtpTimer(600);

    } catch (err) {
        showError('s2-error', 's2-error-text', err.message);
        btn.disabled = false;
        btn.textContent = 'Resend Code';
    }
});

// ============================================================
//  STEP 3 — Reset Password
// ============================================================
const s3Form     = document.getElementById('form-step3');
const s3Password = document.getElementById('s3-password');
const s3Confirm  = document.getElementById('s3-confirm');
const s3PwdErr   = document.getElementById('s3-password-error');
const s3CnfErr   = document.getElementById('s3-confirm-error');
const s3Btn      = document.getElementById('s3-btn');
const strengthBar = document.getElementById('strength-bar');
const strengthLbl = document.getElementById('strength-label');

// Password strength meter
s3Password.addEventListener('input', () => {
    clearFieldError(s3Password, s3PwdErr);
    const pwd = s3Password.value;
    let score = 0;
    if (pwd.length >= 8)  score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;

    const configs = [
        { width: '0%',   color: '',           label: '' },
        { width: '25%',  color: '#EF4444',     label: '😟 Weak' },
        { width: '50%',  color: '#F59E0B',     label: '😐 Fair' },
        { width: '75%',  color: '#3B82F6',     label: '😊 Good' },
        { width: '100%', color: '#10B981',     label: '💪 Strong' },
    ];

    const cfg = configs[score];
    strengthBar.style.width = cfg.width;
    strengthBar.style.backgroundColor = cfg.color;
    if (cfg.label) {
        strengthLbl.textContent = cfg.label;
        strengthLbl.classList.remove('hidden');
    } else {
        strengthLbl.classList.add('hidden');
    }
});

s3Confirm.addEventListener('input', () => clearFieldError(s3Confirm, s3CnfErr));

s3Form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideError('s3-error');

    const pwd = s3Password.value;
    const cnf = s3Confirm.value;
    let valid = true;

    if (!pwd || pwd.length < 8) {
        setFieldError(s3Password, s3PwdErr, 'Password must be at least 8 characters');
        valid = false;
    }
    if (pwd !== cnf) {
        setFieldError(s3Confirm, s3CnfErr, 'Passwords do not match');
        valid = false;
    }
    if (!valid) return;

    setLoading(s3Btn, true, 'Updating...');

    try {
        const res  = await fetch(`${BASE_URL}/controller/auth/reset-password.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email: currentEmail, password: pwd, confirm_password: cnf }),
        });
        const data = await res.json();
        if (!data.status) throw new Error(data.message || 'Failed to reset password');

        goToStep('success');

    } catch (err) {
        showError('s3-error', 's3-error-text', err.message);
    } finally {
        setLoading(s3Btn, false);
    }
});

// ---------- Eye toggle buttons ----------
document.querySelectorAll('.fp-eye-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.dataset.target;
        const input = document.getElementById(targetId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.querySelector('.fp-eye-show').classList.toggle('hidden', isHidden);
        btn.querySelector('.fp-eye-hide').classList.toggle('hidden', !isHidden);
    });
});

// ---------- Init ----------
goToStep(1);
