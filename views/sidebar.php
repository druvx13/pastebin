<?php
/*
 * views/sidebar.php
 * ------------------
 * Paginated list of recent pastes, shown in the right-hand sidebar.
 * Reads the ?p= query parameter for the current page.
 *
 * Expects: $pdo, $basePath (set in index.php)
 *          count_pastes(), fetch_pastes() (from src/helpers.php)
 */

$sidebarPage  = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$totalPastes  = count_pastes($pdo);
$totalPages   = $totalPastes > 0 ? (int) ceil($totalPastes / PASTES_PER_PAGE) : 1;
$sidebarPage  = min($sidebarPage, $totalPages); // clamp to valid range
$sidebarPastes = fetch_pastes($pdo, $sidebarPage, PASTES_PER_PAGE);
?>

<aside class="w-full lg:w-72 shrink-0">
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 lg:sticky lg:top-[4.5rem]">

    <!-- Header row -->
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-semibold text-slate-700 text-sm uppercase tracking-wide">Recent Pastes</h2>
      <span class="text-xs text-slate-400 tabular-nums"><?php echo $totalPastes; ?> total</span>
    </div>

    <?php if (count($sidebarPastes) === 0): ?>
      <p class="text-sm text-slate-400 py-2">No pastes yet.</p>

    <?php else: ?>

      <!-- Paste list -->
      <ul class="space-y-0 divide-y divide-slate-100 text-sm">
        <?php foreach ($sidebarPastes as $r): ?>
          <li class="py-2 first:pt-0 last:pb-0">
            <a class="block font-medium text-indigo-700 hover:text-indigo-900 hover:underline truncate leading-snug"
               href="<?php echo htmlspecialchars($basePath . '?view=' . urlencode($r['slug'])); ?>"
               title="<?php echo htmlspecialchars($r['title'] ?: '[Untitled]'); ?>">
              <?php echo htmlspecialchars($r['title'] ?: '[Untitled]'); ?>
            </a>
            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-400">
              <span class="inline-block bg-slate-100 text-slate-500 rounded px-1.5 py-0.5 font-mono">
                <?php echo htmlspecialchars($r['language']); ?>
              </span>
              <span><?php echo date('M j, Y', strtotime($r['created_at'])); ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Pagination controls (only shown when more than one page exists) -->
      <?php if ($totalPages > 1): ?>
        <div class="mt-4 pt-3 border-t border-slate-100">
          <div class="flex items-center justify-between text-xs">
            <!-- Previous page -->
            <?php if ($sidebarPage > 1): ?>
              <a href="<?php echo htmlspecialchars($basePath . '?p=' . ($sidebarPage - 1)); ?>"
                 class="flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                ← Prev
              </a>
            <?php else: ?>
              <span class="px-2.5 py-1.5 text-slate-300 select-none">← Prev</span>
            <?php endif; ?>

            <!-- Page indicator -->
            <span class="text-slate-500 tabular-nums">
              <?php echo $sidebarPage; ?> / <?php echo $totalPages; ?>
            </span>

            <!-- Next page -->
            <?php if ($sidebarPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars($basePath . '?p=' . ($sidebarPage + 1)); ?>"
                 class="flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                Next →
              </a>
            <?php else: ?>
              <span class="px-2.5 py-1.5 text-slate-300 select-none">Next →</span>
            <?php endif; ?>
          </div>
          <p class="text-center text-xs text-slate-400 mt-1 tabular-nums">
            Showing <?php echo count($sidebarPastes); ?> of <?php echo $totalPastes; ?>
          </p>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <!-- Tips panel -->
    <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400 space-y-1 leading-relaxed">
      <p>💡 <strong class="text-slate-500">Store</strong> the delete token shown after creation to delete from another device.</p>
      <p>🍪 Same-browser deletion works automatically via a cookie.</p>
      <p>💬 Comments are anonymous; providing a name is optional.</p>
    </div>

  </div>
</aside>
