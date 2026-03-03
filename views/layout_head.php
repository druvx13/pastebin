<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Pastebin' : 'Pastebin'; ?></title>

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
            <h1 class="text-2xl font-semibold">
              <a href="<?php echo htmlspecialchars($basePath); ?>" class="hover:underline">Pastebin</a>
            </h1>
            <p class="text-sm text-slate-500">Create, view, delete pastes. Anonymous comments available per paste.</p>
          </div>
          <div class="text-right text-xs text-slate-500">
            PHP + MySQL + Tailwind + highlight.js
          </div>
        </div>
      </header>

      <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- MAIN CONTENT -->
        <section class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-5">
