<?php
/**
 * WCC Error Handling Helpers
 *
 * Provides consistent, user-friendly error messages while logging real details.
 * 
 * Usage:
 *   require_once __DIR__ . '/error.php';
 *   wcc_user_error("Could not save the ticket. Please try again.");
 *
 * For development you can set WCC_DEBUG=true in your environment to show more details.
 */

if (!defined('WCC_DEBUG')) {
    define('WCC_DEBUG', false); // Set to true in development only
}

/**
 * Show a friendly error to the user and log the real issue.
 *
 * @param string $userMessage   Message shown to the end user
 * @param string $logDetails    Technical details (will be logged, never shown unless debug)
 * @param int    $statusCode    HTTP status (default 500)
 */
function wcc_user_error(string $userMessage, string $logDetails = '', int $statusCode = 500): void
{
    if ($logDetails) {
        error_log("[WCC Error] " . $logDetails);
    }

    http_response_code($statusCode);

    // For API calls, return JSON
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $userMessage,
            'error_code' => $statusCode
        ]);
        exit;
    }

    // For regular pages.
    // NOTE: deliberately self-contained (no inc/head.php) — this can fire
    // mid-render on a broken page, so it must not depend on page state.
    require_once __DIR__ . '/version.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - WCC</title>
        <link rel="stylesheet" href="/css/global.css?v=<?= WCC_UI_VERSION ?>">
        <style>
            .error-container {
                max-width: 600px;
                margin: 80px auto;
                padding: 30px;
                background: var(--panel-bg);
                border: 1px solid var(--panel-border);
                border-radius: var(--radius-md);
                text-align: center;
            }
            .error-icon { font-size: 3em; margin-bottom: 15px; }
            .error-title { color: var(--danger); margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <?php 
        // Try to include nav if possible
        if (file_exists(__DIR__ . '/../nav.php')) {
            include __DIR__ . '/../nav.php'; 
        }
        ?>
        <div class="dashboard-container">
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h2 class="error-title">Something went wrong</h2>
                <p><?= htmlspecialchars($userMessage) ?></p>
                
                <?php if (WCC_DEBUG && $logDetails): ?>
                    <details style="margin-top: 20px; text-align: left; font-size: var(--fs-sm);">
                        <summary>Technical details (debug mode)</summary>
                        <pre style="background: var(--surface-1); padding: 10px; border-radius: var(--radius-sm); overflow: auto;"><?= htmlspecialchars($logDetails) ?></pre>
                    </details>
                <?php endif; ?>
                
                <p style="margin-top: 25px; font-size: 0.9em; color: var(--text-secondary);">
                    If this keeps happening, please contact your administrator.
                </p>
                
                <a href="/index.php" class="nav-btn" style="margin-top: 15px;">Return to Tickets Hub</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Global exception handler
 */
set_exception_handler(function (Throwable $e) {
    $message = "Unexpected error occurred.";
    $details = $e->getMessage() . "\n" . $e->getTraceAsString();
    
    // Log to PHP error log
    error_log("[WCC Uncaught Exception] " . $details);
    
    wcc_user_error($message, $details);
});

/**
 * Convert PHP errors to exceptions (so they go through the handler)
 */
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
