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

		$output .= '<script>(function(c,h,l){function m(){g||(r(c.addEventListener),t())}function r(a){a("click",n);a("resize",n);a("scroll",n)}function n(){clearTimeout(u);u=setTimeout(t,32)}function y(a){var d=a.contentWindow,e=a.getAttribute(l+"src");2==a.getAttribute(l+"api")&&(a.onload=function(){var b=new MessageChannel;d.postMessage("s9e:init",e.substr(0,e.indexOf("/",8)),[b.port2]);b.port1.onmessage=function(b){b=(""+b.data).split(" ");z(a,b[0],b[1]||0)}});if(a.contentDocument)d.location.replace(e);else if(a.onload)a.onload()}function A(a){a=a.getBoundingClientRect();if(a.bottom>c.innerHeight)return 2;var d=h.querySelector(".p-navSticky");d=d?d.getBoundingClientRect().height:0;return a.top<d?0:1}function z(a,d,e){var b=A(a),p=0===b||1===b&&1===q,g=p?h.documentElement.getBoundingClientRect().height-c.scrollY:0,f=a.style;if(1!==b||p)f.transition="none",setTimeout(function(){f.transition=""},0);f.height=d+"px";e&&(f.width=e+"px");p&&((a=h.documentElement.getBoundingClientRect().height-c.scrollY-g)&&c.scrollBy(0,a),k=c.scrollY)}function t(){k!==c.scrollY&&(q=k>(k=c.scrollY)?1:0);g=2*c.innerHeight;v=-g/(0===q?4:2);var a=[];f.forEach(function(d){var e=d.getBoundingClientRect(),b;if(!(b=e.bottom<v||e.top>g||!e.width)&&(b=270===e.width)){for(var c=b=d.parentNode;"BODY"!==b.tagName;)0<=b.className.indexOf("bbCodeBlock-expandContent")&&(c=b),b=b.parentNode;b=e.top>c.getBoundingClientRect().bottom}b?a.push(d):y(d)});f=a;f.length||r(c.removeEventListener)}for(var w=h.querySelectorAll("iframe["+l+"src]"),x=0,f=[],v=0,g=0,k=0,q=0,u=0;x<w.length;)f.push(w[x++]);"complete"===h.readyState?m():(c.addEventListener("load",m),setTimeout(m,3E3))})(window,document,"data-s9e-mediaembed-")</script>';
	}
}