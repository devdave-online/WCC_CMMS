<?php
/**
 * WCC UI asset version — single source of truth for cache-busting.
 * Bump on every CSS/JS deploy (replaces the old ?v=time() anti-pattern
 * that re-downloaded global.css on every request).
 */
if (!defined('WCC_UI_VERSION')) {
    define('WCC_UI_VERSION', '2.5.8');
}
