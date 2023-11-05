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

		$output .= '<script>((d,h,r,k,t)=>{function u(){d.clearTimeout(z);z=d.setTimeout(()=>{m=!1},200)}function A(a){["click","load","resize","scroll","visibilitychange"].forEach(b=>a(b,J,{capture:!0,passive:!0}))}function B(){if(r){let a=h.querySelector(r)?.getBoundingClientRect().top??0;return[a,a+d.innerHeight]}return v()}function v(){return[Math.max(0,h.querySelector(".p-navSticky")?.getBoundingClientRect().bottom),d.innerHeight]}function J(a){m?u():"click"===a.type&&"A"===a.target.tagName&&(m=!0,u());d.clearTimeout(C);C=d.setTimeout(D,32)}function E(a){let b=-1,c=h.createElement("iframe"),e=JSON.parse(a.getAttribute(k+"-iframe"));for(;++b<e.length;)c.setAttribute(e[b],e[++b]);c.loading="eager";K(c,a.parentNode);a.replaceWith(c);2==c.getAttribute(k+"-api")&&(c.onload=()=>L(c),a=l[F(c.src)],"string"===typeof a&&G(c,a))}function L(a){const b=new MessageChannel,c=F(a.src);b.port1.onmessage=e=>{const f=""+e.data;d.setTimeout(()=>{G(a,l[c]||f)},a.getBoundingClientRect().height>+f.split(" ")[0]?5E3:0);try{.1>Math.random()&&M(),l[c]=f}catch{}};a.contentWindow.postMessage("s9e:init","*",[b.port2])}function N(a){a=a.getBoundingClientRect();const [b,c]=v();return a.bottom>c?2:a.top<b?0:1}function G(a,b){const [c,e]=[...b.split(" "),0],f=a.style;if(f.height!==c+"px"||e&&f.width!==e+"px"){a=N(a);var g=(b=0===a||1===a&&1===w&&!m)?q.scrollHeight-d.scrollY:0;if(1!==a||b||m||"complete"!==h.readyState)f.transition="none",d.setTimeout(()=>{f.transition=""},32);n=d.scrollY;f.height=c+"px";e&&(f.width=e+"px");b&&0<n&&(a=q.scrollHeight-d.scrollY-g)&&d.scrollBy({behavior:"instant",top:a});n=d.scrollY}}function D(){var a=-1!==n;w=n>(n=d.scrollY)&&!m?1:0;if("hidden"!==h.visibilityState){var b=H;a?"complete"!==h.readyState?a=v():(a=2*d.innerHeight,a=[-a/(0===w?4:2),a]):a=B();b(a)}}function H(a){const b=[],[c,e]=a;x.forEach(f=>{var g=f.getBoundingClientRect();if(g.bottom<c||g.top>e||!g.width)g=!1;else{let p=f.parentElement,I=p;for(;p;)/bbCodeBlock-expandContent/.test(p.className)&&(I=p),p=p.parentElement;g=g.top<=I.getBoundingClientRect().bottom}g?f.hasAttribute(k+"-c2l")?O(f):E(f):b.push(f)});x=b;x.length||A(d.removeEventListener)}function O(a){a.hasAttribute(k+"-c2l-background")&&((a.hasAttribute(k)?a:a.parentElement.parentElement).style.background=a.getAttribute(k+"-c2l-background"));a.onclick=b=>{b.stopPropagation();E(a)}}function K(a,b){a.hasAttribute(k)||b.hasAttribute("style")||(b.className=t+"-inactive",b.onclick=()=>{const c=b.getBoundingClientRect(),e=a.style;e.bottom=q.clientHeight-c.bottom+"px";e.height=c.height+"px";e.width=c.width+"px";"rtl"===q.dir?e.left=c.left+"px":e.right=q.clientWidth-c.right+"px";a.offsetHeight&&/inactive/.test(b.className)?(b.className=t+"-active-tn",a.removeAttribute("style"),y?.click(),y=b):(b.className=t+"-inactive-tn",y=null)},a.addEventListener("transitionend",()=>{/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}))}function F(a){return a.replace(/.*?ifram(e\\/\\d+\\/\\w+)[^#]*(#[^#]+).*/,"s9$1$2")}function M(){if(l instanceof Storage){var a=l.length||0;if(100<a)for(;0<=--a;){const b=l.key(a)||"";/^s9e\\//.test(b)&&.5>Math.random()&&l.removeItem(b)}}}let y=null,q=h.documentElement,m=!1,n=-1,l={},z=0,x=[...h.querySelectorAll("span["+k+"-iframe]")],w=0,C=0;try{l=d.localStorage}catch{}D();A(d.addEventListener);d.navigation?.addEventListener("navigate",a=>{a=a.destination;a.sameDocument&&(a=/#.*/.exec(a.url))&&(r=a[0],m=!0,u(),H(B()))})})(window,document,location.hash,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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