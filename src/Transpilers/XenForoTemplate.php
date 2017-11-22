<?php

/**
* @package   s9e\XenForo\MediaSites
* @copyright Copyright (c) 2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\Transpilers;

class XenForoTemplate implements TranspilerInterface
{
	/**
	* Transpile given XSLT template to XenForo template
	*
	* @param  string $template XSLT template
	* @return string           XenForo template
	*/
	public function transpile($template)
	{
		$template = preg_replace('(\\{@([-\\w]+)\\})', '{$$1}', $template);

		return $template;
	}
}