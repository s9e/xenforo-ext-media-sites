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

require_once $rootDir . '/vendor/autoload.php';

$configurator = new Configurator;

$addonId  = $_SERVER['argv'][1] ?? 's9e/MediaSites';
$addonDir = $rootDir . '/addon';
if (file_exists($rootDir . '/sites'))
{
	$configurator->MediaEmbed->defaultSites = new XmlFileDefinitionCollection($rootDir . '/sites');
}

$builder = new AddonBuilder($addonDir, $configurator);
$builder->nsRoot = strtr($addonId, '/', '\\');
$builder->build();