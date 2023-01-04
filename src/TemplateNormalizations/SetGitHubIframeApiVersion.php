<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use DOMElement;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class SetGitHubIframeApiVersion extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//iframe[@onload or .//xsl:attribute[@name = "onload"]]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$regexp = '(https://s9e\\.github\\.io/iframe/(\\d+))';
		$text   = $element->getAttribute('src') . $element->textContent;
		if (preg_match($regexp, $text, $m))
		{
			$element->setAttribute('data-s9e-mediaembed-api', $m[1]);
		}
	}
}