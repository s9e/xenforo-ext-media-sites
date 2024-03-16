<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\Admin\Controller;

use XF\Entity\BbCodeMediaSite as BbCodeMediaSiteEntity;
use s9e\MediaSites\Setup;

class BbCodeMediaSite extends XFCP_BbCodeMediaSite
{
	protected function bbCodeMediaSiteSaveProcess(BbCodeMediaSiteEntity $site)
	{
		$entityInput = $this->filter(['s9e_disable_auto_embed' => 'bool']);
		if ($site->media_site_id === 'mastodon')
		{
			$entityInput += $this->updateMastodonHosts();
		}
		elseif ($site->media_site_id === 'xenforo')
		{
			$entityInput += $this->updateXenForoHosts();
		}

		$form = parent::bbCodeMediaSiteSaveProcess($site);
		$form->basicEntitySave($site, $entityInput);

		return $form;
	}

	/**
	* @return array Fields to update in the BbCodeMediaSiteEntity instance
	*/
	protected function updateMastodonHosts(): array
	{
		$hosts = $this->filter('s9e_mastodon_hosts', 'string', '');
		$hosts = Setup::normalizeMastodonHosts($hosts);

		$option = $this->app->find('XF:Option', 's9e_MediaSites_MastodonHosts');
		if ($option)
		{
			$option->option_value = $hosts;
			$option->saveIfChanged();
		}

		$regexp = Setup::getHostRegexp(explode("\n", $hosts));

		return ['match_urls' => $regexp];
	}

	/**
	* @return array Fields to update in the BbCodeMediaSiteEntity instance
	*/
	protected function updateXenForoHosts(): array
	{
		$hosts = $this->filter('s9e_xenforo_hosts', 'string', '');
		$hosts = Setup::normalizeHostInput($hosts);

		$option = $this->app->find('XF:Option', 's9e_MediaSites_XenForoHosts');
		if ($option)
		{
			$option->option_value = $hosts;
			$option->saveIfChanged();
		}

		$regexp = Setup::getHostRegexp(explode("\n", $hosts));

		return ['match_urls' => $regexp];
	}
}