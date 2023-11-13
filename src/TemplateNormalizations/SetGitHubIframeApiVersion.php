<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use s9e\SweetDOM\Element;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class SetGitHubIframeApiVersion extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//iframe[@onload or .//xsl:attribute[@name = "onload"]]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$regexp = '(https://s9e\\.github\\.io/iframe/(\\d+))';
		$text   = $element->getAttribute('src') . $element->textContent;
		if (preg_match($regexp, $text, $m))
		{
			$element->setAttribute('data-s9e-mediaembed-api', $m[1]);
		}
	}
}