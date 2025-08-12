<div class="mb-4">
  <h3 class="text-sm font-medium mb-2">Recent pastes</h3>
  <?php if (empty($recentPastes)): ?>
    <div class="text-sm text-slate-500">No pastes yet.</div>
  <?php else: ?>
    <ul class="space-y-2 text-sm">
      <?php foreach ($recentPastes as $r): ?>
        <li class="flex items-start justify-between gap-2">
          <div>
            <a class="font-medium text-slate-700" href="?view=<?php echo htmlspecialchars($r['slug']); ?>"><?php echo htmlspecialchars($r['title'] ?: '[Untitled]'); ?></a>
            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['language']); ?> · <?php echo htmlspecialchars($r['created_at']); ?></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
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
