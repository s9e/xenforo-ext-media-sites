<?php

namespace s9e\AddonBuilder\MediaSites\Tests\Transpilers;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class AbstractTranspilerTest extends TestCase
{
	abstract protected function getTranspiler();
	abstract public static function getTranspilerTests(): array;

	/**
	* @testdox Transpiler tests
	*/
	#[DataProvider('getTranspilerTests')]
	public function test($original, $expected, $siteConfig = [])
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());
		}

		// Remove inter-element whitespace for convenience
		$original = preg_replace('(>\\n\\s*<)', '><', $original);

		$this->assertSame($expected, $this->getTranspiler()->transpile($original, $siteConfig));
	}
}