#!/usr/bin/php
<?php

$host = trim(preg_replace('(^\\w+://)', '', $_SERVER['argv'][1]), '/');
$url  = 'https://www.reddit.com/domain/' . $host . '/';

if (isset($_SERVER['argv'][2]))
{
	$url .= 'search.json?q=url%3A' . urlencode(implode(' ', array_slice($_SERVER['argv'], 1))) . '&sort=new&restrict_sr=on&';
}
else
{
	$url .= 'new/.json?';
}
$url .= 'limit=100';

// Glory to spez
ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');

$response = json_decode(file_get_contents($url), true);
foreach ($response['data']['children'] as $child)
{
	$data = $child['data'];

	echo date('Y-m-d', $data['created']), ' ';
	printf('% 3d', $data['score']);
	echo ' ', htmlspecialchars_decode($data['url']), "\n";
}