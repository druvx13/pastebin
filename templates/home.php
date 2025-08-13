<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Single-File Pastebin (Refactored)",
  "applicationCategory": "DeveloperApplication",
  "operatingSystem": "Any (Web)",
  "description": "A simple and efficient open-source pastebin application where you can create, share, and manage text pastes with syntax highlighting.",
  "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $basePath; ?>",
  "author": {
    "@type": "Organization",
    "name": "Community"
  }
}
</script>
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

<form method="post" action="/" class="space-y-4">
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

  <div>
    <label class="flex items-center">
      <input type="checkbox" name="allow_indexing" value="1" class="rounded">
      <span class="ml-2 text-sm text-slate-700">Allow search engines to index this paste</span>
    </label>
    <p class="text-xs text-slate-500 mt-1">Note: Pastes are private by default (`noindex`). Check this box to make your paste discoverable.</p>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Create Paste</button>
    <button type="reset" class="px-4 py-2 bg-slate-100 rounded">Reset</button>
    <div class="text-sm text-slate-500">A delete token will be shown once after creation and stored in a cookie for this browser.</div>
  </div>
</form>
