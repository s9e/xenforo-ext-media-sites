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

		$output .= '<script>(function(f,g,h){function k(){l||(n(f.addEventListener),p())}function n(a){a("click",m);a("resize",m);a("scroll",m)}function m(){clearTimeout(q);q=setTimeout(p,32)}function v(a){var c=a.contentWindow,d=a.getAttribute(h+"src");2==a.getAttribute(h+"api")&&(a.onload=function(){var b=new MessageChannel;c.postMessage("s9e:init",d.substr(0,d.indexOf("/",8)),[b.port2]);b.port1.onmessage=function(b){b=(""+b.data).split(" ");w(a,b[0],b[1]||0)}});if(a.contentDocument)c.location.replace(d);else if(a.onload)a.onload()}function x(a){a=a.getBoundingClientRect();if(a.bottom>f.innerHeight)return 2;var c=g.querySelector(".p-navSticky");c=c?c.getBoundingClientRect().height:0;return a.top<c?0:1}function w(a,c,d){var b=x(a),r=0===b?g.documentElement.getBoundingClientRect().height-f.scrollY:0,e=a.style;1!==b&&(e.transition="none",setTimeout(function(){e.transition=""},0));e.height=c+"px";d&&(e.width=d+"px");r&&(a=g.documentElement.getBoundingClientRect().height-f.scrollY-r)&&f.scrollBy(0,a)}function p(){l=f.innerHeight+600;var a=[];e.forEach(function(c){var d=c.getBoundingClientRect(),b;if(!(b=-400>d.bottom||d.top>l||!d.width)&&(b=270===d.width)){for(var e=b=c.parentNode;"BODY"!==b.tagName;)0<=b.className.indexOf("bbCodeBlock-expandContent")&&(e=b),b=b.parentNode;b=d.top>e.getBoundingClientRect().bottom}b?a.push(c):v(c)});e=a;e.length||n(f.removeEventListener)}for(var t=g.querySelectorAll("iframe["+h+"src]"),u=0,e=[],l=0,q=0;u<t.length;)e.push(t[u++]);"complete"===g.readyState?k():(f.addEventListener("load",k),setTimeout(k,3E3))})(window,document,"data-s9e-mediaembed-")</script>';
	}
}