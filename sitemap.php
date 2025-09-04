<?php
/**
 * Dynamic Sitemap Generator for PorkPad
 * Automatically generates XML sitemap with all public paste URLs
 */

// Only include config if constants aren't already defined
if (!defined('DB_HOST')) {
    require_once 'config.php';
}
require_once 'classes/Database.php';
require_once 'classes/Paste.php';

// Suppress all PHP errors and warnings for clean XML output
error_reporting(0);
ini_set('display_errors', 0);

// Set XML content type
header('Content-Type: application/xml; charset=utf-8');

// Get database connection
$db = Database::getInstance()->getConnection();
$paste = new Paste($db);

// Get base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$siteUrl = $baseUrl . rtrim(dirname($_SERVER['REQUEST_URI']), '/');

// Start XML sitemap
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Add main pages
$mainPages = [
    ['url' => $siteUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['url' => $siteUrl . '/recent', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => $siteUrl . '/login.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['url' => $siteUrl . '/register.php', 'priority' => '0.6', 'changefreq' => 'monthly']
];

foreach ($mainPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($page['url']) . "</loc>\n";
    echo "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $page['priority'] . "</priority>\n";
    echo "    <lastmod>" . date('Y-m-d\TH:i:s+00:00') . "</lastmod>\n";
    echo "  </url>\n";
}

// Add category pages
try {
    // Get categories from the already loaded config
    if (isset($categories) && is_array($categories)) {
        foreach ($categories as $catId => $catName) {
            if ($catId > 0) { // Skip "None" category
                echo "  <url>\n";
                echo "    <loc>" . htmlspecialchars($siteUrl . '/category.php?id=' . $catId) . "</loc>\n";
                echo "    <changefreq>weekly</changefreq>\n";
                echo "    <priority>0.7</priority>\n";
                echo "    <lastmod>" . date('Y-m-d\TH:i:s+00:00') . "</lastmod>\n";
                echo "  </url>\n";
            }
        }
    }
} catch (Exception $e) {
    // Continue if categories fail
}

// Add public pastes
try {
    // Get all public, non-expired pastes
    $stmt = $db->prepare("
        SELECT id, title, created_at, updated_at 
        FROM pastes 
        WHERE is_private = 0 
        AND password IS NULL
        ORDER BY created_at DESC 
        LIMIT 10000
    ");
    $stmt->execute();
    $pastes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pastes as $pasteData) {
        $lastmod = !empty($pasteData['updated_at']) ? $pasteData['updated_at'] : $pasteData['created_at'];
        $lastmodFormatted = date('Y-m-d\TH:i:s+00:00', strtotime($lastmod));
        
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($siteUrl . '/' . $pasteData['id']) . "</loc>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "    <lastmod>" . $lastmodFormatted . "</lastmod>\n";
        echo "  </url>\n";
    }

} catch (Exception $e) {
    // Log error but continue
    error_log("Sitemap generation error: " . $e->getMessage());
}

// Close sitemap
echo '</urlset>' . "\n";
?>
