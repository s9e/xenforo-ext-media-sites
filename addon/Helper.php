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

		$output .= '<script>(function(d){function h(b){b("click",e);b("resize",e);b("scroll",e)}function e(){clearTimeout(k);k=setTimeout(l,32)}function l(){m=innerHeight+600;var b=[];a.forEach(function(c){var a=c.getBoundingClientRect();-200<a.bottom&&a.top<m&&a.width?(c.contentWindow.location.replace(c.getAttribute(d)),c.removeAttribute(d)):b.push(c)});a=b;a.length||h(removeEventListener)}for(var f=document.getElementsByTagName("iframe"),g=f.length,a=[],m=0,k=0;0<=--g;)f[g].hasAttribute(d)&&a.push(f[g]);h(addEventListener);l()})("data-s9e-lazyload-src")</script>';
	}
}