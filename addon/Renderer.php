<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;

class Renderer
{
	/**
	* @var array
	*/
	protected static $customFormats = [
		'dailymotion' => ['(^(?<id>\\w+):(?<t>\\d+))'],
		'facebook'    => ['(^(?<user>\\w+)/(?<type>post)s/(?<id>\\d+))'],
		'twitch'      => [
			'(^(?<channel>\\w+)$)',
			'(^(?<video_id>\\d+):(?<t>[\\dhms]+)$)',
			'(^clip:(?<clip_id>\\w+))'
		],
		'vimeo'       => ['(^(?<id>\\d+):(?<t>[\\dhms]+))'],
		'youtube'     => ['(^(?<id>[-\\w]+):(?<t>\\d+))']
	];

	/**
	* Generate the HTML code for a site
	*
	* @param  string $mediaKey Media key
	* @param  array  $site     Site's config
	* @param  string $siteId   Site's ID
	* @return string           Embed code
	*/
	public static function render($mediaKey, array $site, $siteId)
	{
		$siteId = strtolower($siteId);
		$vars   = self::parseVars($mediaKey, $siteId);

		// Use a PHP renderer if applicable
		$callback = __CLASS__ . '::render' . ucfirst($siteId);
		if (is_callable($callback))
		{
			return call_user_func($callback, $vars);
		}

		// Use XenForo's default template
		return XF::app()->templater()->renderTemplate('public:_media_site_embed_' . $siteId, $vars);
	}

	/**
	* Adjust vars for Flickr
	*
	* XenForo 2.0 stores IDs in Base 58
	*
	* @link https://www.flickr.com/services/api/misc.urls.html#short
	*
	* @param  array $vars
	* @return array
	*/
	protected static function adjustVarsFlickr(array $vars)
	{
		if (isset($vars['id']) && !is_numeric($vars['id']))
		{
			$chars = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
			$id    = 0;
			foreach (str_split(strrev($vars['id']), 1) as $pos => $digit)
			{
				$id += strpos($chars, $digit) * pow(58, $pos);
			}
			$vars['id'] = $id;
		}

		return $vars;
	}

	/**
	* Adjust vars for Reddit
	*
	* Older versions of the media site used "path" as var name and started with "/r/"
	*
	* @param  array $vars
	* @return array
	*/
	protected static function adjustVarsReddit(array $vars)
	{
		if (isset($vars['path']))
		{
			$vars = ['id' => preg_replace('(^r/)', '', trim($vars['path'], '/'))];
		}

		return $vars;
	}

	/**
	* Adjust vars for SoundCloud
	*
	* @param  array $vars
	* @return array
	*/
	protected static function adjustVarsSoundcloud(array $vars)
	{
		if (isset($vars['id']))
		{
			$vars['id'] = preg_replace('(#.*)', '', $vars['id']);
			if (preg_match('(^tracks/(\\d+))', $vars['id'], $m))
			{
				$vars['track_id'] = $m[1];
			}
			elseif (preg_match('(^playlists/(\\d+))', $vars['id'], $m))
			{
				$vars['playlist_id'] = $m[1];
			}
		}

		return $vars;
	}

	/**
	* Adjust vars for YouTube
	*
	* @param  array $vars
	* @return array
	*/
	protected static function adjustVarsYoutube(array $vars)
	{
		if (!isset($vars['t']) && (isset($vars['h']) || isset($vars['m']) || isset($vars['s'])))
		{
			// Add backward compatibility with the older h/m/s vars
			$vars += ['h' => 0, 'm' => 0, 's' => 0];
			$vars['t'] = intval($vars['h']) * 3600 + intval($vars['m']) * 60 + intval($vars['s']);

			unset($vars['h'], $vars['m'], $vars['s']);
		}

		return $vars;
	}

