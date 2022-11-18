#!/usr/bin/php
<?php declare(strict_types=1);

$filepath = realpath(__DIR__ . '/../addon/Helper.php');

$old = file_get_contents($filepath);
$new = preg_replace_callback(
	'(<script>.*?</script>)',
	function ($m)
	{
		$js = file_get_contents(__DIR__ . '/../src/LazyLoad.min.js');
		$js = str_replace("\n", '', $js);
		$js = trim($js, ';');

		return '<script>' . $js . '</script>';
	},
	$old
);
if ($new !== $old)
{
	file_put_contents($filepath, $new);
	echo "Patched $filepath\n";
}