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
			<a href="{concat($root, '/symphony/extension/email_templates/templates/edit/', ../../handle, '/', local-name())}" class="button">Edit <xsl:value-of select="local-name()"/> layout</a>
		</xsl:for-each>
	</h2>
	<fieldset class="settings">
		<legend>Email Settings</legend>
		<div>
			<xsl:if test="/data/errors/subject">
				<xsl:attribute name="class">
					<xsl:text>invalid</xsl:text>
				</xsl:attribute>
			</xsl:if>
			<label>
				Subject
				<input type="text" name="fields[subject]">
					<xsl:attribute name="value">
						<xsl:if test="/data/fields">
							<xsl:value-of select="/data/fields/subject"/>
						</xsl:if>
						<xsl:if test="not(/data/fields)">
							<xsl:value-of select="/data/templates/entry/subject"/>
						</xsl:if>
					</xsl:attribute>
				</input>
			</label>
			<xsl:if test="/data/errors/subject">
				<p><xsl:value-of select="/data/errors/subject"/></p>
			</xsl:if>
			<xsl:if test="not(/data/errors/subject)">
				<p class="help">Use the {$variable} and {/xpath/query} notation to include dynamic parts. It is not possible to combine the two syntaxes.</p>
			</xsl:if>
		</div>
	</fieldset>
	<fieldset class="settings">
		<legend>Template Settings</legend>
		<div>
			<xsl:if test="/data/errors/name">
				<xsl:attribute name="class">
					<xsl:text>invalid</xsl:text>
				</xsl:attribute>
			</xsl:if>
			<label>
				Name
				<input type="text" name="fields[name]">
					<xsl:attribute name="value">
						<xsl:if test="/data/fields">
							<xsl:value-of select="/data/fields/name"/>
						</xsl:if>
						<xsl:if test="not(/data/fields)">
							<xsl:value-of select="/data/templates/entry/name"/>
						</xsl:if>
					</xsl:attribute>
				</input>
			</label>
			<xsl:if test="/data/errors/name">
				<p><xsl:value-of select="/data/errors/name"/></p>
			</xsl:if>
		</div>
		<label>
			Datasources
			<select multiple="multiple" name="fields[datasources][]">
				<xsl:for-each select="/data/datasources/entry">
					<option value="{handle}">
						<xsl:if test="/data/fields">
							<xsl:if test="/data/fields/datasources/item = handle">
								<xsl:attribute name="selected" select="'selected'"/>
							</xsl:if>
						</xsl:if>
						<xsl:if test="not(/data/fields)">
							<xsl:if test="/data/templates/entry/datasources/item = handle">
								<xsl:attribute name="selected" select="'selected'"/>
							</xsl:if>
						</xsl:if>
						<xsl:value-of select="name"/>
					</option>
				</xsl:for-each>
			</select>
		</label>
		<p class="help">Layouts will be able to use these datasources to build their content.</p>
	</fieldset>
	<div class="actions">
		<input type="submit" accesskey="s" value="Save Changes" name="action[save]" />
		<xsl:if test="not(/data/context/item[@index=1] = 'new')" >
			<button accesskey="d" title="Delete this page" class="button confirm delete" name="action[delete]">Delete</button>
		</xsl:if>
	</div>
</xsl:template>
</xsl:stylesheet>