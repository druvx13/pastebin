<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo $pageTitle ?? 'Pastebin'; ?></title>
  <meta name="description" content="<?php echo $metaDescription ?? 'A simple and efficient open-source pastebin application.'; ?>">
  <meta name="robots" content="<?php echo $robotsMeta ?? 'index, follow'; ?>">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- highlight.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/javascript.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/php.min.js"></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">

</head>
<body class="bg-slate-50 text-slate-800">

  <!-- This div can hold application-level data for JavaScript -->
  <div id="app-data" data-cookie-lifetime="<?php echo COOKIE_LIFETIME; ?>"></div>

  <div class="min-h-screen flex items-start justify-center py-10 px-4">
    <div class="w-full max-w-6xl">

      <?php require 'partials/header.php'; ?>

      <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- MAIN CONTENT -->
        <section class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-5">
          <?php echo $content; // Main content from home.php or view_paste.php ?>
        </section>

        <!-- SIDEBAR -->
        <aside class="bg-white rounded-lg shadow-sm border p-5">
          <?php require 'partials/sidebar.php'; ?>
        </aside>
      </main>

      <?php require 'partials/footer.php'; ?>

    </div>
  </div>

  <!-- Custom JS -->
  <script src="js/main.js"></script>
</body>
</html>
