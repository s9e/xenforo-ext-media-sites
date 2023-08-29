<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites\XF\BbCode\Renderer;

class EditorHtml extends XFCP_EditorHtml
{
	public function renderTagUrl(array $children, $option, array $tag, array $options)
	{
		if (is_array($option) && !empty($option['media']))
		{
			$options['plain']                 = true;
			$options['stopSmilies']           = true;
			$options['treatAsStructuredText'] = false;

			return $this->renderUnparsedTag($tag, $options);
		}

		return parent::renderTagUrl($children, $option, $tag, $options);
	}
}