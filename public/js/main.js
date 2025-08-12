// Initialize highlight.js
hljs.configure({ ignoreUnescapedHTML: true });

document.addEventListener('DOMContentLoaded', function() {
    // Highlight all code blocks on page load
    document.querySelectorAll('pre code').forEach((el) => {
        try {
            hljs.highlightElement(el);
        } catch (e) {
            console.error('Highlight.js error:', e);
        }
    });

    // Page-specific initializations
    // We can check for the existence of an element unique to a page
    // to decide which initializers to run.
    if (document.getElementById('codeBlock')) {
        initViewPastePage();
    }

    // This can run on all pages that have forms
    initForms();
});

/**
 * Initializes functionality specific to the "View Paste" page.
 */
function initViewPastePage() {
    // This div will hold data passed from PHP
    const pasteData = document.getElementById('paste-data');
    if (!pasteData) return;

    // Copy raw content button
    const copyRawBtn = document.getElementById('copyRawBtn');
    const rawContent = pasteData.dataset.rawContent;
    if (copyRawBtn && rawContent) {
        copyRawBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(rawContent);
                copyRawBtn.textContent = 'Copied';
                setTimeout(() => { copyRawBtn.textContent = 'Copy Raw'; }, 1400);
            } catch (e) {
                alert('Copy failed. Your browser may not support the Clipboard API or the page might not be served over HTTPS.');
            }
        });
    }

    // Copy delete token button
    const copyDeleteTokenBtn = document.getElementById('copyDeleteToken');
    const deleteTokenInput = document.getElementById('showDeleteToken');
    if (copyDeleteTokenBtn && deleteTokenInput) {
        copyDeleteTokenBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(deleteTokenInput.value);
                copyDeleteTokenBtn.textContent = 'Copied';
                setTimeout(() => { copyDeleteTokenBtn.textContent = 'Copy'; }, 1400);
            } catch (e) {
                alert('Copy failed.');
            }
        });
    }

    // Autofill delete token from cookie
    const pasteSlug = pasteData.dataset.slug;
    if (pasteSlug) {
        const token = getCookie('paste_token_' + pasteSlug);
        const deleteTokenField = document.getElementById('deleteTokenInput');
        if (token && deleteTokenField && deleteTokenField.value.trim() === '') {
            deleteTokenField.value = token;
        }
    }

    // Prefill commenter name from cookie
    const commenterName = getCookie('commenter_name');
    const commenterNameField = document.getElementById('commenter_name');
    if (commenterName && commenterNameField && commenterNameField.value.trim() === '') {
        commenterNameField.value = commenterName;
    }
}

/**
 * Initializes form handling, like confirmations and validations.
 */
function initForms() {
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const actionInput = form.querySelector('input[name="action"]');
        if (!actionInput) return;

        const action = actionInput.value;

        if (action === 'delete') {
            if (!confirm('Delete this paste? This action cannot be undone.')) {
                e.preventDefault();
            }
        }

        if (action === 'add_comment') {
            const msgField = form.querySelector('textarea[name="comment_msg"]');
            const commentData = document.getElementById('comment-data');
            const maxLength = parseInt(commentData?.dataset.maxLength || '2000');

            if (!msgField || msgField.value.trim().length === 0) {
                alert('Comment cannot be empty.');
                e.preventDefault();
                return;
            }
            if (msgField.value.length > maxLength) {
                alert(`Comment is too long. Maximum length is ${maxLength} characters.`);
                e.preventDefault();
                return;
            }

            // Store commenter name in cookie if provided
            const nameField = form.querySelector('input[name="commenter_name"]');
            const appData = document.getElementById('app-data');
            const cookieLifetime = parseInt(appData?.dataset.cookieLifetime || '2592000');
            if (nameField && nameField.value) {
                const name = nameField.value.trim();
                if (name) {
                    document.cookie = `commenter_name=${encodeURIComponent(name)};path=/;max-age=${cookieLifetime}`;
                }
            }
        }
    });
}

/**
 * Helper function to get a cookie by name.
 * @param {string} name The name of the cookie.
 * @returns {string|null} The value of the cookie or null if not found.
 */
function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    if (match) return decodeURIComponent(match[2]);
    return null;
}
