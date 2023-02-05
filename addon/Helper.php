<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2023 The s9e authors
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
	* Filter a Mastodon host
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterMastodonHost($attrValue)
	{
		$hosts     = explode("\n", XF::options()->s9e_MediaSites_MastodonHosts ?? 'mastodon.social');
		$attrValue = strtolower($attrValue);

		return in_array($attrValue, $hosts, true) ? $attrValue : false;
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

		$output .= '<script>((f,h,g,u)=>{function w(a){["click","load","resize","scroll","visibilitychange"].forEach(b=>a(b,G,{capture:!0,passive:!0}))}function G(){f.clearTimeout(x);x=f.setTimeout(y,32)}function z(a){let b=-1,c=h.createElement("iframe"),d=JSON.parse(a.getAttribute(g+"-iframe"));for(;++b<d.length;)c.setAttribute(d[b],d[++b]);c.loading="eager";b=a.parentNode;c.hasAttribute(g)||b.hasAttribute("style")||(b.className=u+"-inactive",b.onclick=H,c.addEventListener("transitionend",I));a.replaceWith(c);2==c.getAttribute(g+"-api")&&(c.onload=J,a=k[A(c.src)],"string"===typeof a&&B(c,a))}function J(a){const b=new MessageChannel,c=a.target,d=c.src;b.port1.onmessage=e=>{e=""+e.data;B(c,e);try{if(.1>Math.random()&&k instanceof Storage){var m=k.length||0;if(100<m)for(;0<=--m;){const l=k.key(m)||"";/^s9e\//.test(l)&&.5>Math.random()&&k.removeItem(l)}}k[A(d)]=e}catch{}};c.contentWindow.postMessage("s9e:init","*",[b.port2])}function K(a){a=a.getBoundingClientRect();if(a.bottom>f.innerHeight)return 2;let b=-1;!C&&location.hash&&(b=n(location.hash,"top"));0>b&&(b=n(".p-navSticky","bottom"));return a.top<b?0:1}function n(a,b){return(a=h.querySelector(a))?a.getBoundingClientRect()[b]:-1}function B(a,b){b=b.split(" ");L(a,b[0],b[1]||0)}function L(a,b,c){const d=K(a),e=0===d||1===d&&1===p,m=e?n("html","height")-f.scrollY:0,l=a.style;if(1!==d||e||"complete"!==h.readyState)l.transition="none",f.setTimeout(()=>{l.transition=""},32);l.height=b+"px";c&&(l.width=c+"px");e&&((a=n("html","height")-f.scrollY-m)&&f.scrollBy(0,a),q=f.scrollY)}function y(){if("hidden"!==h.visibilityState){q===f.scrollY?p=0:(C=!0,p=q>(q=f.scrollY)?1:0);"complete"===h.readyState&&(v=2*f.innerHeight,D=-v/(0===p?4:2));var a=[];r.forEach(b=>{var c=b.getBoundingClientRect();if(c.bottom<D||c.top>v||!c.width)c=!1;else{let d=b.parentElement,e=d;for(;d;)/bbCodeBlock-expandContent/.test(d.className)&&(e=d),d=d.parentElement;c=c.top<=e.getBoundingClientRect().bottom}c?b.hasAttribute(g+"-c2l")?M(b):z(b):a.push(b)});r=a;r.length||w(f.removeEventListener)}}function H(a){a=a.target;const b=a.firstChild,c=a.getBoundingClientRect(),d=h.documentElement,e=b.style;e.bottom=d.clientHeight-c.bottom+"px";e.height=c.height+"px";e.width=c.width+"px";"rtl"===d.dir?e.left=c.left+"px":e.right=d.clientWidth-c.right+"px";b.offsetHeight&&/inactive/.test(a.className)?(a.className=u+"-active-tn",b.removeAttribute("style"),t&&t.click(),t=a):(a.className=u+"-inactive-tn",t=null)}function I(a){a=a.target;const b=a.parentElement;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function M(a){a.hasAttribute(g+"-c2l-background")&&((a.hasAttribute(g)?a:a.parentElement.parentElement).style.background=a.getAttribute(g+"-c2l-background"));a.onclick=b=>{b.stopPropagation();z(a)}}function A(a){return a.replace(/.*?ifram(e\/\d+\/\w+)[^#]*(#[^#]+).*/,"s9$1$2")}let E=h.querySelectorAll("span["+g+"-iframe]"),F=0,r=[],D=0,v=f.innerHeight,x=0,C=!1,q=f.scrollY,p=0,t=null,k={};for(;F<E.length;)r.push(E[F++]);try{k=f.localStorage}catch{}w(f.addEventListener);y()})(window,document,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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