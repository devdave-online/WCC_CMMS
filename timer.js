// ==========================================
// AUTONOMOUS INDUSTRIAL TIMER
// ==========================================
(function() {
    // 1. The Global Factory Setting
    let SESSION_MINUTES = 10; 

    // 2. The Tester Override
    const debugMinutes = localStorage.getItem('debugTimerMinutes');
    if (debugMinutes) {
        SESSION_MINUTES = parseFloat(debugMinutes);
        console.log("⚠️ DEBUG TIMER ACTIVE: " + SESSION_MINUTES + " minutes");
    }

    const SESSION_DURATION = SESSION_MINUTES * 60 * 1000;
    const TOTAL_BLOCKS = 60;

    function renderBlocks() {
        const blockContainer = document.getElementById('blockContainer');
        if (!blockContainer) return; // Exit silently if no timer UI on this page
        
        blockContainer.innerHTML = ''; // Clear just in case
        for (let i = 0; i < TOTAL_BLOCKS; i++) { 
            let block = document.createElement('div'); 
            block.className = 'time-block'; 
            blockContainer.appendChild(block); 
        }
    }

    function updateVisuals(expiry) {
        const distance = expiry - new Date().getTime();
        let activeBlocks = Math.ceil((distance / SESSION_DURATION) * TOTAL_BLOCKS);
        const blocks = document.querySelectorAll('.time-block');
        
        let currentColor = '#22c55e'; // Green
        
        blocks.forEach((block, index) => {
            if (index < activeBlocks) {
                if (activeBlocks > 6) { 
                    block.style.background = '#22c55e'; block.style.boxShadow = '0 0 10px rgba(34, 197, 94, 0.6)'; 
                    currentColor = '#22c55e';
                } else if (activeBlocks > 2) { 
                    block.style.background = '#ea580c'; block.style.boxShadow = '0 0 10px rgba(234, 88, 12, 0.8)'; 
                    currentColor = '#ea580c'; // Orange
                } else { 
                    block.style.background = '#ef4444'; block.style.boxShadow = '0 0 10px rgba(239, 68, 68, 0.9)'; 
                    currentColor = '#ef4444'; // Red
                }
            } else { 
                block.style.background = 'transparent'; block.style.boxShadow = 'none'; 
            }
        });

        // Update the SVG Mini Gauge colors if it exists on the page
        const gaugeValue = document.getElementById('miniGaugeValue');
        const gaugeNeedle = document.getElementById('miniGaugeNeedle');
        const gaugeDot = document.getElementById('miniGaugeDot');
        
        if (gaugeValue) gaugeValue.setAttribute('stroke', currentColor);
        if (gaugeNeedle) gaugeNeedle.setAttribute('stroke', currentColor);
        if (gaugeDot) gaugeDot.setAttribute('fill', currentColor);

        // Lockout Trigger
        if (distance <= 0) {
            localStorage.removeItem('sessionExpiry');
            window.location.href = '/login.php?expired=1';
        }
    }

    function initTimer() {
        const blockContainer = document.getElementById('blockContainer');
        if (!blockContainer) return;

        renderBlocks();

        let expiry = localStorage.getItem('sessionExpiry');
        const now = new Date().getTime();
        
        if (!expiry || now > expiry) { 
            expiry = now + SESSION_DURATION; 
            localStorage.setItem('sessionExpiry', expiry); 
        }

        // Run instantly to paint the blocks before the first second ticks
        updateVisuals(expiry);

        // Tick every second
        setInterval(() => {
            const currentExpiry = localStorage.getItem('sessionExpiry');
            if (currentExpiry) {
                updateVisuals(currentExpiry);
            } else {
                // Another tab hit 0 and cleared the expiry, or it was manually deleted. Lock out immediately.
                window.location.href = '/login.php?expired=1';
            }
        }, 1000);
    }

    // Auto-start regardless of where you put the <script> tag
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTimer);
    } else {
        initTimer();
    }
})();