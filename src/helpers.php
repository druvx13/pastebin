<?php

/**
 * Generate a unique, random slug.
 * Retries a few times on collision, then falls back to a timestamp-based unique ID.
 */
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
    // Fallback to a less pretty but unique ID if we have too many collisions.
    return uniqid('', true);
}

/**
 * Generate a cryptographically secure token for deletion.
 */
function generate_delete_token(): string
{
    return bin2hex(random_bytes(DELETE_TOKEN_BYTES));
}

/**
 * Fetch the most recent pastes.
 */
function fetch_recent(PDO $pdo, int $count = RECENT_COUNT): array
{
    $stmt = $pdo->prepare("SELECT slug, title, language, created_at FROM `" . TABLE_PASTES . "` ORDER BY created_at DESC LIMIT :cnt");
    $stmt->bindValue(':cnt', $count, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * A list of supported languages for syntax highlighting.
 * The key is the highlight.js class name.
 */
function get_supported_languages(): array
{
    return [
        'text' => 'Plain Text',
        'bash' => 'Bash',
        'json' => 'JSON',
        'xml' => 'XML',
        'html' => 'HTML',
        'css' => 'CSS',
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'php' => 'PHP',
        'python' => 'Python',
        'java' => 'Java',
        'c' => 'C',
        'cpp' => 'C++',
        'csharp' => 'C#',
        'go' => 'Go',
        'ruby' => 'Ruby',
        'rust' => 'Rust',
        'kotlin' => 'Kotlin',
        'sql' => 'SQL',
    ];
}
