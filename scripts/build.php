#!/usr/bin/php
<?php declare(strict_types=1);

use s9e\AddonBuilder\MediaSites\AddonBuilder;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\XmlFileDefinitionCollection;

include_once __DIR__ . '/../vendor/autoload.php';

$addonId  = $_SERVER['ADDON_ID']  ?? 's9e/MediaSites';
$addonDir = $_SERVER['ADDON_DIR'] ?? __DIR__ . '/../addon';
$sitesDir = $_SERVER['SITES_DIR'] ?? null;

$configurator = new Configurator;
if (isset($sitesDir))
{
	$configurator->MediaEmbed->defaultSites = new XmlFileDefinitionCollection($sitesDir);
}

$builder = new AddonBuilder($addonDir, $configurator);
$builder->nsRoot = strtr($addonId, '/', '\\');
$builder->build();