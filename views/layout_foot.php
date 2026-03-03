        </section><!-- end main section -->

        <!-- SIDEBAR -->
        <?php include __DIR__ . '/sidebar.php'; ?>

      </main><!-- end grid -->

      <footer class="mt-8 text-center text-xs text-slate-400">
        Pastebin — PHP + MySQL + Tailwind CSS + highlight.js
      </footer>
    </div><!-- end max-w-6xl -->
  </div><!-- end min-h-screen -->

  <!-- Global JavaScript -->
  <script>
    // Highlight all code blocks on load
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('pre code').forEach(function (el) {
        try { hljs.highlightElement(el); } catch (e) {}
      });
    });

    // Global form submission guard: delete confirmation + comment validation
    document.addEventListener('submit', function (e) {
      var form = e.target;
      var actionInput = form.querySelector('input[name="action"]');
      if (!actionInput) return;

      var action = actionInput.value;

      if (action === 'delete') {
        if (!confirm('Delete this paste? This action cannot be undone.')) {
          e.preventDefault();
        }
        return;
      }

      if (action === 'add_comment') {
        var msg = (form.comment_msg && form.comment_msg.value) ? form.comment_msg.value : '';
        if (msg.trim().length === 0) {
          alert('Comment cannot be empty.');
          e.preventDefault();
          return;
        }
        if (msg.length > <?php echo COMMENT_MAX_LENGTH; ?>) {
          alert('Comment is too long (max <?php echo COMMENT_MAX_LENGTH; ?> characters).');
          e.preventDefault();
          return;
        }
        // Persist commenter name in cookie for future prefills
        var nameInput = form.commenter_name;
        if (nameInput && nameInput.value.trim()) {
          document.cookie = 'commenter_name=' + encodeURIComponent(nameInput.value.trim())
            + ';path=/;max-age=<?php echo COOKIE_LIFETIME; ?>';
        }
      }
    });
  </script>
</body>
</html>
