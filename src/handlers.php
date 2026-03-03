<?php
/* ===========================
   REQUEST HANDLERS
   =========================== */

/* 1) Create paste (POST action=create) */
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $title    = isset($_POST['title'])   ? trim($_POST['title'])   : null;
    $language = (isset($_POST['language']) && array_key_exists($_POST['language'], $languages))
                    ? $_POST['language'] : 'text';
    $content  = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($content === '') {
        header("Location: " . $basePath . "?err=empty");
        exit;
    }

    try {
        $slug         = generate_unique_slug($pdo);
        $delete_token = generate_delete_token();

        $stmt = $pdo->prepare(
            "INSERT INTO `" . TABLE_PASTES . "` (slug, title, language, content, delete_token)
             VALUES (:slug, :title, :lang, :content, :dt)"
        );
        $stmt->execute([
            ':slug'    => $slug,
            ':title'   => $title ?: null,
            ':lang'    => $language,
            ':content' => $content,
            ':dt'      => $delete_token,
        ]);

        // Session flash and cookie so the token survives the redirect
        $_SESSION['last_paste'] = ['slug' => $slug, 'delete_token' => $delete_token];
        setcookie('paste_token_' . $slug, $delete_token, time() + COOKIE_LIFETIME, '/', '', false, false);

        header("Location: " . $basePath . "?view=" . urlencode($slug) . "&created=1");
        exit;
    } catch (PDOException $e) {
        header("Location: " . $basePath . "?err=db");
        exit;
    }
}

/* 2) Delete paste (POST action=delete) */
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $slug           = isset($_POST['slug'])  ? $_POST['slug']  : '';
    $provided_token = isset($_POST['token']) ? $_POST['token'] : '';

    if ($slug === '') {
        header("Location: " . $basePath . "?err=delparams");
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT delete_token FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1"
        );
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            header("Location: " . $basePath . "?err=notfound");
            exit;
        }
        $stored_token = $row['delete_token'];
        $allowed      = false;

        if ($provided_token !== '' && hash_equals($stored_token, $provided_token)) {
            $allowed = true;
        }
        if (!$allowed) {
            $cookieName = 'paste_token_' . $slug;
            if (
                isset($_COOKIE[$cookieName]) &&
                is_string($_COOKIE[$cookieName]) &&
                $_COOKIE[$cookieName] !== '' &&
                hash_equals($stored_token, $_COOKIE[$cookieName])
            ) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            header("Location: " . $basePath . "?err=badtoken");
            exit;
        }

        $del = $pdo->prepare("DELETE FROM `" . TABLE_PASTES . "` WHERE slug = :s");
        $del->execute([':s' => $slug]);
        setcookie('paste_token_' . $slug, '', time() - 3600, '/', '', false, false);

        header("Location: " . $basePath . "?deleted=1");
        exit;
    } catch (PDOException $e) {
        header("Location: " . $basePath . "?err=db");
        exit;
    }
}

/* 3) Add anonymous comment (POST action=add_comment) */
if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $slug           = isset($_POST['slug'])           ? $_POST['slug']           : '';
    $commenter_name = isset($_POST['commenter_name']) ? trim($_POST['commenter_name']) : null;
    $comment_msg    = isset($_POST['comment_msg'])    ? trim($_POST['comment_msg'])    : '';

    if ($slug === '' || $comment_msg === '') {
        header("Location: " . $basePath . "?err=commentparams");
        exit;
    }
    if (mb_strlen($comment_msg) > COMMENT_MAX_LENGTH) {
        header("Location: " . $basePath . "?view=" . urlencode($slug) . "&err=commenttoolong");
        exit;
    }
    if ($commenter_name !== null && mb_strlen($commenter_name) > COMMENT_NAME_MAX) {
        $commenter_name = mb_substr($commenter_name, 0, COMMENT_NAME_MAX);
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1"
        );
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            header("Location: " . $basePath . "?err=notfound");
            exit;
        }
        $paste_id = (int) $row['id'];

        $ins = $pdo->prepare(
            "INSERT INTO `" . TABLE_COMMENTS . "` (paste_id, name, message) VALUES (:pid, :n, :m)"
        );
        $ins->execute([
            ':pid' => $paste_id,
            ':n'   => $commenter_name ?: null,
            ':m'   => $comment_msg,
        ]);

        // Persist commenter name in cookie to pre-fill on next visit
        if ($commenter_name && $commenter_name !== '') {
            setcookie('commenter_name', $commenter_name, time() + COOKIE_LIFETIME, '/', '', false, false);
        }

        header("Location: " . $basePath . "?view=" . urlencode($slug) . "#comments");
        exit;
    } catch (PDOException $e) {
        header("Location: " . $basePath . "?err=db");
        exit;
    }
}

/* 4) Raw endpoint: ?raw=SLUG (returns paste as plain text) */
if (isset($_GET['raw'])) {
    $slug = $_GET['raw'];
    $stmt = $pdo->prepare(
        "SELECT content FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1"
    );
    $stmt->execute([':s' => $slug]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not found";
        exit;
    }
    // Increment view counter
    $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE slug = :s");
    $upd->execute([':s' => $slug]);
    header('Content-Type: text/plain; charset=utf-8');
    echo $row['content'];
    exit;
}

/* 5) View paste: ?view=SLUG */
$viewSlug = isset($_GET['view']) ? $_GET['view'] : null;
$paste    = null;
$comments = [];

if ($viewSlug) {
    $stmt = $pdo->prepare(
        "SELECT id, slug, title, language, content, views, created_at, delete_token
           FROM `" . TABLE_PASTES . "`
          WHERE slug = :s
          LIMIT 1"
    );
    $stmt->execute([':s' => $viewSlug]);
    $paste = $stmt->fetch();

    if ($paste) {
        // Increment view counter
        $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE id = :id");
        $upd->execute([':id' => $paste['id']]);
        $paste['views'] += 1;

        // Fetch comments for this paste
        $cstmt = $pdo->prepare(
            "SELECT id, name, message, created_at
               FROM `" . TABLE_COMMENTS . "`
              WHERE paste_id = :pid
              ORDER BY created_at ASC"
        );
        $cstmt->execute([':pid' => $paste['id']]);
        $comments = $cstmt->fetchAll();
    } else {
        $viewSlug = null; // paste not found
    }
}

/* Recent pastes for the sidebar */
$recentPastes = fetch_recent($pdo);
