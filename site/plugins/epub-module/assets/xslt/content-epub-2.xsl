<?xml version="1.0" encoding="UTF-8"?>

<!--
    
    Transform content of XHTML (HTML4) documents
    
    – Replace stylesheet paths
    – Replace image source paths
    
    – Replace not allowed elements (header, footer, ...)
    – Replace not allowed attributes (role, epub:type, ...)
    
-->

<xsl:stylesheet 
    version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:h="http://www.w3.org/1999/xhtml"
    xmlns:epub="http://www.idpf.org/2007/ops"
    xmlns="http://www.w3.org/1999/xhtml"
    exclude-result-prefixes="h"
>
    
    <xsl:param name="css-folder-path" select="'css'" />
    <xsl:param name="graphic-folder-path" select="'images'"/>
    <xsl:param name="path-delimiter" select="'/'"/>
    
    <xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd" doctype-public="-//W3C//DTD XHTML 1.1//EN" encoding="UTF-8"/>
    
    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="h:head/h:link[@rel = 'stylesheet']/@href">
        <xsl:attribute name="href">
            <xsl:call-template name="trim-path-delimiter">
                <xsl:with-param name="string" select="$css-folder-path"/>
                <xsl:with-param name="delimiter" select="$path-delimiter"/>
            </xsl:call-template>
            <xsl:text>/</xsl:text>
            <xsl:call-template name="substring-after-last">
                <xsl:with-param name="string" select="."/>
                <xsl:with-param name="delimiter" select="$path-delimiter"/>
            </xsl:call-template>
        </xsl:attribute>
    </xsl:template>
    
    <xsl:template match="h:img/@src">
        <xsl:attribute name="src">
            <xsl:call-template name="trim-path-delimiter">
                <xsl:with-param name="string" select="$graphic-folder-path"/>
                <xsl:with-param name="delimiter" select="$path-delimiter"/>
            </xsl:call-template>
            <xsl:text>/</xsl:text>
            <xsl:call-template name="substring-after-last">
                <xsl:with-param name="string" select="."/>
                <xsl:with-param name="delimiter" select="$path-delimiter"/>
            </xsl:call-template>
        </xsl:attribute>
    </xsl:template>
    
    <xsl:template name="substring-after-last">
        <xsl:param name="string" select="''"/>
        <xsl:param name="delimiter" select="$path-delimiter"/>
        <xsl:choose>
            <xsl:when test="contains($string, $delimiter)">
                <xsl:call-template name="substring-after-last">
                    <xsl:with-param name="string" select="substring-after($string, $delimiter)"/>
                    <xsl:with-param name="delimiter" select="$delimiter"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$string"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template name="trim-path-delimiter">
        <xsl:param name="string" select="''"/>
        <xsl:param name="delimiter" select="$path-delimiter"/>
        <xsl:variable name="first-char" select="substring($string, 1, 1)"/>
        <xsl:variable name="last-char" select="substring($string, string-length($string))"/>
        <xsl:variable name="start-index">
            <xsl:choose>
                <xsl:when test="contains($first-char, $path-delimiter)">2</xsl:when>
                <xsl:otherwise>1</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="stop-index">
            <xsl:choose>
                <xsl:when test="contains($first-char, $path-delimiter) and contains($last-char, $path-delimiter)">
                    <xsl:value-of select="string-length($string) - 2"/>
                </xsl:when>
                <xsl:when test="not(contains($first-char, $path-delimiter)) and contains($last-char, $path-delimiter)">
                    <xsl:value-of select="string-length($string) - 1"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="string-length($string)"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:value-of select="substring($string, $start-index, $stop-index)"/>
    </xsl:template>
    
    <!--  XHTML 1.1: Not allowed elements -->
    <xsl:template match="h:meta[@charset]">
        <meta http-equiv="content-type" content="text/html" />
    </xsl:template>
    <xsl:template match="h:section">
        <div>
            <xsl:apply-templates select="@*|node()"/>
        </div>
    </xsl:template>
    <xsl:template match="h:header">
        <div class="doc-header">
            <xsl:apply-templates select="@*|node()"/>
        </div>
    </xsl:template>
    <xsl:template match="h:footer">
        <div class="doc-footer">
            <xsl:apply-templates select="@*|node()"/>
        </div>
    </xsl:template>
    <xsl:template match="h:blockquote/text()">
        <div class="blockquote-text">
            <xsl:value-of select="."/>
        </div>
    </xsl:template>
    <xsl:template match="h:blockquote/h:footer">
        <div class="blockquote-footer">
            <xsl:apply-templates select="@*|node()"/>
        </div>
    </xsl:template>
    <xsl:template match="h:figure">
        <div class="figure">
            <xsl:apply-templates select="@*|node()"/>
        </div>
    </xsl:template>
    <xsl:template match="h:figcaption">
        <div class="figcaption">
            <xsl:apply-templates select="@*|node()"/>
        </div>
    </xsl:template>
    
    <!--  XHTML 1.1: Not allowed attributes -->
    <xsl:template match="@epub:type"></xsl:template>
    <xsl:template match="@role"></xsl:template>
    <xsl:template match="@data-ratio"></xsl:template>
    
</xsl:stylesheet>


