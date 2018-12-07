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
			'((<(?:span data-s9e-mediaembed="[^>]++><span[^>]++><iframe|iframe data-s9e-mediaembed=")[^>]+? )(src="[^>]++))S',
			function ($m)
			{
				$html = $m[1] . 'data-s9e-mediaembed-' . $m[2];
				if (strpos($html, ' onload="') !== false)
				{
					if (strpos($html, 'data-s9e-mediaembed-api') === false)
					{
						$replace = ' onload="if(!contentDocument){$1}"';
					}
					else
					{
						$replace = '';
					}
					$html = preg_replace('( onload="([^"]++)")', $replace, $html);
				}

				return $html;
			},
			$output
		);

		$output .= '<script>(function(f,d){function k(){l(addEventListener);m()}function l(a){a("click",h);a("resize",h);a("scroll",h)}function h(){clearTimeout(n);n=setTimeout(m,32)}function t(a){var b=a.contentWindow,c=a.getAttribute(f+"src");2==a.getAttribute(f+"api")&&(a.onload=function(){var d=new MessageChannel;b.postMessage("s9e:init",c.substr(0,c.indexOf("/",8)),[d.port2]);d.port1.onmessage=function(b){b=(""+b.data).split(" ");u(a,b[0],b[1]||0)}});if(a.contentDocument)b.location.replace(c);else if(a.onload)a.onload()}function v(a){a=a.getBoundingClientRect();if(a.bottom>innerHeight)return 2;var b=d.querySelector(".p-navSticky");b=b?b.getBoundingClientRect().height:0;return a.top<b?0:1}function u(a,b,c){var e=v(a),f=0===e?d.documentElement.getBoundingClientRect().height-pageYOffset:0,g=a.style;1!==e&&(g.transition="none",setTimeout(function(){g.transition=""},0));g.height=b+"px";c&&(g.width=c+"px");f&&(a=d.documentElement.getBoundingClientRect().height-pageYOffset-f)&&scrollBy(0,a)}function m(){p=innerHeight+600;var a=[];e.forEach(function(b){var c=b.getBoundingClientRect();-400<c.bottom&&c.top<p&&c.width?t(b):a.push(b)});e=a;e.length||l(removeEventListener)}for(var q=d.querySelectorAll("iframe["+f+"src]"),r=0,e=[],p=0,n=0;r<q.length;)e.push(q[r++]);"complete"===d.readyState?k():addEventListener("load",k)})("data-s9e-mediaembed-",document)</script>';
	}
}