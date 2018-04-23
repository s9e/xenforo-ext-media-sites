<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\AddOn\AbstractSetup;

class Setup extends AbstractSetup
{
	public function install(array $stepParams = [])
	{
	}

	public function upgrade(array $stepParams = [])
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
}