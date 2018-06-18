<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\Transpilers;

use DOMDocument;
use DOMElement;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

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
		$replacements = [
			'(\\{@([-\\w]+)\\})'                       => '{$$1}',
			'(<xsl:value-of select="@([-\\w]+)"/>)'    => '{$$1}',
			'((<iframe[^>]+?)/>)'                      => '$1></iframe>',
			'( data-s9e-livepreview[^=]*="[^"]*")'     => '',

			'(<xsl:choose><xsl:when test="([^"]++)">)' => '<xf:if is="$1">',
			'(</xsl:when><xsl:when test="([^"]++)">)'  => '<xf:elseif is="$1">',
			'(</xsl:when><xsl:otherwise>)'             => '<xf:else/>',
			'(</xsl:otherwise></xsl:choose>)'          => '</xf:if>',
		];
		$template = preg_replace(array_keys($replacements), array_values($replacements), $template);
		$template = preg_replace_callback(
			'(<xf:(?:else)?if is="\\K[^"]++)',
			function ($m)
			{
				return self::convertCondition($m[0]);
			},
			$template
		);

		if (strpos($template, '<xsl:') !== false)
		{
			throw new RuntimeException('Cannot transpile XSL element');
		}
		if (preg_match('((?<!\\{)\\{(?![{$])[^}]*\\}?)', $template, $m))
		{
			throw new RuntimeException("Cannot transpile attribute value template '" . $m[0] . "'");
		}

		$template = strtr($template, ['{{' => '{', '}}' => '}']);

		return $template;
	}

	/**
	* Convert an XPath expression to a XenForo expression
	*
	* @param  string $expr
	* @return string
	*/
	protected static function convertCondition($expr)
	{
		$replacements = [
			"(^@(\\w+)$)D"         => '$$1',
			"(^@(\\w+)(='.*?')$)D" => '$$1=$2'
		];

		$expr = preg_replace(array_keys($replacements), array_values($replacements), $expr, -1, $cnt);
		if (!$cnt)
		{
			throw new RuntimeException('Cannot convert ' . $expr);
		}

		return $expr;
	}
}