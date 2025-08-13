<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "headline": "<?php echo addslashes(htmlspecialchars($paste['title'] ?: '[Untitled]')); ?>",
  "datePublished": "<?php echo date('c', strtotime($paste['created_at'])); ?>",
  "author": {
    "@type": "Person",
    "name": "Anonymous"
  },
  "articleBody": "<?php echo substr(addslashes(htmlspecialchars($paste['content'])), 0, 5000); // Truncate for sanity ?>",
  "inLanguage": "<?php echo addslashes(htmlspecialchars($paste['language'])); ?>",
  "url": "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/view/' . htmlspecialchars($paste['slug']); ?>"
}
</script>
<!-- This div holds data for JavaScript to use -->
<div id="paste-data"
     data-slug="<?php echo htmlspecialchars($paste['slug']); ?>"
     data-raw-content="<?php echo htmlspecialchars($paste['content']); ?>">
</div>
<div id="comment-data" data-max-length="<?php echo COMMENT_MAX_LENGTH; ?>"></div>


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
    <a class="inline-flex items-center px-3 py-2 rounded bg-slate-100 hover:bg-slate-200 text-sm" href="/raw/<?php echo htmlspecialchars($paste['slug']); ?>" target="_blank">Raw</a>
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
  <form method="post" action="/view/<?php echo htmlspecialchars($paste['slug']); ?>" class="mb-4">
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

  <form method="post" action="/view/<?php echo htmlspecialchars($paste['slug']); ?>" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($paste['slug']); ?>">
    <input id="deleteTokenInput" name="token" placeholder="Delete token (or leave empty to use cookie)" class="px-3 py-2 border rounded col-span-2">
    <button class="px-3 py-2 bg-red-600 text-white rounded">Delete</button>
  </form>
</div>
