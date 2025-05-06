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
use const ENT_COMPAT, ENT_NOQUOTES, FILTER_VALIDATE_INT;
use function array_combine, array_filter, array_rand, explode, filter_var, htmlspecialchars, in_array, intval, json_encode, md5, preg_match, preg_match_all, preg_replace, preg_replace_callback, rawurlencode, str_replace, str_starts_with, strpos, strtolower, trim;

class Helper
{
	protected static $oembedIds    = [];
	protected static $oembedTitles = [];

	public static function extendMediaSiteEntity(Manager $em, Structure &$structure)
	{
		$structure->columns['s9e_disable_auto_embed'] = ['type' => Entity::BOOL, 'default' => false];
	}

	public static function filterBlueskyEmbedder($attrValue)
	{
		return self::filterFederatedHost($attrValue, XF::options()->s9e_MediaSites_BlueskyHosts ?? '');
	}

	public static function filterBlueskyUrl($attrValue)
	{
		return (preg_match('/^at:\\/\\/[.:\\w]+\\/[.\\w]+\\/\\w+$/', $attrValue)) ? $attrValue : false;
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
		return self::filterFederatedHost($attrValue, XF::options()->s9e_MediaSites_MastodonHosts ?? 'mastodon.social');
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

	public static function filterXenForoHost($attrValue)
	{
		return self::filterFederatedHost($attrValue, XF::options()->s9e_MediaSites_XenForoHosts ?? '');
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
			'(<(?:span data-(?:template-name="[^"]++" data-)?s9e-mediaembed="[^>]++><span[^>]*+>\\K<iframe|iframe data-(?:template-name="[^"]++" data-)?s9e-mediaembed=")[^>]*+></iframe>(?!(?:</span>)*+\\s*+</template>))',
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

		$output .= '<script>((e,g,z,h,t)=>{function u(a){const b=[],[c,f]=a;v.forEach(d=>{var k=d.getBoundingClientRect();if(k.bottom<c||k.top>f||!k.width)k=!1;else{let n=d.parentElement,A=n;for(;n;)/bbCodeBlock-expandContent/.test(n.className)&&(A=n),n=n.parentElement;k=k.top<=A.getBoundingClientRect().bottom}k?d.hasAttribute(h+"-c2l")?I(d):B(d):b.push(d)});v=b;v.length||C(e.removeEventListener)}function C(a){["click","load","resize","scroll","visibilitychange"].forEach(b=>a(b,J,{capture:!0,passive:!0}))}function D(){return[g.querySelector(".p-navSticky")?.getBoundingClientRect().bottom??0,e.innerHeight]}function J(a){let b=a.target,c;"click"===a.type&&"A"===b.tagName?(a=b.dataset.contentSelector??"",/^#[-\\w]+$/.test(a)?r(a):(c=/(.*)(#[-\\w]+)$/.exec(b.href))&&c[1]===g.baseURI.replace(/#.*/,"")&&r(c[2])):m&&E();e.clearTimeout(F);F=e.setTimeout(K,32)}function B(a){let b=-1,c=g.createElement("iframe"),f=JSON.parse(a.getAttribute(h+"-iframe"));for(;++b<f.length;)c.setAttribute(f[b],f[++b]);c.loading="eager";"on3"===c.getAttribute(h)&&e.addEventListener("message",d=>{d.source===c.contentWindow&&d.data.height&&w(c,+d.data.height+20+"")});L(c,a.parentNode);a.replaceWith(c);2==c.getAttribute(h+"-api")&&(c.onload=()=>M(c),a=l[G(c.src)],""<a&&w(c,a))}function M(a){const b=new MessageChannel,c=G(a.src);b.port1.onmessage=f=>{const d=""+f.data;e.setTimeout(()=>{w(a,l[c]||d)},l[c]>=+d.split(" ")[0]?2E3:0);try{.1>Math.random()&&N(),l[c]=d}catch{}};a.contentWindow.postMessage("s9e:init","*",[b.port2])}function O(a){a=a.getBoundingClientRect();const [b,c]=D();return a.bottom>c?2:a.top<b?0:1}function w(a,b){const [c,f]=[...b.split(" "),0],d=a.style;if(d.height!==c+"px"||f&&d.width!==f+"px"){a=O(a);var k=(b=0===a||1===a&&1===x&&!m)?p.scrollHeight-e.scrollY:0;if(1!==a||b||m||"complete"!==g.readyState)d.transition="none",e.setTimeout(()=>{d.transition=""},32);q=e.scrollY;d.height=c+"px";f&&(d.width=f+"px");b&&0<q&&(a=p.scrollHeight-e.scrollY-k)&&e.scrollBy({behavior:"instant",top:a});q=e.scrollY}}function K(){x=q>(q=e.scrollY)&&!m?1:0;if("hidden"!==g.visibilityState&&"complete"===g.readyState){const a=2*e.innerHeight;u([-a/(0===x?4:2),a])}}function I(a){a.hasAttribute(h+"-c2l-background")&&((a.hasAttribute(h)?a:a.parentElement.parentElement).style.background=a.getAttribute(h+"-c2l-background"));a.onclick=b=>{b.stopPropagation();B(a)}}function L(a,b){a.hasAttribute(h)||b.hasAttribute("style")||(b.className=t+"-inactive",b.onclick=()=>{const c=b.getBoundingClientRect(),f=a.style;f.bottom=p.clientHeight-c.bottom+"px";f.height=c.height+"px";f.width=c.width+"px";"rtl"===p.dir?f.left=c.left+"px":f.right=p.clientWidth-c.right+"px";a.offsetHeight&&/inactive/.test(b.className)?(b.className=t+"-active-tn",a.removeAttribute("style"),y?.click(),y=b):(b.className=t+"-inactive-tn",y=null)},a.addEventListener("transitionend",()=>{/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}))}function G(a){return a.replace(/.*?ifram(e\\/\\d+\\/\\w+)[^#]*(#[^#]+).*/,"s9$1$2")}function N(){if(l instanceof Storage){var a=l.length||0;if(100<a)for(;0<=--a;){const b=l.key(a)||"";/^s9e\\//.test(b)&&.5>Math.random()&&l.removeItem(b)}}}function r(a){g.querySelector(a)&&(m=!0,E(),a=g.querySelector(a)?.getBoundingClientRect().top??0,u([a,a+e.innerHeight]))}function E(){e.clearTimeout(H);H=e.setTimeout(()=>{m=!1},200)}let y=null,p=g.documentElement,m=!1,q=0,l={},H=0,v=[...g.querySelectorAll(`span[${h}-iframe]`)],x=0,F=0;try{l=e.localStorage}catch{}z&&r(z);m||u(D());C(e.addEventListener);e.navigation?.addEventListener("navigate",a=>{a=a.destination;a.sameDocument&&(a=/#[-\\w]+$/.exec(a.url))&&r(a[0])})})(window,document,location.hash,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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

	protected static function filterFederatedHost(string $attrValue, string $hosts)
	{
		$hosts     = explode("\n", $hosts);
		$attrValue = strtolower($attrValue);
		$host      = $attrValue;
		while ($host !== '')
		{
			if (in_array($host, $hosts, true))
			{
				return $attrValue;
			}

			$host = preg_replace('(^[^\\.]*+\\.*+)', '', $host);
		}

		return false;
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
		elseif (strncmp($attributes['src'] ?? '', 'https://embed.on3.com/', strlen('https://embed.on3.com/')) === 0)
		{
			$attributes['onload'] = 'this.contentWindow.postMessage("","*")';
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
			'data-template-name',
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