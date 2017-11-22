#!/usr/bin/php
<?php

/**
* @copyright Copyright (c) 2013-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

include_once __DIR__ . '/../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->rendering->engine = 'PHP';
$configurator->rendering->engine->forceEmptyElements = false;
$configurator->rendering->engine->useEmptyElements   = false;

class Transpiler extends \s9e\TextFormatter\Configurator\RendererGenerators\PHP
{
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
$transpiler = new Transpiler;

// Create the XML document
$dom  = new DOMDocument('1.0', 'utf-8');
$root = $dom->appendChild($dom->createElement('bb_code_media_sites'));

foreach ($configurator->MediaEmbed->defaultSites as $siteId => $siteConfig)
{
	$site = $root->appendChild($dom->createElement('site'));
	$site->setAttribute('active',         1);
	$site->setAttribute('supported',      1);
	$site->setAttribute('match_is_regex', 1);
	$site->setAttribute('media_site_id',  $siteId);
	$site->setAttribute('site_title',     $siteConfig['name']);

	$tag = $configurator->MediaEmbed->add($siteId);

$php = $transpiler->transpile($tag->template);
echo "$tag->template\n\n$php\n\n\n";

//print_r($siteConfig);exit;

//	$site->appendChild($dom->createTextNode($compiler->compile($tag->template)));

	$root->appendChild($site);
}


$dom->formatOutput = true;
die($dom->saveXML());