<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\Transpilers;

use RuntimeException;
use s9e\SourceOptimizer\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;

class PHPSource extends PHP implements TranspilerInterface
{
	/**
	* @var Optimizer
	*/
	protected $sourceOptimizer;

	/**
	* Transpile given XSLT template to PHP
	*
	* @param  string $template   XSLT template
	* @param  array  $siteConfig Site's config
	* @return string             PHP source
	*/
	public function transpile($template, array $siteConfig = [])
	{
		$php = $this->compileTemplate($template);

		// Collect the name of all vars in use to initialize them with a null value
		$vars = [];
		preg_match_all("(\\\$node->...Attribute\\('([^']+)'\\))", $php, $matches);
		foreach ($matches[1] as $varName)
		{
			$phpValue = (isset($siteConfig['attributes'][$varName]['defaultValue']))
			          ? var_export($siteConfig['attributes'][$varName]['defaultValue'], true)
			          : 'null';
			$vars[$varName] = var_export($varName, true) . '=>' . $phpValue;
		}

		// Replace the variable names and DOM calls
		$php = str_replace('$this->out', '$html', $php);
		$php = preg_replace("(\\\$node->getAttribute\\(('[^']+')\\))", '$vars[$1]', $php);
		$php = preg_replace("(\\\$node->hasAttribute\\(('[^']+')\\))", 'isset($vars[$1])', $php);

		$php = "\$html='';" . $php;
		$php = str_replace("\$html='';\$html.=", '$html=', $php);

		// Replace the template params
		$php = preg_replace_callback(
			"(\\\$this->params\\['([^']+)'\\])",
			function ($m)
			{
				$optionName = 's9e_MediaSites_' . $m[1];

				return '$options->' . $optionName;
			},
			$php
		);

		// Make $MEDIAEMBED_THEME a special case
		$php = str_replace(
			'$options->s9e_MediaSites_MEDIAEMBED_THEME',
			"XF::app()->templater()->getStyle()->getProperty('styleType')",
			$php
		);

		if (preg_match('((?<!\\$options|XF::app\\(\\)|templater\\(\\)|getStyle\\(\\))->[^;]*)', $php, $m))
		{
			throw new RuntimeException('Cannot convert ' . $m[0]);
		}
		if (strpos($php, '$options->') !== false)
		{
			$php = '$options=XF::options();' . $php;
		}
		if (!empty($vars))
		{
			ksort($vars);
			$php = '$vars+=[' . implode(',', $vars) . '];' . $php;
		}

		return $this->getSourceOptimizer()->optimize($php);
	}

	/**
	* Return the cached instance of optimizer
	*
	* @return Optimizer
	*/
	protected function getSourceOptimizer()
	{
		if (!isset($this->sourceOptimizer))
		{
			$this->sourceOptimizer = new Optimizer;
		}

		return $this->sourceOptimizer;
	}
}