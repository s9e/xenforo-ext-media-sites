<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\AddonBuilder\MediaSites\TemplateNormalizations\RemoveDefaultStyle;
use s9e\AddonBuilder\MediaSites\TemplateNormalizations\RemoveLazyLoading;
use s9e\AddonBuilder\MediaSites\TemplateNormalizations\SetGitHubIframeApiVersion;
use s9e\AddonBuilder\MediaSites\TemplateNormalizations\SplitConditionalAttributes;
use s9e\AddonBuilder\MediaSites\TemplateNormalizations\SwitchCSSWidth;
use s9e\AddonBuilder\MediaSites\Transpilers\PHPSource;
use s9e\AddonBuilder\MediaSites\Transpilers\XenForoTemplate;
use s9e\RegexpBuilder\Builder as RegexpBuilder;
use s9e\TextFormatter\Configurator;

class AddonBuilder
{
	protected Configurator $configurator;
	protected array $defaultValues;
	protected string $dir;
	public string $nsRoot = 's9e\\MediaSites';
	protected array $params;
	protected PHPSource $phpTranspiler;
	protected array $phpTemplates = [];
	protected RegexpBuilder $regexpBuilder;
	protected array $sites;
	protected array $tags;
	protected string $version;
	protected int $versionId;
	protected XenForoTemplate $xfTranspiler;

	/**
	* @param string       $dir          Target dir
	* @param Configurator $configurator
	*/
	public function __construct($dir = null, Configurator $configurator = null)
	{
		$this->configurator  = $configurator ?: new Configurator;
		$this->dir           = $dir ?: realpath(__DIR__ . '/../addon');

		$this->phpTranspiler = new PHPSource($this->configurator);
		$this->phpTranspiler->enableQuickRenderer = false;
		$this->phpTranspiler->serializer->convertor->features['php80'] = false;

		$this->regexpBuilder = new RegexpBuilder(['delimiter' => '()', 'output' => 'PHP']);
		$this->sites         = iterator_to_array($this->configurator->MediaEmbed->defaultSites);
		$this->xfTranspiler  = new XenForoTemplate;

		$this->configurator->templateNormalizer->add(new RemoveDefaultStyle);
		$this->configurator->templateNormalizer->add(new RemoveLazyLoading);
		$this->configurator->templateNormalizer->add(new SetGitHubIframeApiVersion);
		$this->configurator->templateNormalizer->add(new SplitConditionalAttributes);
		$this->configurator->templateNormalizer->add(new SwitchCSSWidth);

		$this->patchSites();

		$this->storeVersion();
		$this->normalizeSites();
		$this->storeDefaultValues();
		$this->storeParams();
	}

	/**
	* Build this add-on
	*
	* @return void
	*/
	public function build()
	{
		$this->patchComposer();

		$dom  = $this->createDOM('bb_code_media_sites');
		$root = $dom->documentElement;
		foreach (array_keys($this->sites) as $siteId)
		{
			$this->addSite($root, $siteId);
		}

//		$this->patchOptions();
		$this->patchParser();
		$this->patchRenderer();

		$dom->save($this->dir . '/_data/bb_code_media_sites.xml');
	}

