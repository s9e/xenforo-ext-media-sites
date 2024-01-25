<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\BbCode\Helper\Flickr;
use XF\Db\Schema\Alter;
use XF\Entity\Option;
use const SORT_STRING;
use function array_map, array_unique, count, function_exists, implode, preg_match_all, sort, stripos, strtolower, ucfirst;

class Setup extends AbstractSetup
{
	use StepRunnerUpgradeTrait;

	public function install(array $stepParams = [])
	{
		$this->upgrade2050056Step1();
	}

	public function postInstall(array &$stateChanges)
	{
		$this->setDefaultFindInPage();
		$this->updateDefaultEmbedSuffix();
	}

	public function postUpgrade($previousVersion, array &$stateChanges)
	{
		if ($previousVersion < 2100000)
		{
			$this->setDefaultFindInPage();
		}
		if ($previousVersion < 2110070)
		{
			$this->updateDefaultEmbedSuffix();
		}
		if ($previousVersion < 2120000)
		{
			$this->updateScrapingClient();
		}
	}

	public function uninstall(array $stepParams = [])
	{
		$this->schemaManager()->alterTable(
			'xf_bb_code_media_site',
			function (Alter $table)
			{
				$table->dropColumns('s9e_disable_auto_embed');
			}
		);
		$this->restoreXenForoAddOnData();
	}

	public function upgrade2050056Step1(array $stepParams = [])
	{
		$this->schemaManager()->alterTable(
			'xf_bb_code_media_site',
			function (Alter $table)
			{
				if ($table->getColumnDefinition('s9e_disable_auto_embed'))
				{
					return;
				}

				$table->addColumn('s9e_disable_auto_embed', 'tinyint')->setDefault(0);
			}
		);
	}

	public static function validateClickToLoad($newValue, Option $option)
	{
		$modifications = [
			's9e_MediaSites_ClickToLoad_CSS',
			's9e_MediaSites_YouTube_ClickToLoad'
		];
		self::setTemplateModifications($option, $modifications, (bool) $newValue);

		return true;
	}

	public static function validateBBCodeSuffix(array &$values, Option $option): bool
	{
		if (empty($values['bbcode']))
		{
			$values['bbcode'] = '[i][size=2][url={$url}]View: {$url}[/url][/size][/i]';
		}
		elseif (stripos($values['bbcode'], 'media=') !== false)
		{
			$option->error(XF::phrase('link_bbcode_must_not_include_media'), $option->option_id);

			return false;
		}

		return true;
	}

	public static function validateClickToLoadOembed($newValue, Option $option)
	{
		$modifications = [
			's9e_MediaSites_ClickToLoad_Oembed_CSS',
			's9e_MediaSites_YouTube_ClickToLoad_Oembed'
		];
		self::setTemplateModifications($option, $modifications, (bool) $newValue);

		return true;
	}

	public static function validateFooter($newValue, Option $option)
	{
		self::setTemplateModification($option, 's9e_MediaSites_Footer', ($newValue === 'show'));

		return true;
	}

	public static function validateMarkup($newValue, Option $option)
	{
		return ($newValue === 'media' || XF::$versionId >= 2010000);
	}

	public static function validateMastodonHosts(&$newValue, Option $option)
	{
		preg_match_all('(\\S++)', strtolower($newValue) . "\nmastodon.social", $m);
		$hosts = array_unique($m[0]);
		sort($hosts, SORT_STRING);

		$newValue = implode("\n", $hosts);

		$site = XF::finder('XF:BbCodeMediaSite')
			->where('media_site_id', 'mastodon')
			->where('addon_id',      $option->addon_id)
			->fetchOne();
		if ($site)
		{
			$expr = implode('|', array_map('preg_quote', $hosts));
			if (count($hosts) > 1)
			{
				$expr = '(?:' . $expr . ')';
			}
			$site->match_urls = '(^https?://(?:[^./]+\\.)*' . $expr . "/.(?'id'))i";
			$site->saveIfChanged();
		}

		return true;
	}

	public static function validateNativePlayer($newValue, Option $option)
	{
		$siteIds = ['gifs', 'giphy'];
		foreach ($siteIds as $siteId)
		{
			$key = 's9e_MediaSites_' . ucfirst($siteId) . '_Native';

			self::setTemplateModification($option, $key, (bool) $newValue);
		}

		return true;
	}

	public static function validateScrapingClient($newValue, Option $option): bool
	{
		// Return true unless this is set to cURL and curl_exec() is not available
		return ($newValue !== 'curl' || function_exists('curl_exec'));
	}

	public static function validateTemplateModification($newValue, Option $option)
	{
		self::setTemplateModification($option, $option->option_id, (bool) $newValue);

		return true;
	}

	protected function isActive($siteId)
	{
		return (bool) $this->app->finder('XF:BbCodeMediaSite')
			->where('media_site_id', $siteId)
			->where('addon_id',      's9e/MediaSites')
			->where('active',        1)
			->fetchOne();
	}

	protected function setDefaultFindInPage()
	{
		// Enable FindInPage only if unfurling is enabled
		if (empty($this->app->options()->urlToRichPreview))
		{
			return;
		}

		$option = $this->app->find('XF:Option', 's9e_MediaSites_FindInPage');
		if (!$option)
		{
			// This shouldn't be possible
			return;
		}

		$option->option_value = 1;
		$option->save();
		self::setTemplateModification($option, 's9e_MediaSites_FindInPage', 1);
	}

	protected function updateDefaultEmbedSuffix()
	{
		$autoEmbedMediaOptions = $this->app->options()->autoEmbedMedia;
		if (empty($autoEmbedMediaOptions['linkBbCode']))
		{
			return;
		}

		$option = $this->app->find('XF:Option', 's9e_MediaSites_Url_Suffix');
		if (!$option)
		{
			return;
		}

		$suffix = $option->getOptionValue();
		$suffix['bbcode'] = $autoEmbedMediaOptions['linkBbCode'];

		$option->option_value = $suffix;
		$option->save();
	}

	protected function updateScrapingClient(): void
	{
		// We only need to correct the option if the client is set to Guzzle
		$httpConfig = $this->app->config('http');
		if (!isset($httpConfig['s9e.client']) || $httpConfig['s9e.client'] !== 'guzzle')
		{
			return;
		}

		$option = $this->app->find('XF:Option', 's9e_MediaSites_Scraping_Client');
		if (!$option)
		{
			return;
		}

		$option->option_value = 'xenforo';
		$option->save();
	}

	protected function restoreXenForoAddOnData()
	{
		$this->app->jobManager()->enqueueUnique(
			'XF_BbCodeMediaSite_AddOnData',
			'XF:AddOnData',
			[
				'addon_id'   => 'XF',
				'data_types' => ['XF:BbCodeMediaSite']
			],
			false
		);
	}

	protected static function setTemplateModification(Option $option, $key, $enabled)
	{
		$entity = $option->em()
			->getFinder('XF:TemplateModification')
			->where('modification_key', $key)
			->fetchOne();
		if ($entity)
		{
			$entity->set('enabled', $enabled);
			$entity->save();
		}
	}

	protected static function setTemplateModifications(Option $option, array $keys, $enabled)
	{
		foreach ($keys as $key)
		{
			self::setTemplateModification($option, $key, $enabled);
		}
	}
}