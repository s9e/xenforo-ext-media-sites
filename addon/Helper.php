<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF\Template\Templater;

class Helper
{
	/**
	* Replace iframe src attributes in given HTML
	*
	* @param  Templater  $templater
	* @param  string     $type
	* @param  string     $template
	* @param  string    &$output
	* @return void
	*/
	public static function replaceIframeSrc(Templater $templater, $type, $template, &$output)
	{
		if (strpos($output, 'data-s9e-mediaembed="') === false)
		{
			return;
		}

		$output = preg_replace_callback(
			'((<(?:span data-s9e-mediaembed="[^>]++><span[^>]++><iframe|iframe data-s9e-mediaembed=")[^>]+? src=")([^>]++))S',
			function ($m)
			{
				$html = $m[1] . 'data:text/html," data-s9e-lazyload-src="' . $m[2];
				if (strpos($html, ' onload="') !== false)
				{
					$html = preg_replace(
						'( onload="([^"]++)")',
						' onload="if(!hasAttribute(\'data-s9e-lazyload-src\')){$1}"',
						$html
					);
				}

				return $html;
			},
			$output
		);

		$output .= '<script>(function(e){function a(){clearTimeout(g);g=setTimeout(h,30)}function h(){k=innerHeight+600;var b=[];d.forEach(function(c){var a=c.getBoundingClientRect();-200<a.bottom&&a.top<k?(c.contentWindow.location.replace(c.getAttribute(e)),c.removeAttribute(e)):b.push(c)});d=b;d.length||(removeEventListener("scroll",a),removeEventListener("resize",a))}for(var b=document.getElementsByTagName("iframe"),f=b.length,d=[],k=0,g=0;0<=--f;)b[f].hasAttribute(e)&&d.push(b[f]);addEventListener("scroll",a,{passive:!0});addEventListener("resize",a);h()})("data-s9e-lazyload-src")</script>';
	}
}