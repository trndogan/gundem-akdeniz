<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:media="http://search.yahoo.com/mrss/">
<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>RSS Beslemesi - <xsl:value-of select="/rss/channel/title"/></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style type="text/css">
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; background: #f8fafc; }
    .header { background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 30px 40px; }
    .header h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
    .header p { font-size: 13px; opacity: 0.9; line-height: 1.6; }
    .header a { color: #fed7aa; text-decoration: underline; }
    .subscribe { display: inline-block; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; margin-top: 12px; font-size: 13px; font-weight: 600; }
    .subscribe code { background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 4px; font-size: 12px; }
    .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
    .info { font-size: 13px; color: #64748b; margin-bottom: 16px; }
    .item { background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; transition: box-shadow 0.2s; }
    .item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .item-title { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
    .item-title a { color: #1e293b; text-decoration: none; }
    .item-title a:hover { color: #dc2626; }
    .item-meta { font-size: 12px; color: #94a3b8; margin-bottom: 8px; }
    .item-meta .category { display: inline-block; background: #fef2f2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; margin-left: 8px; }
    .item-desc { font-size: 14px; color: #64748b; line-height: 1.6; }
    .footer { text-align: center; padding: 30px; font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="header">
    <h1>RSS Beslemesi</h1>
    <p><strong><xsl:value-of select="/rss/channel/title"/></strong> - <xsl:value-of select="/rss/channel/description"/></p>
    <div class="subscribe">Bu RSS beslemesine abone olmak için bu URL'yi RSS okuyucunuza ekleyin: <code><xsl:value-of select="/rss/channel/atom:link/@href"/></code></div>
  </div>
  <div class="container">
    <p class="info">Son <strong><xsl:value-of select="count(/rss/channel/item)"/></strong> haber gösteriliyor.</p>
    <xsl:for-each select="/rss/channel/item">
      <div class="item">
        <div class="item-title">
          <a href="{link}" target="_blank"><xsl:value-of select="title"/></a>
        </div>
        <div class="item-meta">
          <xsl:value-of select="pubDate"/>
          <xsl:if test="category">
            <span class="category"><xsl:value-of select="category"/></span>
          </xsl:if>
        </div>
        <xsl:if test="description">
          <div class="item-desc"><xsl:value-of select="description"/></div>
        </xsl:if>
      </div>
    </xsl:for-each>
  </div>
  <div class="footer">
    © 2026 Gündem Akdeniz - RSS Beslemesi
  </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
