<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\ProcessorAction;

use XF;
use XF\BbCode\Processor;

class AnalyzeUsage extends XFCP_AnalyzeUsage
{
	public function analyzeUnfurlUsage($string, Processor $processor)
	{
		parent::analyzeUnfurlUsage($string, $processor);

		$regexp = '(^\\[URL\\s+unfurl="true"(?:\\s+\\w++="[^"]*+")*+\\](.*?)\\[/URL\\])i';
		if (!preg_match_all($regexp, $string, $m))
		{
			return;
		}

		$unfurlRepo = XF::repository('XF:Unfurl');
		foreach ($m[1] as $url)
		{
			$unfurl = $unfurlRepo->getUnfurlResultByUrl($url);
			if ($unfurl)
			{
				$this->unfurls[$unfurl->result_id] = $unfurl->result_id;
			}
		}
	}
}