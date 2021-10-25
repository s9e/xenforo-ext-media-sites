<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\Pub\Controller;

use XF;
use s9e\MediaSites\Parser;

class Editor extends XFCP_Editor
{
	public function actionMedia()
	{
		$view    = parent::actionMedia();
		$params  = $view->getJsonParams();
		$options = XF::options();
		if (isset($params['matchBbCode'], $options->s9e_MediaSites_Markup)
		 && $options->s9e_MediaSites_Markup === 'url')
		{
			$url    = $this->filter('url', 'str');
			$markup = $params['matchBbCode'];
			$unfurl = $options->urlToRichPreview;

			$params['matchBbCode'] = Parser::convertMediaTag($url, $markup, $unfurl);
			$view->setJsonParams($params);
		}

		return $view;
	}
}