<?php
/*
 * views/layout.php
 * Full HTML shell: <head>, <body>, header, main grid (section + aside), footer, global JS.
 * Includes views/paste.php or views/create.php depending on $viewSlug/$paste,
 * and always includes views/sidebar.php.
 */
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pastebin</title>

  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- highlight.js (CDN) -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/javascript.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/php.min.js"></script>
  <script>hljs.configure({ ignoreUnescapedHTML: true });</script>

  <style>
    pre.hljs { padding: 1rem; border-radius: .5rem; overflow: auto; font-size: 0.95rem; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen flex items-start justify-center py-10 px-4">
    <div class="w-full max-w-6xl">

      <!-- Header -->
      <header class="mb-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold">Pastebin</h1>
            <p class="text-sm text-slate-500">
              Create, view, and delete pastes. Anonymous comments available per paste.
            </p>
          </div>
          <div class="text-right text-xs text-slate-500">
            PHP + MySQL + Tailwind + highlight.js
          </div>
        </div>
      </header>

      <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main content area -->
        <section class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-5">
          <?php if ($viewSlug && $paste): ?>
            <?php require __DIR__ . '/paste.php'; ?>
          <?php else: ?>
            <?php require __DIR__ . '/create.php'; ?>
          <?php endif; ?>
        </section>

        <!-- Sidebar -->
        <aside class="bg-white rounded-lg shadow-sm border p-5">
          <?php require __DIR__ . '/sidebar.php'; ?>
        </aside>

      </main>

      <footer class="mt-8 text-center text-xs text-slate-400">
        Pastebin demo with comments.
        For production: secure DB credentials, enable HTTPS, and build Tailwind locally.
      </footer>

    </div>
  </div>

  <!-- Global client-side JS -->
  <script>
    // Highlight all code blocks on load
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('pre code').forEach(function (el) {
        try { hljs.highlightElement(el); } catch (e) {}
      });
    });

    // Delete confirmation + comment client-side validation
    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (!form || !form.querySelector('input[name="action"]')) return;
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
        // Persist commenter name in cookie
        const name = (form.commenter_name && form.commenter_name.value)
                       ? form.commenter_name.value.trim() : '';
        if (name) {
          document.cookie = 'commenter_name=' + encodeURIComponent(name)
                          + ';path=/;max-age=<?php echo COOKIE_LIFETIME; ?>';
        }
      }
    });
  </script>
</body>
</html>
