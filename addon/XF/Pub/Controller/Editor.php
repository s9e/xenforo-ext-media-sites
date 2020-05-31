<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\Pub\Controller;

use s9e\MediaSites\Parser;

class Editor extends XFCP_Editor
{
	public function actionMedia()
	{
		Parser::$inEditor = true;

		return parent::actionMedia();
	}
}