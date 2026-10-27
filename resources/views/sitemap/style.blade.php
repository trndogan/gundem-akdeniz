@php echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>'; @endphp
<xsl:stylesheet version="2.0"
    xmlns:html="http://www.w3.org/TR/REC-html40"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml" lang="tr">
<head>
    <title>XML Sitemap - Gündem Akdeniz</title>
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
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #94a3b8; text-decoration: none; font-size: 13px; margin-top: 12px; }
        .back-link:hover { color: #fff; }
        .content { padding: 32px 0; }
        .stats { display: flex; gap: 24px; margin-bottom: 32px; }
        .stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; flex: 1; }
        .stat-value { font-size: 28px; font-weight: 800; color: #dc2626; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        th { background: #f1f5f9; text-align: left; padding: 14px 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        tr:hover td { background: #fafbfc; }
        td a { color: #dc2626; text-decoration: none; font-weight: 500; word-break: break-all; }
        td a:hover { text-decoration: underline; }
        .date { color: #64748b; font-size: 13px; white-space: nowrap; }
        .priority { text-align: center; }
        .priority-high { color: #dc2626; font-weight: 700; }
        .priority-med { color: #f59e0b; font-weight: 600; }
        .priority-low { color: #64748b; }
        .img-indicator { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; margin-right: 4px; }
        .footer { text-align: center; padding: 32px 0; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; margin-top: 32px; }
        @media (max-width: 768px) { .stats { flex-direction: column; } td, th { padding: 10px 12px; font-size: 12px; } }
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
            <h1>XML Sitemap</h1>
            <p>Bu sitemap dosyası arama motorları tarafından sitenin içeriğini keşfetmek için kullanılır.</p>
            <a href="/sitemap.xml" class="back-link">&#8592; Sitemap Index'e Dön</a>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <div class="stats">
                <div class="stat">
                    <div class="stat-value"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></div>
                    <div class="stat-label">Toplam URL</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px">#</th>
                        <th>URL</th>
                        <th style="width: 80px">Görsel</th>
                        <th style="width: 80px">Öncelik</th>
                        <th style="width: 100px">Sıklık</th>
                        <th style="width: 180px">Son Güncelleme</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:for-each select="sitemap:urlset/sitemap:url">
                        <tr>
                            <td><xsl:value-of select="position()"/></td>
                            <td><a><xsl:attribute name="href"><xsl:value-of select="sitemap:loc"/></xsl:attribute><xsl:value-of select="sitemap:loc"/></a></td>
                            <td style="text-align:center">
                                <xsl:if test="image:image">
                                    <span class="img-indicator" title="Görsel mevcut"></span>
                                </xsl:if>
                            </td>
                            <td class="priority">
                                <xsl:choose>
                                    <xsl:when test="sitemap:priority &gt;= 0.8"><span class="priority-high"><xsl:value-of select="sitemap:priority"/></span></xsl:when>
                                    <xsl:when test="sitemap:priority &gt;= 0.5"><span class="priority-med"><xsl:value-of select="sitemap:priority"/></span></xsl:when>
                                    <xsl:otherwise><span class="priority-low"><xsl:value-of select="sitemap:priority"/></span></xsl:otherwise>
                                </xsl:choose>
                            </td>
                            <td><xsl:value-of select="sitemap:changefreq"/></td>
                            <td class="date"><xsl:value-of select="concat(substring(sitemap:lastmod,1,10),' ',substring(sitemap:lastmod,12,5))"/></td>
                        </tr>
                    </xsl:for-each>
                </tbody>
            </table>
            <div class="footer">
                <p>Gündem Akdeniz - XML Sitemap</p>
            </div>
        </div>
    </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
