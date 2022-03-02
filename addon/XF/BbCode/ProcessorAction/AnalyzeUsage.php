<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\ProcessorAction;

use XF;
use XF\BbCode\Processor;
use XF\BbCode\ProcessorAction\AnalyzerHooks;

class AnalyzeUsage extends XFCP_AnalyzeUsage
{
    public function addAnalysisHooks(AnalyzerHooks $hooks)
    {
        if ((\XF::options()->s9e_MediaSites_Markup ?? '') === 'url')
        {
            $hooks->addTagHook('url', 'analyzeS9eUrlMediaTag');
        }
    }

    public function analyzeS9eUrlMediaTag(array $tag, array $options, $finalOutput)
    {
        if ((($tag['option']['media'] ?? '') !== '') && (\strtolower($tag['option']['unfurl'] ?? '') === 'true'))
        {
            $this->tagCount['media'] = ($this->tagCount['media'] ?? 0) + 1;
        }
    }

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