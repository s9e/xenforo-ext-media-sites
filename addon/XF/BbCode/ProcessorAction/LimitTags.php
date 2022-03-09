<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\ProcessorAction;

use XF\BbCode\ProcessorAction\FiltererHooks;

class LimitTags extends XFCP_LimitTags
{
	public function addFiltererHooks(FiltererHooks $hooks)
	{
		parent::addFiltererHooks($hooks);

		$hooks->addTagHook('url', 'filterUrlMediaTag');
	}

	public function filterUrlMediaTag(array $tag, array $options)
	{
		if ($tag['tag'] === 'url' && isset($tag['option']['media']))
		{
			// Emulate a [media] tag and reprocess this tag
			$tag['tag']    = 'media';
			$tag['option'] = preg_replace('(:.*)', '', $tag['option']['media']);

			return $this->filterTag($tag);
		}

		return null;
	}
}