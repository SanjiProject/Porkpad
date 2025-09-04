<?php
/**
 * SEO Helper Class for PorkPad
 * Generates comprehensive SEO meta tags, Open Graph, Twitter Cards, and structured data
 */

class SEO {
    private $title;
    private $description;
    private $keywords;
    private $image;
    private $url;
    private $type;
    private $author;
    private $publishedTime;
    private $modifiedTime;
    
    public function __construct() {
        $this->setDefaults();
    }
    
    private function setDefaults() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $currentUrl = $baseUrl . $_SERVER['REQUEST_URI'];
        
        $this->title = SITE_NAME . ' - ' . SITE_DESCRIPTION;
        $this->description = SITE_DESCRIPTION . '. Share code snippets, text, and collaborate with developers worldwide.';
        $this->keywords = 'pastebin, code sharing, text sharing, snippet sharing, developer tools, programming, code collaboration';
        $this->image = $baseUrl . rtrim(dirname($_SERVER['REQUEST_URI']), '/') . '/img/header.webp';
        $this->url = $currentUrl;
        $this->type = 'website';
        $this->author = SITE_NAME;
    }
    
    public function setTitle($title) {
        $this->title = htmlspecialchars($title . ' - ' . SITE_NAME, ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setDescription($description) {
        $this->description = htmlspecialchars(substr($description, 0, 160), ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setKeywords($keywords) {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->keywords = htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setImage($image) {
        $this->image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setUrl($url) {
        $this->url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setType($type) {
        $this->type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setAuthor($author) {
        $this->author = htmlspecialchars($author, ENT_QUOTES, 'UTF-8');
        return $this;
    }
    
    public function setPublishedTime($time) {
        $this->publishedTime = date('c', strtotime($time));
        return $this;
    }
    
    public function setModifiedTime($time) {
        $this->modifiedTime = date('c', strtotime($time));
        return $this;
    }
    
    public function renderMetaTags() {
        $output = "\n<!-- SEO Meta Tags -->\n";
        
        // Basic meta tags
        $output .= '<meta charset="UTF-8">' . "\n";
        $output .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $output .= '<title>' . $this->title . '</title>' . "\n";
        $output .= '<meta name="description" content="' . $this->description . '">' . "\n";
        $output .= '<meta name="keywords" content="' . $this->keywords . '">' . "\n";
        $output .= '<meta name="author" content="' . $this->author . '">' . "\n";
        $output .= '<meta name="robots" content="index, follow">' . "\n";
        $output .= '<link rel="canonical" href="' . $this->url . '">' . "\n";
        
        // Open Graph tags
        $output .= "\n<!-- Open Graph Meta Tags -->\n";
        $output .= '<meta property="og:title" content="' . $this->title . '">' . "\n";
        $output .= '<meta property="og:description" content="' . $this->description . '">' . "\n";
        $output .= '<meta property="og:type" content="' . $this->type . '">' . "\n";
        $output .= '<meta property="og:url" content="' . $this->url . '">' . "\n";
        $output .= '<meta property="og:image" content="' . $this->image . '">' . "\n";
        $output .= '<meta property="og:site_name" content="' . SITE_NAME . '">' . "\n";
        
        if ($this->publishedTime) {
            $output .= '<meta property="article:published_time" content="' . $this->publishedTime . '">' . "\n";
        }
        
        if ($this->modifiedTime) {
            $output .= '<meta property="article:modified_time" content="' . $this->modifiedTime . '">' . "\n";
        }
        
        // Twitter Card tags
        $output .= "\n<!-- Twitter Card Meta Tags -->\n";
        $output .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $output .= '<meta name="twitter:title" content="' . $this->title . '">' . "\n";
        $output .= '<meta name="twitter:description" content="' . $this->description . '">' . "\n";
        $output .= '<meta name="twitter:image" content="' . $this->image . '">' . "\n";
        
        // Additional SEO tags
        $output .= "\n<!-- Additional SEO Tags -->\n";
        $output .= '<meta name="theme-color" content="#000000">' . "\n";
        $output .= '<meta name="msapplication-TileColor" content="#000000">' . "\n";
        $output .= '<link rel="sitemap" type="application/xml" href="/sitemap.php">' . "\n";
        
        // Favicon tags
        $output .= "\n<!-- Favicon -->\n";
        $output .= '<link rel="icon" type="image/x-icon" href="./favicon.ico">' . "\n";
        $output .= '<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico">' . "\n";
        $output .= '<link rel="img/apple-touch-icon" sizes="180x180" href="./img/apple-touch-icon.png">' . "\n";
        $output .= '<link rel="icon" type="image/png" sizes="32x32" href="./favicon-32x32.png">' . "\n";
        $output .= '<link rel="icon" type="image/png" sizes="16x16" href="./favicon-16x16.png">' . "\n";
        $output .= '<link rel="manifest" href="./site.webmanifest">' . "\n";
        
        return $output;
    }
    
    public function renderStructuredData($data = []) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $siteUrl = $baseUrl . rtrim(dirname($_SERVER['REQUEST_URI']), '/');
        
        $defaultData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => SITE_NAME,
            'description' => SITE_DESCRIPTION,
            'url' => $siteUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $siteUrl . '/recent?search={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        $structuredData = array_merge($defaultData, $data);
        
        $output = "\n<!-- Structured Data -->\n";
        $output .= '<script type="application/ld+json">' . "\n";
        $output .= json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output .= "\n" . '</script>' . "\n";
        
        return $output;
    }
    
    public function renderPasteStructuredData($pasteData, $author = null) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $siteUrl = $baseUrl . rtrim(dirname($_SERVER['REQUEST_URI']), '/');
        
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'headline' => $pasteData['title'],
            'description' => 'Code snippet shared on ' . SITE_NAME,
            'url' => $siteUrl . '/' . $pasteData['id'],
            'datePublished' => date('c', strtotime($pasteData['created_at'])),
            'publisher' => [
                '@type' => 'Organization',
                'name' => SITE_NAME,
                'url' => $siteUrl
            ],
            'mainEntityOfPage' => $siteUrl . '/' . $pasteData['id'],
            'genre' => 'Code Snippet'
        ];
        
        if ($pasteData['updated_at']) {
            $structuredData['dateModified'] = date('c', strtotime($pasteData['updated_at']));
        }
        
        if ($author) {
            $structuredData['author'] = [
                '@type' => 'Person',
                'name' => $author['username'],
                'url' => $siteUrl . '/user.php?id=' . $author['id']
            ];
        }
        
        if (isset($pasteData['language']) && $pasteData['language'] !== 'text') {
            $structuredData['programmingLanguage'] = $pasteData['language'];
        }
        
        return $this->renderStructuredData($structuredData);
    }
}
