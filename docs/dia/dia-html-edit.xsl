<?xml version="1.0"?>
<!-- 
     Transform dia UML objects to PHP 4.x classes 

     Copyright(c) 2002 Matthieu Sozeau <mattam@netcourrier.com>     

     This program is free software; you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation; either version 2 of the License, or
     (at your option) any later version.
     
     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.
     
     You should have received a copy of the GNU General Public License
     along with this program; if not, write to the Free Software
     Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:output method="text"/>

  <xsl:param name="directory"/>
    
  <xsl:param name="ident">
    <xsl:text>    </xsl:text>
  </xsl:param>
  
  <!-- Visibility indentation -->
  <xsl:param name="visident">
    <xsl:text>  </xsl:text>
  </xsl:param>


  <xsl:template match="class">
  
    <xsl:text>[</xsl:text><xsl:value-of select="@name"/><xsl:text>]&#xa;</xsl:text>
    <xsl:for-each select="attribute">
    <xsl:value-of select="name"/>
    <xsl:text> = </xsl:text>
    <xsl:value-of select="type"/>
    </xsl:for-each>
    <xsl:text>&#xa;</xsl:text>
  
    <xsl:document href="{$directory}{@name}.php" method="text">

    <xsl:text>&#x3c; form action ='</xsl:text>
    <xsl:value-of select="substring(@name,1,3)"/>
    <xsl:text>frmval.php' name='</xsl:text>
    <xsl:value-of select="substring(@name,1,3)"/>
    <xsl:text>frm.php' id='</xsl:text>
    <xsl:value-of select="substring(@name,1,3)"/>
    <xsl:text>'&#x3e;&#xa;</xsl:text>

<!-- falta hidden PHPSESSID -->
				
    <xsl:text>&#x3c;input name='_qf__</xsl:text>
    <xsl:value-of select="substring(@name,1,3)"/>
    <xsl:text>frm' type='hidden' value=''&#x3e;</xsl:text>
    <xsl:text>&#xa;&#x3c;input name='ins' type='hidden' value=''/&#x3e;</xsl:text>
    <xsl:text>&#xa;</xsl:text>
    <xsl:apply-templates select="attributes"/>
    <xsl:text>&#x3c;tr&#x3e;&#x3c;td align='right' valign='top'&#x3e;&#x3c;/td&#x3e;&#xa;</xsl:text>
    <xsl:text>&#x3c;td valign='top' align='left'&#x3e;&#x3c;input name='boton' value='Ok' type='submit'/&#x3e;&#x3c;/td&#x3e;&#x3c;/tr&#x3e;</xsl:text>
    </xsl:document>
  </xsl:template>



<xsl:template match="attribute">
<xsl:text>&#x3c;tr&#x3e;&#x3c;td align='right' valign='top'&#x3e;</xsl:text>
<xsl:value-of select="name"/>
<xsl:text>&#x3c;/td&#x3e;&#xa;&#x3c;td align='left' valign='top'&#x3e;&#x3c;input type='text' name='</xsl:text>
<xsl:value-of select="name"/><xsl:text>'/&#x3e;&#x3c;/td&#x3e;&#x3c;/tr&#x3e;&#xa;</xsl:text>
</xsl:template>
    
</xsl:stylesheet>
<!-- Keep this comment at the end of the file
Local variables:
mode: xml
sgml-omittag:nil
sgml-shorttag:nil
sgml-namecase-general:nil
sgml-general-insert-case:lower
sgml-minimize-attributes:nil
sgml-always-quote-attributes:t
sgml-indent-step:2
sgml-indent-data:t
sgml-parent-document:nil
sgml-exposed-tags:nil
sgml-local-catalogs:nil
sgml-local-ecat-files:nil
End:
-->
