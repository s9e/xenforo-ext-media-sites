<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
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
		'//*[@data-s9e-mediaembed][contains(@style, "max-width")]',
		'//xsl:attribute[@name = "style"][xsl:if or xsl:choose][contains(., "max-width")]//text()'
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
			$this->normalizeTextNode($node);
		}
		else
		{
			parent::normalizeNode($node);
		}
	}

	protected function normalizeStyle(string $style): string
	{
		$style = preg_replace('((^|;)width:100%)', '$1', $style);
		$style = str_replace('max-width:', 'width:', $style);
		$style = trim($style, ';');

		return $style;
	}

	protected function normalizeTextNode(DOMText $text): void
	{
		$text->nodeValue = $this->normalizeStyle($text->nodeValue);
	}
}