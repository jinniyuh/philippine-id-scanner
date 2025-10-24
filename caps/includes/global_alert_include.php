<?php
/**
 * Global Alert Include
 * Add this to any module to enable global alert functionality
 */
?>
<!-- Global Alert System -->
<link rel="stylesheet" href="assets/css/global_alert.css">
<script src="assets/js/global_alert.js"></script>

<style>
/* Additional global alert styles for better integration */
.global-alert-modal {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.global-alert-modal * {
    box-sizing: border-box;
}

/* Ensure alert appears above all other content */
#globalAlertContainer {
    z-index: 99999 !important;
}

/* Pulse animation for critical alerts */
.global-alert-critical .global-alert-header {
    animation: criticalPulse 2s infinite;
}

@keyframes criticalPulse {
    0% { background-color: #dc3545; }
    50% { background-color: #ff4757; }
    100% { background-color: #dc3545; }
}

/* High risk alert animation */
.global-alert-high .global-alert-header {
    animation: highRiskPulse 3s infinite;
}

@keyframes highRiskPulse {
    0% { background-color: #fd7e14; }
    50% { background-color: #ff9800; }
    100% { background-color: #fd7e14; }
}
</style>

<script>
// Enhanced global alert functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize global alert if not already initialized
    if (typeof globalAlert === 'undefined') {
        globalAlert = new GlobalAlert();
    }
    
    // Add keyboard shortcut to close alert (ESC key)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && globalAlert && globalAlert.isVisible) {
            globalAlert.hide();
        }
    });
    
    // Add click outside to close functionality
    document.addEventListener('click', function(e) {
        if (globalAlert && globalAlert.isVisible && e.target.classList.contains('global-alert-overlay')) {
            globalAlert.hide();
        }
    });
});
</script>
