<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
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
		'facebook'    => ['(^(?<user>\\w+)/(?<type>p)osts/(?<id>\\d+))'],
		'twitch'      => [
			'(^(?<channel>\\w+)$)',
			'(^(?<video_id>\\d+):(?<t>[\\dhms]+)$)',
			'(^clip:(?<clip_id>\\w+))'
		],
		'vimeo'       => ['(^(?<id>\\d+):(?<t>[\\dhms]+))'],
		'wistia'      => ['(/medias/(?<id>\\w+))'],
		'youtube'     => ['(^(?<id>[-\\w]+):(?<t>\\d+))']
	];

	/**
	* @var array
	*/
	protected static $defaultValues = [
		'getty'=>['height'=>360,'width'=>640],
		'gfycat'=>['height'=>360,'width'=>640],
		'gifs'=>['height'=>360,'width'=>640],
		'giphy'=>['height'=>360,'width'=>640],
		'internetarchive'=>['height'=>360,'width'=>640]
	];

	/**
	* @var array
	*/
	protected static $filters = [
		'getty'=>['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']],
		'gfycat'=>['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']],
		'gifs'=>['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']],
		'giphy'=>['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']],
		'internetarchive'=>['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']],
		'vimeo'=>['t'=>['s9e\\MediaSites\\Helper::filterTimestamp']],
		'youtube'=>['id'=>['s9e\\MediaSites\\Helper::filterIdentifier'],'t'=>['s9e\\MediaSites\\Helper::filterTimestamp']]
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

		if (isset(self::$defaultValues[$siteId]))
		{
			$vars += self::$defaultValues[$siteId];
		}

		// Use a PHP renderer if applicable
		$callback = __CLASS__ . '::render' . ucfirst($siteId);
		if (is_callable($callback))
		{
			return call_user_func($callback, $vars);
		}

		// Use XenForo's default template
		$html = @XF::app()->templater()->renderTemplate('public:_media_site_embed_' . $siteId, $vars);
		if (empty($html))
		{
			$html = '<div class="blockMessage blockMessage--error blockMessage--iconic">Template <b>public:_media_site_embed_' . $siteId . '</b> not found. Try rebuilding or reinstalling the s9e/MediaSites add-on.</div>';
		}

		return $html;
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

		if (isset(self::$filters[$siteId]))
		{
			foreach (self::$filters[$siteId] as $attrName => $filters)
			{
				if (!isset($vars[$attrName]))
				{
					continue;
				}
				foreach ($filters as $filter)
				{
					$vars[$attrName] = $filter($vars[$attrName]);
					if ($vars[$attrName] === false)
					{
						unset($vars[$attrName]);
						break;
					}
				}
			}
		}

		return $vars;
	}

	protected static function renderBbcnews($vars)
	{
		$vars+=['id'=>null,'playlist'=>null];$html='<span data-s9e-mediaembed="bbcnews"><span><iframe allowfullscreen="" scrolling="no" src="//www.bbc.com/news/av/video-';if((strpos($vars['playlist'],'/news/')===0)){$html.=htmlspecialchars(substr(strstr($vars['playlist'],'/news/'),6),2);}elseif((strpos($vars['id'],'/')!==false)){$html.=htmlspecialchars(substr(strstr($vars['id'],'/'),1),2);}else{$html.=htmlspecialchars($vars['id'],2);}$html.='/embed"></iframe></span></span>';

		return $html;
	}

	protected static function renderDemocracynow($vars)
	{
		$vars+=['id'=>null];$html='<span data-s9e-mediaembed="democracynow"><span><iframe allowfullscreen="" scrolling="no" src="//www.democracynow.org/embed/';if((strpos($vars['id'],'/headlines')!==false)){$html.='headlines/'.htmlspecialchars(strstr($vars['id'],'/headlines',true),2);}elseif((strpos($vars['id'],'2')===0)){$html.='story/'.htmlspecialchars($vars['id'],2);}elseif((strpos($vars['id'],'shows/')===0)){$html.='show/'.htmlspecialchars(substr(strstr($vars['id'],'/'),1),2);}else{$html.=htmlspecialchars($vars['id'],2);}$html.='"></iframe></span></span>';

		return $html;
	}

	protected static function renderKickstarter($vars)
	{
		$vars+=['id'=>null,'video'=>null];$html='<span data-s9e-mediaembed="kickstarter" style="width:';if(isset($vars['video'])){$html.='64';}else{$html.='22';}$html.='0px"><span';if(isset($vars['video'])){$html.='><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($vars['id'],2).'/widget/video.html"></iframe>';}else{$html.=' style="padding-bottom:190.909091%"><iframe allowfullscreen="" scrolling="no" src="//www.kickstarter.com/projects/'.htmlspecialchars($vars['id'],2).'/widget/card.html"></iframe>';}$html.='</span></span>';

		return $html;
	}

	protected static function renderSpreaker($vars)
	{
		$vars+=['episode_id'=>null,'show_id'=>null];$html='<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" scrolling="no" src="https://widget.spreaker.com/player?episode_id='.htmlspecialchars($vars['episode_id'],2).'&amp;show_id='.htmlspecialchars($vars['show_id'],2).'" style="height:'.htmlspecialchars(400-200*isset($vars['episode_id']),2).'px;width:900px"></iframe>';

		return $html;
	}
}