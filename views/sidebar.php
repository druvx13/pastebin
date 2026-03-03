<?php
/*
 * views/sidebar.php
 * ------------------
 * Paginated list of recent pastes in the right-hand sidebar.
 * Uses the ?p= query parameter for the current page.
 *
 * Expects: $pdo, $basePath (set in index.php)
 *          count_pastes(), fetch_pastes() (from src/helpers.php)
 */

$sidebarPage   = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$totalPastes   = count_pastes($pdo);
$totalPages    = $totalPastes > 0 ? (int) ceil($totalPastes / PASTES_PER_PAGE) : 1;
$sidebarPage   = min($sidebarPage, $totalPages);
$sidebarPastes = fetch_pastes($pdo, $sidebarPage, PASTES_PER_PAGE);
?>

<aside class="bg-white rounded-lg shadow-sm border p-5">
  <div class="mb-4">
    <h3 class="text-sm font-medium mb-2">
      Recent pastes
      <span class="text-xs font-normal text-slate-400 ml-1">(<?php echo $totalPastes; ?> total)</span>
    </h3>

    <?php if (count($sidebarPastes) === 0): ?>
      <div class="text-sm text-slate-500">No pastes yet.</div>
    <?php else: ?>
      <ul class="space-y-2 text-sm">
        <?php foreach ($sidebarPastes as $r): ?>
          <li class="flex items-start justify-between gap-2">
            <div>
              <a class="font-medium text-slate-700"
                 href="<?php echo htmlspecialchars($basePath . '?view=' . urlencode($r['slug'])); ?>">
                <?php echo htmlspecialchars($r['title'] ?: '[Untitled]'); ?>
              </a>
              <div class="text-xs text-slate-500">
                <?php echo htmlspecialchars($r['language']); ?>
                &middot;
                <?php echo htmlspecialchars($r['created_at']); ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Pagination controls -->
      <?php if ($totalPages > 1): ?>
        <div class="mt-3 pt-3 border-t flex items-center justify-between text-xs text-slate-500">
          <?php if ($sidebarPage > 1): ?>
            <a href="<?php echo htmlspecialchars($basePath . '?p=' . ($sidebarPage - 1)); ?>"
               class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded">← Prev</a>
          <?php else: ?>
            <span class="px-2 py-1 text-slate-300">← Prev</span>
          <?php endif; ?>

          <span><?php echo $sidebarPage; ?> / <?php echo $totalPages; ?></span>

          <?php if ($sidebarPage < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($basePath . '?p=' . ($sidebarPage + 1)); ?>"
               class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded">Next →</a>
          <?php else: ?>
            <span class="px-2 py-1 text-slate-300">Next →</span>
          <?php endif; ?>
        </div>
        <p class="text-center text-xs text-slate-400 mt-1">
          Showing <?php echo count($sidebarPastes); ?> of <?php echo $totalPastes; ?>
        </p>
      <?php endif; ?>
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
