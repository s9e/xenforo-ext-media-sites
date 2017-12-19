<?php

namespace s9e\MediaSites\Tests;

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
			'_data/options.xml'
		];
		foreach ($files as $filename)
		{
			copy($srcDir . '/' . $filename, $trgDir . '/' . $filename);
		}

		$builder = new AddonBuilder($trgDir);
		$builder->build();

		$this->assertTrue(true);
	}
}