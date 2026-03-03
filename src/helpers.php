<?php
/*
 * src/helpers.php
 * ----------------
 * Pure utility functions: slug/token generation, pagination helpers,
 * language list, and the commenter-name cookie helper.
 */

/**
 * Generate a cryptographically random, URL-safe hex slug.
 * Retries up to 8 times to avoid collisions, then falls back to uniqid().
 */
function generate_unique_slug(PDO $pdo): string
{
    for ($i = 0; $i < 8; $i++) {
        $slug = bin2hex(random_bytes(SLUG_LENGTH_BYTES));
        $stmt = $pdo->prepare(
            'SELECT 1 FROM `' . TABLE_PASTES . '` WHERE slug = :s LIMIT 1'
        );
        $stmt->execute([':s' => $slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
    }
    // Fallback (extremely unlikely)
    return preg_replace('/[^a-z0-9]/', '', uniqid('', true));
}

/**
 * Generate a cryptographically random delete token (hex string).
 */
function generate_delete_token(): string
{
    return bin2hex(random_bytes(DELETE_TOKEN_BYTES));
}

/**
 * Fetch a paginated list of pastes ordered by most-recent first.
 *
 * @param  PDO $pdo
 * @param  int $page    1-based page number
 * @param  int $perPage Rows per page (defaults to PASTES_PER_PAGE)
 * @return array
 */
function fetch_pastes(PDO $pdo, int $page = 1, int $perPage = PASTES_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt   = $pdo->prepare(
        'SELECT slug, title, language, created_at
         FROM `' . TABLE_PASTES . '`
         ORDER BY created_at DESC
         LIMIT :lim OFFSET :off'
    );
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Return the total number of pastes stored in the database.
 */
function count_pastes(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM `' . TABLE_PASTES . '`')
                     ->fetchColumn();
}

/**
 * Return the canonical language map used in the create-form <select>
 * and for syntax-highlight class names.
 *
 * @return array<string, string>  key = highlight.js language id, value = display label
 */
function get_languages(): array
{
    return [
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
}
