/**
 * DatePicker — reusable calendar date-picker widget.
 *
 * Usage:
 *   const picker = new DatePicker({
 *     inputDisplayId : 'my-display-input',   // readonly text input shown to user
 *     hiddenInputId  : 'my-hidden-input',    // hidden input with YYYY-MM-DD value
 *     clearBtnId     : 'my-clear-btn',       // × button inside the input wrapper
 *     modalId        : 'my-calendar-modal',  // the full-screen backdrop div
 *     panelId        : 'my-calendar-panel',  // the white card inside the backdrop
 *     monthYearId    : 'my-month-year',      // span showing "June 2025"
 *     daysGridId     : 'my-days-grid',       // grid where day buttons are injected
 *     prevBtnId      : 'my-prev-btn',        // ‹ button
 *     nextBtnId      : 'my-next-btn',        // › button
 *     cancelBtnId    : 'my-cancel-btn',      // Cancel footer button
 *     todayBtnId     : 'my-today-btn',       // Today footer button
 *   });
 *
 *   picker.getValue()          // → "2025-06-11"  (YYYY-MM-DD) or ""
 *   picker.setValue("2025-06-11")   // programmatically set date
 *   picker.clear()             // reset to empty
 */
export class DatePicker {
    constructor(cfg) {
        // Resolve DOM elements
        this._display   = document.getElementById(cfg.inputDisplayId);
        this._hidden    = document.getElementById(cfg.hiddenInputId);
        this._clearBtn  = document.getElementById(cfg.clearBtnId);
        this._modal     = document.getElementById(cfg.modalId);
        this._panel     = document.getElementById(cfg.panelId);
        this._monthYear = document.getElementById(cfg.monthYearId);
        this._grid      = document.getElementById(cfg.daysGridId);
        this._prevBtn   = document.getElementById(cfg.prevBtnId);
        this._nextBtn   = document.getElementById(cfg.nextBtnId);
        this._cancelBtn = document.getElementById(cfg.cancelBtnId);
        this._todayBtn  = document.getElementById(cfg.todayBtnId);

        // Bail out silently if any required element is missing
        if (!this._display || !this._hidden || !this._modal || !this._panel || !this._grid) {
            console.warn('[DatePicker] One or more required elements not found for config:', cfg);
            return;
        }

        this._selectedDate       = null;
        this._currentDisplayMonth = new Date();
        this._currentDisplayMonth.setDate(1);
        this._disabled           = false;

        this._monthNames = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
        ];

        // ── Detach from parent and re-attach to <body> ─────────────────────────
        // The calendar uses position:fixed + z-index. If the calendar modal is
        // nested inside a parent with transform/filter/overflow/isolation, the
        // browser creates a new stacking context and position:fixed becomes
        // relative to THAT parent — breaking z-index and causing sibling inputs
        // to render on top of the calendar. Moving to <body> fixes this.
        if (this._modal && this._modal.parentElement !== document.body) {
            document.body.appendChild(this._modal);
        }

        // Keep the calendar above the subscription modal and any other dialogs.
        // Inline z-index avoids depending on utility class ordering or CSS build output.
        this._modal.style.zIndex = '10050';

        this._bindEvents();
        this._updateUI();
        this._renderGrid();

        // Accessibility
        this._modal.setAttribute('aria-hidden', 'true');
        this._panel.setAttribute('role', 'dialog');
        this._panel.setAttribute('aria-label', 'Date picker calendar');

