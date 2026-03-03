<?php
/* ===========================
   HELPER FUNCTIONS
   =========================== */

/** Generate a unique slug (hex). Retries up to 8 times, then falls back. */
function generate_unique_slug(PDO $pdo): string
{
    for ($i = 0; $i < 8; $i++) {
        $slug = bin2hex(random_bytes(SLUG_LENGTH_BYTES));
        $stmt = $pdo->prepare("SELECT 1 FROM `" . TABLE_PASTES . "` WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
    }
    return preg_replace('/[^a-z0-9]/', '', uniqid('', true));
}

/** Generate a cryptographically random delete token (hex). */
function generate_delete_token(): string
{
    return bin2hex(random_bytes(DELETE_TOKEN_BYTES));
}

/** Fetch the most recent pastes for the sidebar. */
function fetch_recent(PDO $pdo, int $count = RECENT_COUNT): array
{
    $stmt = $pdo->prepare(
        "SELECT slug, title, language, created_at
           FROM `" . TABLE_PASTES . "`
          ORDER BY created_at DESC
          LIMIT :cnt"
    );
    $stmt->bindValue(':cnt', $count, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* ===========================
   SHARED VIEW DATA
   =========================== */

/** Supported language options for the syntax-highlight selector. */
$languages = [
    'text'       => 'Plain Text',
    'bash'       => 'Bash',
    'json'       => 'JSON',
    'xml'        => 'XML',
    'html'       => 'HTML',
    'css'        => 'CSS',
    'javascript' => 'JavaScript',
    'typescript' => 'TypeScript',
    'php'        => 'PHP',
    'python'     => 'Python',
    'java'       => 'Java',
    'c'          => 'C',
    'cpp'        => 'C++',
    'csharp'     => 'C#',
    'go'         => 'Go',
    'ruby'       => 'Ruby',
    'rust'       => 'Rust',
    'kotlin'     => 'Kotlin',
    'sql'        => 'SQL',
];

/** Base path stripped of query string (used for redirect/link URLs). */
$basePath = strtok($_SERVER['REQUEST_URI'], '?');
