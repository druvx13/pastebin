<?php
/*
 * config/config.php
 * ------------------
 * Database credentials and application-wide constants.
 * Edit these values before deploying.
 */

/* ===========================
   DATABASE CONFIGURATION
   =========================== */
define('DB_HOST', '127.0.0.1');    // MySQL host
define('DB_PORT', '3306');         // MySQL port
define('DB_USER', 'root');         // MySQL username
define('DB_PASS', 'password');     // MySQL password
define('DB_NAME', 'pastebin_app'); // Database name (auto-created on first run)

/* ===========================
   APP CONSTANTS
   =========================== */
define('TABLE_PASTES',        'pastes');
define('TABLE_COMMENTS',      'comments');
define('SLUG_LENGTH_BYTES',   5);           // slug bytes → hex chars = *2
define('DELETE_TOKEN_BYTES',  12);          // delete-token bytes → hex
define('PASTES_PER_PAGE',     15);          // pastes shown per sidebar page
define('COOKIE_LIFETIME',     30*24*3600);  // cookie lifetime (30 days)
define('COMMENT_MAX_LENGTH',  2000);        // max comment body length (chars)
define('COMMENT_NAME_MAX',    100);         // max commenter name length (chars)
