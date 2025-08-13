<?php

// This file is included by index.php and handles all POST actions.
// It assumes $pdo is available.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = $_POST['action'] ?? null;
$basePath = strtok($_SERVER["REQUEST_URI"], '?');

switch ($action) {
    /*
     * ACTION: Create a new paste
     */
    case 'create':
        $title = isset($_POST['title']) ? trim($_POST['title']) : null;
        $language = isset($_POST['language']) && array_key_exists($_POST['language'], get_supported_languages()) ? $_POST['language'] : 'text';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $allow_indexing = (isset($_POST['allow_indexing']) && $_POST['allow_indexing'] == '1') ? 1 : 0;

        if ($content === '') {
            header("Location: /?err=empty");
            exit;
        }

        try {
            $slug = generate_unique_slug($pdo);
            $delete_token = generate_delete_token();

            $stmt = $pdo->prepare("INSERT INTO `" . TABLE_PASTES . "` (slug, title, language, content, delete_token, allow_indexing) VALUES (:slug, :title, :lang, :content, :dt, :idx)");
            $stmt->execute([
                ':slug' => $slug,
                ':title' => $title ?: null,
                ':lang' => $language,
                ':content' => $content,
                ':dt' => $delete_token,
                ':idx' => $allow_indexing,
            ]);

            // Use session flash for the token and a cookie for convenience.
            $_SESSION['last_paste'] = ['slug' => $slug, 'delete_token' => $delete_token];
            setcookie('paste_token_' . $slug, $delete_token, time() + COOKIE_LIFETIME, "/", "", false, false);

            header("Location: /view/" . urlencode($slug) . "?created=1");
            exit;
        } catch (PDOException $e) {
            header("Location: /?err=db");
            exit;
        }
        break;

    /*
     * ACTION: Delete a paste
     */
    case 'delete':
        $slug = $_POST['slug'] ?? '';
        $provided_token = $_POST['token'] ?? '';

        if ($slug === '') {
            header("Location: /?err=delparams");
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT delete_token FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
            $stmt->execute([':s' => $slug]);
            $row = $stmt->fetch();

            if (!$row) {
                header("Location: /?err=notfound");
                exit;
            }

            $stored_token = $row['delete_token'];
            $is_allowed = false;

            // Check provided token or fallback to cookie.
            if ($provided_token !== '' && hash_equals($stored_token, $provided_token)) {
                $is_allowed = true;
            } else {
                $cookieName = 'paste_token_' . $slug;
                if (isset($_COOKIE[$cookieName]) && hash_equals($stored_token, $_COOKIE[$cookieName])) {
                    $is_allowed = true;
                }
            }

            if (!$is_allowed) {
                header("Location: /view/" . urlencode($slug) . "?err=badtoken");
                exit;
            }

            $del = $pdo->prepare("DELETE FROM `" . TABLE_PASTES . "` WHERE slug = :s");
            $del->execute([':s' => $slug]);
            setcookie('paste_token_' . $slug, '', time() - 3600, "/", "", false, false);

            header("Location: /?deleted=1");
            exit;
        } catch (PDOException $e) {
            header("Location: /?err=db");
            exit;
        }
        break;

    /*
     * ACTION: Add a comment
     */
    case 'add_comment':
        $slug = $_POST['slug'] ?? '';
        $name = isset($_POST['commenter_name']) ? trim($_POST['commenter_name']) : null;
        $message = isset($_POST['comment_msg']) ? trim($_POST['comment_msg']) : '';

        if ($slug === '' || $message === '') {
            header("Location: /view/" . urlencode($slug) . "?err=commentparams");
            exit;
        }
        if (mb_strlen($message) > COMMENT_MAX_LENGTH) {
            header("Location: /view/" . urlencode($slug) . "?err=commenttoolong");
            exit;
        }
        if ($name !== null && mb_strlen($name) > COMMENT_NAME_MAX) {
            $name = mb_substr($name, 0, COMMENT_NAME_MAX);
        }

        try {
            // Get paste ID from slug
            $stmt = $pdo->prepare("SELECT id FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
            $stmt->execute([':s' => $slug]);
            $row = $stmt->fetch();
            if (!$row) {
                header("Location: /?err=notfound");
                exit;
            }
            $paste_id = (int)$row['id'];

            // Insert the comment
            $ins = $pdo->prepare("INSERT INTO `" . TABLE_COMMENTS . "` (paste_id, name, message) VALUES (:pid, :n, :m)");
            $ins->execute([':pid' => $paste_id, ':n' => $name ?: null, ':m' => $message]);

            // Set a cookie for the commenter's name for convenience
            if ($name) {
                setcookie('commenter_name', $name, time() + COOKIE_LIFETIME, "/", "", false, false);
            }

            header("Location: /view/" . urlencode($slug) . "#comments");
            exit;
        } catch (PDOException $e) {
            header("Location: /?err=db");
            exit;
        }
        break;
}
