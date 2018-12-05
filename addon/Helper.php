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

		$output .= '<script>(function(h,e,f){function k(){l(h);m()}function l(a){a("click",g);a("resize",g);a("scroll",g)}function g(){clearTimeout(n);n=setTimeout(m,32)}function t(a){var d=a.contentWindow,c=a.getAttribute(e+"src");2==a.getAttribute(e+"api")&&(a.onload=function(){var b=new MessageChannel,e=c.substr(0,c.indexOf("/",8));d.postMessage("s9e:init",e,[b.port2]);b.port1.onmessage=function(c){c=(""+c.data).split(" ");var b=a.style,d=30>a.getBoundingClientRect().top?f.body.getBoundingClientRect().height:0;d&&(b.transition="none");b.height=c[0]+"px";c[1]&&(b.width=c[1]+"px");d&&(scrollBy(0,f.body.getBoundingClientRect().height-d),b.transition="")}});if(a.contentDocument)d.location.replace(c);else if(a.onload)a.onload()}function m(){p=innerHeight+600;var a=[];b.forEach(function(b){var c=b.getBoundingClientRect();-400<c.bottom&&c.top<p&&c.width?t(b):a.push(b)});b=a;b.length||l(removeEventListener)}for(var q=f.querySelectorAll("iframe["+e+"src]"),r=0,b=[],p=0,n=0;r<q.length;)b.push(q[r++]);"complete"===f.readyState?k():h("load",k)})(addEventListener,"data-s9e-mediaembed-",document)</script>';
	}
}