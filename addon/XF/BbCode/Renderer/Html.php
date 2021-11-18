<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\Renderer;

class Html extends XFCP_Html
{
	public function renderTag(array $tag, array $options)
	{
		$html = parent::renderTag($tag, $options);
		if ($this->trimAfter === 1 && strpos($html, 'data-s9e-mediaembed') !== false)
		{
			$this->trimAfter = 0;
		}

		return $html;
	}

	public function renderTagUrl(array $children, $option, array $tag, array $options)
	{
		if (is_array($option) && isset($option['media']) && preg_match('(^(\\w+):(.+))', $option['media'], $m))
		{
			$html = $this->renderTagMedia([$m[2]], $m[1], $tag, []);
			if ($html !== '')
			{
				return $html;
			}
		}

		return parent::renderTagUrl($children, $option, $tag, $options);
	}
}