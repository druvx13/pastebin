<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/helpers.php';

// Initialize Database
try {
    $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    $pdo = $db->getConnection();
    $db->initializeTables();
} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>Error</h1><p>Application could not start. Please check configuration and permissions.</p>";
    // In a real app, log the actual error message from $e->getMessage()
    exit;
}

// Handle POST requests
require_once __DIR__ . '/../src/actions.php';

// Common data for all views
$languages = get_supported_languages();
$recentPastes = fetch_recent($pdo);
$basePath = strtok($_SERVER["REQUEST_URI"], '?');

// View routing
$viewSlug = $_GET['view'] ?? null;
$rawSlug = $_GET['raw'] ?? null;

/*
 * VIEW: Raw content
 */
if ($rawSlug) {
    $stmt = $pdo->prepare("SELECT content FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => $rawSlug]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not found";
        exit;
    }

    $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE slug = :s");
    $upd->execute([':s' => $rawSlug]);

    header('Content-Type: text/plain; charset=utf-8');
    echo $row['content'];
    exit;
}

ob_start();

/*
 * VIEW: A specific paste
 */
if ($viewSlug) {
    $stmt = $pdo->prepare("SELECT id, slug, title, language, content, views, created_at, delete_token FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => $viewSlug]);
    $paste = $stmt->fetch();

    if ($paste) {
        // Increment view counter
        $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE id = :id");
        $upd->execute([':id' => $paste['id']]);
        $paste['views'] += 1;

        // Fetch comments
        $cstmt = $pdo->prepare("SELECT id, name, message, created_at FROM `" . TABLE_COMMENTS . "` WHERE paste_id = :pid ORDER BY created_at ASC");
        $cstmt->execute([':pid' => $paste['id']]);
        $comments = $cstmt->fetchAll();

        // Logic for showing the one-time delete token
        $show_token = null;
        if (isset($_SESSION['last_paste']) && $_SESSION['last_paste']['slug'] === $paste['slug']) {
            $show_token = $_SESSION['last_paste']['delete_token'];
            unset($_SESSION['last_paste']);
        } else {
            $cookieName = 'paste_token_' . $paste['slug'];
            if (isset($_COOKIE[$cookieName])) {
                $show_token = $_COOKIE[$cookieName];
            }
        }

        // Prefill commenter name from cookie
        $prefill_commenter = $_COOKIE['commenter_name'] ?? '';

        $pageTitle = $paste['title'] ? htmlspecialchars($paste['title']) . ' - Pastebin' : '[Untitled] - Pastebin';
        require __DIR__ . '/../templates/view_paste.php';

    } else {
        // Paste not found, show the homepage with an error
        http_response_code(404);
        $pageTitle = 'Not Found - Pastebin';
        $_GET['err'] = 'notfound'; // Use GET to signal error to home template
        require __DIR__ . '/../templates/home.php';
    }
/*
 * VIEW: Homepage (create form)
 */
} else {
    $pageTitle = 'Create New Paste - Pastebin';
    require __DIR__ . '/../templates/home.php';
}

$content = ob_get_clean();

// Render the final layout
require __DIR__ . '/../templates/layout.php';
