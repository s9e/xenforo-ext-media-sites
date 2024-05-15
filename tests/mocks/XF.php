<?php

use Composer\InstalledVersions;

class XF
{
	public static $options;
	public static $config = [];
	public static $styleProperties = [];
	protected static $templates = [];
	protected function __construct()
	{
		if (!isset(self::$options))
		{
			self::$options = new stdClass;
		}
		if (!empty(self::$templates))
		{
			return;
		}

		$rootDir = realpath(InstalledVersions::getRootPackage()['install_path']);

		$dom = new DOMDocument;
		$dom->load($rootDir . '/addon/_data/bb_code_media_sites.xml');
		foreach ($dom->getElementsByTagName('site') as $site)
		{
			$siteId   = $site->getAttribute('media_site_id');
			$template = $site->getElementsByTagName('embed_html')->item(0)->textContent;

			self::$templates[$siteId] = $template;
		}
	}
	public static function app()
	{
		return new self;
	}
	public function client()
	{
		return new GuzzleHttp\Client;
	}
	public static function config($key)
	{
		return self::$config[$key] ?? null;
	}
	public function getProperty($name)
	{
		return self::$styleProperties[$name] ?? '';
	}
	public function getStyle()
	{
		return $this;
	}
	public function http()
	{
		return $this;
	}
	public static function options()
	{
		return self::$options;
	}
	public function templater()
	{
		return $this;
	}
	public function renderTemplate($name, $vars)
	{
		$siteId = str_replace('public:_media_site_embed_', '', $name);
		if (!isset(self::$templates[$siteId]))
		{
			return '';
		}

		$template = self::$templates[$siteId];
		$template = self::renderIf($template, $vars);
		$template = self::renderIfElse($template, $vars);
		$template = self::renderTernaries($template, $vars);
		$template = preg_replace_callback(
			'(\\{\\$(\\w+)\\})',
			function ($m) use ($vars)
			{
				$varName = $m[1];

				return (isset($vars[$varName])) ? $vars[$varName] : '';
			},
			$template
		);

		return $template;
	}
	protected static function contains($haystack, $needle)
	{
		return strpos($haystack, $needle) !== false;
	}
	protected static function renderIf($template, array $vars): string
	{
		$old      = $template;
		$template = preg_replace_callback(
			'(<xf:if is="\\$(\\w+)">([^<]++)</xf:if>)',
			fn($m) => isset($vars[$m[1]]) ? $m[2] : '',
			$template
		);
		$template = preg_replace_callback(
			'(<xf:if is="\\$\\w+">[^<]*+(?:<xf:elseif is="\\$\\w+"/>[^<]*+)*<xf:else/>([^<]*+)</xf:if>)',
			function ($m) use ($vars)
			{
				$default = $m[1];
				preg_match_all('(is="\\$(\\w+)">([^<]*+))', $m[0], $m);
				foreach ($m[1] as $i => $varName)
				{
					if (isset($vars[$varName]))
					{
						return $m[2][$i];
					}
				}

				return $default;
			},
			$template
		);
		if ($template !== $old)
		{
			$template = self::renderIf($template, $vars);
		}

		return $template;
	}
	protected static function renderIfElse($template, array $vars): string
	{
		if (preg_match('(^<xf:if is="\\$(\\w+)">(.*)<xf:else/>(.*)</xf:if>$)s', $template, $m))
		{
			$template = (!empty($vars[$m[1]])) ? $m[2] : $m[3];
		}

		return $template;
	}
	protected static function renderTernaries($template, array $vars)
	{
		preg_match_all('(\\$(\\w+))', $template, $matches);
		$vars += array_fill_keys($matches[1], '');

		return preg_replace_callback(
			'(\\{\\{(.+?)\\}\\})',
			function ($m) use ($vars)
			{
				$php    = $m[1];
				$php    = str_replace(
					"((\$xf.versionId > 2030000 && \$xf.style.isVariationsEnabled()) ? ((\$xf.visitor.style_variation) ? property_variation('styleType', \$xf.visitor.style_variation) : 'auto') : property('styleType'))",
					"property('styleType')",
					$php
				);
				$php    = preg_replace('(\\$xf\\.options\\.(\\w+))', 'XF::options()->$1', $php);
				$php    = preg_replace('(\\$(\\w+))', '$vars["$1"]', $php);
				$php    = str_replace('contains(', 'XF::contains(', $php);
				$php    = preg_replace('(^property\\()', 'XF::app()->templater()->getStyle()->getProperty(', $php);

				$result = eval('return ' . $php . ';');

				return $result;
			},
			$template
		);
	}
}