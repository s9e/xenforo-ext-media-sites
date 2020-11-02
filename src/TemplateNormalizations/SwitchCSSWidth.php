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
		'//iframe[@data-s9e-mediaembed][contains(@style, "width:100%")]',
		'//span[@data-s9e-mediaembed][contains(@style, "width:100%")]',
		'//xsl:attribute[@name = "style"][xsl:if or xsl:choose]//text()[contains(., "width")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		if ($element->tagName === 'xsl:attribute')
		{
			var_dump($element->ownerDocument->saveXML($element));
			return;
		}
		$methodName = 'normalize' . ucfirst($element->tagName);
		$this->$methodName($element);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node)
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

	protected function normalizeIframe(DOMElement $iframe)
	{
		$style = $iframe->getAttribute('style');
		$style = $this->normalizeStyle($style);
		$iframe->setAttribute('style', $style);
	}

	protected function normalizeSpan(DOMElement $span)
	{
		$style = $span->getAttribute('style');
		$style = $this->normalizeStyle($style);
		$span->setAttribute('style', $style);
	}

	protected function normalizeStyle(string $style)
	{
		$style = str_replace(';width:100%', '', $style);
		$style = str_replace('max-width:', 'width:', $style);

		return $style;
	}

	protected function normalizeText(DOMText $text)
	{
	}
}