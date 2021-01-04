<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\Admin\Controller;

use XF\Entity\BbCodeMediaSite as BbCodeMediaSiteEntity;

class BbCodeMediaSite extends XFCP_BbCodeMediaSite
{
	protected function bbCodeMediaSiteSaveProcess(BbCodeMediaSiteEntity $site)
	{
		$entityInput = $this->filter(['s9e_disable_auto_embed' => 'bool']);

		$form = parent::bbCodeMediaSiteSaveProcess($site);
		$form->basicEntitySave($site, $entityInput);

		return $form;
	}
}