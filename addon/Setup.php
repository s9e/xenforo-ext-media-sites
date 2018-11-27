<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\AddOn\AbstractSetup;
use XF\Entity\Option;

class Setup extends AbstractSetup
{
	public function install(array $stepParams = [])
	{
	}

	public function uninstall(array $stepParams = [])
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

	public function upgrade(array $stepParams = [])
	{
	}

	public static function validateExperimentalInstagramIframe($newValue, Option $option)
	{
		self::setTemplateModification($option, 'Instagram_v2', (bool) $newValue);

		return true;
	}

	public static function validateExperimentalTwitterIframe($newValue, Option $option)
	{
		self::setTemplateModification($option, 'Twitter_v2', (bool) $newValue);

		return true;
	}

	public static function validateFooter($newValue, Option $option)
	{
		self::setTemplateModification($option, 'Footer', ($newValue === 'show'));

		return true;
	}

	public static function validateYouTubePrivacy($newValue, Option $option)
	{
		self::setTemplateModification($option, 'YouTube_Privacy', (bool) $newValue);

		return true;
	}

	protected static function setTemplateModification(Option $option, $key, $enabled)
	{
		$entity = $option->em()
			->getFinder('XF:TemplateModification')
			->where('modification_key', 's9e_MediaSites_' . $key)
			->fetchOne();
		if ($entity)
		{
			$entity->set('enabled', $enabled);
			$entity->save();
		}
	}
}