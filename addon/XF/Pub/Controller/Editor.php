<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
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
		$url     = $this->filter('url', 'str');

		if (!isset($params['matchBbCode']) && isset($params['noMatch']) && !empty($options->s9e_MediaSites_FindInPage) && preg_match('(^https?://)i', $url))
		{
			$match = $this->findMatchInPage($url);
			if ($match)
			{
				$params['matchBbCode'] = '[MEDIA=' . $match['media_site_id'] . ']' . $match['media_id'] . '[/MEDIA]';
				unset($params['noMatch']);

				$view->setJsonParams($params);
			}
		}

		if (isset($params['matchBbCode'], $options->s9e_MediaSites_Markup)
		 && $options->s9e_MediaSites_Markup === 'url')
		{
			$markup = $params['matchBbCode'];
			$unfurl = ($options->urlToRichPreview) ? $this->app->repository('XF:Unfurl') : null;

			$params['matchBbCode'] = Parser::convertMediaTag($url, $markup, $unfurl);
			$view->setJsonParams($params);
		}

		return $view;
	}

	protected function findMatchInPage(string $url): ?array
	{
		$match = null;
		$where = array_filter(
			['canonical', 'embedded'],
			fn ($option) => $this->filter('s9e_find_in_page_' . $option, 'bool')
		);
		if (!empty($where))
		{
			$match = Parser::findMatchInPage($url, $where, $this->repository('XF:BbCodeMediaSite'));
		}

		return $match;
	}
}