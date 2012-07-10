<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<form method="post" action="{$current-url}" class="two columns">
		<fieldset class="primary column">
			<xsl:if test="/data/errors/body">
				<xsl:attribute name="class">
					<xsl:text>invalid primary</xsl:text>
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
		</fieldset>
		<div class="secondary column">
			<p class="label">Utilities</p>
			<div class="frame">
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
		</div>
		<div class="actions">
			<input type="submit" accesskey="s" value="Save Changes" name="action[save]" />
		</div>
	</form>
</xsl:template>
</xsl:stylesheet>