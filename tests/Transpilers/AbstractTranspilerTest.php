<?php

namespace s9e\AddonBuilder\MediaSites\Tests\Transpilers;

use Exception;
use PHPUnit\Framework\TestCase;

abstract class AbstractTranspilerTest extends TestCase
{
	abstract protected function getTranspiler();
	abstract public function getTranspilerTests();

	/**
	* @testdox Transpiler tests
	* @dataProvider getTranspilerTests
	*/
	public function test($original, $expected, $siteConfig = [])
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());
		}

		$this->assertSame($expected, $this->getTranspiler()->transpile($original, $siteConfig));
	}
}