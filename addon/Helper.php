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

		$output .= '<script>((f,l,g,v)=>{function x(a){const b={capture:!0,passive:!0};a("click",n,b);a("load",n,b);a("resize",n,b);a("scroll",n,b)}function n(){clearTimeout(y);y=setTimeout(z,32)}function A(a){var b=-1;let c=l.createElement("iframe"),d=JSON.parse(a.getAttribute(g+"-iframe"));for(;++b<d.length;)c.setAttribute(d[b],d[++b]);c.loading="eager";b=a.parentNode;c.hasAttribute(g)||b.hasAttribute("style")||(b.className=v+"-inactive",b.onclick=H,c.addEventListener("transitionend",I));b.replaceChild(c,a);2==c.getAttribute(g+"-api")&&(c.onload=J,a=B(c.src),"string"===typeof h[a]&&(a=h[a].split(" "),C(c,a[0],a[1]||0)))}function J(a){const b=a.target;a=new MessageChannel;const c=b.src;b.contentWindow.postMessage("s9e:init",c.substr(0,c.indexOf("/",8)),[a.port2]);a.port1.onmessage=d=>{d=""+d.data;const e=d.split(" ");C(b,e[0],e[1]||0);try{if(.1>Math.random()&&h instanceof Storage){var m=h.length||0;if(100<m)for(;0<=--m;){const k=h.key(m)||"";/^s9e\//.test(k)&&.5>Math.random()&&h.removeItem(k)}}h[B(c)]=d}catch(k){}}}function K(a){a=a.getBoundingClientRect();if(a.bottom>f.innerHeight)return 2;let b=-1;!D&&location.hash&&(b=p(location.hash,"top"));0>b&&(b=p(".p-navSticky","bottom"));return a.top<b?0:1}function p(a,b){return(a=l.querySelector(a))?a.getBoundingClientRect()[b]:-1}function C(a,b,c){const d=K(a),e=0===d||1===d&&1===q,m=e?p("html","height")-f.scrollY:0,k=a.style;if(1!==d||e||"complete"!==l.readyState)k.transition="none",setTimeout(()=>{k.transition=""},32);k.height=b+"px";c&&(k.width=c+"px");e&&((a=p("html","height")-f.scrollY-m)&&f.scrollBy(0,a),r=f.scrollY)}function z(){r===f.scrollY?q=0:(D=!0,q=r>(r=f.scrollY)?1:0);"complete"===l.readyState&&(w=2*f.innerHeight,E=-w/(0===q?4:2));const a=[];t.forEach(b=>{var c=b.getBoundingClientRect();if(c.bottom<E||c.top>w||!c.width)c=!1;else{let d=b.parentElement,e=d;for(;"BODY"!==d.tagName;)/bbCodeBlock-expandContent/.test(d.className)&&(e=d),d=d.parentElement;c=!(c.top>e.getBoundingClientRect().bottom)}c?b.hasAttribute(g+"-c2l")?L(b):A(b):a.push(b)});t=a;t.length||x(f.removeEventListener)}function H(a){a=a.target;const b=a.firstChild,c=a.getBoundingClientRect(),d=l.documentElement,e=b.style;e.bottom=d.clientHeight-c.bottom+"px";e.height=c.height+"px";e.right=d.clientWidth-c.right+"px";e.width=c.width+"px";/inactive/.test(a.className)?(a.className=v+"-active-tn",b.removeAttribute("style"),u&&u.click(),u=a):(a.className=v+"-inactive-tn",u=null)}function I(a){a=a.target;const b=a.parentElement;/-tn/.test(b.className)&&(b.className=b.className.replace("-tn",""),a.removeAttribute("style"))}function L(a){a.hasAttribute(g+"-c2l-background")&&((a.hasAttribute(g)?a:a.parentElement.parentElement).style.background=a.getAttribute(g+"-c2l-background"));a.onclick=b=>{b.stopPropagation();A(a)}}function B(a){return"s9e/"+a.replace(/.*?iframe\/(\d+\/\w+)[^#]*(#[^#]+)(?:#.*)?/,"$1$2")}let F=l.querySelectorAll("span["+g+"-iframe]"),G=0,t=[],E=0,w=f.innerHeight,y=0,D=!1,r=f.scrollY,q=0,u=null,h={};for(;G<F.length;)t.push(F[G++]);try{h=f.localStorage}catch(a){}x(f.addEventListener);z()})(window,document,"data-s9e-mediaembed","s9e-miniplayer")</script>';
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