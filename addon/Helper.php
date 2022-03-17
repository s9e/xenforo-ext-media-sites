<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2022 The s9e authors
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
	* Filter a URL
	*
	* @param  string $attrValue Original value
	* @return string
	*/
	public static function filterUrl($attrValue)
	{
		return preg_replace_callback(
			'/%(?![0-9A-Fa-f]{2})|[^!#-&*-;=?-Z_a-z~]/',
			function ($m)
			{
				return rawurlencode($m[0]);
			},
			$attrValue
		);
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
			'(<(?:span data-s9e-mediaembed="[^>]++><span[^>]*+>\\K<iframe|iframe data-s9e-mediaembed="[^"]++)[^>]*+></iframe>)',
			function ($m)
			{
				return self::replaceIframe($m[0]);
			},
			$output
		);
		$output = self::addOembedTitles($output);

		$output .= '<script>(function(f,l,g,v){function x(a){var b={capture:!0,passive:!0};a("click",p,b);a("load",p,b);a("resize",p,b);a("scroll",p,b)}function p(){clearTimeout(y);y=setTimeout(z,32)}function A(a){for(var b=l.createElement("iframe"),c=JSON.parse(a.getAttribute(g+"-iframe")),d=-1;++d<c.length;)b.setAttribute(c[d],c[++d]);b.loading="eager";c=a.parentNode;b.hasAttribute(g)||c.hasAttribute("style")||(c.className=v+"-inactive",c.onclick=H,b.addEventListener("transitionend",I));c.replaceChild(b,a);2==b.getAttribute(g+"-api")&&(b.onload=J,a=B(b.src),"string"===typeof h[a]&&(a=h[a].split(" "),C(b,a[0],a[1]||0)))}function J(a){var b=a.target;a=new MessageChannel;var c=b.src;b.contentWindow.postMessage("s9e:init",c.substr(0,c.indexOf("/",8)),[a.port2]);a.port1.onmessage=function(d){d=""+d.data;var e=d.split(" ");C(b,e[0],e[1]||0);try{if(.1>Math.random()){var m=h.length||0;if(100<m)for(;0<=--m;){var k=h.key(m);/^s9e\//.test(k)&&.5>Math.random()&&h.removeItem(k)}}h[B(c)]=d}catch(M){}}}function K(a){a=a.getBoundingClientRect();if(a.bottom>f.innerHeight)return 2;var b=-1;!D&&location.hash&&(b=q(location.hash,"top"));0>b&&(b=q(".p-navSticky","bottom"));return a.top<b?0:1}function q(a,b){return(a=l.querySelector(a))?a.getBoundingClientRect()[b]:-1}function C(a,b,c){var d=K(a),e=0===d||1===d&&1===r,m=e?q("html","height")-f.scrollY:0,k=a.style;if(1!==d||e||"complete"!==l.readyState)k.transition="none",setTimeout(function(){k.transition=""},32);k.height=b+"px";c&&(k.width=c+"px");e&&((a=q("html","height")-f.scrollY-m)&&f.scrollBy(0,a),n=f.scrollY)}function z(){n===f.scrollY?r=0:(D=!0,r=n>(n=f.scrollY)?1:0);"complete"===l.readyState&&(w=2*f.innerHeight,E=-w/(0===r?4:2));var a=[];t.forEach(function(b){var c=b.getBoundingClientRect();if(c.bottom<E||c.top>w||!c.width)c=!1;else{for(var d=b.parentNode,e=d;"BODY"!==d.tagName;)/bbCodeBlock-expandContent/.test(d.className)&&(e=d),d=d.parentNode;c=!(c.top>e.getBoundingClientRect().bottom)}c?b.hasAttribute(g+"-c2l")?L(b):A(b):a.push(b)});t=a;t.length||x(f.removeEventListener)}function H(a){a=a.target;var b=a.firstChild,c=a.getBoundingClientRect(),d=l.documentElement,e=b.style;e.bottom=d.clientHeight-c.bottom+"px";e.height=c.height+"px";e.right=d.clientWidth-c.right+"px";e.width=c.width+"px";b.offsetHeight;/inactive/.test(a.className)?(a.className=v+"-active-tn",b.removeAttribute("style"),u&&u.click(),u=a):(a.className=v+"-inactive-tn",u=null)}function I(a){a=a.target;var b=a.parentNode;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function L(a){a.hasAttribute(g+"-c2l-background")&&((a.hasAttribute(g)?a:a.parentNode.parentNode).style.background=a.getAttribute(g+"-c2l-background"));a.onclick=function(b){b.stopPropagation();A(a)}}function B(a){return"s9e/"+a.replace(/.*?iframe\/(\d+\/\w+)[^#]*/,"$1")}for(var F=l.querySelectorAll("span["+g+"-iframe]"),G=0,t=[],E=0,w=f.innerHeight,y=0,D=!1,n=0,r=0,u=null,h={};G<F.length;)t.push(F[G++]);try{h=f.localStorage}catch(a){}setTimeout(function(){n=f.scrollY;x(f.addEventListener);z()},32)})(window,document,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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

				return  ' data-s9e-mediaembed-c2l-oembed-title="' . htmlspecialchars(self::$oembedTitles[$m[1]][$m[2]] ?? '') . '"';
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