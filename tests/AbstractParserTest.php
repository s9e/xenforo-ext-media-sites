<?php declare(strict_types=1);

namespace s9e\MediaSites\Tests;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use XF;
use XF\Entity\BbCodeMediaSite;
use s9e\MediaSites\Parser;

abstract class AbstractParserTest extends TestCase
{
	protected static $sites = [];
	public static function setUpBeforeClass(): void
	{
		CachingParser::$cacheDir = __DIR__ . '/.cache';

		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
		foreach ($dom->getElementsByTagName('site') as $site)
		{
			$siteId = $site->getAttribute('media_site_id');
			$regexp = $site->getElementsByTagName('match_urls')->item(0)->textContent;

			self::$sites[$siteId] = $regexp;
		}
	}

	/**
	* @dataProvider getMatchTests
	*/
	public function testMatch($url, $expected, array $config = [])
	{
		XF::$config = $config;

		$mediaKey = false;
		foreach (self::$sites as $siteId => $regexp)
		{
			if (!preg_match($regexp, $url, $m))
			{
				continue;
			}
			$mediaKey = CachingParser::match($url, $m['id'], new BbCodeMediaSite, $siteId);
			if ($mediaKey !== false)
			{
				break;
			}
		}

		$this->assertSame($expected, $mediaKey);
	}

	abstract public function getMatchTests(): array;
}

class CachingParser extends Parser
{
	public static $cacheDir;

	protected static function wget($url, $headers = []): string
	{
		// Return the content from the cache if applicable
		if (isset(self::$cacheDir) && file_exists(self::$cacheDir))
		{
			$cacheFile = self::$cacheDir . '/http.' . crc32(serialize([$url, $headers])) . '.html';
			if (file_exists($cacheFile))
			{
				return file_get_contents($cacheFile);
			}
		}

		$response = parent::wget($url, $headers);
		if ($response && isset($cacheFile))
		{
			file_put_contents($cacheFile, $response);
		}

		return $response;
	}
}