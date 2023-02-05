<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\BbCode\Helper\Flickr;
use XF\Db\Schema\Alter;
use XF\Entity\Option;

class Setup extends AbstractSetup
{
	use StepRunnerUpgradeTrait;

	public function install(array $stepParams = [])
	{
		$this->upgrade2050056Step1();
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

//	public function upgrade2031170Step1(array $stepParams = [])
//	{
//		if (!$this->isActive('flickr'))
//		{
//			return;
//		}
//
//		$stepParams = $this->upgradePosts(
//			$stepParams,
//			'%[MEDIA=flickr]%',
//			function ($message)
//			{
//				return preg_replace_callback(
//					'(\\[MEDIA=flickr\\]\\K\\d++(?=\\[/MEDIA\\]))',
//					function ($m)
//					{
//						return Flickr::base58_encode($m[0]);
//					},
//					$message
//				);
//			}
//		);
//		if (!empty($stepParams))
//		{
//			return $stepParams;
//		}
//
//		$this->restoreXenForoAddOnData();
//	}

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
			$site->match_urls = '(^https?://(?:[^./]+\.)*(?:' . implode('|', array_map('preg_quote', $hosts)) . ")/.(?'id'))i";
			$site->saveIfChanged();
		}

		return true;
	}

	public static function validateNativePlayer($newValue, Option $option)
	{
		$siteIds = ['gfycat', 'gifs', 'giphy'];
		foreach ($siteIds as $siteId)
		{
			$key = 's9e_MediaSites_' . ucfirst($siteId) . '_Native';

			self::setTemplateModification($option, $key, (bool) $newValue);
		}

		return true;
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

	protected function upgradePosts(array $stepParams, $like, $callback)
	{
		if (!isset($stepParams['post_id']))
		{
			$stepParams['post_id'] = 0;
			$stepParams['limit']   = 50;
		}

		$start = microtime(true);
		$posts = $this->app->finder('XF:Post')
			->where('post_id', '>',    $stepParams['post_id'])
			->where('message', 'LIKE', $like)
			->order('post_id')
			->limit($stepParams['limit'])
			->fetch();
		foreach ($posts as $post)
		{
			$old = $post->message;
			$new = $callback($old);
			if ($new !== $old)
			{
				$editor = $this->app->service('XF:Post\\Editor', $post);
				$editor->setIsAutomated();
				$editor->logEdit(false);
				$editor->logHistory(false);
				$editor->setMessage($new, false);
				$editor->save();
			}
			$stepParams['post_id'] = $post->post_id;
		}
		$end = microtime(true);

		if (count($posts) < $stepParams['limit'])
		{
			return;
		}

		if ($end - $start > 1)
		{
			$stepParams['limit'] = max(20, floor($stepParams['limit'] / 2));
		}
		elseif ($stepParams['limit'] < 500)
		{
			$stepParams['limit'] += 10;
		}

		return $stepParams;
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