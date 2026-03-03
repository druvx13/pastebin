<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Pastebin' : 'Pastebin'; ?></title>

  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- highlight.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/javascript.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/php.min.js"></script>
  <script>hljs.configure({ ignoreUnescapedHTML: true });</script>

  <style>
    /* Syntax-highlighted code blocks */
    pre.hljs           { padding: 1rem; border-radius: .5rem; overflow: auto; font-size: 0.9rem; line-height: 1.5; }
    /* Thin, styled scrollbar for code blocks */
    pre                { scrollbar-width: thin; scrollbar-color: #475569 #1e293b; }
    pre::-webkit-scrollbar        { height: 6px; width: 6px; }
    pre::-webkit-scrollbar-track  { background: #1e293b; }
    pre::-webkit-scrollbar-thumb  { background: #475569; border-radius: 3px; }
    /* Smooth focus ring across interactive elements */
    :focus-visible     { outline: 2px solid #6366f1; outline-offset: 2px; }
  </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">

  <!-- ===== Top Navigation Bar ===== -->
  <nav class="bg-indigo-700 text-white shadow-md sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
      <!-- Brand -->
      <a href="<?php echo htmlspecialchars($basePath); ?>"
         class="flex items-center gap-2 font-bold text-lg tracking-tight hover:opacity-90 transition-opacity">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0
                   01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span>Pastebin</span>
      </a>

      <!-- Nav actions -->
      <div class="flex items-center gap-2">
        <a href="<?php echo htmlspecialchars($basePath); ?>"
           class="text-sm font-medium bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
          + New Paste
        </a>
      </div>
    </div>
  </nav>

  <!-- ===== Page Wrapper ===== -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Sidebar-first layout on mobile (sidebar stacks below main), side-by-side on ≥lg -->
    <div class="flex flex-col lg:flex-row gap-6">

      <!-- ===== MAIN CONTENT (injected by home.php / paste_view.php) ===== -->
      <main class="flex-1 min-w-0">
