<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use DOMElement;
use DOMNode;
use DOMText;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class SwitchCSSWidth extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//iframe[@data-s9e-mediaembed][contains(@style, "width")]',
		'//span[@data-s9e-mediaembed][contains(@style, "width")]',
		'//xsl:attribute[@name = "style"][xsl:if or xsl:choose]//text()[contains(., "width")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element): void
	{
		$style = $element->getAttribute('style');
		$style = $this->normalizeStyle($style);
		$element->setAttribute('style', $style);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node): void
	{
		if ($node instanceof DOMText)
		{
			$this->normalizeText($node);
		}
		else
		{
			parent::normalizeNode($node);
		}
	}

	protected function normalizeStyle(string $style): string
	{
		$style = preg_replace('((^|;)width:100%(;)?)', '$1', $style);
		$style = str_replace('max-width:', 'width:', $style);

		return $style;
	}

	protected function normalizeText(DOMText $text): void
	{
		$text->nodeValue = $this->normalizeStyle($text->nodeValue);
	}
}