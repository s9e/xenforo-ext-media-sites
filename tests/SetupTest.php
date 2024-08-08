<?php

namespace s9e\MediaSites\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use XF;
use s9e\MediaSites\Setup;

/**
* @covers s9e\MediaSites\Setup
*/
class SetupTest extends TestCase
{
	#[DataProvider('getNormalizeHostInputTests')]
	public function testNormalizeHostInput(string $expected, string $original): void
	{
		$this->assertEquals($expected, Setup::normalizeHostInput($original));
	}

	public static function getNormalizeHostInputTests(): array
	{
		return [
			[
				"example.com\nexample.org",
				"example.org\nexample.com"
			],
			[
				"example.com",
				"example.com\nexample.com"
			],
		];
	}

	#[DataProvider('getNormalizeMastodonHostsTests')]
	public function testNormalizeMastodonHosts(string $expected, string $original): void
	{
		$this->assertEquals($expected, Setup::normalizeMastodonHosts($original));
	}

	public static function getNormalizeMastodonHostsTests(): array
	{
		return [
			[
				'mastodon.social',
				''
			],
			[
				'mastodon.social',
				'mastodon.social'
			],
			[
				"example.com\nmastodon.social",
				"example.com"
			],
		];
	}

	#[DataProvider('getGetHostRegexpTests')]
	public function testGetHostRegexp(string $expected, array $hosts): void
	{
		$this->assertEquals($expected, Setup::getHostRegexp($hosts));
	}

	public static function getGetHostRegexpTests(): array
	{
		return [
			[
				"(^https?://(?:[^./]++\\.)*?mastodon\\.social/.(?'id'))i",
				['mastodon.social']
			],
			[
				"(^https?://(?:[^./]++\\.)*?(?:example\\.com|mastodon\\.social)/.(?'id'))i",
				['example.com', 'mastodon.social']
			],
			[
				'((?!))',
				[]
			],
		];
	}
}