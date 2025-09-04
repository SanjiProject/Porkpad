<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
<xsl:output method="html" indent="yes" encoding="UTF-8"/>

<xsl:template match="/">
<html>
<head>
    <title>XML Sitemap - PorkPad</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .sitemap-info { background: #e9ecef; padding: 20px; border-radius: 6px; margin-bottom: 30px; }
        .sitemap-info p { margin: 5px 0; color: #666; }
        .url-list { border-collapse: collapse; width: 100%; }
        .url-list th { background: #000; color: white; padding: 12px; text-align: left; font-weight: 600; }
        .url-list td { padding: 12px; border-bottom: 1px solid #eee; }
        .url-list tr:hover { background: #f8f9fa; }
        .url-link { color: #007bff; text-decoration: none; word-break: break-all; }
        .url-link:hover { text-decoration: underline; }
        .priority { text-align: center; font-weight: 600; }
        .changefreq { text-align: center; }
        .lastmod { text-align: center; font-family: monospace; font-size: 0.9em; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #000; }
        .stat-label { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️ XML Sitemap</h1>
        
        <div class="sitemap-info">
            <p><strong>What is this?</strong> This is a machine-readable sitemap that helps search engines discover and index all pages on this website.</p>
            <p><strong>For Users:</strong> You can browse the site structure below or visit the <a href="/">homepage</a> to start exploring.</p>
            <p><strong>For Search Engines:</strong> This sitemap contains <strong><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></strong> URLs ready for indexing.</p>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-number"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></div>
                <div class="stat-label">Total URLs</div>
            </div>
            <div class="stat">
                <div class="stat-number"><xsl:value-of select="count(sitemap:urlset/sitemap:url[sitemap:priority='1.0'])"/></div>
                <div class="stat-label">High Priority</div>
            </div>
            <div class="stat">
                <div class="stat-number"><xsl:value-of select="count(sitemap:urlset/sitemap:url[sitemap:changefreq='daily'])"/></div>
                <div class="stat-label">Updated Daily</div>
            </div>
        </div>

        <table class="url-list">
            <thead>
                <tr>
                    <th style="width: 50%;">URL</th>
                    <th style="width: 15%;">Priority</th>
                    <th style="width: 15%;">Change Frequency</th>
                    <th style="width: 20%;">Last Modified</th>
                </tr>
            </thead>
            <tbody>
                <xsl:for-each select="sitemap:urlset/sitemap:url">
                    <xsl:sort select="sitemap:priority" order="descending"/>
                    <tr>
                        <td>
                            <a href="{sitemap:loc}" class="url-link">
                                <xsl:value-of select="sitemap:loc"/>
                            </a>
                        </td>
                        <td class="priority">
                            <xsl:value-of select="sitemap:priority"/>
                        </td>
                        <td class="changefreq">
                            <xsl:value-of select="sitemap:changefreq"/>
                        </td>
                        <td class="lastmod">
                            <xsl:value-of select="substring(sitemap:lastmod, 1, 10)"/>
                        </td>
                    </tr>
                </xsl:for-each>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 14px;">
            <p>Generated automatically by PorkPad • <a href="/">Return to Homepage</a></p>
        </div>
    </div>
</body>
</html>
</xsl:template>

</xsl:stylesheet>
