<?php
/*
 * src/actions.php
 * ----------------
 * HTTP request handlers for all POST actions and the raw-content GET endpoint.
 * Every handler redirects (PRG pattern) or exits after writing a response.
 */

/**
 * Handle POST action=create.
 * Validates input, inserts a new paste, stores the delete token in a session
 * flash and a cookie, then redirects to the paste view.
 *
 * @param PDO    $pdo
 * @param string $basePath  Base URL path (no query string)
 * @param array  $languages Valid language keys
 */
function handle_create(PDO $pdo, string $basePath, array $languages): void
{
    $title    = isset($_POST['title'])    ? trim($_POST['title'])    : null;
    $language = (isset($_POST['language']) && array_key_exists($_POST['language'], $languages))
                ? $_POST['language']
                : 'text';
    $content  = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($content === '') {
        header('Location: ' . $basePath . '?err=empty');
        exit;
    }

    try {
        $slug         = generate_unique_slug($pdo);
        $delete_token = generate_delete_token();

        $stmt = $pdo->prepare(
            'INSERT INTO `' . TABLE_PASTES . '`
             (slug, title, language, content, delete_token)
             VALUES (:slug, :title, :lang, :content, :dt)'
        );
        $stmt->execute([
            ':slug'    => $slug,
            ':title'   => $title ?: null,
            ':lang'    => $language,
            ':content' => $content,
            ':dt'      => $delete_token,
        ]);

        // Session flash (one-time display) + long-lived cookie fallback
        $_SESSION['last_paste'] = ['slug' => $slug, 'delete_token' => $delete_token];
        setcookie(
            'paste_token_' . $slug,
            $delete_token,
            time() + COOKIE_LIFETIME,
            '/', '', false, false
        );

        header('Location: ' . $basePath . '?view=' . urlencode($slug) . '&created=1');
        exit;

    } catch (PDOException $e) {
        header('Location: ' . $basePath . '?err=db');
        exit;
    }
}

/**
 * Handle POST action=delete.
 * Verifies the supplied token (or cookie fallback), deletes the paste, then
 * redirects to the homepage with a success flag.
 *
 * @param PDO    $pdo
 * @param string $basePath
 */
function handle_delete(PDO $pdo, string $basePath): void
{
    $slug           = isset($_POST['slug'])  ? $_POST['slug']  : '';
    $provided_token = isset($_POST['token']) ? $_POST['token'] : '';

    if ($slug === '') {
        header('Location: ' . $basePath . '?err=delparams');
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT delete_token FROM `' . TABLE_PASTES . '`
             WHERE slug = :s LIMIT 1'
        );
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            header('Location: ' . $basePath . '?err=notfound');
            exit;
        }

        $stored_token = $row['delete_token'];
        $allowed      = false;

        // Check explicitly provided token first
        if ($provided_token !== '' && hash_equals($stored_token, $provided_token)) {
            $allowed = true;
        }

        // Cookie fallback
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
            header('Location: ' . $basePath . '?err=badtoken');
            exit;
        }

        $del = $pdo->prepare('DELETE FROM `' . TABLE_PASTES . '` WHERE slug = :s');
        $del->execute([':s' => $slug]);
        setcookie('paste_token_' . $slug, '', time() - 3600, '/', '', false, false);

        header('Location: ' . $basePath . '?deleted=1');
        exit;

    } catch (PDOException $e) {
        header('Location: ' . $basePath . '?err=db');
        exit;
    }
}

/**
 * Handle POST action=add_comment.
 * Validates input, inserts an anonymous comment linked to the paste,
 * optionally stores the commenter name in a cookie, then redirects back
 * to the paste page anchored at #comments.
 *
 * @param PDO    $pdo
 * @param string $basePath
 */
function handle_add_comment(PDO $pdo, string $basePath): void
{
    $slug           = isset($_POST['slug'])           ? $_POST['slug']                       : '';
    $commenter_name = isset($_POST['commenter_name']) ? trim($_POST['commenter_name'])        : null;
    $comment_msg    = isset($_POST['comment_msg'])    ? trim($_POST['comment_msg'])           : '';

    if ($slug === '' || $comment_msg === '') {
        header('Location: ' . $basePath . '?err=commentparams');
        exit;
    }

    if (mb_strlen($comment_msg) > COMMENT_MAX_LENGTH) {
        header('Location: ' . $basePath . '?view=' . urlencode($slug) . '&err=commenttoolong');
        exit;
    }

    if ($commenter_name !== null && mb_strlen($commenter_name) > COMMENT_NAME_MAX) {
        $commenter_name = mb_substr($commenter_name, 0, COMMENT_NAME_MAX);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id FROM `' . TABLE_PASTES . '` WHERE slug = :s LIMIT 1'
        );
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            header('Location: ' . $basePath . '?err=notfound');
            exit;
        }

        $paste_id = (int) $row['id'];
        $ins      = $pdo->prepare(
            'INSERT INTO `' . TABLE_COMMENTS . '`
             (paste_id, name, message)
             VALUES (:pid, :n, :m)'
        );
        $ins->execute([
            ':pid' => $paste_id,
            ':n'   => $commenter_name ?: null,
            ':m'   => $comment_msg,
        ]);

        if ($commenter_name && $commenter_name !== '') {
            setcookie(
                'commenter_name',
                $commenter_name,
                time() + COOKIE_LIFETIME,
                '/', '', false, false
            );
        }

        header('Location: ' . $basePath . '?view=' . urlencode($slug) . '#comments');
        exit;

    } catch (PDOException $e) {
        header('Location: ' . $basePath . '?err=db');
        exit;
    }
}

/**
 * Handle GET ?raw=SLUG.
 * Returns the paste content as plain text and increments the view counter.
 *
 * @param PDO $pdo
 */
function handle_raw(PDO $pdo): void
{
    $slug = $_GET['raw'];
    $stmt = $pdo->prepare(
        'SELECT content FROM `' . TABLE_PASTES . '` WHERE slug = :s LIMIT 1'
    );
    $stmt->execute([':s' => $slug]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found';
        exit;
    }

    $upd = $pdo->prepare(
        'UPDATE `' . TABLE_PASTES . '` SET views = views + 1 WHERE slug = :s'
    );
    $upd->execute([':s' => $slug]);

    header('Content-Type: text/plain; charset=utf-8');
    echo $row['content'];
    exit;
}
