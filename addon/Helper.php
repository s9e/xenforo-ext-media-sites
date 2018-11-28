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

		$output .= '<script>(function(d){function f(b){b("click",e);b("resize",e);b("scroll",e)}function e(){clearTimeout(g);g=setTimeout(h,32)}function h(){k=innerHeight+600;var b=[];a.forEach(function(c){var a=c.getBoundingClientRect();a.bottom>(c.hasAttribute("onload")?0:-200)&&a.top<k&&a.width?(c.contentWindow.location.replace(c.getAttribute(d)),c.removeAttribute(d)):b.push(c)});a=b;a.length||f(removeEventListener)}for(var l=document.querySelectorAll("iframe["+d+"]"),m=0,a=[],k=0,g=0;m<l.length;)a.push(l[m++]);f(addEventListener);h()})("data-s9e-lazyload-src")</script>';
	}
}