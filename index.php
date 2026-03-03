<?php
/*
 * index.php — Application entry point
 * =====================================
 * This is the only public file. It boots the app, routes requests, fetches
 * data, then delegates rendering to the views in views/.
 *
 * Directory layout
 * ----------------
 *   config/config.php   — DB credentials & app constants
 *   src/db.php          — PDO factory + schema auto-creation
 *   src/helpers.php     — slug/token helpers, pagination, language list
 *   src/actions.php     — POST handlers (create, delete, add_comment)
 *   views/layout_head.php — HTML <head> + sticky navbar
 *   views/layout_foot.php — footer + global JS + </body></html>
 *   views/home.php        — create-paste form
 *   views/paste_view.php  — view paste, comments, delete form
 *   views/sidebar.php     — paginated recent-pastes sidebar
 *
 * Installation
 * ------------
 * 1. Edit DB_HOST / DB_USER / DB_PASS / DB_NAME in config/config.php.
 * 2. Point a PHP-enabled web server at this directory.
 * 3. Open the site — the database and tables are auto-created on first load.
 */

/* ---- Session (needed for one-time delete-token flash) ---- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---- Bootstrap ---- */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/actions.php';

$pdo       = db_connect();
$languages = get_languages();
$basePath  = strtok($_SERVER['REQUEST_URI'], '?');

/* ---- Route POST actions (PRG pattern) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    switch ($action) {
        case 'create':      handle_create($pdo, $basePath, $languages);  break;
        case 'delete':      handle_delete($pdo, $basePath);              break;
        case 'add_comment': handle_add_comment($pdo, $basePath);         break;
    }
}

/* ---- Raw-content endpoint: ?raw=SLUG ---- */
if (isset($_GET['raw'])) {
    handle_raw($pdo);
    // handle_raw() always exits
}

/* ---- View a paste: ?view=SLUG ---- */
$viewSlug = isset($_GET['view']) ? $_GET['view'] : null;
$paste    = null;
$comments = [];

if ($viewSlug) {
    $stmt = $pdo->prepare(
        'SELECT id, slug, title, language, content, views, created_at, delete_token
         FROM `' . TABLE_PASTES . '`
         WHERE slug = :s LIMIT 1'
    );
    $stmt->execute([':s' => $viewSlug]);
    $paste = $stmt->fetch();

    if ($paste) {
        // Increment view counter
        $upd = $pdo->prepare(
            'UPDATE `' . TABLE_PASTES . '` SET views = views + 1 WHERE id = :id'
        );
        $upd->execute([':id' => $paste['id']]);
        $paste['views'] += 1;

        // Fetch comments (oldest first)
        $cstmt = $pdo->prepare(
            'SELECT id, name, message, created_at
             FROM `' . TABLE_COMMENTS . '`
             WHERE paste_id = :pid
             ORDER BY created_at ASC'
        );
        $cstmt->execute([':pid' => $paste['id']]);
        $comments = $cstmt->fetchAll();
    } else {
        $viewSlug = null; // slug not found — fall through to homepage
    }
}

/* ---- Page title ---- */
$pageTitle = $paste
    ? ($paste['title'] ?: 'Untitled paste')
    : 'New Paste';

/* ---- Render ---- */
include __DIR__ . '/views/layout_head.php';

if ($paste) {
    include __DIR__ . '/views/paste_view.php';
} else {
    include __DIR__ . '/views/home.php';
}

include __DIR__ . '/views/layout_foot.php';
