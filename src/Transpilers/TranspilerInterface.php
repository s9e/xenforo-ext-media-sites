<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\Transpilers;

interface TranspilerInterface
{
	/**
	* Transpile given XSLT template to the target language
	*
	* @param  string $template XSLT template
	* @return string           Template in the target language
	*/
	public function transpile($template);
}