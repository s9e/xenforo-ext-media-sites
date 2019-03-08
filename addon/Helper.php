<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2019 The s9e Authors
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
			'((<(?:span data-s9e-mediaembed="[^>]++><span[^>]*+><iframe|iframe data-s9e-mediaembed=")[^>]+? )(src="[^>]++))S',
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

		$output .= '<script>(function(c,g,n){function p(){f||(h=c.scrollY,u(c.addEventListener),v())}function u(a){a("click",q);a("resize",q);a("scroll",q)}function q(){clearTimeout(w);w=setTimeout(v,32)}function B(a){var b=a.contentWindow,d=a.getAttribute(n+"src");2==a.getAttribute(n+"api")&&(a.onload=function(){var e=new MessageChannel;b.postMessage("s9e:init",d.substr(0,d.indexOf("/",8)),[e.port2]);e.port1.onmessage=function(b){b=(""+b.data).split(" ");C(a,b[0],b[1]||0)}});if(a.contentDocument)b.location.replace(d);else if(a.onload)a.onload();D(a)}function E(a){a=a.getBoundingClientRect();if(a.bottom>c.innerHeight)return 2;var b=-1;!x&&location.hash&&(b=k(location.hash,"top"));0>b&&(b=k(".p-navSticky","bottom"));return a.top<b?0:1}function k(a,b){var d=g.querySelector(a);return d?d.getBoundingClientRect()[b]:-1}function C(a,b,d){var e=E(a),r=0===e||1===e&&1===t,g=r?k("html","height")-c.scrollY:0,f=a.style;if(1!==e||r)f.transition="none",setTimeout(function(){f.transition=""},0);f.height=b+"px";d&&(f.width=d+"px");r&&((a=k("html","height")-c.scrollY-g)&&c.scrollBy(0,a),h=c.scrollY)}function v(){h!==c.scrollY&&(x=!0,t=h>(h=c.scrollY)?1:0);f=2*c.innerHeight;y=-f/(0===t?4:2);var a=[];l.forEach(function(b){var d=b.getBoundingClientRect(),e;if(!(e=d.bottom<y||d.top>f||!d.width)&&(e=270===d.width)){for(var c=e=b.parentNode;"BODY"!==e.tagName;)0<=e.className.indexOf("bbCodeBlock-expandContent")&&(c=e),e=e.parentNode;e=d.top>c.getBoundingClientRect().bottom}e?a.push(b):B(b)});l=a;l.length||u(c.removeEventListener)}function F(a){a=a.target;var b=a.firstChild,d=a.getBoundingClientRect(),e=g.documentElement,c=b.style;c.bottom=e.clientHeight-d.bottom+"px";c.height=d.height+"px";c.right=e.clientWidth-d.right+"px";c.width=d.width+"px";b.offsetHeight;/inactive/.test(a.className)?(a.className="s9e-miniplayer-active-tn",b.removeAttribute("style"),m&&m.click(),m=a):(a.className="s9e-miniplayer-inactive-tn",m=null)}function G(a){a=a.target;var b=a.parentNode;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function D(a){var b=a.parentNode;a.hasAttribute("data-s9e-mediaembed")||b.hasAttribute("style")||(b.className="s9e-miniplayer-inactive",b.onclick=F,a.addEventListener("transitionend",G))}for(var z=g.querySelectorAll("iframe["+n+"src]"),A=0,l=[],y=0,f=0,w=0,x=!1,h=0,t=0;A<z.length;)l.push(z[A++]);"complete"===g.readyState?p():(c.addEventListener("load",p),setTimeout(p,3E3));var m=null})(window,document,"data-s9e-mediaembed-");</script>';
	}
}