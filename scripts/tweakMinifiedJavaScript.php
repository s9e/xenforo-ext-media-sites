#!/usr/bin/php
<?php declare(strict_types=1);

$filepath = realpath($_SERVER['argv'][1]);
if (!$filepath)
{
	var_dump($_SERVER['argv'][1]);
	die("No input file.\n");
}

$old = file_get_contents($filepath);

// Replace "var a=1;let b=2;" with "let a=1,b=2";
$new = preg_replace_callback(
	'(var (\w++=[^;]++);let )',
	function ($m)
	{
		return 'let ' . $m[1] . ',';
	},
	$old
);
if ($new !== $old)
{
	file_put_contents($filepath, $new);
	echo "Patched $filepath\n";
}