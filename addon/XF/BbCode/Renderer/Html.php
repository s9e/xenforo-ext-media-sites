<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\Renderer;

use XF;

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
				$html .= $this->renderMediaSuffix($children, $options);

				return $html;
			}
		}

		return parent::renderTagUrl($children, $option, $tag, $options);
	}

	protected function removeMarkupFromSubTree(array $tree): array
	{
		$cleanTree = [];
		foreach ($tree as $element)
		{
			if (is_array($element))
			{
				// Replace the original markup with empty strings
				$element['original'] = ['', ''];
				$element['children'] = $this->removeMarkupFromSubTree($element['children']);
			}

			$cleanTree[] = $element;
		}

		return $cleanTree;
	}

	protected function renderMediaSuffix(array $children, array $options): string
	{
		$suffixOptions  = XF::options()->s9e_MediaSites_Url_Suffix ?? [];
		$suffixOptions += ['bbcode' => '[i][size=2][url={$url}]View: {$url}[/url][/size][/i]', 'enabled' => true];
		if (!$suffixOptions['enabled'])
		{
			return '';
		}

		// NOTE: $children will usually contain 1 string but may contain any number of array|string
		//       if the [URL] tag contains markup, e.g. [URL][B]...[/B][URL]
		$url = $this->renderSubTreePlain($this->removeMarkupFromSubTree($children), $options);

		// Escape all characters that could interfere with BBCodes as a precaution
		$url = preg_replace('(\\[(?=/?\\w+[ =\\]]))', '%5B', $url);
		$url = str_replace('"', '%22', $url);

		$bbcodeSuffix = "\n" . str_replace('{$url}', $url, $suffixOptions['bbcode']);

		/**
		* @see XF\BbCode\Traverser::renderSubTree()
		*/
		return $this->renderSubTree(
			XF::app()->bbCode()->parser()->parse($bbcodeSuffix, $this->rules),
			$options
		);
	}
}