<?php
/* ===========================
   BOOTSTRAP: CONNECT & INIT DB
   =========================== */
try {
    // Connect without DB name to allow DB creation if needed
    $dsnNoDB = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsnNoDB, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create DB if missing
    $safeDb = str_replace('`', '``', DB_NAME);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Reconnect with DB selected
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create pastes table if not exists
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS `" . TABLE_PASTES . "` (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        slug         VARCHAR(64)     NOT NULL UNIQUE,
        title        VARCHAR(255)    DEFAULT NULL,
        language     VARCHAR(64)     DEFAULT 'text',
        content      LONGTEXT        NOT NULL,
        delete_token VARCHAR(128)    NOT NULL,
        views        INT UNSIGNED    NOT NULL DEFAULT 0,
        created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (created_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Create comments table if not exists (anonymous comments)
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS `" . TABLE_COMMENTS . "` (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        paste_id   BIGINT UNSIGNED NOT NULL,
        name       VARCHAR(150)    DEFAULT NULL,
        message    TEXT            NOT NULL,
        created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (paste_id),
        FOREIGN KEY (paste_id) REFERENCES `" . TABLE_PASTES . "` (id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

} catch (PDOException $e) {
    http_response_code(500);
    echo "<h1>Database error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
