#!/usr/bin/php
<?php declare(strict_types=1);

function isMediaSiteFile($hash, $path)
{
	return str_contains($path, 'src/XF/BbCode/Helper/')
		|| str_contains($path, 'src/addons/XF/_data/bb_code_media_sites.xml');
}

$localPath  = __DIR__ . '/hashes.json';
$targetPath = __DIR__ . '/../target/src/addons/XF/hashes.json';

$localHashes  = json_decode(file_get_contents($localPath), true);
$targetHashes = array_filter(
	json_decode(file_get_contents($targetPath), true),
	'isMediaSiteFile',
	ARRAY_FILTER_USE_BOTH
);

ksort($localHashes);
ksort($targetHashes);

if ($localHashes === $targetHashes)
{
	die("No changes detected.\n");
}

$file = '{';
foreach ($targetHashes as $path => $hash)
{
	$file .= "\n\t\"$path\": \"$hash\",";
}
$file[-1] = "\n";
$file .= '}';

file_put_contents($localPath, $file);

die("Updated $localPath\n");