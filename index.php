<?php
/*
================================================================================
Single-file Pastebin — Monolithic index.php
--------------------------------------------------------------------------------
This monolithic file implements a Pastebin-like application with:

- Create / View / Delete pastes.
- Anonymous comments on individual paste pages.
- Syntax highlighting via highlight.js (CDN).
- Responsive UI with Tailwind CSS (CDN).
- MySQL database auto-creation and table initialization.
- One-time display of delete-token upon creation (session flash) + cookie fallback.
- Comments table auto-created. Comments are plain-text, escaped on render.
- All code (PHP, HTML, CSS, JS) in one file for minimal setup.

INSTALL
1. Edit DB credentials in DATABASE CONFIG section below.
2. Upload to PHP-enabled webroot as index.php.
3. Visit page. DB and tables auto-created on first run.
================================================================================
*/

/* -------------------------
   SESSION (required for one-time token flash)
   ------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===========================
   DATABASE CONFIGURATION
   =========================== */
define('DB_HOST', '127.0.0.1');    // MySQL host
define('DB_PORT', '3306');         // MySQL port
define('DB_USER', 'root');         // MySQL username
define('DB_PASS', 'password');     // MySQL password
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

/* ===========================
   BOOTSTRAP: CONNECT & INIT
   =========================== */
try {
    // Connect without DB to allow DB creation if needed
    $dsnNoDB = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsnNoDB, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create DB if missing
    $safeDb = str_replace('`', '``', DB_NAME);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Reconnect with DB selected
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create pastes table if not exists
    $createPastesSQL = "
      CREATE TABLE IF NOT EXISTS `" . TABLE_PASTES . "` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(64) NOT NULL UNIQUE,
        title VARCHAR(255) DEFAULT NULL,
        language VARCHAR(64) DEFAULT 'text',
        content LONGTEXT NOT NULL,
        delete_token VARCHAR(128) NOT NULL,
        views INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createPastesSQL);

    // Create comments table if not exists (anonymous comments)
    // columns: id, paste_id (FK), name (nullable), message, created_at
    $createCommentsSQL = "
      CREATE TABLE IF NOT EXISTS `" . TABLE_COMMENTS . "` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        paste_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(150) DEFAULT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (paste_id),
        FOREIGN KEY (paste_id) REFERENCES `" . TABLE_PASTES . "` (id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createCommentsSQL);

} catch (PDOException $e) {
    http_response_code(500);
    echo "<h1>Database error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

/* ===========================
   HELPERS
   =========================== */

/** Generate a unique slug (hex). Retries then fallback. */
function generate_unique_slug(PDO $pdo) {
    for ($i=0; $i<8; $i++) {
        $slug = bin2hex(random_bytes(SLUG_LENGTH_BYTES));
        $stmt = $pdo->prepare("SELECT 1 FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
        if (!$stmt->fetch()) return $slug;
    }
    return preg_replace('/[^a-z0-9]/', '', uniqid('', true));
}

/** Generate a delete token (hex) */
function generate_delete_token() {
    return bin2hex(random_bytes(DELETE_TOKEN_BYTES));
}

/* Language options for select and highlight classes */
$languages = [
    'text' => 'Plain Text',
    'bash' => 'Bash',
    'json' => 'JSON',
    'xml' => 'XML',
    'html' => 'HTML',
    'css' => 'CSS',
    'javascript' => 'JavaScript',
    'typescript' => 'TypeScript',
    'php' => 'PHP',
    'python' => 'Python',
    'java' => 'Java',
    'c' => 'C',
    'cpp' => 'C++',
    'csharp' => 'C#',
    'go' => 'Go',
    'ruby' => 'Ruby',
    'rust' => 'Rust',
    'kotlin' => 'Kotlin',
    'sql' => 'SQL',
];

/* Base path for links */
$basePath = strtok($_SERVER["REQUEST_URI"], '?');

/* ===========================
   REQUEST HANDLERS
   =========================== */

/* 1) Create paste (improved: session flash + cookie) */
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $language = isset($_POST['language']) && array_key_exists($_POST['language'], $languages) ? $_POST['language'] : 'text';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($content === '') {
        header("Location: " . $basePath . "?err=empty");
        exit;
    }

    try {
        $slug = generate_unique_slug($pdo);
        $delete_token = generate_delete_token();

        $stmt = $pdo->prepare("INSERT INTO `" . TABLE_PASTES . "` (slug, title, language, content, delete_token) VALUES (:slug, :title, :lang, :content, :dt)");
        $stmt->execute([
            ':slug' => $slug,
            ':title' => $title ?: null,
            ':lang' => $language,
            ':content' => $content,
            ':dt' => $delete_token
        ]);

        // session flash & cookie for convenience
        $_SESSION['last_paste'] = ['slug' => $slug, 'delete_token' => $delete_token];
        setcookie('paste_token_' . $slug, $delete_token, time() + COOKIE_LIFETIME, "/", "", false, false);

        header("Location: " . $basePath . "?view=" . urlencode($slug) . "&created=1");
        exit;
    } catch (PDOException $e) {
        header("Location: " . $basePath . "?err=db");
        exit;
    }
}

/* 2) Delete paste (accept token or cookie fallback) */
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $slug = isset($_POST['slug']) ? $_POST['slug'] : '';
    $provided_token = isset($_POST['token']) ? $_POST['token'] : '';

    if ($slug === '') {
        header("Location: " . $basePath . "?err=delparams");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT delete_token FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            header("Location: " . $basePath . "?err=notfound");
            exit;
        }
        $stored_token = $row['delete_token'];
        $allowed = false;

        if ($provided_token !== '') {
            if (hash_equals($stored_token, $provided_token)) $allowed = true;
        }
        if (!$allowed) {
            $cookieName = 'paste_token_' . $slug;
            if (isset($_COOKIE[$cookieName]) && is_string($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] !== '') {
                if (hash_equals($stored_token, $_COOKIE[$cookieName])) $allowed = true;
            }
        }

        if (!$allowed) {
            header("Location: " . $basePath . "?err=badtoken");
            exit;
        }

        $del = $pdo->prepare("DELETE FROM `" . TABLE_PASTES . "` WHERE slug = :s");
        $del->execute([':s' => $slug]);
        setcookie('paste_token_' . $slug, '', time() - 3600, "/", "", false, false);

        header("Location: " . $basePath . "?deleted=1");
        exit;
    } catch (PDOException $e) {
        header("Location: " . $basePath . "?err=db");
        exit;
    }
}

