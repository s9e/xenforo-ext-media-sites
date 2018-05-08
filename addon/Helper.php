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

		$output .= '<script>(function(d){function a(){clearTimeout(f);f=setTimeout(g,32)}function g(){h=innerHeight+600;var c=[];b.forEach(function(a){var b=a.getBoundingClientRect();-200<b.bottom&&b.top<h&&b.width?(a.contentWindow.location.replace(a.getAttribute(d)),a.removeAttribute(d)):c.push(a)});b=c;b.length||(removeEventListener("scroll",a),removeEventListener("resize",a),removeEventListener("click",a))}for(var c=document.getElementsByTagName("iframe"),e=c.length,b=[],h=0,f=0;0<=--e;)c[e].hasAttribute(d)&&b.push(c[e]);addEventListener("scroll",a);addEventListener("resize",a);addEventListener("click",a);g()})("data-s9e-lazyload-src")</script>';
	}
}