	/**
	* Add a configured site to given element
	*
	* @param  DOMElement $root
	* @param  string     $siteId
	* @return void
	*/
	protected function addSite(DOMElement $root, $siteId)
	{
		$siteConfig          = $this->sites[$siteId];
		$this->tags[$siteId] = $this->configurator->MediaEmbed->add($siteId, $siteConfig);

		$site = $root->appendChild($root->ownerDocument->createElement('site'));
		$site->setAttribute('media_site_id',              $siteId);
		$site->setAttribute('site_title',                 $siteConfig['name']);
		$site->setAttribute('site_url',                   $siteConfig['homepage']);
		$site->setAttribute('match_is_regex',             1);
		$site->setAttribute('match_callback_class',       $this->nsRoot . '\\Parser');
		$site->setAttribute('match_callback_method',      'match');
		$site->setAttribute('embed_html_callback_class',  $this->nsRoot . '\\Renderer');
		$site->setAttribute('embed_html_callback_method', 'render');
		$site->setAttribute('cookie_third_parties',       $siteConfig['cookie_third_parties'] ?? $siteId);
		$site->setAttribute('supported',                  1);
		$site->setAttribute('active',                     1);
		$site->setAttribute('oembed_enabled',             0);
		if (isset($siteConfig['oembed']['endpoint'], $siteConfig['oembed']['scheme'])
		 && !preg_match('(\\{@(?!id\\}))', $siteConfig['oembed']['scheme']))
		{
			$site->setAttribute('oembed_api_endpoint', $siteConfig['oembed']['endpoint']);
			$site->setAttribute('oembed_url_scheme',   str_replace('{@id}', '{$id}', $siteConfig['oembed']['scheme']));
		}
		$site->setAttribute('oembed_retain_scripts',      0);

		// Create a regexp that matches all hostnames handled by this media site
		$regexp = $this->getHostRegexp((array) $siteConfig['host']);
		$site->appendChild($site->ownerDocument->createElement('match_urls'))
		     ->appendChild($site->ownerDocument->createCDATASection($regexp));

		$template   = (string) $this->tags[$siteId]->template;
		$xfTemplate = '';
		if ($siteId === 'mastodon')
		{
			$template = '<xsl:choose><xsl:when test="@invalid">@<xsl:value-of select="@name"/>@<xsl:value-of select="@invalid"/>/<xsl:value-of select="@id"/></xsl:when><xsl:otherwise>' . $template . '</xsl:otherwise></xsl:choose>';
		}
		try
		{
			$xfTemplate = $this->xfTranspiler->transpile($template);
		}
		catch (RuntimeException $e)
		{
			$phpTemplate = $this->phpTranspiler->transpile($template, $siteConfig);
			$this->phpTemplates[$siteId] = $phpTemplate;
		}

		$site->appendChild($site->ownerDocument->createElement('embed_html'))
		     ->appendChild($site->ownerDocument->createCDATASection($xfTemplate));
	}

	/**
	* Create a new DOMDocument
	*
	* @param  string      $elName
	* @return DOMDocument
	*/
	protected function createDOM($elName)
	{
		$dom  = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = true;
		$dom->appendChild($dom->createElement($elName));

		return $dom;
	}

	/**
	* Export an array to PHP
	*
	* @param  array  $array
	* @return string
	*/
	protected function exportArray(array $array)
	{
		ksort($array);

		$i = 0;
		$elements = [];
		foreach ($array as $k => $v)
		{
			$php = ($k === $i++) ? '' : var_export($k, true) . '=>';

			if (is_array($v))
			{
				$php .= self::exportArray($v);
			}
			elseif (strpos($v, "\n") !== false || (strpos($v, "'") !== false && strpos($v, '"') === false))
			{
				$php .= '"' . addcslashes($v, "\\\$\"\n") . '"';
			}
			else
			{
				$php .= var_export($v, true);
			}

			$elements[] = $php;
		}

		return '[' . implode(',', $elements) . ']';
	}

	/**
	* Export a given site's configuration to PHP
	*
	* @param  string $siteId
	* @return string
	*/
	protected function exportSite($siteId)
	{
		$required = [];
		foreach ($this->tags[$siteId]->attributes as $attrName => $attribute)
		{
			if ($attribute->required)
			{
				$required[] = $attrName;
			}
		}

		$config = $this->sites[$siteId];
		$site   = [
			(isset($config['extract'])) ? $config['extract'] : [],
			$required,
			(isset($config['scrape']))  ? $config['scrape']  : [],
			self::getFiltersConfig($config)
		];

		// Remove empty arrays at the end of the site's config
		$i = count($site);
		while (--$i >= 0)
		{
			if ($site[$i] === [])
			{
				unset($site[$i]);
			}
			else
			{
				break;
			}
		}

		return self::exportArray($site);
	}

	/**
	* Generate and return the PHP source for the parser's config
	*
	* @return string
	*/
	protected function generateParserConfig()
	{
		$php = '';
		foreach (array_keys($this->sites) as $siteId)
		{
			$php .= "\n\t\t'" . $siteId . "'=>" . $this->exportSite($siteId) . ',';
		}
		$php = substr($php, 0, -1);

		return $php;
	}