/* 3) Add anonymous comment (action=add_comment)
   - expects: slug (paste slug), message (required), name (optional)
   - stores comment linked to paste.id
*/
if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $slug = isset($_POST['slug']) ? $_POST['slug'] : '';
    $commenter_name = isset($_POST['commenter_name']) ? trim($_POST['commenter_name']) : null;
    $comment_msg = isset($_POST['comment_msg']) ? trim($_POST['comment_msg']) : '';

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
        // get paste id
        $stmt = $pdo->prepare("SELECT id FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            header("Location: " . $basePath . "?err=notfound");
            exit;
        }
        $paste_id = (int)$row['id'];

        // insert comment
        $ins = $pdo->prepare("INSERT INTO `" . TABLE_COMMENTS . "` (paste_id, name, message) VALUES (:pid, :n, :m)");
        $ins->execute([
            ':pid' => $paste_id,
            ':n' => $commenter_name ?: null,
            ':m' => $comment_msg
        ]);

        // optional: store commenter name in cookie to prefill next time
        if ($commenter_name && $commenter_name !== '') {
            setcookie('commenter_name', $commenter_name, time() + COOKIE_LIFETIME, "/", "", false, false);
        }

        // redirect back to paste with anchor for comments
        header("Location: " . $basePath . "?view=" . urlencode($slug) . "#comments");
        exit;
    } catch (PDOException $e) {
        header("Location: " . $basePath . "?err=db");
        exit;
    }
}

/* Raw endpoint: ?raw=SLUG (plaintext) */
if (isset($_GET['raw'])) {
    $slug = $_GET['raw'];
    $stmt = $pdo->prepare("SELECT content FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => $slug]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not found";
        exit;
    }
    // increment views
    $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE slug = :s");
    $upd->execute([':s' => $slug]);
    header('Content-Type: text/plain; charset=utf-8');
    echo $row['content'];
    exit;
}

