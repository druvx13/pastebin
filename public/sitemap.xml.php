<?php

// This script generates a sitemap on the fly.
// For better performance on high-traffic sites, this should be a static file
// generated periodically by a cron job.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

try {
    // Establish a database connection
    $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    $pdo = $db->getConnection();

    // Fetch all pastes that are allowed to be indexed
    $stmt = $pdo->prepare("SELECT slug, created_at FROM " . TABLE_PASTES . " WHERE allow_indexing = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $pastes = $stmt->fetchAll();

    // Determine the base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = strtok($_SERVER["REQUEST_URI"], 'sitemap.xml.php');
    $baseUrl = $protocol . '://' . $host . $basePath;

    // Add the homepage to the sitemap
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl) . "</loc>\n";
    echo "    <lastmod>" . date('c') . "</lastmod>\n";
    echo "    <priority>1.0</priority>\n";
    echo "  </url>\n";

    // Add each public paste to the sitemap
    foreach ($pastes as $paste) {
        $url = rtrim($baseUrl, '/') . '/view/' . htmlspecialchars($paste['slug']);
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        echo "    <lastmod>" . date('c', strtotime($paste['created_at'])) . "</lastmod>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }

} catch (Exception $e) {
    // If there's an error, just close the sitemap tag to ensure it's valid XML.
    // In a real application, you should log the error.
}

echo '</urlset>' . "\n";
