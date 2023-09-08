<?php

namespace s9e\MediaSites\Tests;

use ArrayObject;
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
		XF::$options         = (object) new ArrayObject($options, ArrayObject::ARRAY_AS_PROPS);
		XF::$styleProperties = $styleProperties;
		$this->assertEquals($expected, static::getRendererClass()::render($mediaKey, [], $siteId));
	}
}