	/**
	* Parse vars from a media key
	*
	* @param  string $mediaKey Media key
	* @param  string $siteId
	* @return array            Associative array
	*/
	protected static function parseVars($mediaKey, $siteId)
	{
		// If the value looks like a series of key=value pairs, add them to $vars
		$vars = [];
		if (preg_match('(^(\\w+=[^;]*)(?>;(?1))*$)', $mediaKey))
		{
			foreach (explode(';', $mediaKey) as $pair)
			{
				list($k, $v) = explode('=', $pair);
				$vars[urldecode($k)] = urldecode($v);
			}
		}
		else
		{
			$vars['id'] = $mediaKey;
		}

		if (isset(self::$customFormats[$siteId]))
		{
			foreach (self::$customFormats[$siteId] as $regexp)
			{
				// Add named captures from custom formats
				preg_match($regexp, $mediaKey, $m);
				$vars = $m + $vars;
			}
		}

		$callback = __CLASS__ . '::adjustVars' . ucfirst($siteId);
		if (is_callable($callback))
		{
			$vars = call_user_func($callback, $vars);
		}

		return $vars;
	}

	protected static function renderAmazon($vars)
	{
		$vars+=['id'=>null,'tld'=>null];$options=XF::options();$html='<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//';if($vars['tld']==='es'||$vars['tld']==='it'){$html.='rcm-eu.amazon-adsystem.com/e/cm?lt1=_blank&amp;bc1=FFFFFF&amp;bg1=FFFFFF&amp;fc1=000000&amp;lc1=0000FF&amp;p=8&amp;l=as1&amp;f=ifr&amp;asins='.htmlspecialchars($vars['id'],2).'&amp;o=';if($vars['tld']==='es'){$html.='30';}else{$html.='29';}$html.='&amp;t=';if($vars['tld']==='es'&&$options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_ES!==''){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_ES,2);}elseif($vars['tld']==='it'&&$options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_IT!==''){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_IT,2);}else{$html.='_';}}else{$html.='ws-';if($vars['tld']==='in'){$html.='in';}elseif($vars['tld']==='jp'){$html.='fe';}elseif(isset($vars['tld'])&&(strpos('desfrituk',$vars['tld'])!==false)){$html.='eu';}else{$html.='na';}$html.='.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&amp;OneJS=1&amp;Operation=GetAdHtml&amp;MarketPlace=';if(isset($vars['tld'])){$html.=htmlspecialchars(strtr($vars['tld'],'acdefijknprstu','ACDEFIJBNPRSTG'),2);}else{$html.='US';}$html.='&amp;ad_type=product_link&amp;tracking_id=';if($vars['tld']==='ca'){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_CA,2);}elseif($vars['tld']==='de'){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_DE,2);}elseif($vars['tld']==='fr'){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_FR,2);}elseif($vars['tld']==='in'){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_IN,2);}elseif($vars['tld']==='jp'){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_JP,2);}elseif($vars['tld']==='uk'){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_UK,2);}elseif($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG!==''){$html.=htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG,2);}else{$html.='-20';}$html.='&amp;marketplace=amazon&amp;region=';if(isset($vars['tld'])){$html.=htmlspecialchars(strtr($vars['tld'],'acdefijknprstu','ACDEFIJBNPRSTG'),2);}else{$html.='US';}$html.='&amp;asins='.htmlspecialchars($vars['id'],2).'&amp;show_border=true&amp;link_opens_in_new_window=true';}$html.='"></iframe></span></span>';

		return $html;
	}

	protected static function renderAudiomack($vars)
	{
		$vars+=['id'=>null,'mode'=>null];$html='<iframe data-s9e-mediaembed="audiomack" allowfullscreen="" scrolling="no"';if($vars['mode']==='album'){$html.=' src="https://www.audiomack.com/embed/album/'.htmlspecialchars($vars['id'],2).'" style="border:0;height:400px;max-width:900px;width:100%"';}else{$html.=' src="https://www.audiomack.com/embed/song/'.htmlspecialchars($vars['id'],2).'" style="border:0;height:252px;max-width:900px;width:100%"';}$html.='></iframe>';

		return $html;
	}

	protected static function renderBbcnews($vars)
	{
		$vars+=['id'=>null,'playlist'=>null];$html='<span data-s9e-mediaembed="bbcnews" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.bbc.com';if((strpos($vars['id'],'av/')===0)){$html.='/news/'.htmlspecialchars($vars['id'],2).'/embed';}elseif((strpos($vars['playlist'],'/news/')===0)&&(strpos($vars['playlist'],'A')!==false)){$html.=htmlspecialchars(strstr($vars['playlist'],'A',true),2).'/embed';}else{$html.='/news/av/embed/'.htmlspecialchars($vars['id'],2);}$html.='"></iframe></span></span>';

		return $html;
	}

	protected static function renderCbsnews($vars)
	{
		$vars+=['id'=>null,'pid'=>null];$html='<span data-s9e-mediaembed="cbsnews" style="display:inline-block;width:100%;max-width:640px"><span';if((strpos($vars['id'],'-')!==false)){$html.=' style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="https://www.cbsnews.com/embed/videos/'.htmlspecialchars($vars['id'],2).'/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe>';}elseif(isset($vars['pid'])){$html.=' style="display:block;overflow:hidden;position:relative;padding-bottom:62.1875%;padding-bottom:calc(56.25% + 38px)"><object data="//www.cbsnews.com/common/video/cbsnews_player.swf" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"><param name="flashvars" value="pType=embed&amp;si=254&amp;pid='.htmlspecialchars($vars['pid'],2).'"></object>';}else{$html.=' style="display:block;overflow:hidden;position:relative;padding-bottom:62.5%;padding-bottom:calc(56.25% + 40px)"><object data="//i.i.cbsi.com/cnwk.1d/av/video/cbsnews/atlantis2/cbsnews_player_embed.swf" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"><param name="flashvars" value="si=254&amp;contentValue='.htmlspecialchars($vars['id'],2).'"></object>';}$html.='</span></span>';

		return $html;
	}

	protected static function renderDemocracynow($vars)
	{
		$vars+=['id'=>null];$html='<span data-s9e-mediaembed="democracynow" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.democracynow.org/embed/';if((strpos($vars['id'],'/headlines')!==false)){$html.='headlines/'.htmlspecialchars(strstr($vars['id'],'/headlines',true),2);}elseif((strpos($vars['id'],'2')===0)){$html.='story/'.htmlspecialchars($vars['id'],2);}elseif((strpos($vars['id'],'shows/')===0)){$html.='show/'.htmlspecialchars(substr(strstr($vars['id'],'/'),1),2);}else{$html.=htmlspecialchars($vars['id'],2);}$html.='"></iframe></span></span>';

		return $html;
	}

	protected static function renderDumpert($vars)
	{
		$vars+=['id'=>null];$html='<span data-s9e-mediaembed="dumpert" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.dumpert.nl/embed/'.htmlspecialchars(strtr($vars['id'],'_','/'),2).'/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>';

		return $html;
	}

	protected static function renderGametrailers($vars)
	{
		$vars+=['id'=>null];$html='<span data-s9e-mediaembed="gametrailers" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//';if((strpos($vars['id'],'mgid:')===0)){$html.='media.mtvnservices.com/embed/'.htmlspecialchars($vars['id'],2);}else{$html.='embed.gametrailers.com/embed/'.htmlspecialchars($vars['id'],2).'?embed=1&amp;suppressBumper=1';}$html.='"></iframe></span></span>';

		return $html;
	}

	protected static function renderKickstarter($vars)
	{
		$vars+=['id'=>null,'video'=>null];$html='<span data-s9e-mediaembed="kickstarter"';if(isset($vars['video'])){$html.=' style="display:inline-block;width:100%;max-width:480px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:75%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($vars['id'],2).'/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';}else{$html.=' style="display:inline-block;width:100%;max-width:220px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($vars['id'],2).'/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';}$html.='</span>';

		return $html;
	}

	protected static function renderMedium($vars)
	{
		$vars+=['id'=>null];$html='<iframe data-s9e-mediaembed="medium" allowfullscreen="" onload="window.addEventListener(\'message\',function(a){a=a.data.split(\'::\');\'m\'===a[0]&amp;&amp;0&lt;src.indexOf(a[1])&amp;&amp;a[2]&amp;&amp;(style.height=a[2]+\'px\')})" scrolling="no" src="https://api.medium.com/embed?type=story&amp;path=%2F%2F'.htmlspecialchars($vars['id'],2).'&amp;id='.htmlspecialchars(strtr($vars['id'],'abcdef','111111'),2).'" style="border:1px solid;border-color:#eee #ddd #bbb;border-radius:5px;box-shadow:rgba(0,0,0,.15) 0 1px 3px;height:400px;max-width:400px;width:100%"></iframe>';

		return $html;
	}

	protected static function renderOrfium($vars)
	{
		$vars+=['album_id'=>null,'playlist_id'=>null,'set_id'=>null,'track_id'=>null];$html='<iframe data-s9e-mediaembed="orfium" allowfullscreen="" scrolling="no" src="https://www.orfium.com/embedded/';if(isset($vars['album_id'])){$html.='album/'.htmlspecialchars($vars['album_id'],2);}elseif(isset($vars['playlist_id'])){$html.='playlist/'.htmlspecialchars($vars['playlist_id'],2);}elseif(isset($vars['set_id'])){$html.='live-set/'.htmlspecialchars($vars['set_id'],2);}else{$html.='track/'.htmlspecialchars($vars['track_id'],2);}$html.='" style="border:0;height:';if(isset($vars['album_id'])){$html.='550';}else{$html.='275';}$html.='px;max-width:900px;width:100%"></iframe>';

		return $html;
	}

	protected static function renderPinterest($vars)
	{
		$vars+=['id'=>null];$html='<iframe data-s9e-mediaembed="pinterest" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/pinterest.min.html#'.htmlspecialchars($vars['id'],2).'" style="border:0;height:360px;max-width:';if((strpos($vars['id'],'/')!==false)){$html.='730';}else{$html.='345';}$html.='px;width:100%"></iframe>';

		return $html;
	}

	protected static function renderSoundcloud($vars)
	{
		$vars+=['id'=>null,'playlist_id'=>null,'secret_token'=>null,'track_id'=>null];$html='<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=';if(isset($vars['playlist_id'])){$html.='https%3A//api.soundcloud.com/playlists/'.htmlspecialchars($vars['playlist_id'],2);}elseif(isset($vars['track_id'])){$html.='https%3A//api.soundcloud.com/tracks/'.htmlspecialchars($vars['track_id'],2).'&amp;secret_token='.htmlspecialchars($vars['secret_token'],2);}else{if((strpos($vars['id'],'://')===false)){$html.='https%3A//soundcloud.com/';}$html.=htmlspecialchars($vars['id'],2);}$html.='" style="border:0;height:';if(isset($vars['playlist_id'])||(strpos($vars['id'],'/sets/')!==false)){$html.='450';}else{$html.='166';}$html.='px;max-width:900px;width:100%"></iframe>';

		return $html;
	}

	protected static function renderSpotify($vars)
	{
		$vars+=['id'=>null,'path'=>null];$html='<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allow="encrypted-media" allowfullscreen="" scrolling="no" src="https://open.spotify.com/embed/'.htmlspecialchars(strtr($vars['id'],':','/').$vars['path'],2).'" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>';

		return $html;
	}

	protected static function renderTed($vars)
	{
		$vars+=['id'=>null];$html='<span data-s9e-mediaembed="ted" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//embed.ted.com/'.htmlspecialchars($vars['id'],2);if((strpos($vars['id'],'.html')===false)){$html.='.html';}$html.='"></iframe></span></span>';

		return $html;
	}
}