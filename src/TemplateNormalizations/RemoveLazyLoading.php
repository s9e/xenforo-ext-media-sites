<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use DOMElement;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class RemoveLazyLoading extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//iframe[@loading]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$element->removeAttribute('loading');
	}
}