/* View paste (if ?view=SLUG) - fetch paste and its comments */
$viewSlug = isset($_GET['view']) ? $_GET['view'] : null;
$paste = null;
$comments = [];
if ($viewSlug) {
    $stmt = $pdo->prepare("SELECT id, slug, title, language, content, views, created_at, delete_token FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => $viewSlug]);
    $paste = $stmt->fetch();

    if ($paste) {
        // increment views
        $upd = $pdo->prepare("UPDATE `" . TABLE_PASTES . "` SET views = views + 1 WHERE id = :id");
        $upd->execute([':id' => $paste['id']]);
        $paste['views'] += 1;

        // fetch comments for this paste
        $cstmt = $pdo->prepare("SELECT id, name, message, created_at FROM `" . TABLE_COMMENTS . "` WHERE paste_id = :pid ORDER BY created_at ASC");
        $cstmt->execute([':pid' => $paste['id']]);
        $comments = $cstmt->fetchAll();
    } else {
        $viewSlug = null; // not found
    }
}

/* Recent pastes for sidebar */
function fetch_recent(PDO $pdo, $count = RECENT_COUNT) {
    $stmt = $pdo->prepare("SELECT slug, title, language, created_at FROM `" . TABLE_PASTES . "` ORDER BY created_at DESC LIMIT :cnt");
    $stmt->bindValue(':cnt', (int)$count, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
$recentPastes = fetch_recent($pdo);

/* ===========================
   RENDER HTML UI (monolithic)
   =========================== */
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pastebin — Single-file</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- highlight.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/javascript.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/php.min.js"></script>
  <script>hljs.configure({ignoreUnescapedHTML:true});</script>

  <style>
    pre.hljs { padding: 1rem; border-radius: .5rem; overflow:auto; font-size:0.95rem; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen flex items-start justify-center py-10 px-4">
    <div class="w-full max-w-6xl">

      <!-- Header -->
      <header class="mb-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold">Pastebin — Single-file</h1>
            <p class="text-sm text-slate-500">Create, view, delete pastes. Anonymous comments available per paste.</p>
          </div>
          <div class="text-right text-xs text-slate-500">
            PHP + MySQL + Tailwind + highlight.js
          </div>
        </div>
      </header>

      <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- MAIN -->
        <section class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-5">

          <?php if ($viewSlug && $paste): ?>
            <?php
              // One-time token display logic: session flash first, then cookie fallback
              $show_token = null;
              if (isset($_SESSION['last_paste']) && is_array($_SESSION['last_paste']) && $_SESSION['last_paste']['slug'] === $paste['slug']) {
                  $show_token = $_SESSION['last_paste']['delete_token'];
                  unset($_SESSION['last_paste']);
              } else {
                  $cookieName = 'paste_token_' . $paste['slug'];
                  if (isset($_COOKIE[$cookieName]) && is_string($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] !== '') {
                      $show_token = $_COOKIE[$cookieName];
                  }
              }

              // Prefill commenter name from cookie if exists
              $prefill_commenter = isset($_COOKIE['commenter_name']) ? $_COOKIE['commenter_name'] : '';
            ?>

            <div class="mb-4 flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-medium"><?php echo htmlspecialchars($paste['title'] ?: '[Untitled]'); ?></h2>
                <div class="text-sm text-slate-500 mt-1">
                  Language: <span class="font-medium"><?php echo htmlspecialchars($paste['language']); ?></span>
                  &nbsp;•&nbsp;
                  Created: <?php echo htmlspecialchars($paste['created_at']); ?>
                  &nbsp;•&nbsp;
                  Views: <?php echo (int)$paste['views']; ?>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <a class="inline-flex items-center px-3 py-2 rounded bg-slate-100 hover:bg-slate-200 text-sm" href="<?php echo htmlspecialchars($basePath . '?raw=' . urlencode($paste['slug'])); ?>" target="_blank">Raw</a>
                <button id="copyRawBtn" class="inline-flex items-center px-3 py-2 rounded bg-indigo-600 text-white text-sm">Copy Raw</button>
              </div>
            </div>

            <?php if (!empty($show_token)): ?>
              <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm">
                <div class="font-medium">Delete token (store securely)</div>
                <div class="mt-2 flex items-center gap-2">
                  <input id="showDeleteToken" readonly class="px-3 py-2 border rounded w-full" value="<?php echo htmlspecialchars($show_token); ?>">
                  <button id="copyDeleteToken" class="px-3 py-2 bg-indigo-600 text-white rounded">Copy</button>
                </div>
                <div class="text-xs text-slate-500 mt-2">This token is required to delete the paste if you don't use the same browser. It is shown once after creation.</div>
              </div>
            <?php endif; ?>

            <div class="prose max-w-none mb-6">
              <pre><code id="codeBlock" class="language-<?php echo htmlspecialchars($paste['language']); ?>"><?php
                echo htmlspecialchars($paste['content']);
              ?></code></pre>
            </div>

            <!-- Comments section -->
            <div id="comments" class="mt-6">
              <h3 class="text-md font-semibold mb-3">Comments (<?php echo count($comments); ?>)</h3>

              <!-- Add comment form -->
              <form method="post" class="mb-4" onsubmit="return validateComment(this);">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($paste['slug']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                  <input name="commenter_name" id="commenter_name" class="px-3 py-2 border rounded" placeholder="Name (optional)" maxlength="<?php echo COMMENT_NAME_MAX; ?>" value="<?php echo htmlspecialchars($prefill_commenter); ?>">
                  <div class="md:col-span-2">
                    <textarea name="comment_msg" id="comment_msg" rows="3" class="w-full px-3 py-2 border rounded" placeholder="Write your comment..." maxlength="<?php echo COMMENT_MAX_LENGTH; ?>"></textarea>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded">Post Comment</button>
                  <button type="reset" class="px-3 py-2 bg-slate-100 rounded">Reset</button>
                  <div class="text-xs text-slate-500 ml-auto">Comments are anonymous; provide a name to display it.</div>
                </div>
              </form>

              <!-- Comments list -->
              <?php if (count($comments) === 0): ?>
                <div class="text-sm text-slate-500">No comments yet. Be the first to comment.</div>
              <?php else: ?>
                <div class="space-y-3">
                  <?php foreach ($comments as $c): ?>
                    <div class="p-3 border rounded bg-slate-50">
                      <div class="flex items-center justify-between">
                        <div class="text-sm font-medium"><?php echo htmlspecialchars($c['name'] ?: 'Anonymous'); ?></div>
                        <div class="text-xs text-slate-400"><?php echo htmlspecialchars($c['created_at']); ?></div>
                      </div>
                      <div class="mt-2 text-sm text-slate-800 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($c['message'])); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

            </div>

            <!-- Delete section -->
            <div class="mt-6 text-sm text-slate-600">
              <p class="mb-2">To delete this paste, enter the delete token (shown above at creation) or use the same browser (cookie-based). If you don't have either, contact the site admin or remove via DB.</p>

              <form method="post" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2" onsubmit="return confirm('Delete this paste? This action cannot be undone.')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($paste['slug']); ?>">
                <input id="deleteTokenInput" name="token" placeholder="Delete token (or leave empty to use cookie)" class="px-3 py-2 border rounded col-span-2">
                <button class="px-3 py-2 bg-red-600 text-white rounded">Delete</button>
              </form>
            </div>

            <script>
              document.addEventListener('DOMContentLoaded', function(){
                try { hljs.highlightElement(document.getElementById('codeBlock')); } catch(e){}
                // copy raw
                const crBtn = document.getElementById('copyRawBtn');
                if (crBtn) {
                  crBtn.addEventListener('click', async function(){
                    try {
                      const raw = <?php echo json_encode($paste['content']); ?>;
                      await navigator.clipboard.writeText(raw);
                      this.textContent = 'Copied';
                      setTimeout(()=> this.textContent = 'Copy Raw', 1400);
                    } catch (e) { alert('Copy failed'); }
                  });
                }
                // copy delete token
                const copyBtn = document.getElementById('copyDeleteToken');
                const delInput = document.getElementById('showDeleteToken');
                if (copyBtn && delInput) {
                  copyBtn.addEventListener('click', async function(){
                    try {
                      await navigator.clipboard.writeText(delInput.value);
                      this.textContent = 'Copied';
                      setTimeout(()=> this.textContent = 'Copy', 1400);
                    } catch(e) { alert('Copy failed'); }
                  });
                }
                // Autofill delete token input from cookie
                (function autofillFromCookie(){
                  try {
                    const slug = <?php echo json_encode($paste['slug']); ?>;
                    const cname = 'paste_token_' + slug;
                    const val = (function getCookie(n){ const m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'); return m ? decodeURIComponent(m[2]) : null; })(cname);
                    if (val) {
                      const deleteInput = document.getElementById('deleteTokenInput');
                      if (deleteInput && deleteInput.value.trim() === '') deleteInput.value = val;
                    }
                  } catch(e){}
                })();
                // Prefill commenter name input from commenter_name cookie
                (function prefillCommenter(){
                  try {
                    const v = (function getCookie(n){ const m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'); return m ? decodeURIComponent(m[2]) : null; })('commenter_name');
                    if (v) {
                      const el = document.getElementById('commenter_name');
                      if (el && el.value.trim() === '') el.value = v;
                    }
                  } catch(e){}
                })();
              });

              // client-side validation for comment form
              function validateComment(form) {
                const msg = form.comment_msg.value || '';
                if (msg.trim().length === 0) {
                  alert('Comment cannot be empty.');
                  return false;
                }
                if (msg.length > <?php echo COMMENT_MAX_LENGTH; ?>) {
                  alert('Comment too long.');
                  return false;
                }
                return true;
              }
            </script>

          <?php else: ?>
            <!-- Create page -->
            <h2 class="text-lg font-medium mb-3">Create a new paste</h2>

            <?php if (isset($_GET['err'])): ?>
              <div class="mb-3 text-sm text-red-600">
                <?php
                  $e = $_GET['err'];
                  if ($e === 'empty') echo 'Content cannot be empty.';
                  elseif ($e === 'db') echo 'A database error occurred.';
                  elseif ($e === 'badtoken') echo 'Invalid token provided for deletion.';
                  elseif ($e === 'notfound') echo 'Paste not found.';
                  elseif ($e === 'commenttoolong') echo 'Comment too long.';
                  else echo 'An error occurred.';
                ?>
              </div>
            <?php elseif (isset($_GET['deleted'])): ?>
              <div class="mb-3 text-sm text-green-700">Paste deleted.</div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
              <input type="hidden" name="action" value="create">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Title (optional)</label>
                <input name="title" maxlength="255" class="w-full px-3 py-2 border rounded" placeholder="Short description">
              </div>

              <div class="flex gap-4 items-center">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Language</label>
                  <select name="language" class="w-48 px-3 py-2 border rounded">
                    <?php foreach ($languages as $key => $label): ?>
                      <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="ml-auto text-sm text-slate-500">Tip: Use Raw link to fetch plain content programmatically.</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Content</label>
                <textarea name="content" rows="14" class="w-full px-3 py-3 border rounded font-mono" placeholder="// paste your code here"></textarea>
              </div>

              <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Create Paste</button>
                <button type="reset" class="px-4 py-2 bg-slate-100 rounded">Reset</button>
                <div class="text-sm text-slate-500">A delete token will be shown once after creation and stored in a cookie for this browser.</div>
              </div>
            </form>

          <?php endif; ?>
        </section>

        <!-- SIDEBAR -->
        <aside class="bg-white rounded-lg shadow-sm border p-5">
          <div class="mb-4">
            <h3 class="text-sm font-medium mb-2">Recent pastes</h3>
            <?php if (count($recentPastes) === 0): ?>
              <div class="text-sm text-slate-500">No pastes yet.</div>
            <?php else: ?>
              <ul class="space-y-2 text-sm">
                <?php foreach ($recentPastes as $r): ?>
                  <li class="flex items-start justify-between gap-2">
                    <div>
                      <a class="font-medium text-slate-700" href="<?php echo htmlspecialchars($basePath . '?view=' . urlencode($r['slug'])); ?>"><?php echo htmlspecialchars($r['title'] ?: '[Untitled]'); ?></a>
                      <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['language']); ?> · <?php echo htmlspecialchars($r['created_at']); ?></div>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <div class="mt-4 text-xs text-slate-500">
            Tips:
            <ul class="list-disc ml-4 mt-2">
              <li>Store the delete token shown immediately to delete the paste from another device.</li>
              <li>Cookie fallback allows deletion from the same browser without manual token copy.</li>
              <li>Comments are anonymous; name is optional. Provide a name to display it.</li>
            </ul>
          </div>
        </aside>
      </main>

      <footer class="mt-8 text-center text-xs text-slate-400">
        Single-file Pastebin demo with comments. For production: secure DB credentials, enable HTTPS, and build Tailwind.
      </footer>
    </div>
  </div>

  <!-- Client JS -->
  <script>
    // highlight code blocks on load
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('pre code').forEach((el) => {
        try { hljs.highlightElement(el); } catch(e){}
      });
    });

    // Basic delete confirmation and simple client-side comment validation previously defined per-page
    document.addEventListener('submit', function(e){
      const form = e.target;
      if (form && form.querySelector('input[name="action"]')) {
        const action = form.querySelector('input[name="action"]').value;
        if (action === 'delete') {
          if (!confirm('Delete this paste? This action cannot be undone.')) {
            e.preventDefault();
            return false;
          }
        }
        if (action === 'add_comment') {
          const msg = form.comment_msg.value || '';
          if (msg.trim().length === 0) {
            alert('Comment cannot be empty.');
            e.preventDefault();
            return false;
          }
          if (msg.length > <?php echo COMMENT_MAX_LENGTH; ?>) {
            alert('Comment too long.');
            e.preventDefault();
            return false;
          }
          // store commenter name in cookie if provided
          const name = (form.commenter_name && form.commenter_name.value) ? form.commenter_name.value.trim() : '';
          if (name) {
            document.cookie = 'commenter_name=' + encodeURIComponent(name) + ';path=/;max-age=<?php echo COOKIE_LIFETIME; ?>';
          }
        }
      }
    });
  </script>
</body>
</html>
