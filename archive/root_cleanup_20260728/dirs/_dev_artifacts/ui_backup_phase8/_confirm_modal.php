<!-- Global Confirmation Modal -->
<div id="wccGlobalConfirmModal" class="wcc-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center;">
    <div class="wcc-modal-content" style="background: var(--panel-bg); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 30px; width: 90%; max-width: 400px; box-shadow: 0 15px 40px rgba(0,0,0,0.4); transform: translateY(-20px); opacity: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div id="wccModalIcon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5em; flex-shrink: 0;">
                ⚠️
            </div>
            <div>
                <h3 id="wccModalTitle" style="margin: 0; color: var(--text-primary); font-size: 1.2em; font-weight: 700;">Are you sure?</h3>
                <p id="wccConfirmMessage" style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 0.95em; line-height: 1.5;"></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
            <button id="wccConfirmCancelBtn" onclick="closeWccConfirm()" class="nav-btn" style="background: rgba(255,255,255,0.05); color: var(--text-primary); border: 1px solid rgba(255,255,255,0.1); padding: 10px 20px;">Cancel</button>
            <a id="wccConfirmActionBtn" href="#" class="nav-btn" style="background: #ef4444; color: white; border: none; padding: 10px 20px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center;">Delete</a>
        </div>
    </div>
</div>

<script>
    function resetWccModalUI() {
        const titleEl = document.getElementById('wccModalTitle');
        const iconEl = document.getElementById('wccModalIcon');
        const cancelBtn = document.getElementById('wccConfirmCancelBtn');
        const actionBtn = document.getElementById('wccConfirmActionBtn');
        
        titleEl.innerText = 'Are you sure?';
        iconEl.innerHTML = '⚠️';
        iconEl.style.color = '#ef4444';
        iconEl.style.background = 'rgba(239, 68, 68, 0.15)';
        cancelBtn.style.display = 'inline-block';
        actionBtn.style.background = '#ef4444';
    }

    function openWccConfirm(message, actionUrlOrCallback, buttonText = 'Confirm') {
        resetWccModalUI();
        const modal = document.getElementById('wccGlobalConfirmModal');
        const msgEl = document.getElementById('wccConfirmMessage');
        const btnEl = document.getElementById('wccConfirmActionBtn');
        const content = modal.querySelector('.wcc-modal-content');
        
        msgEl.innerText = message;
        btnEl.innerText = buttonText;
        
        btnEl.onclick = function(e) {
            e.preventDefault();
            if (typeof actionUrlOrCallback === 'function') {
                closeWccConfirm();
                actionUrlOrCallback();
            } else if (typeof actionUrlOrCallback === 'string') {
                window.location.href = actionUrlOrCallback;
            }
        };
        
        modal.style.display = 'flex';
        void modal.offsetWidth;
        content.style.transform = 'translateY(0)';
        content.style.opacity = '1';
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
        if (title === 'Error' || title.includes('Validation')) {
            iconEl.innerHTML = '⚠️';
            iconEl.style.color = '#ef4444';
            iconEl.style.background = 'rgba(239, 68, 68, 0.15)';
            btnEl.style.background = '#ef4444';
            btnEl.innerText = 'OK';
        } else {
            iconEl.innerHTML = '✅';
            iconEl.style.color = '#22c55e';
            iconEl.style.background = 'rgba(34, 197, 94, 0.15)';
            btnEl.style.background = '#3b82f6';
            
            // Premium feature: 5 second auto-close/redirect
            if (window.wccAlertInterval) clearInterval(window.wccAlertInterval);
            let countdown = 5;
            btnEl.innerText = `OK (${countdown}s)`;
            
            window.wccAlertInterval = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    btnEl.innerText = `OK (${countdown}s)`;
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
    }

    function closeWccConfirm() {
        const modal = document.getElementById('wccGlobalConfirmModal');
        const content = modal.querySelector('.wcc-modal-content');
        
        content.style.transform = 'translateY(-20px)';
        content.style.opacity = '0';
        
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
</script>
