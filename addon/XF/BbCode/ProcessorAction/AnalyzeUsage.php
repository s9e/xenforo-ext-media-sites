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
		// Remove the media attribute from [URL unfurl="true" tags to make them look like regular
		// links. It doesn't need to be exact as the modified string is not used anywhere else
		$regexp = '(\\[URL\\s+unfurl="true"\\K\\s+media="[^"]*+")i';
		$string = preg_replace($regexp, '', $string);

		parent::analyzeUnfurlUsage($string, $processor);
	}
}