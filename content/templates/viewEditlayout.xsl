<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>	
		<span>Email Templates</span>
		<xsl:for-each select="/data/templates/entry/layouts/*">
			<a href="" class="button">Edit <xsl:value-of select="local-name()"/> layout</a>
		</xsl:for-each>
	</h2>
	<fieldset class="primary">
		<div>
			<xsl:if test="/data/errors/body">
				<xsl:attribute name="class">
					<xsl:text>invalid</xsl:text>
				</xsl:attribute>
			</xsl:if>
		
			<label>
				Body
				<textarea class="code" cols="80" rows="30" name="fields[body]">
					<xsl:if test="/data/fields">
						<xsl:value-of select="/data/fields/body"/>
					</xsl:if>
					<xsl:if test="not(/data/fields)">
						<xsl:value-of select="/data/layout"/>
					</xsl:if>
				</textarea>
				<xsl:if test="/data/errors/body">
					<p><xsl:copy-of select="/data/errors/body" /></p>
				</xsl:if>
			</label>
		</div>
	</fieldset>
	<div class="secondary">
		<p class="label">Utilities</p>
		<ul id="utilities">
			<xsl:for-each select="/data/utilities/item">
				<li>
					<xsl:if test="(position() mod 2) = 1">
						<xsl:attribute name="class">
							odd
						</xsl:attribute>
					</xsl:if>
					<a href="{$root}/symphony/blueprints/utilities/edit/{substring-before(.,'.xsl')}"><xsl:value-of select="." /></a>
				</li>
			</xsl:for-each>
		</ul>
	</div>
	<div class="actions">
		<input type="submit" accesskey="s" value="Save Changes" name="action[save]" />
	</div>
</xsl:template>
</xsl:stylesheet>