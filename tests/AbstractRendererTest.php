<?php

namespace s9e\MediaSites\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use XF;

abstract class AbstractRendererTest extends TestCase
{
	abstract public function getRendererClass(): string;
	abstract public static function getRenderTests(): array;

	#[DataProvider('getRenderTests')]
	public function testRender($siteId, $mediaKey, $expected, $options = [], $styleProperties = [])
	{
		XF::$options         = (object) $options;
		XF::$styleProperties = $styleProperties;
		$this->assertEquals($expected, static::getRendererClass()::render($mediaKey, [], $siteId));
	}
}