	/**
	* Generate the list of default values used in the renderer
	*
	* @return string
	*/
	protected function generateRendererDefaultValues()
	{
		$php = '';
		foreach ($this->defaultValues as $siteId => $defaultValues)
		{
			$php .= "\n\t\t'" . $siteId . "'=>" . self::exportArray($defaultValues) . ',';
		}

		return substr($php, 0, -1);
	}

	/**
	* Generate the list of filters used in the renderer
	*
	* @return string
	*/
	protected function generateRendererFilters()
	{
		$php = '';
		foreach ($this->sites as $siteId => $siteConfig)
		{
			$filters = self::getFiltersConfig($siteConfig, true);
			if (!empty($filters))
			{
				$php .= "\n\t\t'" . $siteId . "'=>" . self::exportArray($filters) . ',';
			}
		}

		return substr($php, 0, -1);
	}

	/**
	* Generate and return the PHP source for all the PHP renderers
	*
	* @return string
	*/
	protected function generateRenderers()
	{
		$php = '';
		foreach ($this->phpTemplates as $siteId => $body)
		{
			$php .= "\n\tprotected static function render" . ucfirst($siteId) . "(\$vars)\n\t{\n\t\t" . $body . "\n\n\t\treturn \$html;\n\t}\n";
		}

		return $php;
	}

	/**
	* Generate an array of filter config for given site
	*
	* @param  array $config
	* @param  bool  $helperOnly Whether to restrict filters to s9e\MediaSites\Helper callbacks
	* @return array
	*/
	protected static function getFiltersConfig(array $config, bool $helperOnly = false)
	{
		$filters = [];
		if (empty($config['attributes']))
		{
			return $filters;
		}

		foreach ($config['attributes'] as $attrName => $attribute)
		{
			if (empty($attribute['filterChain']))
			{
				continue;
			}
			foreach ($attribute['filterChain'] as $filter)
			{
				if ($filter[0] === '#')
				{
					$filter = 's9e\\MediaSites\\Helper::filter' . ucfirst(substr($filter, 1));
				}
				if ($helperOnly && !str_starts_with($filter, 's9e\\MediaSites\\Helper::'))
				{
					continue;
				}

				$filters[$attrName][] = $filter;
			}
		}

		return $filters;
	}

	/**
	* Create a regexp that matches a list of hostnames
	*
	* @param  string[]
	* @return string
	*/
	protected function getHostRegexp(array $hosts)
	{
		return '(^https?://(?:[^./]+\\.)*' . $this->regexpBuilder->build($hosts) . "/.(?'id'))i";
	}


	/**
	* Normalize a list of regexps
	*
	* Will replace named captured in the form (?'name') with (?<name>)
	*
	* @param  string[] $regexps
	* @return string[]
	*/
	protected function normalizeRegexps(array $regexps)
	{
		foreach ($regexps as &$regexp)
		{
			$regexp = preg_replace("(\\(\\?'(\\w+)')", '(?<$1>', $regexp);
		}
		unset($regexp);

		return $regexps;
	}

	/**
	* Normalize the stored sites config
	*
	* @return void
	*/
	protected function normalizeSites()
	{
		foreach ($this->sites as $siteId => &$siteConfig)
		{
			$siteConfig['extract'] = $this->normalizeRegexps($siteConfig['extract']);
			foreach ($siteConfig['scrape'] as &$scrape)
			{
				$scrape['extract'] = $this->normalizeRegexps($scrape['extract']);
			}
			unset($scrape);
		}
		unset($siteConfig);
	}

	/**
	* Patch composer.json with this add-on's current version
	*
	* @return void
	*/
	protected function patchComposer()
	{
		$filepath = __DIR__ . '/../composer.json';
		file_put_contents(
			$filepath,
			preg_replace(
				'("version":\\s*"\\K[^"]*)',
				strtolower(strtr($this->version, ' ', '-')),
				file_get_contents($filepath)
			)
		);
	}

	/**
	* Patch given file in-place
	*
	* @param  string $filename
	* @param  string $regexp
	* @param  string $methodName
	* @return void
	*/
	protected function patchFile($filename, $regexp, $methodName)
	{
		$filepath = $this->dir . '/' . $filename;
		file_put_contents(
			$filepath,
			preg_replace_callback(
				$regexp,
				function ($m) use ($methodName)
				{
					return $this->$methodName();
				},
				file_get_contents($filepath)
			)
		);
	}

