<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use s9e\SweetDOM\Element;
use s9e\SweetDOM\Node;
use s9e\SweetDOM\Text;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class SwitchCSSWidth extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//*[@data-s9e-mediaembed][contains(@style, "max-width")]',
		'//xsl:attribute[@name = "style"][xsl:if or xsl:choose][contains(., "max-width")]//text()'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$style = $element->getAttribute('style');
		$style = $this->normalizeStyle($style);
		$element->setAttribute('style', $style);
	}

	protected function normalizeStyle(string $style): string
	{
		$style = preg_replace('((^|;)width:100%)', '$1', $style);
		$style = str_replace('max-width:', 'width:', $style);
		$style = trim($style, ';');

		return $style;
	}

	protected function normalizeText(Text $node): void
	{
		$node->nodeValue = $this->normalizeStyle($node->nodeValue);
	}
}