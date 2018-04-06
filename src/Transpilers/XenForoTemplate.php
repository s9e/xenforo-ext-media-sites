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
			'(\\{@([-\\w]+)\\})'                         => '{$$1}',
			'(<xsl:value-of select="@([-\\w]+)"/>)'      => '{$$1}',
			'((<iframe[^>]+?)/>)'                        => '$1></iframe>',
			'( data-s9e-livepreview[^=]*="[^"]*")'       => '',

//			'(<xsl:choose><xsl:when test="@([-\\w]+)">)' => '<xf:if is="$$1">',
//			'(<xsl:when test="@([-\\w]+)">)'             => '<xf:elseif is="$$1"/>',
//			'(<xsl:otherwise>)'                          => '<xf:else/>',
//			'(</xsl:otherwise>)'                         => '',
//			'(</xsl:when>)'                              => '',
//			'(</xsl:choose>)'                            => '</xf:if>',
//			'(<xf:if is="([^"]+)">([^\']+)<xf:else/>([^\']+)</xf:if>)'
//				=> "{{ \$1 ? '\$2' : '\$3' }}"
		];

		// <b><xsl:attribute name="foo">
		// <b foo="

		// <b><xsl:choose><xsl:attribute name="foo">
		// <b {{ $x ? 'foo=".."' : '' }}>

		$template = preg_replace(array_keys($replacements), array_values($replacements), $template);


/**
		if (strpos($template, '<xf:if') !== false)
		{
			echo "\n===========================\n";
			echo $template;
			echo "\n===========================\n";

			die('!');
		}
/**/

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
}