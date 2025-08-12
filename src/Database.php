<?php

class Database
{
    private ?PDO $pdo = null;
    private string $host;
    private string $port;
    private string $name;
    private string $user;
    private string $pass;

    public function __construct(string $host, string $port, string $name, string $user, string $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->name = $name;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                // First, connect without a database name to create it if it doesn't exist.
                $dsnNoDB = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $this->host, $this->port);
                $pdo = new PDO($dsnNoDB, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Create the database if it's missing.
                $safeDbName = str_replace('`', '``', $this->name);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Now, reconnect with the database selected.
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->name);
                $this->pdo = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

            } catch (PDOException $e) {
                // In a real application, you'd want to log this error.
                http_response_code(500);
                echo "<h1>Database connection error</h1><p>Please check your configuration.</p>";
                // Optional: echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                exit;
            }
        }
        return $this->pdo;
    }

    public function initializeTables(): void
    {
        $pdo = $this->getConnection();
        try {
            // Create 'pastes' table if it doesn't exist.
            $createPastesSQL = "
              CREATE TABLE IF NOT EXISTS `" . TABLE_PASTES . "` (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(64) NOT NULL UNIQUE,
                title VARCHAR(255) DEFAULT NULL,
                language VARCHAR(64) DEFAULT 'text',
                content LONGTEXT NOT NULL,
                delete_token VARCHAR(128) NOT NULL,
                views INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (created_at)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($createPastesSQL);

            // Create 'comments' table if it doesn't exist.
            $createCommentsSQL = "
              CREATE TABLE IF NOT EXISTS `" . TABLE_COMMENTS . "` (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                paste_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(150) DEFAULT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (paste_id),
                FOREIGN KEY (paste_id) REFERENCES `" . TABLE_PASTES . "` (id) ON DELETE CASCADE
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($createCommentsSQL);

        } catch (PDOException $e) {
            http_response_code(500);
            echo "<h1>Database table creation error</h1><p>Could not initialize the database tables.</p>";
            // Optional: echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            exit;
        }
    }
}
