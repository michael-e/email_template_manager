<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<form method="post" action="{$current-url}">
		<table class="selectable">
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Layouts</th>
					<th scope="col">Preview</th>
				</tr>
			</thead>
			<tbody>
				<xsl:if test="/data/templates/entry">
					<xsl:apply-templates select="/data/templates/entry"/>
				</xsl:if>
				<xsl:if test="not(/data/templates/entry)">
					<tr>
						<td class="inactive" colspan="3">
							None found
						</td>
					</tr>
				</xsl:if>
			</tbody>
		</table>
	    <div class="actions">
	        <fieldset class="apply">
	            <div>
	                <select name="with-selected">
	                    <option value="">With Selected...</option>
	                    <option class="confirm" value="delete">Delete</option>
	                </select>
	            </div>
	            <button type="submit" name="action[apply]">Apply</button>
	        </fieldset>
	    </div>
	</form>
</xsl:template>

<xsl:template match="templates/entry">
	<tr>
		<td>
			<a href="{concat($root, '/symphony/extension/email_template_manager/templates/edit/', handle)}"><xsl:value-of select="name"/></a>
			<input name="items[{handle}]" type="checkbox" />
		</td>
		<td>
			<xsl:apply-templates select="layouts/*" mode="edit"/>
		</td>
		<td>
			<xsl:apply-templates select="layouts/*" mode="preview"/>
		</td>
	</tr>
</xsl:template>

<xsl:template match="templates/entry/layouts/*" mode="edit">
	<a href="{concat($root, '/symphony/extension/email_template_manager/templates/edit/', ../../handle, '/', local-name())}" style="text-transform:uppercase"><xsl:value-of select="local-name()"/></a>
</xsl:template>

<xsl:template match="templates/entry/layouts/*" mode="preview">
	<a href="{concat($root, '/symphony/extension/email_template_manager/templates/preview/', ../../handle, '/', local-name())}" style="text-transform:uppercase"><xsl:value-of select="local-name()"/></a>
</xsl:template>
</xsl:stylesheet>