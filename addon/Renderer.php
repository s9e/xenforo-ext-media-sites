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
		$vars+=['id'=>null,'tld'=>null];$options=XF::options();$html='<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-';if($vars['tld']==='jp'){$html.='fe';}elseif(isset($vars['tld'])&&(strpos('desfrinituk',$vars['tld'])!==false)){$html.='eu';}else{$html.='na';}$html.='.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=';switch($vars['tld']){case'ca':$html.='15&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_CA,2);break;case'de':$html.='3&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_DE,2);break;case'es':$html.='30&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_ES,2);break;case'fr':$html.='8&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_FR,2);break;case'in':$html.='31&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_IN,2);break;case'it':$html.='29&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_IT,2);break;case'jp':$html.='9&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_JP,2);break;case'uk':$html.='2&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG_UK,2);break;default:$html.='1&amp;t='.htmlspecialchars($options->s9e_MediaSites_AMAZON_ASSOCIATE_TAG,2);}$html.='&amp;asins='.htmlspecialchars($vars['id'],2).'"></iframe></span></span>';

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

	protected static function renderGametrailers($vars)
	{
		$vars+=['id'=>null];$html='<span data-s9e-mediaembed="gametrailers" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//';if((strpos($vars['id'],'mgid:')===0)){$html.='media.mtvnservices.com/embed/'.htmlspecialchars($vars['id'],2);}else{$html.='embed.gametrailers.com/embed/'.htmlspecialchars($vars['id'],2).'?embed=1&amp;suppressBumper=1';}$html.='"></iframe></span></span>';

		return $html;
	}

	protected static function renderKickstarter($vars)
	{
		$vars+=['id'=>null,'video'=>null];$html='<span data-s9e-mediaembed="kickstarter"';if(isset($vars['video'])){$html.=' style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($vars['id'],2).'/widget/video.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';}else{$html.=' style="display:inline-block;width:100%;max-width:220px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($vars['id'],2).'/widget/card.html" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span>';}$html.='</span>';

		return $html;
	}

	protected static function renderMedium($vars)
	{
		$vars+=['id'=>null];$html='<iframe data-s9e-mediaembed="medium" allowfullscreen="" onload="window.addEventListener(\'message\',function(a){a=a.data.split(\'::\');\'m\'===a[0]&amp;&amp;0&lt;src.indexOf(a[1])&amp;&amp;a[2]&amp;&amp;(style.height=a[2]+\'px\')})" scrolling="no" src="https://api.medium.com/embed?type=story&amp;path=%2F%2F'.htmlspecialchars($vars['id'],2).'&amp;id='.htmlspecialchars(strtr($vars['id'],'abcdef','111111'),2).'" style="border:1px solid;border-color:#eee #ddd #bbb;border-radius:5px;box-shadow:rgba(0,0,0,.15) 0 1px 3px;height:400px;max-width:400px;width:100%"></iframe>';

		return $html;
	}
}