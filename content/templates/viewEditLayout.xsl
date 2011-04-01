<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>
		<span><xsl:value-of select="/data/templates/entry/name" /> - <xsl:value-of select="translate(/data/context/item[@index = 3],'abcdefghijklmnopqrstuvwxyz','ABCDEFGHIJKLMNOPQRSTUVWXYZ')" /></span>
		<a class="button" href="{$root}/symphony/extension/email_template_manager/templates/preview/{/data/context/item[@index = 2]}/{/data/context/item[@index = 3]}/">Preview layout</a>
		<xsl:for-each select="/data/templates/entry/layouts/*">
			<xsl:if test="not(local-name() = /data/context/item[@index = 3])">
				<a href="{concat(substring-before($current-url, concat('/',/data/context/item[@index = 3],'/')), '/', local-name() , '/')}" class="button">Edit <xsl:value-of select="translate(local-name(),'abcdefghijklmnopqrstuvwxyz','ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/> layout</a>
			</xsl:if>
		</xsl:for-each>
		<a class="button" href="{substring-before($current-url, concat('/',/data/context/item[@index = 3],'/'))}/">Edit configuration</a>
	</h2>
	<form method="post" action="{$current-url}">
		<fieldset class="primary">
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
	</form>
</xsl:template>
</xsl:stylesheet>