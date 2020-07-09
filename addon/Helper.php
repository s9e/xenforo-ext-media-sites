<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\Entity\Oembed;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;
use XF\Template\Templater;

class Helper
{
	protected static $oembedIds    = [];
	protected static $oembedTitles = [];

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

		self::$oembedIds = [];
		$output = preg_replace_callback(
			'((?:<span data-s9e-mediaembed="[^>]++><span[^>]*+>\\K<iframe|<iframe data-s9e-mediaembed="[^"]++)[^>]*+></iframe>)',
			function ($m)
			{
				return self::replaceIframe($m[0]);
			},
			$output
		);
		$output = self::addOembedTitles($output);

		$output .= '<script>(function(f,k,g,t){function w(a){a("click",m);a("load",m);a("resize",m);a("scroll",m)}function m(){clearTimeout(x);x=setTimeout(y,32)}function z(a){for(var b=k.createElement("iframe"),d=JSON.parse(a.getAttribute(g+"-iframe")),c=-1;++c<d.length;)b.setAttribute(d[c],d[++c]);b.loading="eager";2==b.getAttribute(g+"-api")&&(b.onload=function(){var e=new MessageChannel;b.contentWindow.postMessage("s9e:init",this.src.substr(0,this.src.indexOf("/",8)),[e.port2]);e.port1.onmessage=function(h){h=(""+h.data).split(" ");E(b,h[0],h[1]||0)}});d=a.parentNode;F(b,d);d.replaceChild(b,a)}function G(a){a=a.getBoundingClientRect();if(a.bottom>f.innerHeight)return 2;var b=-1;!A&&location.hash&&(b=n(location.hash,"top"));0>b&&(b=n(".p-navSticky","bottom"));return a.top<b?0:1}function n(a,b){return(a=k.querySelector(a))?a.getBoundingClientRect()[b]:-1}function E(a,b,d){var c=G(a),e=0===c||1===c&&1===u,h=e?n("html","height")-f.scrollY:0,p=a.style;if(1!==c||e)p.transition="none",setTimeout(function(){p.transition=""},0);p.height=b+"px";d&&(p.width=d+"px");e&&((a=n("html","height")-f.scrollY-h)&&f.scrollBy(0,a),l=f.scrollY)}function y(){l!==f.scrollY&&(A=!0,u=l>(l=f.scrollY)?1:0);"complete"===k.readyState&&(v=2*f.innerHeight,B=-v/(0===u?4:2));var a=[];q.forEach(function(b){var d=b.getBoundingClientRect(),c;if(!(c=d.bottom<B||d.top>v||!d.width)&&(c=270===d.width)){for(var e=c=b.parentNode;"BODY"!==c.tagName;)/bbCodeBlock-expandContent/.test(c.className)&&(e=c),c=c.parentNode;c=d.top>e.getBoundingClientRect().bottom}c?a.push(b):b.hasAttribute(g+"-c2l")?H(b):z(b)});q=a;q.length||w(f.removeEventListener)}function I(a){a=a.target;var b=a.firstChild,d=a.getBoundingClientRect(),c=k.documentElement,e=b.style;e.bottom=c.clientHeight-d.bottom+"px";e.height=d.height+"px";e.right=c.clientWidth-d.right+"px";e.width=d.width+"px";b.offsetHeight;/inactive/.test(a.className)?(a.className=t+"-active-tn",b.removeAttribute("style"),r&&r.click(),r=a):(a.className=t+"-inactive-tn",r=null)}function J(a){a=a.target;var b=a.parentNode;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function H(a){a.hasAttribute(g+"-c2l-background")&&((a.hasAttribute(g)?a:a.parentNode.parentNode).style.background=a.getAttribute(g+"-c2l-background"));a.onclick=function(b){b.stopPropagation();z(a)}}function F(a,b){a.hasAttribute(g)||b.hasAttribute("style")||(b.className=t+"-inactive",b.onclick=I,a.addEventListener("transitionend",J))}for(var C=k.querySelectorAll("span["+g+"-iframe]"),D=0,q=[],B=0,v=f.innerHeight,x=0,A=!1,l=0,u=0;D<C.length;)q.push(C[D++]);setTimeout(function(){l=f.scrollY;w(f.addEventListener);y()},32);var r=null})(window,document,"data-s9e-mediaembed","s9e-miniplayer")</script>';
	}

	protected static function addOembedTitles(string $html): string
	{
		self::fetchOembed();
		if (empty(self::$oembedTitles))
		{
			return $html;
		}

		return preg_replace_callback(
			'(data-s9e-mediaembed-c2l="([^"<>]++)"[^>]*?data-s9e-mediaembed-c2l-oembed-id="([^"<>]++)"(?=[^<>]*+>)\\K)',
			function ($m)
			{
				if (!isset(self::$oembedTitles[$m[1]][$m[2]]))
				{
					return '';
				}

				return  ' data-s9e-mediaembed-c2l-oembed-title="' . htmlspecialchars(self::$oembedTitles[$m[1]][$m[2]]) . '"';
			},
			$html
		);
	}

	protected static function fetchOembed(): void
	{
		self::fetchOembedFromLogs();
		self::fetchOembedFromService();
		self::$oembedIds = [];
	}

	protected static function fetchOembedFromLogs(): void
	{
		$hashes = [];
		foreach (self::$oembedIds as $siteId => $mediaIds)
		{
			foreach ($mediaIds as $mediaId)
			{
				$hashes[] = md5($siteId . $mediaId);
			}
		}
		if (empty($hashes))
		{
			return;
		}

		$oembeds = XF::finder('XF:Oembed')->where('media_hash', $hashes)->fetch();
		foreach ($oembeds as $oembed)
		{
			$mediaId = $oembed->media_id;
			$siteId  = $oembed->media_site_id;
			self::$oembedTitles[$siteId][$mediaId] = (string) $oembed->title;

			if (!self::shouldRefetch($oembed))
			{
				unset(self::$oembedIds[$siteId][$mediaId]);
			}
		}
	}

	protected static function fetchOembedFromService(): void
	{
		self::$oembedIds = array_filter(self::$oembedIds);

		// Limit the number of active fetches to 2
		if (empty(self::$oembedIds) || XF::repository('XF:Oembed')->getTotalActiveFetches() >= 2)
		{
			return;
		}

		// Pick one random entry before clearing the array
		$siteId  = array_rand(self::$oembedIds);
		$mediaId = array_rand(self::$oembedIds[$siteId]);
		$oembed  = XF::service('XF:Oembed')->getOembed($siteId, $mediaId);
		if ($oembed)
		{
			self::$oembedTitles[$siteId][$mediaId] = $oembed->title ?? '';
		}
	}

	protected static function replaceIframe(string $original): string
	{
		preg_match_all('(([-\\w]++)="([^"]*+))', $original, $m);
		$attributes = array_combine($m[1], $m[2]);
		$attributes = self::replaceClickToLoadAttributes($attributes);

		if (isset($attributes['data-s9e-mediaembed-api']))
		{
			unset($attributes['onload']);
		}
		if (isset($attributes['data-s9e-mediaembed-c2l'], $attributes['data-s9e-mediaembed-c2l-oembed-id']))
		{
			$siteId  = $attributes['data-s9e-mediaembed-c2l'];
			$mediaId = $attributes['data-s9e-mediaembed-c2l-oembed-id'];

			self::$oembedIds[$siteId][$mediaId] = $mediaId;
		}

		$values = [];
		foreach ($attributes as $attrName => $attrValue)
		{
			if (strpos($attrName, 'c2l') !== false)
			{
				continue;
			}
			$values[] = $attrName;
			$values[] = $attrValue;
		}

		$attrNames = [
			'data-s9e-mediaembed',
			'data-s9e-mediaembed-c2l',
			'data-s9e-mediaembed-c2l-background',
			'data-s9e-mediaembed-c2l-oembed-id',
			'style'
		];

		$html = '<span';
		foreach ($attrNames as $attrName)
		{
			if (isset($attributes[$attrName]))
			{
				$html .= ' ' . $attrName . '="' . htmlspecialchars($attributes[$attrName], ENT_COMPAT, 'utf-8') . '"';
			}
		}
		$html .= " data-s9e-mediaembed-iframe='" . str_replace("'", '&#39;', htmlspecialchars(json_encode($values), ENT_NOQUOTES, 'utf-8', false)) . "'";
		$html .= '></span>';

		return $html;
	}

	protected static function replaceClickToLoadAttributes(array $attributes): array
	{
		if (isset($attributes['data-s9e-mediaembed-c2l-src']))
		{
			$attributes['src'] = $attributes['data-s9e-mediaembed-c2l-src'];
		}
		if (isset($attributes['data-s9e-mediaembed-c2l'], $attributes['style']))
		{
			$regexp = '(\\bbackground:([^;]++);?)';
			if (preg_match($regexp, $attributes['style'], $m))
			{
				$attributes['data-s9e-mediaembed-c2l-background'] = trim($m[1]);
				$attributes['style'] = trim(preg_replace($regexp, '', $attributes['style']));
				if (empty($attributes['style']))
				{
					unset($attributes['style']);
				}
			}
		}

		return $attributes;
	}

	protected static function shouldRefetch(Oembed $oembed): bool
	{
		// NOTE: __isset() returns true even if null
		if ($oembed->title !== null)
		{
			return false;
		}

		// Give up after 10 failures
		if ($oembed->fail_count >= 10)
		{
			return false;
		}

		// Don't refetch within an hour of failure
		if ($oembed->failed_date > (XF::$time - 3600))
		{
			return false;
		}

		return true;
	}
}