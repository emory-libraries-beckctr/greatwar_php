<?xml version="1.0" encoding="ISO-8859-1"?>  

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
	xmlns:html="http://www.w3.org/TR/REC-html40" 
	xmlns:ino="http://namespaces.softwareag.com/tamino/response2" 
	xmlns:xql="http://metalab.unc.edu/xql/">

<!-- Note: all links in this stylesheet should be relative to the root
     directory of the entire site. -->

<xsl:include href="teipoetry.xsl"/>
<xsl:include href="teinote.xsl"/>

<xsl:param name="mode"></xsl:param>
<!-- options:
     browse = list of titles with author/editor
     poetbrowse = browse by poet
     contents = list of poems (and any other content) in a single book 
     poem = full-text of a single poem (or poem-level item)
     frontmatter = forewords and such
     search = search results (list of poems)
-->

<xsl:output method="html"/>  

<xsl:template match="/"> 
  <xsl:choose>
    <xsl:when test="$mode='browse'">
      <ul>
        <xsl:apply-templates select="//div" mode="browse"/>
     </ul>
    </xsl:when>
    <xsl:when test="$mode='poetbrowse'">
      <xsl:element name="script">
        <xsl:attribute name="language">Javascript</xsl:attribute>
        <xsl:attribute name="src">toggle-list.js</xsl:attribute>
      </xsl:element> <!-- script -->
      <ul>
        <xsl:apply-templates select="//div" mode="poetbrowse"/>
     </ul>
    </xsl:when>
    <xsl:when test="$mode='contents'">
      <xsl:apply-templates select="//div" mode="contents"/>
    </xsl:when>
    <xsl:when test="$mode='poem'">
      <xsl:apply-templates select="//div" mode="poem"/>
    </xsl:when>
    <xsl:when test="$mode='frontmatter'">
      <xsl:apply-templates select="//div1"/>
    </xsl:when>
    <xsl:when test="$mode='search'">
      <xsl:apply-templates select="//div2" mode="search"/>
    </xsl:when>
  </xsl:choose>
</xsl:template>



<xsl:template match="div" mode="browse">
 <li>
   <a>
   <xsl:attribute name="href">poetry/contents.php?id=<xsl:value-of select="@id"/></xsl:attribute>
     <xsl:value-of select="titleStmt/title"/></a>
       <br/><xsl:value-of select="titleStmt/author"/>
 </li>
</xsl:template>


<xsl:key name="poetkey" match="div" use="docAuthor/@n"/>

<xsl:template match="div" mode="poetbrowse">

  <!-- only print poet name if it hasn't already been printed -->
  <xsl:choose>
    <xsl:when test="docAuthor/@n = preceding-sibling::div[1]/docAuthor/@n">
    </xsl:when>
    <xsl:otherwise>
    <div class="toggle">
    <xsl:variable name="lastname">
      <xsl:value-of select="substring-before(docAuthor/@n, ',')"/>
    </xsl:variable>
      <!-- create toggle image -->
      <xsl:call-template name="toggle-image">
        <xsl:with-param name="id"><xsl:value-of select="$lastname"/></xsl:with-param>
      </xsl:call-template>
 <!--     <xsl:apply-templates select="docAuthor"/> -->
      <xsl:value-of select="docAuthor/@n"/>
      <ul>
       <xsl:attribute name="id"><xsl:value-of select="$lastname"/></xsl:attribute>
        <xsl:apply-templates select="key('poetkey', docAuthor/@n)" mode="poetbrowsetitle"/>
      </ul>
     </div>
    </xsl:otherwise>
  </xsl:choose>

</xsl:template>

<!-- poems should link to the poem page -->
<xsl:template match="div[@type='poem']" mode="poetbrowsetitle">
  <li>
    <a>
     <xsl:attribute name="href">poetry/view.php?id=<xsl:value-of select="@id"/></xsl:attribute>
     <xsl:value-of select="@n"/>
     <xsl:if test="not(@n)">[untitled]</xsl:if>
    </a> 

    <font class="type">(<xsl:value-of select="@type"/>)</font>
  </li>
</xsl:template>

<!-- any other divs should be entire volumes -->
<xsl:template match="div" mode="poetbrowsetitle">
  <xsl:variable name="myid"><xsl:value-of select="div2[1]/@id"/></xsl:variable>
  <li class="toggle">
      <!-- create toggle image -->
      <xsl:call-template name="toggle-image">
        <xsl:with-param name="id"><xsl:value-of select="$myid"/></xsl:with-param>
      </xsl:call-template>

  <a>
  <xsl:attribute name="href">poetry/contents.php?id=<xsl:value-of select="@docname"/></xsl:attribute>
  <xsl:value-of select="@n"/>
  <xsl:if test="not(@n)">
    <xsl:value-of select="head"/>
  </xsl:if>
  </a>
   <font class="type">(<xsl:value-of select="@type"/>)</font>
   
  <ul>
    <xsl:attribute name="id"><xsl:value-of select="$myid"/></xsl:attribute>
    <xsl:apply-templates select="div2" mode="contents"/>
  </ul>
</li>
</xsl:template>

<xsl:template match="div" mode="contents">
  <ul>
    <xsl:apply-templates select="div1" mode="contents"/>
  </ul>
  <xsl:apply-templates select="teiHeader"/>
