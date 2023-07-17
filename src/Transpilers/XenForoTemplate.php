<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
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
			'(\\{\\{)'                               => '&#123;',
			'(\\}\\})'                               => '&#125;',
			'(\\{\\$([A-Z]\\w+)\\})'                 => '{{$xf.options.s9e_MediaSites_$1}}',
			'(\\{@(\\w+)\\})'                        => '{$$1}',
			'(<xsl:value-of select="@(\\w+)"/>)'     => '{$$1}',
			'(<xsl:value-of select="\\$(\\w+)"/>)'   => '{{$xf.options.s9e_MediaSites_$1}}',
			'((<iframe[^>]+?)/>)'                    => '$1></iframe>',
			'( data-s9e-livepreview[^=]*="[^"]*")'   => '',
			"(\\{translate\\(@id,'(.)','(.)'\\)\\})" => "{\$id|replace('\$1','\$2')}",

			'(<xsl:if test="([^"]++)">)'               => '<xf:if is="$1">',
			'(</xsl:if>)'                              => '</xf:if>',
			'(<xsl:choose><xsl:when test="([^"]++)">)' => '<xf:if is="$1">',
			'(</xsl:when><xsl:when test="([^"]++)">)'  => '<xf:elseif is="$1"/>',
			'(</xsl:when><xsl:otherwise>)'             => '<xf:else/>',
			'(</xsl:otherwise></xsl:choose>)'          => '</xf:if>',
		];
		$template = preg_replace('((<xsl:when[^>]*)/>)', '$1></xsl:when>', $template);
		$template = preg_replace(array_keys($replacements), array_values($replacements), $template);
		$template = preg_replace_callback(
			'(<xf:(?:else)?if is="\\K[^"]++)',
			function ($m)
			{
				return $this->convertXPath($m[0]);
			},
			$template
		);
		$template = preg_replace_callback(
			'(<xsl:value-of select="(.*?)"/>)',
			function ($m)
			{
				return '{{ ' . $this->convertXPath($m[1]) . ' }}';
			},
			$template
		);

		// Replace xf:if with inline ternaries in attributes
		$template = preg_replace_callback(
			'(<xsl:attribute[^>]+>\\K.*?(?=</xsl:attribute))',
			function ($m)
			{
				return $this->convertTernaries($m[0]);
			},
			$template
		);

		// Inline xsl:attribute elements in HTML elements
		$template = $this->loopReplace(
			'((<(?!\\w+:)[^>]*)><xsl:attribute name="(\\w+)">(.*?)</xsl:attribute>)',
			'$1 $2="$3">',
			$template
		);

		// Test whether we've been able to transpile everything
		if (strpos($template, '<xsl:') !== false)
		{
			throw new RuntimeException('Cannot transpile XSL element');
		}
		if (preg_match('((?<!\\{)\\{(?![{$])[^}]*\\}?)', $template, $m))
		{
			throw new RuntimeException("Cannot transpile attribute value template '" . $m[0] . "'");
		}

		// Unescape braces
		$template = strtr($template, ['&#123;' => '{', '&#125;' => '}']);

		// Replace the $MEDIAEMBED_THEME parameter with the XenForo style property
		$template = str_replace('$xf.options.s9e_MediaSites_MEDIAEMBED_THEME', "property('styleType')", $template);

		return $template;
	}

	/**
	* Convert template content to be used in a ternary
	*
	* @param  string $str
	* @return string
	*/
	protected function convertMixedContent($str)
	{
		// Escape ternaries
		$str = preg_replace_callback(
			'(\\{\\{\\s*(.*?)\\s*\\}\\})',
			function ($m)
			{
				return '@' . base64_encode($m[1]) . '@';
			},
			$str
		);
		$str = "'" . $str . "'";
		$str = preg_replace('(\\{(\\$\\w+)\\})', "' . $1 . '", $str);

		// Unescape ternaries
		$str = preg_replace_callback(
			'(@([^@]++)@)',
			function ($m)
			{
				return "' . (" . base64_decode($m[1]) . ") . '";
			},
			$str
		);

		// Remove empty concatenations
		$str = str_replace("'' . ", '', $str);
		$str = str_replace(" . ''", '', $str);

		return $str;
	}

	/**
	* Convert xf:if elements into inline ternaries
	*
	* @param  string $template
	* @return string
	*/
	protected function convertTernaries($template)
	{
		$old      = $template;
		$template = preg_replace_callback(
			'(<xf:if is="[^"]+">[^<]*(?:<xf:else[^>]*?/>[^<]*)*</xf:if>)',
			function ($m)
			{
				return $this->convertTernary($m[0]);
			},
			$template
		);
		if ($template !== $old)
		{
			$template = $this->convertTernaries($template);
		}

		return $template;
	}

	/**
	* Convert given xf:if element into inline ternaries
	*
	* @param  string $template
	* @return string
	*/
	protected function convertTernary($template)
	{
		preg_match_all('(<xf:(?:else)?if is="([^"]+)"/?>([^<]*))', $template, $m, PREG_SET_ORDER);

		$expr = '';
		foreach ($m as $i => list($match, $condition, $content))
		{
			if ($i > 0)
			{
				$expr .= '(';
			}

			// Make sure compound conditions are in parentheses
			if (preg_match('( (?:and|or) )', $condition))
			{
				$condition = '(' . $condition . ')';
			}

			$expr .= $condition . ' ? ' . $this->convertMixedContent($content) . ' : ';
		}
		if (preg_match('(<xf:else/>\\K[^<]*)', $template, $m))
		{
			$else = $this->convertMixedContent($m[0]);
			if (str_contains($else, ' '))
			{
				// Add parentheses if the else clause is more than one token
				$else = '(' . $else . ')';
			}
			$expr .= $else;
		}
		else
		{
			$expr .= "''";
		}
		$expr .= str_repeat(')', $i);

		return '{{ ' . $expr . ' }}';
	}

	/**
	* Convert a starts-with() call
	*/
	protected static function convertStartsWith(string $attrName, string $str): string
	{
		return '$' . $attrName . '|substr(0,' . strlen($str) . ') == ' . var_export($str, true);
	}

	/**
	* Convert an XPath expression to a XenForo expression
	*/
	protected function convertXPath($expr): string
	{
		if (str_starts_with($expr, 'starts-with'))
		{
			return $this->convertStartsWithExpr($expr);
		}

		$replacements = [
			"(^translate\\(@(\\w+),'(.)','(.)'\\))" => '$$1|replace(\'$2\',\'$3\')',

			"(^\\$(\\w+)(!=|(=))('.*')$)D"         => '$xf.options.s9e_MediaSites_$1$2$3$4',
			"(^contains\\(\\$(\\w+,'[^']+')\\)$)D" => 'contains($xf.options.s9e_MediaSites_$1)',

			'(^@(\\w+)$)D'                 => '$$1',
			"(^@(\\w+)(='.*')$)D"          => '$$1=$2',
			'(^@(\\w+)>(\\d+)$)D'          => '$$1>$2',
			'(^100\\*@height div@width$)D' => '100*$height/$width',
			'(^100\\*\\(@height\\+(\\d+)\\)div@width$)D'        => '100*($height+$1)/$width',
			"(^contains\\(@(\\w+,'[^']+')\\)$)D"                => 'contains($$1)',
			"(^not\\(contains\\(@(\\w+,'[^']+')\\)\\)$)D"       => '!contains($$1)',
			"(^@(\\w+) or ?contains\\(@(\\w+,'[^']+')\\)$)D"    => '$$1 or contains($$2)',
			"(^@(\\w+) and ?contains\\(('[^']+'),@(\\w+)\\)$)D" => '$$1 and contains($2,$$3)',
			"(^@(\\w+) and@(\\w+)!=('[^']++')$)D"               => '$$1 and $$2!=$3',

			"(^substring-after\\(@(\\w+),('[^']+')\\)$)"  => '$$1|split($2)|last()',
			"(^substring-before\\(@(\\w+),('[^']+')\\)$)" => '$$1|split($2)|first()',
		];

		$expr = html_entity_decode($expr);
		$expr = preg_replace(array_keys($replacements), array_values($replacements), $expr, -1, $cnt);
		if (!$cnt)
		{
			throw new RuntimeException('Cannot convert ' . $expr);
		}

		return $expr;
	}

	protected function convertStartsWithExpr(string $expr): string
	{
		$regexp = "(^starts-with\\(@(\\w+),'([^']+)'\\)((?:or starts-with\\(@(\\w+),'([^']+)'\\))*)$)";
		if (!preg_match($regexp, $expr, $m))
		{
			throw new RuntimeException('Cannot convert ' . $expr);
		}

		$expr = $this->convertStartsWith($m[1], $m[2]);
		if (!empty($m[3]))
		{
			$expr .= ' or ' . $this->convertStartsWithExpr(substr($m[3], 3));
		}

		return $expr;
	}

	/**
	* Repeatedly perform given pattern replacement until it stops matching
	*
	* @param  string $match   Match regexp
	* @param  string $replace Replacement
	* @param  string $str     Original string
	* @return string          Modified string
	*/
	protected function loopReplace($match, $replace, $str)
	{
		do
		{
			$str = preg_replace($match, $replace, $str, 1, $cnt);
		}
		while ($cnt > 0);

		return $str;
	}
}