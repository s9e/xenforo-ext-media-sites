<?php

namespace s9e\MediaSites\Tests\Transpilers;

use Exception;
use PHPUnit_Framework_TestCase;

abstract class AbstractTranspilerTest extends PHPUnit_Framework_TestCase
{
	abstract public function getTranspilerTests();

	/**
	* @testdox Transpiler tests
	* @dataProvider getTranspilerTests
	*/
	public function test($original, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected));
			$this->setExpectedExceptionMessage($expected->getMessage());
		}

		$className  = str_replace('\\Tests\\', '\\', substr(get_class($this), 0, -4));
		$transpiler = new $className;

		$this->assertSame($expected, $transpiler->transpile($original));
	}
}