</xsl:template>

<xsl:template match="teiHeader">
<!-- first two are redundant -->
<!--  <xsl:value-of select="fileDesc/titleStmt/title"/><br/>
  <xsl:value-of select="fileDesc/titleStmt/author"/><br/> -->
 <p class="copyright">
  <xsl:value-of select="fileDesc/sourceDesc/bibl"/><br/>
 </p>
</xsl:template>

<xsl:template match="div1" mode="contents">
  <li>
  <xsl:value-of select="@n"/>
  <xsl:if test="not(@n)">
    <xsl:value-of select="head"/>
  </xsl:if>
   <font class="type">(<xsl:value-of select="@type"/>)</font>
	<br/> 
  <xsl:value-of select="docAuthor"/> <xsl:value-of select="docDate"/> <xsl:value-of select="bibl"/>
   
  <ul>
    <xsl:apply-templates select="div2" mode="contents"/>
  </ul>
</li>
</xsl:template>

<!-- similar to above, but with a link to content -->
<xsl:template match="div1[@type='Foreword']" mode="contents">
 <li>
  <a>
   <xsl:attribute name="href">poetry/front.php?id=<xsl:value-of select="@id"/></xsl:attribute>
  <xsl:value-of select="@n"/>
  <xsl:choose>
    <xsl:when test="not(@n)">
      <xsl:value-of select="head"/>
    </xsl:when>
    <xsl:when test="not(head)">
       [untitled]
    </xsl:when>
  </xsl:choose>
  </a>
   <font class="type">(<xsl:value-of select="@type"/>)</font>
 </li>
</xsl:template>

<!-- same as above, but linked to content -->
<xsl:template match="div1[@type='colophon']" mode="contents">
  <li>
    <xsl:value-of select="p"/>    
    <font class="type">(<xsl:value-of select="@type"/>)</font> 
  </li>
</xsl:template>



<xsl:template match="div2" mode="contents">
  <li>
    <a>
     <xsl:attribute name="href">poetry/view.php?id=<xsl:value-of select="@id"/></xsl:attribute>
     <xsl:value-of select="@n"/>
     <xsl:if test="not(@n)">[untitled]</xsl:if>
    </a> 
    <xsl:if test="docAuthor">
	<xsl:value-of select="concat(' - ', docAuthor)"/>
    </xsl:if>

    <font class="type">(<xsl:value-of select="@type"/>)</font>
  </li>
</xsl:template>

<!-- search results -->
<xsl:template match="div2" mode="search">
   <p>
    <a>
     <xsl:attribute name="href">poetry/view.php?id=<xsl:value-of select="@id"/></xsl:attribute>
     <xsl:value-of select="@n"/>
     <xsl:if test="not(@n)">[untitled]</xsl:if>
    </a> 

   <xsl:if test="docAuthor"><xsl:value-of select="concat(' - ', docAuthor)"/></xsl:if>

   <!-- search results mode: display line count and lines matching
	search term -->
    <xsl:apply-templates select="linecount" mode="search"/>
    <p class="linematch">
      <xsl:apply-templates select="l" mode="search"/>
    </p>
  </p>
</xsl:template>

<xsl:template match="linecount" mode="search">
  <font class="extent">
     <xsl:text> (</xsl:text><xsl:value-of select="."/><xsl:text> lines)</xsl:text>
  </font>
</xsl:template>

<!-- matching lines from keyword search -->
<xsl:template match="l" mode="search">
  <xsl:text>... </xsl:text>
  <xsl:apply-templates/>
  <xsl:text> ...</xsl:text>
  <br/>
</xsl:template>

<xsl:template match="div1">
  <xsl:apply-templates/>
</xsl:template>

<xsl:template match="p">
  <p><xsl:apply-templates/></p>
</xsl:template>

<xsl:template match="div" mode="poem">

  <xsl:apply-templates select="div2" mode="poem"/>

  <p class="source"> from 
  <a>
   <xsl:attribute name="href">poetry/contents.php?id=<xsl:value-of select="@id"/></xsl:attribute>
     <xsl:value-of select="//titleStmt/title"/></a>, <xsl:value-of select="//titleStmt/author"/>
  </p>

  <xsl:apply-templates select="teiHeader"/>
</xsl:template>

<xsl:template match="div2" mode="poem">
 <table class="poem">
  <tr><td>
   <!-- ignore docAuthor in poem mode -->
   <xsl:apply-templates select="*[not(self::docAuthor)]"/>
  </td></tr>
 </table>

  <xsl:call-template name="endnotes"/>
</xsl:template>

 <!-- create toggle image -->
<xsl:template name="toggle-image">
  <xsl:param name="id"/>
   <xsl:element name="a">
     <xsl:element name="img">
       <xsl:attribute name="onclick">javascript:toggle_ul('<xsl:value-of select="$id"/>')</xsl:attribute>
       <xsl:attribute name="href">javascript:toggle_ul('<xsl:value-of select="$id"/>')</xsl:attribute>
       <xsl:attribute name="src">images/closed.gif</xsl:attribute>
       <xsl:attribute name="id"><xsl:value-of select="concat($id,'-gif')"/></xsl:attribute>
     </xsl:element> <!-- img -->
   </xsl:element> <!-- a -->
</xsl:template>

</xsl:stylesheet>