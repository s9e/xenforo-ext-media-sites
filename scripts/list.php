#!/usr/bin/php
<?php

$dom = new DOMDocument;
$dom->load(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
$sites = $dom->getElementsByTagName('site');

$names = [];
foreach ($sites as $site)
{
	$names[] = $site->getAttribute('site_title');
}

Collator::create('en_US')->sort($names);

$text = 'This add-on contains the definitions for [b]' . $sites->length . ' media sites[/b]: ' . implode(', ', $names) . '.';
$text = str_replace('BitChute', 'Bit Chute', $text);

echo "$text\n";