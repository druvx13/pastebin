<?php
/*
 * views/home.php
 * ---------------
 * Create-paste form and homepage error/success notices.
 *
 * Expects: $languages, $basePath (set in index.php)
 */
?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">

  <!-- Page title -->
  <h1 class="text-xl font-semibold text-slate-800 mb-5">Create a New Paste</h1>

  <!-- ===== Notices ===== -->
  <?php if (isset($_GET['err'])): ?>
    <div class="mb-5 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2
          0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      <span>
        <?php
          $err = $_GET['err'];
          if      ($err === 'empty')          echo 'Content cannot be empty.';
          elseif  ($err === 'db')             echo 'A database error occurred. Please try again.';
          elseif  ($err === 'badtoken')       echo 'Invalid or missing delete token.';
          elseif  ($err === 'notfound')       echo 'Paste not found.';
          elseif  ($err === 'commenttoolong') echo 'Comment is too long (max ' . COMMENT_MAX_LENGTH . ' characters).';
          else                                echo 'An unexpected error occurred. Please try again.';
        ?>
      </span>
    </div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="mb-5 flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700" role="status">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0
          00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414
          0l4-4z" clip-rule="evenodd"/>
      </svg>
      <span>Paste deleted successfully.</span>
    </div>
  <?php endif; ?>

  <!-- ===== Create Paste Form ===== -->
  <form method="post" class="space-y-5">
    <input type="hidden" name="action" value="create">

    <!-- Title -->
    <div>
      <label for="paste-title" class="block text-sm font-medium text-slate-700 mb-1">
        Title <span class="font-normal text-slate-400">(optional)</span>
      </label>
      <input id="paste-title" name="title" maxlength="255"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm
                    focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                    placeholder:text-slate-400 transition"
             placeholder="Short description of your paste">
    </div>

    <!-- Language selector -->
    <div class="flex flex-wrap items-end gap-4">
      <div>
        <label for="paste-lang" class="block text-sm font-medium text-slate-700 mb-1">Language</label>
        <select id="paste-lang" name="language"
                class="px-3 py-2 border border-slate-300 rounded-lg text-sm
                       focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                       bg-white transition">
          <?php foreach ($languages as $key => $label): ?>
            <option value="<?php echo htmlspecialchars($key); ?>">
              <?php echo htmlspecialchars($label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <p class="ml-auto text-xs text-slate-400 self-end pb-0.5">
        Use the <strong>Raw</strong> link to fetch paste content programmatically.
      </p>
    </div>

    <!-- Content textarea -->
    <div>
      <label for="paste-content" class="block text-sm font-medium text-slate-700 mb-1">Content</label>
      <textarea id="paste-content" name="content" rows="16"
                class="w-full px-3 py-3 border border-slate-300 rounded-lg font-mono text-sm
                       focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                       placeholder:text-slate-400 resize-y transition"
                placeholder="// paste your code or text here"></textarea>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap items-center gap-3">
      <button type="submit"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
                     text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
        Create Paste
      </button>
      <button type="reset"
              class="px-5 py-2 bg-slate-100 hover:bg-slate-200 active:bg-slate-300
                     text-slate-700 text-sm font-medium rounded-lg transition-colors">
        Reset
      </button>
      <p class="text-xs text-slate-400 ml-auto">
        A delete token will be shown once after creation and saved in your browser.
      </p>
    </div>
  </form>

</div>
