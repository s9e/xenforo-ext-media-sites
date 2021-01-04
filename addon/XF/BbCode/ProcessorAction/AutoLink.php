<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\ProcessorAction;

use XF\App;

class AutoLink extends XFCP_AutoLink
{
	public function __construct(App $app, array $config = [])
	{
		foreach ($config['embedSites'] as $siteId => $site)
		{
			if (!empty($site->s9e_disable_auto_embed))
			{
				unset($config['embedSites'][$siteId]);
			}
		}
		parent::__construct($app, $config);
	}
}