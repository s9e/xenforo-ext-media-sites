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

		$output .= '<script>(function(g,f){function k(){l(addEventListener);m()}function l(a){a("click",h);a("resize",h);a("scroll",h)}function h(){clearTimeout(n);n=setTimeout(m,32)}function u(a){var c=a.contentWindow,d=a.getAttribute(g+"src");2==a.getAttribute(g+"api")&&(a.onload=function(){var b=new MessageChannel;c.postMessage("s9e:init",d.substr(0,d.indexOf("/",8)),[b.port2]);b.port1.onmessage=function(b){b=(""+b.data).split(" ");v(a,b[0],b[1]||0)}});if(a.contentDocument)c.location.replace(d);else if(a.onload)a.onload()}function w(a){a=a.getBoundingClientRect();if(a.bottom>innerHeight)return 2;var c=f.querySelector(".p-navSticky");c=c?c.getBoundingClientRect().height:0;return a.top<c?0:1}function v(a,c,d){var b=w(a),p=0===b?f.documentElement.getBoundingClientRect().height-pageYOffset:0,e=a.style;1!==b&&(e.transition="none",setTimeout(function(){e.transition=""},0));e.height=c+"px";d&&(e.width=d+"px");p&&(a=f.documentElement.getBoundingClientRect().height-pageYOffset-p)&&scrollBy(0,a)}function m(){q=innerHeight+600;var a=[];e.forEach(function(c){var d=c.getBoundingClientRect(),b;if(!(b=-400>d.bottom||d.top>q||!d.width)&&(b=270===d.width)){for(var e=b=c.parentNode;"BODY"!==b.tagName;)0<=b.className.indexOf("bbCodeBlock-expandContent")&&(e=b),b=b.parentNode;b=d.top>e.getBoundingClientRect().bottom}b?a.push(c):u(c)});e=a;e.length||l(removeEventListener)}for(var r=f.querySelectorAll("iframe["+g+"src]"),t=0,e=[],q=0,n=0;t<r.length;)e.push(r[t++]);"complete"===f.readyState?k():addEventListener("load",k)})("data-s9e-mediaembed-",document)</script>';
	}
}