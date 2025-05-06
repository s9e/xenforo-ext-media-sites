<?php declare(strict_types=1);

use Rector\Config\RectorConfig;

const MHASH_XXH128 = 'xxh128';
const MHASH_XXH3   = 'xxh3';
const MHASH_XXH32  = 'xxh32';
const MHASH_XXH64  = 'xxh64';

return RectorConfig::configure()
	->withPaths([__DIR__ . '/addon'])
	->withDowngradeSets(php74: true)
	->withTypeCoverageLevel(0)
	->withDeadCodeLevel(0)
	->withCodeQualityLevel(0);