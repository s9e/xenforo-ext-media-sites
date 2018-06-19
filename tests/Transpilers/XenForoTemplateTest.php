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
				'<xsl:value-of select="foo()"/>',
				new RuntimeException('Cannot transpile XSL element')
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
		];
	}
}