<?php declare(strict_types=1);

namespace XF\Util;

use Composer\InstalledVersions;

class File
{
	public static function getTempDir(): string
	{
		return realpath(InstalledVersions::getRootPackage()['install_path'] . '/target/internal_data/temp');
	}
}