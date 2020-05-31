<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF\Container;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;
use XF\Template\Templater;

class Helper
{
	public static function extendMediaSiteEntity(Manager $em, Structure &$structure)
	{
		$structure->columns['s9e_disable_auto_embed'] = ['type' => Entity::BOOL, 'default' => false];
	}

	/**
	* Filter an identifier value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterIdentifier($attrValue)
	{
		return (preg_match('/^[-0-9A-Za-z_]+$/D', $attrValue)) ? $attrValue : false;
	}

	/**
	* Filter a timestamp value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterTimestamp($attrValue)
	{
		if (preg_match('/^(?=\\d)(?:(\\d+)h)?(?:(\\d+)m)?(?:(\\d+)s)?$/D', $attrValue, $m))
		{
			$m += [0, 0, 0, 0];

			return intval($m[1]) * 3600 + intval($m[2]) * 60 + intval($m[3]);
		}

		return self::filterUint($attrValue);
	}

	/**
	* Filter a uint value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterUint($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_INT, [
			'options' => ['min_range' => 0]
		]);
	}

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

		$output .= '<script>(function(d,g,f,p){function q(){k||(h=d.scrollY,v(d.addEventListener),w())}function v(a){a("click",r);a("resize",r);a("scroll",r)}function r(){clearTimeout(x);x=setTimeout(w,32)}function C(a){var b=a.contentWindow,c=a.getAttribute(f+"-src");2==a.getAttribute(f+"-api")&&(a.onload=function(){var e=new MessageChannel;b.postMessage("s9e:init",c.substr(0,c.indexOf("/",8)),[e.port2]);e.port1.onmessage=function(b){b=(""+b.data).split(" ");D(a,b[0],b[1]||0)}});if(a.contentDocument)b.location.replace(c);else if(a.onload)a.onload();E(a)}function F(a){a=a.getBoundingClientRect();if(a.bottom>d.innerHeight)return 2;var b=-1;!y&&location.hash&&(b=l(location.hash,"top"));0>b&&(b=l(".p-navSticky","bottom"));return a.top<b?0:1}function l(a,b){var c=g.querySelector(a);return c?c.getBoundingClientRect()[b]:-1}function D(a,b,c){var e=F(a),t=0===e||1===e&&1===u,g=t?l("html","height")-d.scrollY:0,f=a.style;if(1!==e||t)f.transition="none",setTimeout(function(){f.transition=""},0);f.height=b+"px";c&&(f.width=c+"px");t&&((a=l("html","height")-d.scrollY-g)&&d.scrollBy(0,a),h=d.scrollY)}function w(){h!==d.scrollY&&(y=!0,u=h>(h=d.scrollY)?1:0);k=2*d.innerHeight;z=-k/(0===u?4:2);var a=[];m.forEach(function(b){var c=b.getBoundingClientRect(),e;if(!(e=c.bottom<z||c.top>k||!c.width)&&(e=270===c.width)){c=c.top;for(var d=e=b.parentNode;"BODY"!==e.tagName;)/bbCodeBlock-expandContent/.test(e.className)&&(d=e),e=e.parentNode;e=c>d.getBoundingClientRect().bottom}e?a.push(b):C(b)});m=a;m.length||v(d.removeEventListener)}function G(a){a=a.target;var b=a.firstChild,c=a.getBoundingClientRect(),e=g.documentElement,d=b.style;d.bottom=e.clientHeight-c.bottom+"px";d.height=c.height+"px";d.right=e.clientWidth-c.right+"px";d.width=c.width+"px";b.offsetHeight;/inactive/.test(a.className)?(a.className=p+"-active-tn",b.removeAttribute("style"),n&&n.click(),n=a):(a.className=p+"-inactive-tn",n=null)}function H(a){a=a.target;var b=a.parentNode;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function E(a){var b=a.parentNode;a.hasAttribute(f)||b.hasAttribute("style")||(b.className=p+"-inactive",b.onclick=G,a.addEventListener("transitionend",H))}for(var A=g.querySelectorAll("iframe["+f+"-src]"),B=0,m=[],z=0,k=0,x=0,y=!1,h=0,u=0;B<A.length;)m.push(A[B++]);"complete"===g.readyState?q():(d.addEventListener("load",q),setTimeout(q,3E3));var n=null})(window,document,"data-s9e-mediaembed","s9e-miniplayer")</script>';
	}

	/**
	* Replace iframes in given HTML
	*
	* @param  Templater  $templater
	* @param  string     $type
	* @param  string     $template
	* @param  string    &$output
	* @return void
	*/
	public static function replaceIframes(Templater $templater, $type, $template, &$output)
	{
		if (strpos($output, 'data-s9e-mediaembed="') === false)
		{
			return;
		}

		$output = preg_replace_callback(
			'((?:<span data-s9e-mediaembed="[^>]++><span[^>]*+>\\K<iframe|<iframe data-s9e-mediaembed="[^"]++)[^>]*+></iframe>)',
			function ($m)
			{
				return self::replaceIframe($m[0]);
			},
			$output
		);

		$output .= '<script>(function(d,g,f,p){function q(){k||(h=d.scrollY,v(d.addEventListener),w())}function v(a){a("click",r);a("resize",r);a("scroll",r)}function r(){clearTimeout(x);x=setTimeout(w,32)}function C(a){for(var b=g.createElement("iframe"),c=JSON.parse(a.getAttribute(f+"-iframe")),e=-1;++e<c.length;)b.setAttribute(c[e],c[++e]);b.loading="eager";2==b.getAttribute(f+"-api")&&(b.onload=function(){var a=new MessageChannel,c=this.src.substr(0,this.src.indexOf("/",8));b.contentWindow.postMessage("s9e:init",c,[a.port2]);a.port1.onmessage=function(a){a=(""+a.data).split(" ");D(b,a[0],a[1]||0)}});c=a.parentNode;E(b,c);c.replaceChild(b,a)}function F(a){a=a.getBoundingClientRect();if(a.bottom>d.innerHeight)return 2;var b=-1;!y&&location.hash&&(b=l(location.hash,"top"));0>b&&(b=l(".p-navSticky","bottom"));return a.top<b?0:1}function l(a,b){var c=g.querySelector(a);return c?c.getBoundingClientRect()[b]:-1}function D(a,b,c){var e=F(a),t=0===e||1===e&&1===u,g=t?l("html","height")-d.scrollY:0,f=a.style;if(1!==e||t)f.transition="none",setTimeout(function(){f.transition=""},0);f.height=b+"px";c&&(f.width=c+"px");t&&((a=l("html","height")-d.scrollY-g)&&d.scrollBy(0,a),h=d.scrollY)}function w(){h!==d.scrollY&&(y=!0,u=h>(h=d.scrollY)?1:0);k=2*d.innerHeight;z=-k/(0===u?4:2);var a=[];m.forEach(function(b){var c=b.getBoundingClientRect();if(c.bottom<z||c.top>k||!c.width)c=!1;else{var e;if(e=270===c.width){c=c.top;for(var d=e=b.parentNode;"BODY"!==e.tagName;)/bbCodeBlock-expandContent/.test(e.className)&&(d=e),e=e.parentNode;e=c>d.getBoundingClientRect().bottom}c=e?!1:!0}c?C(b):a.push(b)});m=a;m.length||v(d.removeEventListener)}function G(a){a=a.target;var b=a.firstChild,c=a.getBoundingClientRect(),e=g.documentElement,d=b.style;d.bottom=e.clientHeight-c.bottom+"px";d.height=c.height+"px";d.right=e.clientWidth-c.right+"px";d.width=c.width+"px";b.offsetHeight;/inactive/.test(a.className)?(a.className=p+"-active-tn",b.removeAttribute("style"),n&&n.click(),n=a):(a.className=p+"-inactive-tn",n=null)}function H(a){a=a.target;var b=a.parentNode;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function E(a,b){a.hasAttribute(f)||b.hasAttribute("style")||(b.className=p+"-inactive",b.onclick=G,a.addEventListener("transitionend",H))}for(var A=g.querySelectorAll("span["+f+"-iframe]"),B=0,m=[],z=0,k=0,x=0,y=!1,h=0,u=0;B<A.length;)m.push(A[B++]);"complete"===g.readyState?q():(d.addEventListener("load",q),setTimeout(q,3E3));var n=null})(window,document,"data-s9e-mediaembed","s9e-miniplayer");</script>';
	}

	protected static function replaceIframe(string $original): string
	{
		preg_match_all('(([-\\w]++)="([^"]*+))', $original, $m);
		$attributes = array_combine($m[1], $m[2]);

		if (isset($attributes['data-s9e-mediaembed-api']))
		{
			unset($attributes['onload']);
		}

		$values = [];
		foreach ($attributes as $attrName => $attrValue)
		{
			$values[] = $attrName;
			$values[] = $attrValue;
		}

		$html = '<span';
		if (isset($attributes['data-s9e-mediaembed']))
		{
			$html .= ' data-s9e-mediaembed="' . htmlspecialchars($attributes['data-s9e-mediaembed'], ENT_COMPAT, 'utf-8') . '"';
		}
		$html .= " data-s9e-mediaembed-iframe='" . str_replace("'", '&#39;', htmlspecialchars(json_encode($values), ENT_NOQUOTES, 'utf-8')) . "'";
		if (isset($attributes['style']))
		{
			$html .= ' style="' . htmlspecialchars($attributes['style'], ENT_COMPAT, 'utf-8') . '"';
		}
		$html .= '></span>';

		return $html;
	}
}