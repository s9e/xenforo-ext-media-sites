#!/usr/bin/php
<?php declare(strict_types=1);

use s9e\AddonBuilder\MediaSites\AddonBuilder;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\XmlFileDefinitionCollection;

$rootDir = realpath(__DIR__);
while (!file_exists($rootDir . '/vendor'))
{
	$rootDir = dirname($rootDir);
}

$addonDir = $rootDir . '/addon';
$addonId  = json_decode(file_get_contents($addonDir . '/addon.json'))->title;

require_once $rootDir . '/vendor/autoload.php';

$configurator = new Configurator;
if (file_exists($rootDir . '/sites'))
{
	$configurator->MediaEmbed->defaultSites = new XmlFileDefinitionCollection($rootDir . '/sites');
}

$builder = new AddonBuilder($addonDir, $configurator);
$builder->nsRoot = strtr($addonId, '/', '\\');
$builder->build();