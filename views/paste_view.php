<?php
/*
 * views/paste_view.php
 * ---------------------
 * Render a single paste: content with syntax highlighting, comments section,
 * delete form, and the one-time delete-token notice.
 *
 * Expects: $paste, $comments, $basePath (set in index.php)
 */

// One-time token display: prefer session flash, fall back to cookie
$show_token = null;
if (
    isset($_SESSION['last_paste']) &&
    is_array($_SESSION['last_paste']) &&
    $_SESSION['last_paste']['slug'] === $paste['slug']
) {
    $show_token = $_SESSION['last_paste']['delete_token'];
    unset($_SESSION['last_paste']);
} else {
    $cookieName = 'paste_token_' . $paste['slug'];
    if (
        isset($_COOKIE[$cookieName]) &&
        is_string($_COOKIE[$cookieName]) &&
        $_COOKIE[$cookieName] !== ''
    ) {
        $show_token = $_COOKIE[$cookieName];
    }
}

// Prefill commenter name from cookie
$prefill_commenter = isset($_COOKIE['commenter_name'])
    ? htmlspecialchars($_COOKIE['commenter_name'])
    : '';
?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">

  <!-- ===== Paste header ===== -->
  <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
    <div class="min-w-0">
      <h1 class="text-xl font-semibold text-slate-800 truncate">
        <?php echo htmlspecialchars($paste['title'] ?: '[Untitled]'); ?>
      </h1>
      <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
        <span class="inline-block bg-slate-100 text-slate-600 rounded px-2 py-0.5 font-mono">
          <?php echo htmlspecialchars($paste['language']); ?>
        </span>
        <span><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($paste['created_at']))); ?></span>
        <span><?php echo (int) $paste['views']; ?> view<?php echo $paste['views'] !== 1 ? 's' : ''; ?></span>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="flex shrink-0 items-center gap-2">
      <a href="<?php echo htmlspecialchars($basePath . '?raw=' . urlencode($paste['slug'])); ?>"
         target="_blank" rel="noopener"
         class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200
                text-slate-700 text-sm font-medium transition-colors">
        Raw
      </a>
      <button id="copyRawBtn"
              class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700
                     text-white text-sm font-medium transition-colors">
        Copy
      </button>
    </div>
  </div>

  <!-- ===== Delete-token notice (shown once after creation or from cookie) ===== -->
  <?php if (!empty($show_token)): ?>
    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm" role="status">
      <p class="font-medium text-amber-800 mb-2">🔑 Save your delete token</p>
      <div class="flex flex-col sm:flex-row gap-2">
        <input id="showDeleteToken" readonly
               class="flex-1 px-3 py-2 border border-amber-300 rounded-lg bg-white font-mono text-xs
                      focus:outline-none focus:ring-2 focus:ring-amber-400"
               value="<?php echo htmlspecialchars($show_token); ?>">
        <button id="copyDeleteToken"
                class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm
                       font-medium transition-colors whitespace-nowrap">
          Copy Token
        </button>
      </div>
      <p class="mt-2 text-xs text-amber-700">
        This token is required to delete the paste from another device. It will not be shown again.
      </p>
    </div>
  <?php endif; ?>

  <!-- ===== Code block ===== -->
  <div class="overflow-hidden rounded-lg">
    <pre><code id="codeBlock" class="language-<?php echo htmlspecialchars($paste['language']); ?>"><?php
      echo htmlspecialchars($paste['content']);
    ?></code></pre>
  </div>

  <!-- ===== Comments ===== -->
  <section id="comments">
    <h2 class="text-base font-semibold text-slate-700 mb-4">
      Comments
      <span class="ml-1 text-sm font-normal text-slate-400">(<?php echo count($comments); ?>)</span>
    </h2>

    <!-- Add-comment form -->
    <form method="post" class="mb-6 space-y-3">
      <input type="hidden" name="action" value="add_comment">
      <input type="hidden" name="slug"   value="<?php echo htmlspecialchars($paste['slug']); ?>">

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <input name="commenter_name" id="commenter_name"
               class="px-3 py-2 border border-slate-300 rounded-lg text-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                      placeholder:text-slate-400 transition"
               placeholder="Name (optional)"
               maxlength="<?php echo COMMENT_NAME_MAX; ?>"
               value="<?php echo $prefill_commenter; ?>">
        <div class="sm:col-span-2">
          <textarea name="comment_msg" id="comment_msg" rows="3"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                           placeholder:text-slate-400 resize-y transition"
                    placeholder="Write your comment…"
                    maxlength="<?php echo COMMENT_MAX_LENGTH; ?>"></textarea>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button type="submit"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm
                       font-medium rounded-lg transition-colors">
          Post Comment
        </button>
        <button type="reset"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm
                       font-medium rounded-lg transition-colors">
          Reset
        </button>
        <p class="ml-auto text-xs text-slate-400">
          Comments are public and anonymous; providing a name is optional.
        </p>
      </div>
    </form>

    <!-- Comment list -->
    <?php if (count($comments) === 0): ?>
      <p class="text-sm text-slate-400 py-2">No comments yet. Be the first to comment.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($comments as $c): ?>
          <div class="p-4 border border-slate-200 rounded-lg bg-slate-50">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
              <span class="text-sm font-medium text-slate-700">
                <?php echo htmlspecialchars($c['name'] ?: 'Anonymous'); ?>
              </span>
              <span class="text-xs text-slate-400">
                <?php echo htmlspecialchars(date('M j, Y H:i', strtotime($c['created_at']))); ?>
              </span>
            </div>
            <div class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">
              <?php echo nl2br(htmlspecialchars($c['message'])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ===== Delete paste ===== -->
  <section class="pt-4 border-t border-slate-100">
    <h2 class="text-sm font-semibold text-slate-600 mb-1">Delete this paste</h2>
    <p class="text-xs text-slate-400 mb-3">
      Enter the delete token shown at creation, or leave it empty if you're on the same browser (cookie-based).
    </p>
    <form method="post"
          class="flex flex-col sm:flex-row gap-2">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="slug"   value="<?php echo htmlspecialchars($paste['slug']); ?>">
      <input id="deleteTokenInput" name="token"
             placeholder="Delete token (or leave empty for cookie)"
             class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm
                    focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent
                    placeholder:text-slate-400 transition">
      <button type="submit"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium
                     rounded-lg transition-colors whitespace-nowrap">
        Delete Paste
      </button>
    </form>
  </section>

</div>

<!-- ===== Paste-page Scripts ===== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Apply syntax highlighting to the code block
  try { hljs.highlightElement(document.getElementById('codeBlock')); } catch (e) {}

  // "Copy" button — copies raw paste content to clipboard
  var copyRawBtn = document.getElementById('copyRawBtn');
  if (copyRawBtn) {
    copyRawBtn.addEventListener('click', function () {
      var raw = <?php echo json_encode($paste['content']); ?>;
      navigator.clipboard.writeText(raw).then(function () {
        copyRawBtn.textContent = 'Copied!';
        setTimeout(function () { copyRawBtn.textContent = 'Copy'; }, 1500);
      }).catch(function () { alert('Copy failed — please use the Raw link instead.'); });
    });
  }

  // "Copy Token" button — copies the shown delete token
  var copyTokenBtn = document.getElementById('copyDeleteToken');
  var tokenInput   = document.getElementById('showDeleteToken');
  if (copyTokenBtn && tokenInput) {
    copyTokenBtn.addEventListener('click', function () {
      navigator.clipboard.writeText(tokenInput.value).then(function () {
        copyTokenBtn.textContent = 'Copied!';
        setTimeout(function () { copyTokenBtn.textContent = 'Copy Token'; }, 1500);
      }).catch(function () { alert('Copy failed.'); });
    });
  }

  // Auto-fill the delete-token input from the cookie (if present)
  (function autofillDeleteToken() {
    var slug    = <?php echo json_encode($paste['slug']); ?>;
    var cname   = 'paste_token_' + slug;
    var match   = document.cookie.match('(^|;)\\s*' + cname + '\\s*=\\s*([^;]+)');
    var val     = match ? decodeURIComponent(match[2]) : null;
    var input   = document.getElementById('deleteTokenInput');
    if (val && input && input.value.trim() === '') {
      input.value = val;
    }
  })();

  // Auto-fill commenter name from the commenter_name cookie
  (function prefillCommenter() {
    var match = document.cookie.match('(^|;)\\s*commenter_name\\s*=\\s*([^;]+)');
    var val   = match ? decodeURIComponent(match[2]) : null;
    var el    = document.getElementById('commenter_name');
    if (val && el && el.value.trim() === '') {
      el.value = val;
    }
  })();
});
</script>
