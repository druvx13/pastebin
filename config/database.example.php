<?php

/* ===========================
   DATABASE CONFIGURATION
   =========================== */
define('DB_HOST', '127.0.0.1');    // MySQL host
define('DB_PORT', '3306');         // MySQL port
define('DB_USER', 'your_db_user'); // MySQL username
define('DB_PASS', 'your_db_password');     // MySQL password
define('DB_NAME', 'pastebin_app'); // Desired DB name (auto-created if allowed)

/* ===========================
   APP CONSTANTS
   =========================== */
define('TABLE_PASTES', 'pastes');
define('TABLE_COMMENTS', 'comments');
define('SLUG_LENGTH_BYTES', 5);   // slug length (bytes -> hex chars = *2)
define('DELETE_TOKEN_BYTES', 12); // delete token bytes (hex)
define('RECENT_COUNT', 20);       // number of recent pastes on homepage
define('COOKIE_LIFETIME', 30*24*3600); // cookie lifetime for paste tokens (30 days)
define('COMMENT_MAX_LENGTH', 2000);
define('COMMENT_NAME_MAX', 100);
