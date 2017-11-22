<?php

/**
* @package   s9e\XenForo\MediaSites
* @copyright Copyright (c) 2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\Transpilers;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP;

class Transpiler extends PHP implements TranspilerInterface
{
	/**
	* Transpile given XSLT template to PHP
	*
	* @param  string $template XSLT template
	* @return string           PHP source
	*/
	public function transpile($template)
	{
		$php = $this->compileTemplate($template);
		$php = str_replace('$this->out', '$html', $php);
		$php = preg_replace("(\\\$node->getAttribute\\(('[^']+')\\))", '$var[$1]', $php);
		$php = preg_replace("(\\\$node->hasAttribute\\(('[^']+')\\))", 'isset($var[$1])', $php);

		if (substr($php, 0, 7) === '$html.=')
		{
			$php = '$html=' . substr($php, 7);
		}
		else
		{
			$php = "\$html='';" . $php;
		}

		return $php;
	}
}