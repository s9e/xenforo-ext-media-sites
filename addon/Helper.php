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

		$output .= '<script>((d,h,r,k,t)=>{function u(){d.clearTimeout(A);A=d.setTimeout(()=>{m=!1},200)}function B(a){["click","load","resize","scroll","visibilitychange"].forEach(b=>a(b,K,{capture:!0,passive:!0}))}function C(){if(r){let a=h.querySelector(r)?.getBoundingClientRect().top??0;return[a,a+d.innerHeight]}return v()}function v(){return[Math.max(0,h.querySelector(".p-navSticky")?.getBoundingClientRect().bottom),d.innerHeight]}function K(a){m?u():w||"scroll"!==a.type?"click"===a.type&&"A"===a.target.tagName&&(m=!0,u()):w=!0;d.clearTimeout(D);D=d.setTimeout(E,32)}function F(a){let b=-1,c=h.createElement("iframe"),e=JSON.parse(a.getAttribute(k+"-iframe"));for(;++b<e.length;)c.setAttribute(e[b],e[++b]);c.loading="eager";L(c,a.parentNode);a.replaceWith(c);2==c.getAttribute(k+"-api")&&(c.onload=()=>M(c),a=l[G(c.src)],"string"===typeof a&&H(c,a))}function M(a){const b=new MessageChannel,c=G(a.src);b.port1.onmessage=e=>{const f=""+e.data;d.setTimeout(()=>{H(a,l[c]||f)},a.getBoundingClientRect().height>+f.split(" ")[0]?4E3:0);try{.1>Math.random()&&N(),l[c]=f}catch{}};a.contentWindow.postMessage("s9e:init","*",[b.port2])}function O(a){a=a.getBoundingClientRect();const [b,c]=v();return a.bottom>c?2:a.top<b?0:1}function H(a,b){const [c,e]=[...b.split(" "),0],f=a.style;if(f.height!==c+"px"||e&&f.width!==e+"px"){a=O(a);var g=(b=0===a||1===a&&1===x&&!m)?p.scrollHeight-d.scrollY:0;if(1!==a||b||m||"complete"!==h.readyState)f.transition="none",d.setTimeout(()=>{f.transition=""},32);q=d.scrollY;f.height=c+"px";e&&(f.width=e+"px");b&&0<q&&(a=p.scrollHeight-d.scrollY-g)&&d.scrollBy({behavior:"instant",top:a});q=d.scrollY}}function E(){x=q>(q=d.scrollY)&&!m?1:0;if("hidden"!==h.visibilityState){var a=I;if(w)if("complete"!==h.readyState)var b=v();else b=2*d.innerHeight,b=[-b/(0===x?4:2),b];else b=C();a(b)}}function I(a){const b=[],[c,e]=a;y.forEach(f=>{var g=f.getBoundingClientRect();if(g.bottom<c||g.top>e||!g.width)g=!1;else{let n=f.parentElement,J=n;for(;n;)/bbCodeBlock-expandContent/.test(n.className)&&(J=n),n=n.parentElement;g=g.top<=J.getBoundingClientRect().bottom}g?f.hasAttribute(k+"-c2l")?P(f):F(f):b.push(f)});y=b;y.length||B(d.removeEventListener)}function P(a){a.hasAttribute(k+"-c2l-background")&&((a.hasAttribute(k)?a:a.parentElement.parentElement).style.background=a.getAttribute(k+"-c2l-background"));a.onclick=b=>{b.stopPropagation();F(a)}}function L(a,b){a.hasAttribute(k)||b.hasAttribute("style")||(b.className=t+"-inactive",b.onclick=()=>{const c=b.getBoundingClientRect(),e=a.style;e.bottom=p.clientHeight-c.bottom+"px";e.height=c.height+"px";e.width=c.width+"px";"rtl"===p.dir?e.left=c.left+"px":e.right=p.clientWidth-c.right+"px";a.offsetHeight&&/inactive/.test(b.className)?(b.className=t+"-active-tn",a.removeAttribute("style"),z?.click(),z=b):(b.className=t+"-inactive-tn",z=null)},a.addEventListener("transitionend",()=>{/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}))}function G(a){return a.replace(/.*?ifram(e\\/\\d+\\/\\w+)[^#]*(#[^#]+).*/,"s9$1$2")}function N(){if(l instanceof Storage){var a=l.length||0;if(100<a)for(;0<=--a;){const b=l.key(a)||"";/^s9e\\//.test(b)&&.5>Math.random()&&l.removeItem(b)}}}let z=null,p=h.documentElement,w=!1,m=!1,q=d.scrollY,l={},A=0,y=[...h.querySelectorAll("span["+k+"-iframe]")],x=0,D=0;try{l=d.localStorage}catch{}E();B(d.addEventListener);d.navigation?.addEventListener("navigate",a=>{a=a.destination;a.sameDocument&&(a=/#.*/.exec(a.url))&&(r=a[0],m=!0,u(),I(C()))})})(window,document,location.hash,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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