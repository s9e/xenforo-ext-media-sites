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

		$output .= '<script>(function(b,k,l){function m(){f||(g=b.scrollY,r(b.addEventListener),t())}function r(a){a("click",n);a("resize",n);a("scroll",n)}function n(){clearTimeout(u);u=setTimeout(t,32)}function A(a){var d=a.contentWindow,b=a.getAttribute(l+"src");2==a.getAttribute(l+"api")&&(a.onload=function(){var c=new MessageChannel;d.postMessage("s9e:init",b.substr(0,b.indexOf("/",8)),[c.port2]);c.port1.onmessage=function(c){c=(""+c.data).split(" ");B(a,c[0],c[1]||0)}});if(a.contentDocument)d.location.replace(b);else if(a.onload)a.onload()}function C(a){a=a.getBoundingClientRect();if(a.bottom>b.innerHeight)return 2;var d=-1;!w&&location.hash&&(d=h(location.hash,"top"));0>d&&(d=h(".p-navSticky","bottom"));return a.top<d?0:1}function h(a,b){return(a=k.querySelector(a))?a.getBoundingClientRect()[b]:-1}function B(a,d,v){var c=C(a),p=0===c||1===c&&1===q,f=p?h("html","height")-b.scrollY:0,e=a.style;if(1!==c||p)e.transition="none",setTimeout(function(){e.transition=""},0);e.height=d+"px";v&&(e.width=v+"px");p&&((a=h("html","height")-b.scrollY-f)&&b.scrollBy(0,a),g=b.scrollY)}function t(){g!==b.scrollY&&(w=!0,q=g>(g=b.scrollY)?1:0);f=2*b.innerHeight;x=-f/(0===q?4:2);var a=[];e.forEach(function(b){var d=b.getBoundingClientRect(),c;if(!(c=d.bottom<x||d.top>f||!d.width)&&(c=270===d.width)){for(var e=c=b.parentNode;"BODY"!==c.tagName;)0<=c.className.indexOf("bbCodeBlock-expandContent")&&(e=c),c=c.parentNode;c=d.top>e.getBoundingClientRect().bottom}c?a.push(b):A(b)});e=a;e.length||r(b.removeEventListener)}for(var y=k.querySelectorAll("iframe["+l+"src]"),z=0,e=[],x=0,f=0,u=0,w=!1,g=0,q=0;z<y.length;)e.push(y[z++]);"complete"===k.readyState?m():(b.addEventListener("load",m),setTimeout(m,3E3))})(window,document,"data-s9e-mediaembed-")</script>';
	}
}