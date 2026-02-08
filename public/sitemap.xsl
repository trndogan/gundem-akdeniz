<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>XML Site Haritası - Gündem Akdeniz</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style type="text/css">
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; background: #f8fafc; }
    .header { background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 30px 40px; }
    .header h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
    .header p { font-size: 13px; opacity: 0.9; line-height: 1.6; }
    .header a { color: #fecaca; text-decoration: underline; }
    .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
    .info { font-size: 13px; color: #64748b; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    th { background: #1e293b; color: white; text-align: left; padding: 12px 16px; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 10px 16px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
    tr:hover td { background: #f8fafc; }
    tr:last-child td { border-bottom: none; }
    a { color: #2563eb; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .footer { text-align: center; padding: 30px; font-size: 12px; color: #94a3b8; }
    .badge { display: inline-block; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
  </style>
</head>
<body>
  <div class="header">
    <h1>XML Site Haritası</h1>
    <p>Bu XML site haritası <strong>Gündem Akdeniz</strong> tarafından oluşturulmuştur. Google gibi arama motorlarının web sitenizdeki yayınları / sayfaları / görüntüleri taramak ve yeniden taramak için kullandığı bir dosyadır.</p>
  </div>
  <div class="container">

  <!-- Sitemap Index -->
  <xsl:if test="sitemap:sitemapindex">
    <p class="info">Bu XML site haritası dizini dosyası <strong><xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/></strong> site haritasını içeriyor.</p>
    <table>
      <tr>
        <th>Site Haritası</th>
        <th style="width:250px">Son Düzenleme</th>
      </tr>
      <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
        <tr>
          <td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
          <td><xsl:value-of select="sitemap:lastmod"/></td>
        </tr>
      </xsl:for-each>
    </table>
  </xsl:if>

  <!-- URL Set -->
  <xsl:if test="sitemap:urlset">
    <p class="info">Bu site haritası <strong><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></strong> URL içeriyor.</p>
    <table>
      <tr>
        <th>URL</th>
        <th style="width:100px">Öncelik</th>
        <th style="width:120px">Değişim Sıklığı</th>
        <th style="width:250px">Son Düzenleme</th>
      </tr>
      <xsl:for-each select="sitemap:urlset/sitemap:url">
        <tr>
          <td>
            <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
            <xsl:if test="news:news">
              <xsl:text> </xsl:text><span class="badge">Haber</span>
            </xsl:if>
            <xsl:if test="image:image">
              <xsl:text> </xsl:text><span class="badge">Görsel</span>
            </xsl:if>
          </td>
          <td><xsl:value-of select="sitemap:priority"/></td>
          <td><xsl:value-of select="sitemap:changefreq"/></td>
          <td><xsl:value-of select="sitemap:lastmod"/></td>
        </tr>
      </xsl:for-each>
    </table>
  </xsl:if>

  </div>
  <div class="footer">
    © 2026 Gündem Akdeniz - XML Site Haritası
  </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
