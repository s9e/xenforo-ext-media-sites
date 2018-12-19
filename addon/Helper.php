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

		$output .= '<script>(function(d,g,k){function l(){f||(p(d.addEventListener),q())}function p(a){a("click",m);a("resize",m);a("scroll",m)}function m(){clearTimeout(r);r=setTimeout(q,32)}function y(a){var c=a.contentWindow,e=a.getAttribute(k+"src");2==a.getAttribute(k+"api")&&(a.onload=function(){var b=new MessageChannel;c.postMessage("s9e:init",e.substr(0,e.indexOf("/",8)),[b.port2]);b.port1.onmessage=function(b){b=(""+b.data).split(" ");z(a,b[0],b[1]||0)}});if(a.contentDocument)c.location.replace(e);else if(a.onload)a.onload()}function A(a){a=a.getBoundingClientRect();if(a.bottom>d.innerHeight)return 2;var c=g.querySelector(".p-navSticky");c=c?c.getBoundingClientRect().height:0;return a.top<c?0:1}function z(a,c,e){var b=A(a),t=0===b?g.documentElement.getBoundingClientRect().height-d.scrollY:0,f=a.style;1!==b&&(f.transition="none",setTimeout(function(){f.transition=""},0));f.height=c+"px";e&&(f.width=e+"px");t&&(a=g.documentElement.getBoundingClientRect().height-d.scrollY-t)&&d.scrollBy(0,a)}function q(){n!==d.scrollY&&(u=n>(n=d.scrollY)?1:0);f=2*d.innerHeight;v=-f/(0===u?4:2);var a=[];h.forEach(function(c){var e=c.getBoundingClientRect(),b;if(!(b=e.bottom<v||e.top>f||!e.width)&&(b=270===e.width)){for(var d=b=c.parentNode;"BODY"!==b.tagName;)0<=b.className.indexOf("bbCodeBlock-expandContent")&&(d=b),b=b.parentNode;b=e.top>d.getBoundingClientRect().bottom}b?a.push(c):y(c)});h=a;h.length||p(d.removeEventListener)}for(var w=g.querySelectorAll("iframe["+k+"src]"),x=0,h=[],v=0,f=0,n=0,u=0,r=0;x<w.length;)h.push(w[x++]);"complete"===g.readyState?l():(d.addEventListener("load",l),setTimeout(l,3E3))})(window,document,"data-s9e-mediaembed-")</script>';
	}
}