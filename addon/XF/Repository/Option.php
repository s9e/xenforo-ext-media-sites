<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\Repository;

use XF;

class Option extends XFCP_Option
{
	public function finder($identifier)
	{
		$finder = parent::finder($identifier);
		if ($identifier === 'XF:Option' && XF::$versionId < 2010000)
		{
			$finder->where('option_id', '!=', 's9e_MediaSites_Markup');
		}

		return $finder;
	}
}