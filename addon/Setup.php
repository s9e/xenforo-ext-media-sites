<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\BbCode\Helper\Flickr;
use XF\Entity\Option;

class Setup extends AbstractSetup
{
	use StepRunnerUpgradeTrait;

	public function install(array $stepParams = [])
	{
	}

	public function uninstall(array $stepParams = [])
	{
		$this->restoreXenForoAddOnData();
	}

	public static function validateTemplateModification($newValue, Option $option)
	{
		self::setTemplateModification($option, $option->option_id, (bool) $newValue);

		return true;
	}

	public static function validateFooter($newValue, Option $option)
	{
		self::setTemplateModification($option, 's9e_MediaSites_Footer', ($newValue === 'show'));

		return true;
	}

	protected function upgrade2031170Step1(array $stepParams = [])
	{
		$stepParams = $this->upgradePosts(
			$stepParams,
			'%[MEDIA=flickr]%',
			'(\\[MEDIA=flickr\\]\\K\\d++(?=\\[/MEDIA\\]))',
			function ($m)
			{
				return Flickr::base58_encode($m[0]);
			}
		);
		if (!empty($stepParams))
		{
			return $stepParams;
		}

		$this->restoreXenForoAddOnData();
	}

	protected function upgradePosts(array $stepParams, $like, $regexp, $callback)
	{
		if (!isset($stepParams['post_id']))
		{
			$stepParams['post_id'] = XF::app()->db()->fetchOne('SELECT MAX(post_id) FROM xf_post');
			$stepParams['range']   = 500;
		}

		$maxId = $stepParams['post_id'];
		$minId = $maxId + 1 - $stepParams['range'];

		$start = microtime(true);
		$posts = XF::app()->finder('XF:Post')
			->where('message', 'LIKE', $like)
			->where('post_id', 'BETWEEN', [$minId, $maxId])
			->fetch();
		foreach ($posts as $post)
		{
			$old = $post->message;
			$new = preg_replace_callback($regexp, $callback, $old);
			if ($new !== $old)
			{
				$editor = XF::app()->service('XF:Post\\Editor', $post);
				$editor->setIsAutomated();
				$editor->logEdit(false);
				$editor->logHistory(false);
				$editor->setMessage($new, false);
				$editor->save();
			}
		}
		$end = microtime(true);

		if ($minId > 0)
		{
			$stepParams['post_id'] = $minId - 1;
			if ($end - $start > 1)
			{
				$stepParams['range'] = floor($stepParams['range'] / 2);
			}
			elseif ($stepParams['range'] < 10000)
			{
				$stepParams['range'] += 500;
			}

			return $stepParams;
		}
	}

	protected function restoreXenForoAddOnData()
	{
		XF::app()->jobManager()->enqueueUnique(
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
}