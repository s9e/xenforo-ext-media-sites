#!/usr/bin/php
<?php

$dom = new DOMDocument;
$dom->load(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
$sites = $dom->getElementsByTagName('site');

$text = 'This add-on contains the definitions for [b]' . $sites->length . ' media sites[/b]: ';
foreach ($sites as $site)
{
	$text .= $site->getAttribute('site_title') . ', ';
}
$text = str_replace('BitChute', 'Bit Chute', $text);
$text = substr($text, 0, -2) . '.';

echo "$text\n";