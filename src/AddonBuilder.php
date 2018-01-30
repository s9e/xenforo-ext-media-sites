<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites;

use DOMDocument;
use DOMElement;
use RuntimeException;
use s9e\AddonBuilder\MediaSites\Transpilers\PHPSource;
use s9e\AddonBuilder\MediaSites\Transpilers\XenForoTemplate;
use s9e\RegexpBuilder\Builder as RegexpBuilder;
use s9e\TextFormatter\Configurator;

class AddonBuilder
{
	/**
	* @var Configurator
	*/
	protected $configurator;

	/**
	* @var string
	*/
	protected $dir;

	/**
	* @var array
	*/
	protected $params;

	/**
	* @var PHPSource
	*/
	protected $phpTranspiler;

	/**
	* @var array
	*/
	protected $phpTemplates = [];

	/**
	* @var RegexpBuilder
	*/
	protected $regexpBuilder;

	/**
	* @var array
	*/
	protected $sites;

	/**
	* @var string
	*/
	protected $version;

	/**
	* @var integer
	*/
	protected $versionId;

	/**
	* @var XenForoTemplate
	*/
	protected $xfTemplate;

	/**
	* @param string       $dir          Target dir
	* @param Configurator $configurator
	*/
	public function __construct($dir = null, Configurator $configurator = null)
	{
		$this->configurator  = $configurator ?: $this->getConfigurator();
		$this->dir           = $dir ?: realpath(__DIR__ . '/../addon');

		$this->phpTranspiler = new PHPSource($this->configurator);
		$this->regexpBuilder = new RegexpBuilder(['delimiter' => '()', 'output' => 'PHP']);
		$this->sites         = iterator_to_array($this->configurator->MediaEmbed->defaultSites);
		$this->xfTranspiler  = new XenForoTemplate;

		$this->storeVersion();
		$this->normalizeSites();
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

		$this->patchOptions();
		$this->patchPhrases();
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
		$site->setAttribute('match_callback_class',       's9e\\MediaSites\\Parser');
		$site->setAttribute('match_callback_method',      'match');
		$site->setAttribute('embed_html_callback_class',  's9e\\MediaSites\\Renderer');
		$site->setAttribute('embed_html_callback_method', 'render');
		$site->setAttribute('supported',                  1);
		$site->setAttribute('active',                     1);
		$site->setAttribute('oembed_enabled',             0);
		$site->setAttribute('oembed_retain_scripts',      0);

		// Create a regexp that matches all hostnames handled by this media site
		$regexp = $this->getHostRegexp((array) $siteConfig['host']);
		$site->appendChild($site->ownerDocument->createElement('match_urls'))
		     ->appendChild($site->ownerDocument->createCDATASection($regexp));

		$template   = (string) $this->tags[$siteId]->template;
		$xfTemplate = '';
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
	* Create and return an instance of Configurator
	*
	* @return Configurator
	*/
	protected function getConfigurator()
	{
		$configurator = new Configurator;
		$configurator->rendering->engine = 'PHP';
		$configurator->rendering->engine->enableQuickRenderer = false;

		return $configurator;
	}

	/**
	* Generate an array of filter config for given site
	*
	* @param  array $config
	* @return array
	*/
	protected static function getFiltersConfig(array $config)
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
					$filter = 's9e\\MediaSites\\Parser::filter' . ucfirst(substr($filter, 1));
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
	* Collect and return existing phrases
	*
	* @return array
	*/
	protected function getOldPhrases()
	{
		$phrases = [];

		$dom = new DOMDocument;
		$dom->load($this->dir . '/_data/phrases.xml');
		foreach ($dom->getElementsByTagName('phrase') as $phrase)
		{
			$phrases[$phrase->getAttribute('title')] = [
				'value'          => $phrase->textContent,
				'version_id'     => $phrase->getAttribute('version_id'),
				'version_string' => $phrase->getAttribute('version_string')
			];
		}

		return $phrases;
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

	/**
	* Patch the options file with options created from parameters
	*
	* @return void
	*/
	protected function patchOptions()
	{
		$dom  = $this->createDOM('options');
		$root = $dom->documentElement;

		$order = 0;
		foreach ($this->params as $paramName => $paramConfig)
		{
			$option = $root->appendChild($dom->createElement('option'));
			$option->setAttribute('option_id',   's9e_MediaSites_' . $paramName);
			$option->setAttribute('edit_format', 'textbox');
			$option->setAttribute('data_type',   'string');
			$option->appendChild($dom->createElement('default_value', $paramConfig['value']));

			$relation = $option->appendChild($dom->createElement('relation'));
			$relation->setAttribute('group_id',      's9e_MediaSites');
			$relation->setAttribute('display_order', $order++);
		}

		$dom->save($this->dir . '/_data/options.xml');
	}

	/**
	* Patch the phrases file
	*
	* @return void
	*/
	protected function patchPhrases()
	{
		$phrases = [
			'option_group.s9e_MediaSites'             => 's9e Media Sites',
			'option_group_description.s9e_MediaSites' => 'Options related to the media sites'
		];
		foreach ($this->params as $paramName => $paramConfig)
		{
			$optionId = 's9e_MediaSites_' . $paramName;
			$phrases['option.'         . $optionId] = $paramConfig['title'];
			$phrases['option_explain.' . $optionId] = '';
		}

		ksort($phrases);
		$oldPhrases = $this->getOldPhrases();

		$dom  = $this->createDOM('phrases');
		$root = $dom->documentElement;
		foreach ($phrases as $title => $value)
		{
			if (isset($oldPhrases[$title]) && $oldPhrases[$title]['value'] === $value)
			{
				$versionId = $oldPhrases[$title]['version_id'];
				$version   = $oldPhrases[$title]['version_string'];
			}
			else
			{
				$versionId = $this->versionId;
				$version   = $this->version;
			}

			$phrase = $root->appendChild($dom->createElement('phrase'));
			$phrase->setAttribute('title',          $title);
			$phrase->setAttribute('version_id',     $versionId);
			$phrase->setAttribute('version_string', $version);
			$phrase->appendChild($dom->createCDATASection($value));
		}
		$dom->save($this->dir . '/_data/phrases.xml');
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
			'(\\n\\tprotected static function render.*?\\n(?=\\}))s',
			'generateRenderers'
		);
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