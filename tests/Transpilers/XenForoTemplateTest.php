<?php

namespace s9e\AddonBuilder\MediaSites\Tests\Transpilers;

use RuntimeException;
use s9e\AddonBuilder\MediaSites\Transpilers\XenForoTemplate;

/**
* @covers s9e\AddonBuilder\MediaSites\Transpilers\XenForoTemplate
*/
class XenForoTemplateTest extends AbstractTranspilerTest
{
	protected function getTranspiler()
	{
		return new XenForoTemplate;
	}

	public static function getTranspilerTests(): array
	{
		return [
			[
				'',
				''
			],
			[
				'<xsl:apply-templates/>',
				new RuntimeException('Cannot transpile XSL element')
			],
			[
				'<xsl:value-of select="foo()"/>',
				new RuntimeException('Cannot convert foo()')
			],
			[
				'<hr title="{foo()}"/>',
				new RuntimeException("Cannot transpile attribute value template '{foo()}'")
			],
			[
				'<hr title="{{foo()}}"/>',
				'<hr title="{foo()}"/>'
			],
			[
				'<iframe data-s9e-mediaembed="audioboom" allowfullscreen="" scrolling="no" src="//audioboom.com/posts/{@id}/embed/v3" style="border:0;height:150px;max-width:700px;width:100%"/>',
				'<iframe data-s9e-mediaembed="audioboom" allowfullscreen="" scrolling="no" src="//audioboom.com/posts/{$id}/embed/v3" style="border:0;height:150px;max-width:700px;width:100%"></iframe>'
			],
			[
				'<hr data-s9e-livepreview-ignore-attrs=""/>',
				'<hr/>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@album">album</xsl:when>
					<xsl:otherwise>track</xsl:otherwise>
				</xsl:choose>',
				'<xf:if is="$album">album<xf:else/>track</xf:if>'
			],
			[
				'<xsl:if test="@foo">foo</xsl:if>',
				'<xf:if is="$foo">foo</xf:if>'
			],
			[
				'<iframe><xsl:attribute name="title">foo</xsl:attribute></iframe>',
				'<iframe title="foo"></iframe>'
			],
			[
				'<span><xsl:attribute name="title">foo</xsl:attribute></span>',
				'<span title="foo"></span>'
			],
			[
				'<iframe>
					<xsl:attribute name="src">
						<xsl:choose>
							<xsl:when test="@foo">foo</xsl:when>
							<xsl:otherwise>bar</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</iframe>',
				'<iframe src="{{ $foo ? \'foo\' : \'bar\' }}"></iframe>',
			],
			[
				'<iframe>
					<xsl:attribute name="src">
						<xsl:choose>
							<xsl:when test="@foo">foo<xsl:value-of select="@foo"/></xsl:when>
							<xsl:otherwise>bar</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</iframe>',
				'<iframe src="{{ $foo ? \'foo\' . $foo : \'bar\' }}"></iframe>',
			],
			[
				'<iframe>
					<xsl:attribute name="src">
						<xsl:choose>
							<xsl:when test="@foo">foo</xsl:when>
							<xsl:when test="@bar">bar</xsl:when>
							<xsl:otherwise>baz</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</iframe>',
				'<iframe src="{{ $foo ? \'foo\' : ($bar ? \'bar\' : \'baz\') }}"></iframe>',
			],
			[
				'<iframe>
					<xsl:attribute name="src">
						<xsl:choose>
							<xsl:when test="@foo">0</xsl:when>
							<xsl:when test="@bar">1</xsl:when>
							<xsl:when test="@baz">2</xsl:when>
							<xsl:otherwise>3</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</iframe>',
				'<iframe src="{{ $foo ? \'0\' : ($bar ? \'1\' : ($baz ? \'2\' : \'3\')) }}"></iframe>',
			],
			[
				'<iframe>
					<xsl:attribute name="src">
						<xsl:choose>
							<xsl:when test="@foo">
								<xsl:choose>
									<xsl:when test="@bar">foobar</xsl:when>
									<xsl:otherwise>foobaz</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>bar</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</iframe>',
				"<iframe src=\"{{ \$foo ? (\$bar ? 'foobar' : 'foobaz') : 'bar' }}\"></iframe>",
			],
			[
				'<iframe><xsl:attribute name="style">padding-bottom:<xsl:value-of select="100*@height div@width"/></xsl:attribute></iframe>',
				'<iframe style="padding-bottom:{{ 100*$height/$width }}"></iframe>'
			],
			[
				'<span><xsl:attribute name="style"><xsl:if test="@width&gt;0">padding-bottom:<xsl:value-of select="100*@height div@width"/></xsl:if></xsl:attribute></span>',
				'<span style="{{ $width>0 ? \'padding-bottom:\' . (100*$height/$width) : \'\' }}"></span>'
			],
			[
				'<span><xsl:attribute name="style"><xsl:if test="@width&gt;0">padding-bottom:<xsl:value-of select="100*(@height+49)div@width"/></xsl:if></xsl:attribute></span>',
				'<span style="{{ $width>0 ? \'padding-bottom:\' . (100*($height+49)/$width) : \'\' }}"></span>'
			],
			[
				'<xsl:if test="contains(@foo,\'/\')">x</xsl:if>',
				'<xf:if is="contains($foo,\'/\')">x</xf:if>'
			],
			[
				'<xsl:if test="not(contains(@foo,\'/\'))">x</xsl:if>',
				'<xf:if is="!contains($foo,\'/\')">x</xf:if>'
			],
			[
				"<iframe src=\"{translate(@id,'_','/')}\"></iframe>",
				"<iframe src=\"{\$id|replace('_','/')}\"></iframe>"
			],
			[
				'<xsl:if test="starts-with(@foo,\'abc\')">x</xsl:if>',
				'<xf:if is="$foo|substr(0,3) == \'abc\'">x</xf:if>'
			],
			[
				'<xsl:if test="starts-with(@foo,\'abc\')or starts-with(@bar,\'xy\')">x</xsl:if>',
				'<xf:if is="$foo|substr(0,3) == \'abc\' or $bar|substr(0,2) == \'xy\'">x</xf:if>'
			],
			[
				'<hr data-x="{$MEDIAEMBED_THEME}"/>',
				"<hr data-x=\"{{((\$xf.versionId > 2030000 && \$xf.style.isVariationsEnabled()) ? ((\$xf.visitor.style_variation) ? property_variation('styleType', \$xf.visitor.style_variation) : 'auto') : property('styleType'))}}\"/>",
			],
			[
				'<xsl:if test="$MEDIAEMBED_THEME=\'dark\'">.</xsl:if>',
				"<xf:if is=\"((\$xf.versionId > 2030000 && \$xf.style.isVariationsEnabled()) ? ((\$xf.visitor.style_variation) ? property_variation('styleType', \$xf.visitor.style_variation) : 'auto') : property('styleType'))=='dark'\">.</xf:if>"
			],
			[
				'<xsl:if test="$MEDIAEMBED_THEME!=\'dark\'">.</xsl:if>',
				"<xf:if is=\"((\$xf.versionId > 2030000 && \$xf.style.isVariationsEnabled()) ? ((\$xf.visitor.style_variation) ? property_variation('styleType', \$xf.visitor.style_variation) : 'auto') : property('styleType'))!='dark'\">.</xf:if>"
			],
			[
				'<xsl:if test="$MEDIAEMBED_THEME=\'dark\'">.</xsl:if>',
				"<xf:if is=\"((\$xf.versionId > 2030000 && \$xf.style.isVariationsEnabled()) ? ((\$xf.visitor.style_variation) ? property_variation('styleType', \$xf.visitor.style_variation) : 'auto') : property('styleType'))=='dark'\">.</xf:if>"
			],
			[
				'<xsl:value-of select="substring-after(@id,\'/\')"/>',
				"{{ \$id|split('/')|last() }}"
			],
			[
				'<xsl:value-of select="substring-before(@id,\'/headlines\')"/>',
				"{{ \$id|split('/headlines')|first() }}"
			],
			[
				'<a>
					<xsl:attribute name="title">
						<xsl:choose>
							<xsl:when test="starts-with(@foo,\'xx\')or starts-with(@bar,\'yy\')">x</xsl:when>
							<xsl:otherwise>y</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</a>',
				"<a title=\"{{ (\$foo|substr(0,2) == 'xx' or \$bar|substr(0,2) == 'yy') ? 'x' : 'y' }}\"></a>"
			],
			[
				'<iframe><xsl:attribute name="src">https://www.youtube.com/embed/<xsl:value-of select="@id"/><xsl:choose><xsl:when test="@clip">?clip=<xsl:value-of select="@clip"/>&amp;clipt=<xsl:value-of select="@clipt"/></xsl:when><xsl:otherwise><xsl:if test="@list">?list=<xsl:value-of select="@list"/></xsl:if><xsl:if test="@t"><xsl:choose><xsl:when test="@list">&amp;</xsl:when><xsl:otherwise>?</xsl:otherwise></xsl:choose>start=<xsl:value-of select="@t"/></xsl:if></xsl:otherwise></xsl:choose></xsl:attribute></iframe>',
				"<iframe src=\"https://www.youtube.com/embed/{\$id}{{ \$clip ? '?clip=' . \$clip . '&amp;clipt=' . \$clipt : ((\$list ? '?list=' . \$list : '') . (\$t ? (\$list ? '&amp;' : '?') . 'start=' . \$t : '')) }}\"></iframe>"
			],
			[
				'<xsl:if test="@type=\'r\'or@type=\'v\'">.</xsl:if>',
				'<xf:if is="$type==\'r\' or $type==\'v\'">.</xf:if>'
			],
			[
				'<xsl:if test="$FOO=\'light\'or$FOO=\'dark\'">.</xsl:if>',
				'<xf:if is="$xf.options.s9e_MediaSites_FOO==\'light\' or $xf.options.s9e_MediaSites_FOO==\'dark\'">.</xf:if>'
			],
		];
	}
}