<!-- Global Confirmation Modal -->
<div id="wccGlobalConfirmModal" class="wcc-modal" role="dialog" aria-modal="true" aria-labelledby="wccModalTitle" aria-describedby="wccConfirmMessage" style="display: none; position: fixed; inset: 0; background: rgba(2, 6, 23, 0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center;">
    <div class="wcc-modal-content" style="background: var(--modal-bg); border: 1px solid var(--panel-border); border-radius: var(--radius-lg); padding: 30px; width: 90%; max-width: 400px; box-shadow: var(--shadow-3); transform: translateY(-20px); opacity: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; gap: 20px; animation: none;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div id="wccModalIcon" aria-hidden="true" style="background: var(--danger-bg); color: var(--danger); width: 50px; height: 50px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.5em; flex-shrink: 0;">
                ⚠️
            </div>
            <div>
                <h3 id="wccModalTitle" style="margin: 0; color: var(--text-primary); font-size: 1.2em; font-weight: 700;"><?= __e('modal.are_you_sure') ?></h3>
                <p id="wccConfirmMessage" style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: var(--fs-sm); line-height: 1.5;"></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
            <button type="button" id="wccConfirmCancelBtn" onclick="closeWccConfirm()" class="btn" style="padding: 10px 20px;"><?= __e('btn.cancel') ?></button>
            <button type="button" id="wccConfirmActionBtn" class="btn btn-danger" style="padding: 10px 20px;"><?= __e('common.delete') ?></button>
        </div>
    </div>
</div>

<script>
    function resetWccModalUI() {
        const titleEl = document.getElementById('wccModalTitle');
        const iconEl = document.getElementById('wccModalIcon');
        const cancelBtn = document.getElementById('wccConfirmCancelBtn');
        const actionBtn = document.getElementById('wccConfirmActionBtn');

        titleEl.innerText = (typeof t === 'function' ? t('modal.are_you_sure') : 'Are you sure?');
        iconEl.innerHTML = '⚠️';
        iconEl.style.color = 'var(--danger)';
        iconEl.style.background = 'var(--danger-bg)';
        cancelBtn.style.display = 'inline-flex';
        actionBtn.className = 'btn btn-danger';
        actionBtn.style.padding = '10px 20px';
    }

    function openWccConfirm(message, actionUrlOrCallback, buttonText) {
        if (buttonText === undefined || buttonText === null) {
            buttonText = (typeof t === 'function' ? t('common.confirm') : 'Confirm');
        }
        resetWccModalUI();
        const modal = document.getElementById('wccGlobalConfirmModal');
        if (!modal) {
            // Fallback if confirm overlay failed to load
            if (typeof actionUrlOrCallback === 'function') {
                if (window.confirm(String(message || 'Confirm?'))) actionUrlOrCallback();
            } else if (typeof actionUrlOrCallback === 'string' && window.confirm(String(message || 'Confirm?'))) {
                window.location.href = actionUrlOrCallback;
            }
            return;
        }
        const msgEl = document.getElementById('wccConfirmMessage');
        const btnEl = document.getElementById('wccConfirmActionBtn');
        const content = modal.querySelector('.wcc-modal-content');

        if (msgEl) msgEl.innerText = message == null ? '' : String(message);
        if (btnEl) btnEl.innerText = buttonText;

        if (btnEl) {
            btnEl.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (typeof actionUrlOrCallback === 'function') {
                    closeWccConfirm();
                    // Defer so modal close animation / focus steal does not cancel submit
                    window.setTimeout(function () { actionUrlOrCallback(); }, 0);
                } else if (typeof actionUrlOrCallback === 'string') {
                    window.location.href = actionUrlOrCallback;
                }
            };
        }

        modal.classList.add('open');
        modal.style.display = 'flex';
        if (content) {
            void modal.offsetWidth;
            content.style.transform = 'translateY(0)';
            content.style.opacity = '1';
        }
        var cancelBtn = document.getElementById('wccConfirmCancelBtn');
        if (cancelBtn) cancelBtn.focus();
    }

    function openWccAlert(title, message, redirectUrl = null) {
        resetWccModalUI();
        const modal = document.getElementById('wccGlobalConfirmModal');
        const msgEl = document.getElementById('wccConfirmMessage');
        const btnEl = document.getElementById('wccConfirmActionBtn');
        const cancelBtn = document.getElementById('wccConfirmCancelBtn');
        const titleEl = document.getElementById('wccModalTitle');
        const iconEl = document.getElementById('wccModalIcon');
        const content = modal.querySelector('.wcc-modal-content');

        titleEl.innerText = title;
        msgEl.innerText = message;
        cancelBtn.style.display = 'none';
        const errTitle = (typeof t === 'function' ? t('common.error') : 'Error');
        const valTitle = (typeof t === 'function' ? t('common.validation_error') : 'Validation Error');
        if (title === 'Error' || title === errTitle || title.includes('Validation') || title === valTitle) {
            iconEl.innerHTML = '⚠️';
            iconEl.style.color = 'var(--danger)';
            iconEl.style.background = 'var(--danger-bg)';
            btnEl.className = 'btn btn-danger';
            btnEl.innerText = (typeof t === 'function' ? t('common.ok') : 'OK');
        } else {
            iconEl.innerHTML = '✅';
            iconEl.style.color = 'var(--success)';
            iconEl.style.background = 'var(--success-bg)';
            btnEl.className = 'btn btn-primary';

            // Premium feature: 5 second auto-close/redirect
            if (window.wccAlertInterval) clearInterval(window.wccAlertInterval);
            let countdown = 5;
            const okLabel = function(n) {
                return (typeof t === 'function' ? t('modal.ok_countdown', { n: n }) : ('OK (' + n + 's)'));
            };
            btnEl.innerText = okLabel(countdown);

            window.wccAlertInterval = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    btnEl.innerText = okLabel(countdown);
                } else {
                    clearInterval(window.wccAlertInterval);
                    btnEl.click();
                }
            }, 1000);
        }

        btnEl.onclick = function(e) {
            e.preventDefault();
            if (window.wccAlertInterval) clearInterval(window.wccAlertInterval);
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                closeWccConfirm();
            }
        };

        modal.style.display = 'flex';
        void modal.offsetWidth;
        content.style.transform = 'translateY(0)';
        content.style.opacity = '1';
        btnEl.focus();
    }

    function closeWccConfirm() {
        const modal = document.getElementById('wccGlobalConfirmModal');
        if (!modal) return;
        const content = modal.querySelector('.wcc-modal-content');

        if (window.wccAlertInterval) clearInterval(window.wccAlertInterval);
        if (content) {
            content.style.transform = 'translateY(-20px)';
            content.style.opacity = '0';
        }

        setTimeout(function () {
            modal.style.display = 'none';
            modal.classList.remove('open');
        }, 300);
    }

    // Esc closes the confirm/alert dialog
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('wccGlobalConfirmModal');
            if (modal && modal.style.display === 'flex') closeWccConfirm();
        }
    });
</script>
