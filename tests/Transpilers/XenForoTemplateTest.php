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
		];
	}
}