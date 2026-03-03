<?php
/*
 * Pastebin — Entry Point
 *
 * See README.md for full setup instructions and directory layout.
 *
 * Quick start:
 * 1. Edit DB credentials in src/config.php.
 * 2. Deploy the project root to a PHP-enabled web server.
 * 3. Visit the page — the database and tables are created automatically.
 */

// Start session (required for one-time delete-token flash after paste creation)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/src/config.php';   // constants: DB_*, TABLE_*, app settings
require_once __DIR__ . '/src/db.php';        // sets $pdo; creates DB/tables if missing
require_once __DIR__ . '/src/helpers.php';   // functions, $languages, $basePath
require_once __DIR__ . '/src/handlers.php';  // handles POST/GET; sets $viewSlug, $paste, $comments, $recentPastes

require __DIR__ . '/views/layout.php';       // render full HTML page
