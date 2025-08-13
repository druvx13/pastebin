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

// Simple routing logic based on the path
$uri = strtok($_SERVER["REQUEST_URI"], '?');
$parts = explode('/', trim($uri, '/'));

// The first part of the path determines the action
$route = $parts[0] ?? 'home';
$slug = $parts[1] ?? null;

/*
 * VIEW: Raw content
 */
if ($route === 'raw' && $slug) {
    $stmt = $pdo->prepare("SELECT content FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => $slug]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not found";
        exit;
    }

    $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE slug = :s");
    $upd->execute([':s' => $slug]);

    header('Content-Type: text/plain; charset=utf-8');
    echo $row['content'];
    exit;
}

ob_start();

switch ($route) {
    /*
     * VIEW: A specific paste
     */
    case 'view':
        if (!$slug) {
            // Redirect to home if no slug is provided
            header("Location: /");
            exit;
        }
        $stmt = $pdo->prepare("SELECT id, slug, title, language, content, views, created_at, delete_token, allow_indexing FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
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
            $metaDescription = 'Paste titled "' . htmlspecialchars($paste['title'] ?: 'Untitled') . '". Language: ' . htmlspecialchars($paste['language']) . '. ' . substr(htmlspecialchars($paste['content']), 0, 150) . '...';
            $robotsMeta = $paste['allow_indexing'] ? 'index, follow' : 'noindex, nofollow';
            require __DIR__ . '/../templates/view_paste.php';

        } else {
            // Paste not found, show the homepage with an error
            http_response_code(404);
            $pageTitle = 'Not Found - Pastebin';
            $metaDescription = 'The paste you were looking for could not be found.';
            $robotsMeta = 'noindex, nofollow';
            $_GET['err'] = 'notfound'; // Use GET to signal error to home template
            require __DIR__ . '/../templates/home.php';
        }
        break;

    /*
     * VIEW: Homepage (create form)
     */
    case 'home':
    default:
        $pageTitle = 'Create New Paste - Pastebin';
        $metaDescription = 'A simple and efficient open-source pastebin application where you can create, share, and manage text pastes with syntax highlighting.';
        $robotsMeta = 'index, follow';
        require __DIR__ . '/../templates/home.php';
        break;
}

$content = ob_get_clean();

// Render the final layout
require __DIR__ . '/../templates/layout.php';
