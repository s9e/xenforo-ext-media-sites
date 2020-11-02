<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use DOMElement;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class SwitchCSSWidth extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//iframe[@data-s9e-mediaembed]',
		'//span[@data-s9e-mediaembed][starts-with(@style, "width:100%")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$style = $element->getAttribute('style');
		$style = preg_replace(
			'(width:100%;max-width:([^;]++))',
			'width:$1;max-width:100%',
			$style
		);
		$element->setAttribute('style', $style);
	}
}