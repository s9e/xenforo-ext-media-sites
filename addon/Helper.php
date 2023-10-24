<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) The s9e authors
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

		$cnt = 0;

		self::$oembedIds = [];
		$output = preg_replace_callback(
			'(<(?:span data-s9e-mediaembed="[^>]++><span[^>]*+>\\K<iframe|iframe data-s9e-mediaembed="[^"]++)[^>]*+></iframe>(?!(?:</span>)*+\\s*+</template>))',
			function ($m) use (&$cnt)
			{
				++$cnt;

				return self::replaceIframe($m[0]);
			},
			$output
		);
		$output = self::addOembedTitles($output);

		if (!$cnt)
		{
			return;
		}

		$output .= '<script>((e,k,f,r)=>{function w(a){["click","load","resize","scroll","visibilitychange"].forEach(b=>a(b,F,{capture:!0,passive:!0}))}function F(){e.clearTimeout(x);x=e.setTimeout(y,32)}function z(a){let b=-1,c=k.createElement("iframe"),d=JSON.parse(a.getAttribute(f+"-iframe"));for(;++b<d.length;)c.setAttribute(d[b],d[++b]);c.loading="eager";G(c,a.parentNode);a.replaceWith(c);2==c.getAttribute(f+"-api")&&(c.onload=H,a=g[A(c.src)],"string"===typeof a&&B(c,a))}function H(a){const b=new MessageChannel,c=a.target,d=A(c.src);b.port1.onmessage=h=>{const l=""+h.data;e.setTimeout(()=>{B(c,g[d]||l)},c.getBoundingClientRect().height>+l.split(" ")[0]?5E3:0);try{.1>Math.random()&&I(),g[d]=l}catch{}};c.contentWindow.postMessage("s9e:init","*",[b.port2])}function J(a){a=a.getBoundingClientRect();if(a.bottom>e.innerHeight)return 2;let b=-1;!C&&location.hash&&(b=D(location.hash,"top"));0>b&&(b=D(".p-navSticky","bottom"));return a.top<b?0:1}function D(a,b){return(a=k.querySelector(a))?a.getBoundingClientRect()[b]:-1}function B(a,b){b=b.split(" ");K(a,b[0],b[1]||0)}function K(a,b,c){const d=J(a),h=0===d||1===d&&1===n,l=h?m.scrollHeight-e.scrollY:0,p=a.style;if(1!==d||h||"complete"!==k.readyState)p.transition="none",e.setTimeout(()=>{p.transition=""},32);p.height=b+"px";c&&(p.width=c+"px");h&&((a=m.scrollHeight-e.scrollY-l)&&e.scrollBy(0,a),q=e.scrollY)}function y(){if("hidden"!==k.visibilityState){q===e.scrollY?n=0:(C=!0,n=q>(q=e.scrollY)?1:0);"complete"===k.readyState&&(t=2*e.innerHeight,E=-t/(0===n?4:2));var a=[];u.forEach(b=>{var c=b.getBoundingClientRect();if(c.bottom<E||c.top>t||!c.width)c=!1;else{let d=b.parentElement,h=d;for(;d;)/bbCodeBlock-expandContent/.test(d.className)&&(h=d),d=d.parentElement;c=c.top<=h.getBoundingClientRect().bottom}c?b.hasAttribute(f+"-c2l")?L(b):z(b):a.push(b)});u=a;u.length||w(e.removeEventListener)}}function L(a){a.hasAttribute(f+"-c2l-background")&&((a.hasAttribute(f)?a:a.parentElement.parentElement).style.background=a.getAttribute(f+"-c2l-background"));a.onclick=b=>{b.stopPropagation();z(a)}}function G(a,b){a.hasAttribute(f)||b.hasAttribute("style")||(b.className=r+"-inactive",b.onclick=()=>{const c=b.getBoundingClientRect(),d=a.style;d.bottom=m.clientHeight-c.bottom+"px";d.height=c.height+"px";d.width=c.width+"px";"rtl"===m.dir?d.left=c.left+"px":d.right=m.clientWidth-c.right+"px";a.offsetHeight&&/inactive/.test(b.className)?(b.className=r+"-active-tn",a.removeAttribute("style"),v?.click(),v=b):(b.className=r+"-inactive-tn",v=null)},a.addEventListener("transitionend",()=>{/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}))}function A(a){return a.replace(/.*?ifram(e\/\d+\/\w+)[^#]*(#[^#]+).*/,"s9$1$2")}function I(){if(g instanceof Storage){var a=g.length||0;if(100<a)for(;0<=--a;){const b=g.key(a)||"";/^s9e\//.test(b)&&.5>Math.random()&&g.removeItem(b)}}}let v=null,t=e.innerHeight,m=k.documentElement,C=!1,q=e.scrollY,g={},u=[...k.querySelectorAll("span["+f+"-iframe]")],n=0,E=0,x=0;try{g=e.localStorage}catch{}w(e.addEventListener);y()})(window,document,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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