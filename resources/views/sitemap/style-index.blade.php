@php echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>'; @endphp
<xsl:stylesheet version="2.0"
    xmlns:html="http://www.w3.org/TR/REC-html40"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml" lang="tr">
<head>
    <title>Sitemap Index - Gündem Akdeniz</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style type="text/css">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; color: #1e293b; }
        .header { background: #fff; border-bottom: 3px solid #dc2626; padding: 24px 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #1e293b; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #dc2626, #b91c1c); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 18px; }
        .logo-text { font-size: 22px; font-weight: 900; letter-spacing: -0.5px; }
        .logo-text span { color: #dc2626; }
        .hero { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 48px 0; }
        .hero h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .hero p { color: #94a3b8; font-size: 15px; }
        .content { padding: 32px 0; }
        .stats { display: flex; gap: 24px; margin-bottom: 32px; }
        .stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; flex: 1; }
        .stat-value { font-size: 28px; font-weight: 800; color: #dc2626; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        th { background: #f1f5f9; text-align: left; padding: 14px 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        tr:hover td { background: #fafbfc; }
        td a { color: #dc2626; text-decoration: none; font-weight: 600; }
        td a:hover { text-decoration: underline; }
        .date { color: #64748b; font-size: 13px; }
        .footer { text-align: center; padding: 32px 0; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; margin-top: 32px; }
        .badge { display: inline-block; background: #fef2f2; color: #dc2626; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; }
        @media (max-width: 768px) { .stats { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="/" class="logo">
                <div class="logo-icon">GA</div>
                <div class="logo-text">GÜNDEM<span>AKDENİZ</span></div>
            </a>
        </div>
    </div>
    <div class="hero">
        <div class="container">
            <h1>XML Sitemap Index</h1>
            <p>Bu sitemap index dosyası arama motorları tarafından sitenin yapısını anlamak için kullanılır.</p>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <div class="stats">
                <div class="stat">
                    <div class="stat-value"><xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/></div>
                    <div class="stat-label">Toplam Sitemap</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px">#</th>
                        <th>Sitemap URL</th>
                        <th style="width: 220px">Son Güncelleme</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                        <tr>
                            <td><xsl:value-of select="position()"/></td>
                            <td><a><xsl:attribute name="href"><xsl:value-of select="sitemap:loc"/></xsl:attribute><xsl:value-of select="sitemap:loc"/></a></td>
                            <td class="date"><xsl:value-of select="concat(substring(sitemap:lastmod,1,10),' ',substring(sitemap:lastmod,12,5))"/></td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
            <div class="footer">
                <p>Gündem Akdeniz - XML Sitemap Index</p>
            </div>
        </div>
    </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