        // Observe modal visibility to keep aria-hidden in sync.
        // IMPORTANT: only watch the 'class' attribute — if we watch all attributes,
        // setting aria-hidden itself re-triggers the observer → infinite loop → browser hang.
        new MutationObserver((mutations) => {
            mutations.forEach((mut) => {
                if (mut.attributeName === 'class') {
                    const visible = !this._modal.classList.contains('invisible');
                    this._modal.setAttribute('aria-hidden', visible ? 'false' : 'true');
                }
            });
        }).observe(this._modal, { attributes: true, attributeFilter: ['class'] });
    }

    // ─── Public API ────────────────────────────────────────────────────────────

    /** Returns the selected date as YYYY-MM-DD string, or "" if none. */
    getValue() {
        return this._hidden?.value ?? '';
    }

    /** Programmatically set the picker to a YYYY-MM-DD string or a Date object. */
    setValue(dateInput) {
        if (!dateInput) { this.clear(); return; }
        const d = (dateInput instanceof Date) ? dateInput : new Date(dateInput + 'T00:00:00');
        if (isNaN(d.getTime())) { this.clear(); return; }
        this._selectedDate = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        this._currentDisplayMonth = new Date(d.getFullYear(), d.getMonth(), 1);
        this._updateUI();
        this._renderGrid();
    }

    /** Reset to no date selected. */
    clear() {
        this._selectedDate = null;
        this._updateUI();
        this._hidden?.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /** Enable/disable user interaction for this picker. */
    setDisabled(disabled = false) {
        this._disabled = !!disabled;
        if (this._display) {
            this._display.classList.toggle('cursor-not-allowed', this._disabled);
            this._display.classList.toggle('opacity-80', this._disabled);
        }
        if (this._clearBtn) {
            this._clearBtn.classList.toggle('pointer-events-none', this._disabled);
            this._clearBtn.classList.toggle('opacity-60', this._disabled);
        }
        if (this._modal && this._disabled) {
            this._closeModal();
        }
    }

    // ─── Private helpers ───────────────────────────────────────────────────────

    _formatReadable(date) {
        if (!date) return '';
        return date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    }

    _formatISO(date) {
        if (!date) return '';
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    _updateUI() {
        if (this._selectedDate) {
            if (this._display)  this._display.value = this._formatReadable(this._selectedDate);
            if (this._hidden)   this._hidden.value  = this._formatISO(this._selectedDate);
            if (this._clearBtn) this._clearBtn.classList.remove('invisible');
        } else {
            if (this._display)  this._display.value = '';
            if (this._hidden)   this._hidden.value  = '';
            if (this._clearBtn) this._clearBtn.classList.add('invisible');
        }
    }

    _renderGrid() {
        if (!this._grid || !this._monthYear) return;

        const year  = this._currentDisplayMonth.getFullYear();
        const month = this._currentDisplayMonth.getMonth();

        this._monthYear.textContent = `${this._monthNames[month]} ${year}`;

        const firstDay      = new Date(year, month, 1).getDay();
        const daysInMonth   = new Date(year, month + 1, 0).getDate();
        const prevDaysCount = new Date(year, month, 0).getDate();

        const cells = [];

        // Leading prev-month days
        for (let i = 0; i < firstDay; i++) {
            cells.push({ dateObj: new Date(year, month - 1, prevDaysCount - firstDay + i + 1), current: false });
        }
        // Current month
        for (let d = 1; d <= daysInMonth; d++) {
            cells.push({ dateObj: new Date(year, month, d), current: true });
        }
        // Trailing next-month days (to fill 42 cells)
        for (let i = 1; cells.length < 42; i++) {
            cells.push({ dateObj: new Date(year, month + 1, i), current: false });
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        this._grid.innerHTML = '';

        cells.forEach(({ dateObj, current }) => {
            const cell = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
            const isSelected = this._selectedDate && this._selectedDate.toDateString() === cell.toDateString();
            const isToday    = today.toDateString() === cell.toDateString();

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerText = cell.getDate();
            btn.setAttribute('aria-label', `Select ${cell.toDateString()}`);
            btn.className = [
                'day-cell w-full aspect-square flex items-center justify-center rounded-full text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-blue-400',
                !current    ? 'text-gray-400 dark:text-gray-600'      : 'text-gray-700 dark:text-gray-300',
                isSelected  ? 'bg-blue-600 text-white shadow-md hover:bg-blue-700'
                            : 'hover:bg-blue-50 dark:hover:bg-gray-600',
                (isToday && !isSelected) ? 'border border-blue-300 bg-blue-50/40 text-blue-700 dark:text-blue-300' : '',
            ].join(' ');

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this._selectedDate = cell;
                this._updateUI();
                this._closeModal();
                // Dispatch a native 'change' event on the hidden input so callers can listen
                this._hidden?.dispatchEvent(new Event('change', { bubbles: true }));
            });

            this._grid.appendChild(btn);
        });
    }

    _openModal() {
        if (!this._modal || !this._panel || this._disabled) return;
        // Sync display month to selected date if one exists
        if (this._selectedDate) {
            this._currentDisplayMonth = new Date(this._selectedDate.getFullYear(), this._selectedDate.getMonth(), 1);
        } else {
            const t = new Date();
            this._currentDisplayMonth = new Date(t.getFullYear(), t.getMonth(), 1);
        }
        this._renderGrid();

        this._modal.classList.remove('invisible', 'pointer-events-none');
        this._modal.classList.add('visible', 'pointer-events-auto');
        setTimeout(() => {
            this._modal.classList.add('opacity-100');
            this._panel.classList.remove('scale-95', 'opacity-0');
            this._panel.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    _closeModal() {
        if (!this._modal || !this._panel) return;
        this._modal.classList.remove('opacity-100');
        this._panel.classList.remove('scale-100', 'opacity-100');
        this._panel.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            this._modal.classList.add('invisible', 'pointer-events-none');
            this._modal.classList.remove('visible', 'pointer-events-auto');
        }, 200);
    }

    _changeMonth(delta) {
        if (this._disabled) return;
        const d = this._currentDisplayMonth;
        this._currentDisplayMonth = new Date(d.getFullYear(), d.getMonth() + delta, 1);
        this._renderGrid();
    }

    _selectToday() {
        if (this._disabled) return;
        const t = new Date();
        t.setHours(0, 0, 0, 0);
        this._selectedDate = new Date(t.getFullYear(), t.getMonth(), t.getDate());
        this._currentDisplayMonth = new Date(t.getFullYear(), t.getMonth(), 1);
        this._updateUI();
        this._closeModal();
        this._hidden?.dispatchEvent(new Event('change', { bubbles: true }));
    }

    _bindEvents() {
        // Open on input click / focus
        this._display?.addEventListener('click',   (e) => { if (this._disabled) return; e.preventDefault(); this._openModal(); });
        this._display?.addEventListener('keydown', (e) => { if (this._disabled) return; if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this._openModal(); } });

        // Clear button
        this._clearBtn?.addEventListener('click', (e) => { if (this._disabled) return; e.stopPropagation(); this.clear(); });

        // Month navigation
        this._prevBtn?.addEventListener('click', (e) => { if (this._disabled) return; e.stopPropagation(); this._changeMonth(-1); });
        this._nextBtn?.addEventListener('click', (e) => { if (this._disabled) return; e.stopPropagation(); this._changeMonth(1); });

        // Footer actions
        this._cancelBtn?.addEventListener('click', () => { if (!this._disabled) this._closeModal(); });
        this._todayBtn?.addEventListener('click',  () => { if (!this._disabled) this._selectToday(); });

        // Click outside panel closes it
        this._modal?.addEventListener('click', (e) => {
            if (e.target === this._modal) this._closeModal();
        });

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this._modal && !this._modal.classList.contains('invisible')) {
                this._closeModal();
            }
        });
    }
}
