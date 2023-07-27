<?php

namespace s9e\MediaSites\Tests;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use XF\Template\Templater;
use s9e\AddonBuilder\MediaSites\AddonBuilder;

/**
* @covers s9e\AddonBuilder\MediaSites\AddonBuilder
*/
class AddonBuilderTest extends TestCase
{
	public function test()
	{
		$srcDir = realpath(__DIR__ . '/../addon');
		$trgDir = uniqid(sys_get_temp_dir() . '/');
		mkdir($trgDir);
		mkdir($trgDir . '/_data');

		$files = [
			'addon.json',
			'Parser.php',
			'Renderer.php',
			'_data/bb_code_media_sites.xml',
			'_data/code_event_listeners.xml',
			'_data/option_groups.xml',
			'_data/options.xml',
			'_data/phrases.xml',
			'_data/template_modifications.xml'
		];
		foreach ($files as $filename)
		{
			copy($srcDir . '/' . $filename, $trgDir . '/' . $filename);
		}

		$builder = new AddonBuilder($trgDir);
		$builder->build();

		$this->assertTrue(true);

		array_map('unlink', glob($trgDir . '/*/*.*'));
		array_map('unlink', glob($trgDir . '/*.*'));
		array_map('rmdir',  glob($trgDir . '/*'));
		rmdir($trgDir);
	}

	#[DataProvider('getModificationTests')]
	public function testModifications($regexp, $template)
	{
		$this->assertMatchesRegularExpression($regexp, $template);
	}

	public static function getModificationTests(): array
	{
		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
		$templates = [];
		foreach ($dom->getElementsByTagName('site') as $site)
		{
			$siteId   = $site->getAttribute('media_site_id');
			$template = $site->getElementsByTagName('embed_html')->item(0)->textContent;

			$templates[$siteId] = $template;
		}

		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../addon/_data/template_modifications.xml');

		$xpath = new DOMXPath($dom);
		$query = '//modification[@action="preg_replace"][starts-with(@template, "_media_site_embed_")]/find';

		$modifications = [];
		foreach ($xpath->query($query) as $find)
		{
			$regexp = $find->textContent;
			$name   = $find->parentNode->getAttribute('template');
			$siteId = substr($name, 18);

			$modifications[] = [$regexp, $templates[$siteId]];
		}

		return $modifications;
	}

	public function testMaxWidthCSS()
	{
		$filepath = realpath(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
		$file     = file_get_contents($filepath);

		$this->assertStringNotContainsString('max-width', $file);
	}

	public function testCookieConsent()
	{
		$filepath = realpath(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
		$file     = file_get_contents($filepath);

		$this->assertMatchesRegularExpression(
			'(<site media_site_id="youtube"[^>]*? cookie_third_parties="youtube")',
			$file
		);
	}
}