	protected function patchSiteGoogledrive(array $siteConfig): array
	{
		$siteConfig['cookie_third_parties'] = 'google';

		return $siteConfig;
	}

	protected function patchSiteGoogleplus(array $siteConfig): array
	{
		$siteConfig['cookie_third_parties'] = 'google';

		return $siteConfig;
	}

	protected function patchSiteGooglesheets(array $siteConfig): array
	{
		$siteConfig['cookie_third_parties'] = 'google';

		return $siteConfig;
	}

	protected function patchSiteMastodon(array $siteConfig): array
	{
		$callback = 's9e\\MediaSites\\Helper::filterMastodonHost';
		$this->configurator->MediaEmbed->allowedFilters[] = $callback;
		$siteConfig['attributes']['host']['filterChain'] = [$callback];

		return $siteConfig;
	}

	protected function patchSites(): void
	{
		foreach ($this->sites as $siteId => $siteConfig)
		{
			$methodName = 'patchSite' . ucfirst($siteId);
			if (is_callable([$this, $methodName]))
			{
				$this->sites[$siteId] = $this->$methodName($siteConfig);
			}
		}

		$filepath = $this->dir . '/../target/src/addons/XF/_data/bb_code_media_sites.xml';
		if (file_exists($filepath))
		{
			$dom = new DOMDocument;
			$dom->load($filepath);
			foreach ($dom->getElementsByTagName('site') as $site)
			{
				$siteId = $site->getAttribute('media_site_id');
				if (!isset($this->sites[$siteId]) || !$site->hasAttribute('cookie_third_parties'))
				{
					continue;
				}

				$this->sites[$siteId]['cookie_third_parties'] = $site->getAttribute('cookie_third_parties');
			}
		}
	}

	/**
	* Patch the Parser file with current config
	*
	* @return void
	*/
	protected function patchParser()
	{
		$this->patchFile(
			'Parser.php',
			'(\\n\\tprotected static \\$sites = \\[\\K.*?(?=\\n\\t\\]))s',
			'generateParserConfig'
		);
	}

	/**
	* Patch the Parser file with current renderers
	*
	* @return void
	*/
	protected function patchRenderer()
	{
		$this->patchFile(
			'Renderer.php',
			'(\\n\\tprotected static \\$filters = \\[\\K.*?(?=\\n\\t\\]))s',
			'generateRendererFilters'
		);
		$this->patchFile(
			'Renderer.php',
			'(\\n\\tprotected static \\$defaultValues = \\[\\K.*?(?=\\n\\t\\]))s',
			'generateRendererDefaultValues'
		);
		$this->patchFile(
			'Renderer.php',
			'(\\n\\tprotected static function render.*?\\n(?=\\}))s',
			'generateRenderers'
		);
	}

	/**
	* Store the default values of attributes defined in sites config
	*
	* @return void
	*/
	protected function storeDefaultValues()
	{
		$this->defaultValues = [];
		foreach ($this->sites as $siteId => $siteConfig)
		{
			if (empty($siteConfig['attributes']))
			{
				continue;
			}
			foreach ($siteConfig['attributes'] as $attrName => $attrConfig)
			{
				if (isset($attrConfig['defaultValue']))
				{
					$this->defaultValues[$siteId][$attrName] = $attrConfig['defaultValue'];
				}
			}
		}
	}

	/**
	* Store parameters defined in sites config
	*
	* @return void
	*/
	protected function storeParams()
	{
		$this->params = [];
		foreach ($this->sites as $siteConfig)
		{
			if (empty($siteConfig['parameters']))
			{
				continue;
			}
			foreach ($siteConfig['parameters'] as $paramName => $paramConfig)
			{
				$paramConfig += [
					'title' => ucfirst(strtolower(strtr($paramName, '_', ' '))),
					'value' => ''
				];

				$this->params[$paramName] = $paramConfig;
			}
		}
		ksort($this->params);
	}

	/**
	* Store this add-on's version info
	*
	* @return void
	*/
	protected function storeVersion()
	{
		$config = json_decode(file_get_contents($this->dir . '/addon.json'));
		$this->versionId = $config->version_id;
		$this->version   = $config->version_string;
	}
}