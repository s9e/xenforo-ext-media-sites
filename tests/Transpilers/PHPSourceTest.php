<?php

namespace s9e\AddonBuilder\MediaSites\Tests\Transpilers;

use RuntimeException;
use s9e\AddonBuilder\MediaSites\Transpilers\PHPSource;
use s9e\TextFormatter\Configurator;

/**
* @covers s9e\AddonBuilder\MediaSites\Transpilers\PHPSource
*/
class PHPSourceTest extends AbstractTranspilerTest
{
	protected function getTranspiler()
	{
		return new PHPSource(new Configurator);
	}

	public static function getTranspilerTests(): array
	{
		return [
			[
				'',
				"\$html='';"
			],
			[
				'<iframe src="{foo()}"/>',
				new RuntimeException('Cannot convert ->xpath->evaluate')
			],
			[
				'<iframe src="{@id}"/>',
				"\$vars+=['id'=>null];\$html='<iframe src=\"'.htmlspecialchars(\$vars['id']??'',2).'\"></iframe>';"
			],
			[
				'<iframe src="{@id}"/>',
				"\$vars+=['id'=>'xyz'];\$html='<iframe src=\"'.htmlspecialchars(\$vars['id']??'',2).'\"></iframe>';",
				['attributes' => ['id' => ['defaultValue' => 'xyz']]]
			],
			[
				'<iframe src="{$FOO}"/>',
				"\$options=XF::options();\$html='<iframe src=\"'.htmlspecialchars(\$options->s9e_MediaSites_FOO,2).'\"></iframe>';"
			],
			[
				'<iframe data-s9e-mediaembed="audioboom" allowfullscreen="" scrolling="no" src="//audioboom.com/posts/{@id}/embed/v3" style="border:0;height:150px;max-width:700px;width:100%"/>',
				'$vars+=[\'id\'=>null];$html=\'<iframe data-s9e-mediaembed="audioboom" allowfullscreen="" scrolling="no" src="//audioboom.com/posts/\'.htmlspecialchars($vars[\'id\']??\'\',2).\'/embed/v3" style="border:0;height:150px;max-width:700px;width:100%"></iframe>\';'
			],
			[
				'<hr data-x="{$MEDIAEMBED_THEME}"/>',
				"\$html='<hr data-x=\"'.htmlspecialchars(XF::app()->templater()->getStyle()->getProperty('styleType'),2).'\">';"
			],
			[
				'<xsl:if test="$MEDIAEMBED_THEME=\'dark\'">.</xsl:if>',
				"\$html='';if(XF::app()->templater()->getStyle()->getProperty('styleType')==='dark'){\$html.='.';}"
			],
		];
	}
}