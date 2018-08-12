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

	public function getTranspilerTests()
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
				'<iframe src="{{ $foo ? ($bar ? \'foobar\' : \'foobaz\') : \'bar\' }}"></iframe>',
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
		];
	}
}