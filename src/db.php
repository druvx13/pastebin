<?php
/*
 * src/db.php
 * -----------
 * Database connection, schema creation, and the db_connect() factory.
 * Requires constants defined in config/config.php.
 */

/**
 * Create a PDO connection, auto-create the database and tables if they do not
 * exist yet, and return the ready-to-use PDO instance.
 *
 * On failure the function emits a plain-text error and exits so that the
 * calling code never receives a null/false value.
 */
function db_connect(): PDO
{
    try {
        // Connect without selecting a database so we can CREATE DATABASE
        $dsnNoDB = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $pdo = new PDO($dsnNoDB, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $safeDb = str_replace('`', '``', DB_NAME);
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$safeDb}`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // Reconnect with the target database selected
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Pastes table
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Comments table (cascade-deletes when a paste is removed)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `" . TABLE_COMMENTS . "` (
                id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                paste_id   BIGINT UNSIGNED NOT NULL,
                name       VARCHAR(150)    DEFAULT NULL,
                message    TEXT            NOT NULL,
                created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (paste_id),
                FOREIGN KEY (paste_id)
                    REFERENCES `" . TABLE_PASTES . "` (id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        return $pdo;

    } catch (PDOException $e) {
        http_response_code(500);
        echo '<h1>Database error</h1><pre>'
            . htmlspecialchars($e->getMessage()) . '</pre>';
        exit;
    }
}
