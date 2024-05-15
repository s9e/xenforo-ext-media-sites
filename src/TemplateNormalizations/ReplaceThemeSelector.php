<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use s9e\SweetDOM\Element;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class ReplaceThemeSelector extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//xsl:if[@test = "$MEDIAEMBED_THEME"]',
		'//xsl:when[@test = "$MEDIAEMBED_THEME"]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$element->setAttribute('test', 'true()');
	}
}