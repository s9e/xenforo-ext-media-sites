<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use Exception;
use XF;
use XF\BbCode\Helper\Flickr;
use XF\Entity\BbCodeMediaSite;
use XF\Repository\BbCodeMediaSite as MediaRepository;
use XF\Repository\Unfurl;
use XF\Util\File;
use const CURLOPT_ENCODING, CURLOPT_FOLLOWLOCATION, CURLOPT_HEADER, CURLOPT_HTTPHEADER, 
CURLOPT_PROXY, CURLOPT_RETURNTRANSFER, CURLOPT_SSL_VERIFYPEER, CURLOPT_URL;
use function array_flip, array_intersect_key, array_keys, call_user_func, curl_exec, curl_init, curl_setopt, file_exists, file_get_contents, file_put_contents, function_exists, implode, is_callable, is_numeric, ksort, md5, preg_match, preg_match_all, preg_replace, preg_replace_callback, serialize, sort, str_replace, strlen, strtolower, strtr, trim, ucfirst, urlencode;

class Parser
{
	/**
	* @var array Volatile cache used for scraping
	*/
	protected static $cache = [];

	/**
	* @var CurlHandle|resource
	*/
	protected static $curl;

	/**
	* @var array
	*/
	protected static $customFormats = [
		'dailymotion' => ['$id:$t'],
		'facebook'    => [
			'$user/$posts/$id',
			'story_fbid=$id:id=$page_id'
		],
		'soundcloud'  => [
			'$id#track_id=$track_id',
			'$id#playlist_id=$playlist_id',
			'$id#playlist_id=$playlist_id;track_id=$track_id'
		],
		'twitch'      => ['$channel', 'clip:$clip_id', '$video_id:$t'],
		'vimeo'       => ['$id:$h', '$id:$h:$t', '$id:$t'],
		'youtube'     => ['$id:$t', '$id, list: $list', '$id:$t, list: $list']
	];

	/**
	* @var array
	*/
	protected static $sites = [
		'abcnews'=>[['!abcnews\\.go\\.com/(?:video/embed\\?id=|[^/]+/video/[^/]+-)(?<id>\\d+)!']],
		'acast'=>[['@play\\.acast\\.com/s/(?<show_id>[-.\\w]+)/(?<episode_id>[-.\\w]+)(?:\\?seek=(?<t>\\d+))?@','@shows\\.acast\\.com/(?<show_id>[-.\\w]+)/(?:episodes/)?(?<episode_id>[-.\\w]+)(?:\\?seek=(?<t>\\d+))?@'],[],[['extract'=>['@"showId":"(?<show_id>[-0-9a-f]+)@','@"id":"(?<episode_id>[-0-9a-f]+)@'],'match'=>['@play\\.acast\\.com/s/[-.\\w]+/.@','@shows\\.acast\\.com/[-.\\w]+/.@'],'url'=>'https://feeder.acast.com/api/v1/shows/{@show_id}/episodes/{@episode_id}']]],
		'anchor'=>[['@anchor.fm/(?:[-\\w]+/)*?episodes/(?:[-\\w]+-)(?<id>\\w+)(?![-\\w])@']],
		'applepodcasts'=>[['@podcasts\\.apple\\.com/(?<country>\\w+)/podcast/[-\\w%]*/id(?<podcast_id>\\d+)(?:\\?i=(?<episode_id>\\d+))?@']],
		'audioboom'=>[['!audioboo(?:\\.f|m\\.co)m/(?:boo|post)s/(?<id>\\d+)!']],
		'audiomack'=>[['!audiomack\\.com/(?<mode>album|song)/(?<artist>[-\\w]+)/(?<title>[-\\w]+)!','!audiomack\\.com/(?<artist>[-\\w]+)/(?<mode>album|song)/(?<title>[-\\w]+)!']],
		'audius'=>[[],[],[['extract'=>['!"id"\\s*:\\s*"(?<track_id>\\w+)"!'],'match'=>["@audius\\.co/(?!v1/)(?'user'[-.\\w]+)/(?!album/|playlist/)(?'slug'[%\\-.\\w]+)@"],'url'=>'https://discoveryprovider.audius.co/v1/resolve?app_name=s9e-textformatter&url=/{@user}/{@slug}'],['extract'=>['!"id"\\s*:\\s*"(?<album_id>\\w+)"!'],'match'=>["@audius\\.co/(?!v1/)(?'user'[-.\\w]+)/album/(?'slug'[%\\-.\\w]+)@"],'url'=>'https://discoveryprovider.audius.co/v1/resolve?app_name=s9e-textformatter&url=/{@user}/album/{@slug}'],['extract'=>['!"id"\\s*:\\s*"(?<playlist_id>\\w+)"!'],'match'=>["@audius\\.co/(?!v1/)(?'user'[-.\\w]+)/playlist/(?'slug'[%\\-.\\w]+)@"],'url'=>'https://discoveryprovider.audius.co/v1/resolve?app_name=s9e-textformatter&url=/{@user}/playlist/{@slug}']]],
		'bandcamp'=>[[],[],[['extract'=>['!/album=(?<album_id>\\d+)!'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['!bandcamp\\.com/album/.!']],['extract'=>['!(?:"|&quot;)album_id(?:"|&quot;):(?<album_id>\\d+)!','!(?:"|&quot;)track_num(?:"|&quot;):(?<track_num>\\d+)!','!/track=(?<track_id>\\d+)!'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['!bandcamp\\.com/track/.!']]]],
		'bbcnews'=>[['@bbc\\.co(?:m|\\.uk)/news/(?:av|video_and_audio)/(?:\\w+-)+(?<id>\\d+)@','@bbc\\.co(?:m|\\.uk)/news/(?:av|video_and_audio)/embed/(?<id>\\w+/\\d+)@','@bbc\\.co(?:m|\\.uk)/news/(?:av|video_and_audio)/\\w+/(?<id>\\d+)@','@bbc\\.co(?:m|\\.uk)/news/av-embeds/(?<id>\\d+)@']],
		'bitchute'=>[['@bitchute\\.com/(?:embed|video)/(?<id>[-\\w]+)@']],
		'bluesky'=>[['#^https://(?<embedder>[.\\w]+)/oembed.*?url=(?<url>[\\w%.]+)#'],['embedder','url'],[['extract'=>['#https://(?<embedder>[.\\w]+)/oembed.*?url=(?<url>[\\w%.]+)#'],'match'=>['#/profile/[^/]+/post/.#']]],['embedder'=>['s9e\\MediaSites\\Helper::filterBlueskyEmbedder'],'url'=>['urldecode','s9e\\MediaSites\\Helper::filterBlueskyUrl']]],
		'brightcove'=>[['@link\\.brightcove\\.com/services/player/bcpid(?<bcpid>\\d+).*?bckey=(?<bckey>[-,~\\w]+).*?bctid=(?<bctid>\\d+)@','@players\\.brightcove\\.net/(?<bcpid>\\d+)/.*?videoId=(?<bctid>\\d+)@']],
		'bunny'=>[['@/(?:embed|play)/(?<video_library_id>\\d+)/(?<video_id>[-\\w]+)@']],
		'captivate'=>[['@//player\\.captivate\\.fm/episode/(?<id>[-\\w]+)(?:\\?t=(?<t>\\d+))?@'],[],[['extract'=>['@//player\\.captivate\\.fm/episode/(?<id>[-\\w]+)@'],'match'=>['@//(?!player\\.)[-\\w]+\\.captivate\\.fm/episode/.@']]]],
		'castos'=>[['@(?<host>[-\\w]+)\\.castos\\.com/player/(?<id>\\d+)@'],[],[['extract'=>['@(?<host>[-\\w]+)\\.castos\\.com/player/(?<id>\\d+)@'],'match'=>['@castos\\.com/(?:podcasts/[^/]*+/)?episodes/.@']]]],
		'cbsnews'=>[['#cbsnews\\.com/videos?/(?!watch/)(?<id>[-\\w]+)#','#cbsnews\\.com/video/watch/\\?id=(?<id>\\d+)#']],
		'clyp'=>[['@clyp\\.it/(?!user/)(?<id>\\w+)@']],
		'cnbc'=>[['!cnbc\\.com/gallery/\\?video=(?<id>\\d+)!'],[],[['extract'=>['!byGuid=(?<id>\\d+)!'],'match'=>['!cnbc\\.com/video/20\\d\\d/\\d\\d/\\d\\d/\\w!']]]],
		'cnn'=>[['!cnn.com/videos/(?<id>.*\\.cnn)!','!cnn\\.com/video/data/2\\.0/video/(?<id>.*\\.cnn)!']],
		'cnnmoney'=>[['!money\\.cnn\\.com/video/(?<id>.*\\.cnnmoney)!']],
		'codepen'=>[['!codepen\\.io/(?<user>[-\\w]+)/(?:details|embed|full|live|pen)/(?<id>\\w+)!']],
		'comedycentral'=>[[],[],[['extract'=>['!(?<id>mgid:arc:(?:episode|video):[.\\w]+:[-\\w]+)!'],'match'=>['!c(?:c|omedycentral)\\.com/(?:full-episode|video-clip)s/!']]]],
		'coub'=>[['!coub\\.com/view/(?<id>\\w+)!']],
		'dailymotion'=>[['!dai\\.ly/(?<id>[a-z0-9]+)!i','!dailymotion\\.com/(?:live/|swf/|user/[^#]+#video=|(?:related/\\d+/)?video/)(?<id>[a-z0-9]+)!i','!start=(?<t>\\d+)!']],
		'democracynow'=>[['!democracynow.org/(?:embed/)?(?<id>(?:\\w+/)?\\d+/\\d+/\\d+(?:/\\w+)?)!'],[],[['extract'=>["!democracynow\\.org/(?<id>(?:\\w+/)?\\d+/\\d+/\\d+(?:/\\w+)?)' rel='canonical!"],'match'=>['!m\\.democracynow\\.org/stories/\\d!']]]],
		'dumpert'=>[['!dumpert\\.nl/(?:item|mediabase)/(?<id>\\d+[/_]\\w+)!']],
		'eighttracks'=>[['!8tracks\\.com/[-\\w]+/(?<id>\\d+)(?=#|$)!'],[],[['extract'=>['!eighttracks://mix/(?<id>\\d+)!'],'match'=>['!8tracks\\.com/[-\\w]+/\\D!']]]],
		'espn'=>[['#video/(?:clip(?:\\?id=|/_/id/))?(?<id>\\d+)#']],
		'facebook'=>[['@facebook\\.com/.*?(?:fbid=|/permalink/|\\?v=)(?<id>\\d+)@','@facebook\\.com/(?<user>[.\\w]+)/(?<type>[pv])(?:ost|ideo)s?/(?:[-%.\\w]+/)?(?<id>\\d+)\\b@','@facebook\\.com/video/(?=post|video)(?<type>[pv])@','@facebook\\.com/events/(?<id>\\d+)\\b(?!/permalink)@','@facebook\\.com/watch/\\?(?<type>[pv])=@','@facebook.com/groups/[^/]*/(?<type>p)osts/(?<id>\\d+)@','@facebook\\.com/(?<user>[.\\w]+)/posts/pfbid(?<pfbid>\\w+)@','@facebook\\.com/permalink\\.php\\?story_fbid=(?:(?<id>\\d+)|pfbid(?<pfbid>\\w+))&id=(?<page_id>\\d+)@','@facebook\\.com/(?<type>r)eel/(?<id>\\d+)@'],[],[['extract'=>['@facebook\\.com/(?<user>[.\\w]+)/(?<type>[pv])\\w+/(?<id>\\d+)(?!\\w)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@facebook\\.com/[.\\w]+/posts/pfbid@'],'url'=>'https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2F{@user}%2Fposts%2Fpfbid{@pfbid}'],['extract'=>['@story_fbid=(?<id>\\d+)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>["@facebook\\.com/permalink\\.php\\?story_fbid=pfbid(?'pfbid'\\w+)&id=(?'page_id'\\d+)@"],'url'=>'https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2Fpermalink.php%3Fstory_fbid%3Dpfbid{@pfbid}%26id%3D{@page_id}'],['extract'=>['@facebook\\.com/watch/\\?(?<type>v)=(?<id>\\d+)@','@facebook\\.com/(?<user>[.\\w]+)/(?<type>v)ideos/(?<id>\\d+)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@fb\\.watch/.@']],['extract'=>['@facebook\\.com/\\w+/(?<user>[.\\w]+)/permalink/(?<id>\\d+)(?!\\w)@','@og:url[^>]+facebook\\.com/(?<user>[.\\w]+)/(?<type>[pv])(?:ost|ideo)s?/(?:[-\\w%]+/)?(?<id>\\d+)\\b@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@facebook\\.com/share/[pv]/\\w@']]]],
		'falstad'=>[['!falstad\\.com/circuit/circuitjs\\.html\\?c(?:ct=(?<cct>[^&]+)|tz=(?<ctz>[-+=\\w]+))!']],
		'flickr'=>[['@flickr\\.com/photos/[^/]+/(?<id>\\d+)@','@flic\\.kr/(?!p/)[^/]+/(?<id>\\d+)@'],[],[['extract'=>['@flickr\\.com/photos/[^/]+/(?<id>\\d+)@'],'match'=>["@flic\\.kr/p/(?'short'\\w+)@"],'url'=>'https://www.flickr.com/photo.gne?rb=1&short={@short}']]],
		'foxnews'=>[['!video\\.foxnews\\.com/v/(?<id>\\d+)!']],
		'funnyordie'=>[['!funnyordie\\.com/videos/(?<id>[0-9a-f]+)!']],
		'gamespot'=>[['!gamespot\\.com.*?/(?:events|videos)/.*?-(?<id>\\d+)/(?:[#?].*)?$!']],
		'getty'=>[[],['et','sig'],[['extract'=>['!/embed/(?<id>\\d+)!','!"height":[ "]*(?<height>\\d+)!','!"width":[ "]*(?<width>\\d+)!','!\\?et=(?<et>[-=\\w]+)!','!\\\\u0026sig=(?<sig>[-=\\w]+)!'],'match'=>["!(?:gty\\.im|gettyimages\\.[.\\w]+/detail(?=/).*?)/(?'id'\\d+)!"],'url'=>'https://embed.gettyimages.com/preview/{@id}']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'gifs'=>[['!gifs\\.com/(?:gif/)?(?<id>\\w+)!'],[],[['extract'=>['!meta property="og:image:width" content="(?<width>\\d+)!','!meta property="og:image:height" content="(?<height>\\d+)!'],'match'=>['//'],'url'=>'https://gifs.com/gif/{@id}']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'giphy'=>[['!giphy\\.com/(?<type>gif|video|webp)\\w+/(?:[-\\w]+-)*(?<id>\\w+)!','!giphy\\.com/media/(?<id>\\w+)/\\w+\\.(?<type>gif|webp)!','!i\\.giphy\\.com/(?<id>\\w+)\\.(?<type>gif|webp)!'],[],[['extract'=>['!"height"\\s*:\\s*(?<height>\\d+)!','!"width"\\s*:\\s*(?<width>\\d+)!'],'header'=>['Accept: */*','User-agent: PHP'],'match'=>['//'],'url'=>'https://giphy.com/services/oembed?url=https://media.giphy.com/media/{@id}/giphy.gif']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'gist'=>[['@gist\\.github\\.com/(?<id>(?:[-\\w]+/)?[\\da-f]+(?:/[\\da-f]+)?\\b(?!/archive))@']],
		'globalnews'=>[['!globalnews\\.ca/video/(?<id>\\d+)!'],[],[['extract'=>['!globalnews\\.ca/video/(?<id>\\d+)!'],'match'=>['!globalnews\\.ca/video/rd/!']]]],
		'gofundme'=>[['@gofundme\\.com/(?<id>\\w+)(?![^#?])@']],
		'googledrive'=>[['!drive\\.google\\.com/.*?(?:file/d/|id=)(?<id>[-\\w]+)!']],
		'googleplus'=>[['!//plus\\.google\\.com/(?:u/\\d+/)?(?:\\+(?<name>[^/]+)|(?<oid>\\d+))/posts/(?<pid>\\w+)!'],[],[],['name'=>['urldecode']]],
		'googlesheets'=>[['@docs\\.google\\.com/spreadsheet(?:/ccc\\?key=|(?:[^e]/)+)(?<id>(?:e/)?[-\\w]+)@','@oid=(?<oid>\\d+)@','@#gid=(?<gid>\\d+)@','@/pub(?<type>chart)@']],
		'hudl'=>[['!hudl\\.com/athlete/(?<athlete>\\d+)/highlights/(?<highlight>[\\da-f]+)!','!hudl\\.com/video/\\d+/(?<athlete>\\d+)/(?<highlight>[\\da-f]+)!','@hudl\\.com/video/(?<id>\\w+)(?![\\w/])@'],[],[['extract'=>['!hudl\\.com/video/\\d+/(?<athlete>\\d+)/(?<highlight>[\\da-f]+)!','@hudl\\.com/video/(?<id>\\w+)(?![\\w/])@'],'match'=>['!hudl\\.com/v/!']]]],
		'hulu'=>[[],[],[['extract'=>['!eid=(?<id>[-\\w]+)!'],'match'=>['!hulu\\.com/watch/!']]]],
		'ign'=>[['!(?<id>https?://.*?ign\\.com/videos/.+)!i']],
		'imdb'=>[[],[],[['extract'=>['!imdb\\.com/[/\\w]+?/vi(?<id>\\d+)!'],'match'=>["!imdb\\.com/[/\\w]+?/vi(?'id'\\d+)!"],'url'=>'https://www.imdb.com/video/embed/vi{@id}/']]],
		'imgur'=>[['@imgur\\.com/(?<id>a/\\w+)@','@i\\.imgur\\.com/(?<id>\\w{5,7})[lms]?\\.@','@imgur\\.com/(?!upload\\b)(?<id>\\w+)(?![\\w./])@'],[],[['extract'=>['@data-id="(?<id>[\\w/]+)"@'],'match'=>["@imgur\\.com/(?![art]/|user/)(?'path'(?:gallery/)?\\w+)(?![\\w.])@"],'url'=>'https://api.imgur.com/oembed.xml?url=/{@path}']]],
		'indiegogo'=>[['!indiegogo\\.com/projects/(?<id>[-\\w]+)!']],
		'instagram'=>[['!instagram\\.com/(?:[.\\w]+/)?(?:p|reel|tv)/(?<id>[-\\w]+)!']],
		'internetarchive'=>[[],[],[['extract'=>['!meta property="twitter:player" content="https://archive.org/embed/(?<id>[^/"]+)!','!meta property="og:video:width" content="(?<width>\\d+)!','!meta property="og:video:height" content="(?<height>\\d+)!'],'match'=>['!archive\\.org/(?:details|embed)/!']]],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'id'=>['htmlspecialchars_decode'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'izlesene'=>[['!izlesene\\.com/video/[-\\w]+/(?<id>\\d+)!']],
		'jsfiddle'=>[['@jsfiddle.net/(?:(?<user>\\w+)/)?(?!\\d+\\b|embedded\\b|show\\b)(?<id>\\w+)\\b(?:/(?<revision>\\d+)\\b)?@']],
		'jwplatform'=>[['!jwplatform\\.com/\\w+/(?<id>[-\\w]+)!']],
		'kaltura'=>[['@/p(?:artner_id)?/(?<partner_id>\\d+)/@','@/sp/(?<sp>\\d+)/@','@/uiconf_id/(?<uiconf_id>\\d+)/@','@\\bentry_id[=/](?<entry_id>\\w+)@'],['entry_id','partner_id','uiconf_id'],[['extract'=>['@kaltura\\.com/p/(?<partner_id>\\d+)/sp/(?<sp>\\d+)/\\w*/uiconf_id/(?<uiconf_id>\\d+)(?:/.*?\\bentry_id=(?<entry_id>\\w+))?@','@/entry_id/(?<entry_id>\\w+)@'],'match'=>['@kaltura\\.com/(?:media|tiny)/.@']]]],
		'khl'=>[[],[],[['extract'=>['!/feed/start/(?<id>[/\\w]+)!'],'match'=>['!video\\.khl\\.ru/(?:event|quote)s/\\d!']]]],
		'kickstarter'=>[['!kickstarter\\.com/projects/(?<id>[^/]+/[^/?]+)(?:/widget/(?:(?<card>card)|(?<video>video)))?!']],
		'libsyn'=>[[],[],[['extract'=>['!embed/episode/id/(?<id>\\d+)!'],'match'=>['@(?!\\.mp3)....$@']]]],
		'liveleak'=>[['!liveleak\\.com/(?:e/|view\\?i=)(?<id>\\w+)!'],[],[['extract'=>['!liveleak\\.com/e/(?<id>\\w+)!'],'match'=>['!liveleak\\.com/view\\?t=!']]]],
		'livestream'=>[['!livestream\\.com/accounts/(?<account_id>\\d+)/events/(?<event_id>\\d+)!','!/videos/(?<video_id>\\d+)!','!original\\.livestream\\.com/(?<channel>\\w+)/video\\?clipId=(?<clip_id>[-\\w]+)!'],[],[['extract'=>['!accounts/(?<account_id>\\d+)/events/(?<event_id>\\d+)!'],'match'=>['@livestream\\.com/(?!accounts/\\d+/events/\\d)@']],['extract'=>['!//original\\.livestream\\.com/(?<channel>\\w+)/video/(?<clip_id>[-\\w]+)!'],'match'=>['!livestre.am!']]]],
		'mailru'=>[[],[],[['extract'=>['!"itemId": ?"?(?<id>\\d+)!'],'match'=>['!my\\.mail\\.ru/\\w+/\\w+/video/\\w+/\\d!']]]],
		'mastodon'=>[['#//(?<host>[-.\\w]+)/(?:web/)?(?:@|users/)(?<name>\\w+)/(?:posts/|statuses/)?(?<id>\\d+)#'],['host'],[['extract'=>['#"url":"https://(?<host>[-.\\w]+)/@(?<name>\\w+)/(?<id>\\d+)"#'],'match'=>["#^(?'origin'https://[^/]+)/(?:web/)?(?:@\\w+@[-.\\w]+|statuses)/(?'id'\\d+)#"],'url'=>'{@origin}/api/v1/statuses/{@id}']],['host'=>['s9e\\MediaSites\\Helper::filterMastodonHost']]],
		'medium'=>[['#medium\\.com/(?:s/\\w+/|@?[-\\w]+/)?(?:[%\\w]+-)*(?<id>[0-9a-f]+)(?![%\\w])#']],
		'megaphone'=>[['@megaphone\\.fm/.*?\\?(?:e|selected)=(?<id>\\w+)@','@(?:dcs|player|traffic)\\.megaphone\\.fm/(?<id>\\w+)@','@megaphone\\.link/(?<id>\\w+)@']],
		'metacafe'=>[['!metacafe\\.com/watch/(?<id>\\d+)!']],
		'mixcloud'=>[['@mixcloud\\.com/(?!categories|tag)(?<id>[-\\w]+/[^/&]+)/@']],
		'mlb'=>[['#mlb\\.com/video/(?:[-\\w/]+/)?(?:c-|v|[-\\w]+-c)(?<id>\\d+)#']],
		'mrctv'=>[[],[],[['extract'=>['!mrctv\\.org/embed/(?<id>\\d+)!'],'match'=>['!mrctv\\.org/videos/.!']]]],
		'msnbc'=>[[],[],[['extract'=>['@embedded-video/(?!undefined)(?<id>\\w+)@'],'match'=>['@msnbc\\.com/[-\\w]+/watch/@','@on\\.msnbc\\.com/.@']]]],
		'nachovideo'=>[['!nachovideo\\.com/(?:embed|video)/(?<id>\\d+)\\b!']],
		'natgeochannel'=>[['@channel\\.nationalgeographic\\.com/(?<id>[-/\\w]+/videos/[-\\w]+)@']],
		'natgeovideo'=>[[],[],[['extract'=>['@guid="(?<id>[-\\w]+)"@'],'match'=>['@video\\.nationalgeographic\\.com/(?:tv|video)/\\w@']]]],
		'nbcnews'=>[['!nbcnews\\.com/(?:widget/video-embed/|video/[-\\w]+?-)(?<id>\\d+)!']],
		'nhl'=>[['#nhl\\.com/(?:\\w+/)?video(?:/(?![ct]-)[-\\w]+)?(?:/t-(?<t>\\d+))?(?:/c-(?<c>\\d+))?#']],
		'npr'=>[[],[],[['extract'=>['!player/embed/(?<i>\\d+)/(?<m>\\d+)!'],'header'=>'Cookie: trackingChoice=false; choiceVersion=1','match'=>['!npr\\.org/[/\\w]+/\\d+!','!n\\.pr/\\w!']]]],
		'nytimes'=>[['!nytimes\\.com/video/[a-z]+/(?:[a-z]+/)?(?<id>\\d+)!','!nytimes\\.com/video/\\d+/\\d+/\\d+/[a-z]+/(?<id>\\d+)!'],[],[['extract'=>['!/video/movies/(?<id>\\d+)!'],'match'=>["!nytimes\\.com/movie(?:s/movie)?/(?'playlist'\\d+)/[-\\w]+/trailers!"],'url'=>'http://www.nytimes.com/svc/video/api/playlist/{@playlist}?externalId=true']]],
		'odysee'=>[['#odysee\\.com/(?:\\$/\\w+/)?(?<name>[^:/]+)[:/](?<id>\\w{40})#','#odysee\\.com/(?<path>@[^:/]+:\\w/[^:/]+:\\w)#'],[],[['extract'=>['#"contentUrl".*api/\\w+/streams/\\w+/(?<name>[^/]+)/(?<id>\\w{40})#'],'match'=>['#odysee\\.com/@[^/:]+:\\w+/.#']]],['name'=>['s9e\\MediaSites\\Helper::filterUrl'],'path'=>['s9e\\MediaSites\\Helper::filterUrl']]],
		'on3'=>[['!/db/(?:[-\\w]*-)?(?<id>[0-9]+)/industry-comparison/!']],
		'orfium'=>[['@album/(?<album_id>\\d+)@','@playlist/(?<playlist_id>\\d+)@','@live-set/(?<set_id>\\d+)@','@track/(?<track_id>\\d+)@']],
		'pastebin'=>[['@pastebin\\.com/(?!u/)(?:\\w+(?:\\.php\\?i=|/))?(?<id>\\w+)@']],
		'pinterest'=>[['@pinterest.com/pin/(?<id>\\d+)@','@pinterest.com/(?!_/|discover/|explore/|news_hub/|pin/|search/)(?<id>[-\\w]+/[-\\w]+)@']],
		'podbean'=>[['!podbean\\.com/(?:[-\\w]+/)*(?:player[-\\w]*/|\\w+/pb-)(?<id>[-\\w]+)!'],[],[['extract'=>['!podbean\\.com/player[^/]*/\\?i=(?<id>[-\\w]+)!'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@podbean\\.com/(?:media/shar)?e/(?!pb-)@']]]],
		'prezi'=>[['#//prezi\\.com/(?!(?:a(?:bout|mbassadors)|c(?:o(?:llaborate|mmunity|ntact)|reate)|exp(?:erts|lore)|ip(?:ad|hone)|jobs|l(?:ear|ogi)n|m(?:ac|obility)|pr(?:es(?:s|ent)|icing)|recommend|support|user|windows|your)/)(?<id>\\w+)/#']],
		'reddit'=>[['!(?<id>\\w+/comments/\\w+(?:/\\w+/\\w+)?)!'],[],[['extract'=>['!(?<id>\\w+/comments/\\w+(?:/\\w+/\\w+)?)!'],'header'=>'User-agent: FreeBSD/11.0 Lynx/56','match'=>['!reddit\\.com/r/[^/]+/s/\\w!']]]],
		'rumble'=>[['!rumble\\.com/embed/(?<id>\\w+)!'],[],[['extract'=>['!video"?:"(?<id>\\w+)!'],'match'=>['#rumble\\.com/(?!embed/).#']]]],
		'rutube'=>[['!rutube\\.ru/(?:play/embed/|tracks/.*?v=|video/)(?<id>\\w+)!']],
		'scribd'=>[['!scribd\\.com/(?:mobile/)?(?:doc(?:ument)?|presentation)/(?<id>\\d+)!']],
		'sendvid'=>[['!sendvid\\.com/(?<id>\\w+)!']],
		'slideshare'=>[['!slideshare.net/slideshow/embed_code/key/(?<key>\\w+)$!'],['key'],[['extract'=>['!embed_code/key/(?<key>\\w+)!','!data-slideshow-id="(?<id>\\d+)"!'],'match'=>['@slideshare\\.net/[^/]+/\\w(?![-\\w]+-\\d{6,}$)@']]]],
		'soundcloud'=>[['@https?://(?:api\\.)?soundcloud\\.com/(?!pages/)(?<id>[-/\\w]+/[-/\\w]+|^[^/]+/[^/]+$)@i','@api\\.soundcloud\\.com/playlists/(?<playlist_id>\\d+)@','@api\\.soundcloud\\.com/tracks/(?<track_id>\\d+)(?:\\?secret_token=(?<secret_token>[-\\w]+))?@','@soundcloud\\.com/(?!playlists/|tracks/)[-\\w]+/(?:sets/)?[-\\w]+/(?=s-)(?<secret_token>[-\\w]+)@'],[],[['extract'=>['@soundcloud(?::/)?:tracks:(?<track_id>\\d+)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@soundcloud\\.com/(?!playlists/\\d|tracks/\\d)[-\\w]+/[-\\w]@']],['extract'=>['@soundcloud(?::/)?/playlists:(?<playlist_id>\\d+)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@soundcloud\\.com/[-\\w]+/sets/@']]]],
		'sporcle'=>[['#sporcle.com/framed/.*?gid=(?<id>\\w+)#'],[],[['extract'=>['#encodedGameID\\W+(?<id>\\w+)#'],'match'=>['#sporcle\\.com/games/(?!\\w*category/)[-\\w]+/[-\\w]#']]]],
		'sportsnet'=>[[],[],[['extract'=>['@bc_videos\\s*:\\s*(?<id>\\d+)@'],'match'=>['//']]]],
		'spotify'=>[['!(?:open|play)\\.spotify\\.com/(?:intl-\\w+/|user/[-.\\w]+/)*(?<id>(?:album|artist|episode|playlist|show|track)(?:[:/][-.\\w]+)+)!'],[],[['extract'=>['!(?:open|play)\\.spotify\\.com/(?:intl-\\w+/|user/[-.\\w]+/)*(?<id>(?:album|artist|episode|playlist|show|track)(?:[:/][-.\\w]+)+)!'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['!https?://(?:link\\.tospotify\\.com|spotify\\.link)/.!']]]],
		'spreaker'=>[['!spreaker\\.com/episode/(?<episode_id>\\d+)!'],[],[['extract'=>['!episode_id=(?<episode_id>\\d+)!','!show_id=(?<show_id>\\d+)!'],'match'=>['!spreaker\\.com/(?:show|user)/.!']]]],
		'steamstore'=>[['!store.steampowered.com/app/(?<id>\\d+)!']],
		'strawpoll'=>[['!strawpoll\\.me/(?<id>\\d+)!']],
		'streamable'=>[['!streamable\\.com/(?:e/)?(?<id>\\w+)!']],
		'teamcoco'=>[['!teamcoco\\.com/video/(?<id>\\d+)!'],[],[['extract'=>['!embed/v/(?<id>\\d+)!'],'match'=>['!teamcoco\\.com/video/\\D!']]]],
		'ted'=>[['#ted\\.com/(?<id>(?:talk|playlist)s/[-\\w]+(?:\\.html)?)(?![-\\w]|/transcript)#i']],
		'telegram'=>[['@//t.me/(?!addstickers/|joinchat/)(?:s/)?(?<id>\\w+/\\d+)@']],
		'theatlantic'=>[['!theatlantic\\.com/video/index/(?<id>\\d+)!']],
		'theguardian'=>[['!theguardian\\.com/(?<id>\\w+/video/20(?:0[0-9]|1[0-7])[-/\\w]+)!']],
		'theonion'=>[['!theonion\\.com/video/[-\\w]+[-,](?<id>\\d+)!']],
		'threads'=>[['!threads\\.net/(?:@[-\\w.]+/pos)?t/(?<id>[-\\w]+)!']],
		'tiktok'=>[['#tiktok\\.com/(?:@[.\\w]+/video|v|(?:i18n/)?share/video)/(?<id>\\d+)#'],[],[['extract'=>['#tiktok\\.com/(?:@[.\\w]+/video|v|(?:i18n/)?/share/video)/(?<id>\\d+)#'],'match'=>["#//v[mt]\\.tiktok\\.com/(?'short_id'\\w+)#","#tiktok\\.com/t/(?'short_id'\\w+)#"],'url'=>'https://www.tiktok.com/t/{@short_id}']]],
		'tmz'=>[['@tmz\\.com/videos/(?<id>\\w+)@']],
		'tradingview'=>[['!tradingview\\.com/(?:chart/[^/]+|i)/(?<chart>\\w+)!','!tradingview\\.com/symbols/(?<symbol>[-:\\w]+)!']],
		'traileraddict'=>[[],[],[['extract'=>['@v\\.traileraddict\\.com/(?<id>\\d+)@'],'match'=>['@traileraddict\\.com/(?!tags/)[^/]+/.@']]]],
		'trendingviews'=>[['!(?:mydailyfreedom\\.com|trendingviews\\.com?)/(?:tv/)?(?:embed|videos?)/(?:[^/]+-)?(?<id>\\d+)!']],
		'tumblr'=>[['!(?<name>[-\\w]+)\\.tumblr\\.com/post/(?<id>\\d+)!','!(?:at|www)\\.tumblr\\.com/(?<name>[-\\w]+)/(?<id>\\d+)!'],['did','key','name'],[['extract'=>['!did=(?:\\\\"|\\\\u0022)(?<did>[-\\w]+)!','!embed/post/t:(?<key>[-\\w]+)!'],'header'=>'User-agent: curl','match'=>['!\\w\\.tumblr\\.com/post/\\d!','!(?:at|www)\\.tumblr\\.com/[-\\w]+/\\d+!'],'url'=>'https://www.tumblr.com/oembed/1.0?url=https://{@name}.tumblr.com/post/{@id}']]],
		'twentyfoursevensports'=>[['!247sports\\.com/playersport/[-\\w]*?(?<player_id>\\d+)/embed!i'],[],[['extract'=>['!247sports\\.com/playersport/[-\\w]*?(?<player_id>\\d+)/embed!i'],'header'=>'User-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36','match'=>['!247sports\\.com/Player/[-\\w]*?\\d!i']]]],
		'twitch'=>[['#twitch\\.tv/(?:videos|\\w+/v)/(?<video_id>\\d+)?#','#www\\.twitch\\.tv/(?!videos/)(?<channel>\\w+)(?:/clip/(?<clip_id>[-\\w]+))?#','#t=(?<t>(?:(?:\\d+h)?\\d+m)?\\d+s)#','#clips\\.twitch\\.tv/(?:(?<channel>\\w+)/)?(?<clip_id>[-\\w]+)#']],
		'twitter'=>[['@(?:twitter|x)\\.com/(?:#!/|i/)?\\w+/(?:status(?:es)?|tweet)/(?<id>\\d+)@']],
		'ustream'=>[['!ustream\\.tv/recorded/(?<vid>\\d+)!'],[],[['extract'=>['!embed/(?<cid>\\d+)!'],'match'=>['#ustream\\.tv/(?!explore/|platform/|recorded/|search\\?|upcoming$|user/)(?:channel/)?[-\\w]+#']]]],
		'vbox7'=>[['!vbox7\\.com/play:(?<id>[\\da-f]+)!']],
		'veoh'=>[['!veoh\\.com/(?:m/watch\\.php\\?v=|watch/)v(?<id>\\w+)!']],
		'vevo'=>[['!vevo\\.com/watch/(.*?/)?(?<id>[A-Z]+\\d+)!']],
		'videodetective'=>[['!videodetective\\.com/\\w+/[-\\w]+/(?:trailer/P0*)?(?<id>\\d+)!']],
		'vimeo'=>[['!vimeo\\.com/(?:channels/[^/]+/|video/)?(?<id>\\d+)(?:/(?<h>\\w+))?\\b!','!#t=(?<t>[\\dhms]+)!'],[],[],['t'=>['s9e\\MediaSites\\Helper::filterTimestamp']]],
		'vine'=>[['!vine\\.co/v/(?<id>[^/]+)!']],
		'vk'=>[['!vk(?:\\.com|ontakte\\.ru)/(?:[\\w.]+\\?z=)?video(?<oid>-?\\d+)_(?<vid>\\d+).*?hash=(?<hash>[0-9a-f]+)!','!vk(?:\\.com|ontakte\\.ru)/video_ext\\.php\\?oid=(?<oid>-?\\d+)&id=(?<vid>\\d+)&hash=(?<hash>[0-9a-f]+)!'],[],[['extract'=>['#meta property="og:video" content=".*?oid=(?<oid>-?\\d+).*?id=(?<vid>\\d+).*?hash=(?<hash>[0-9a-f]+)#'],'header'=>'User-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36','match'=>['#^(?!.*?hash=)#']]]],
		'vocaroo'=>[['!voca(?:\\.ro|roo\\.com)/(?:i/)?(?<id>\\w+)!']],
		'vox'=>[['!vox.com/.*#ooid=(?<id>[-\\w]+)!']],
		'washingtonpost'=>[['#washingtonpost\\.com/video/c/\\w+/(?<id>[-0-9a-f]+)#','#washingtonpost\\.com/video/[-/\\w]+/(?<id>[-0-9a-f]+)_video\\.html#']],
		'wavekit'=>[['#play\\.wavekit\\.app/(?:embed|share)/audio/(?<audio_id>\\w+)#','#play\\.wavekit\\.app/(?:embed|share)/playlist/(?<playlist_id>\\w+)#']],
		'wistia'=>[['!wistia.com/(?:(?:embed/iframe|medias)/|.*wmediaid=)(?<id>\\w+)!'],[],[['extract'=>['!"type":"(?:\\w+_)?(?<type>audio)!'],'match'=>['!wistia.com/(?:(?:embed/iframe|medias)/|.*wmediaid=)\\w!'],'url'=>'https://fast.wistia.net/embed/iframe/{@id}']]],
		'wshh'=>[['!worldstar(?:hiphop)?\\.com/(?:emb|featur)ed/(?<id>\\d+)!'],[],[['extract'=>['!(?:v: ?"?|worldstar(?:hiphop)?\\.com/embed/)(?<id>\\d+)!'],'match'=>['!worldstar(?:hiphop)?\\.com/(?:\\w+/)?video\\.php\\?v=\\w+!']]]],
		'wsj'=>[['@wsj\\.com/[^#]*#!(?<id>[-0-9A-F]{36})@','@wsj\\.com/video/[^/]+/(?<id>[-0-9A-F]{36})@'],[],[['extract'=>['@wsj\\.com/video/[^/]+/(?<id>[-0-9A-F]{36})@'],'match'=>['@on\\.wsj\\.com/\\w@']]]],
		'xboxclips'=>[['@(?:gameclips\\.io|xboxclips\\.com)/(?!game/)(?<user>[^/]+)/(?!screenshots/)(?<id>[-0-9a-f]+)@']],
		'xboxdvr'=>[['!(?:gamer|xbox)dvr\\.com/gamer/(?<user>[^/]+)/video/(?<id>\\d+)!']],
		'xenforo'=>[['!^(?<url>https://.*?/)media/albums/(?:[-\\w]+\\.)?(?<xfmg_album_id>\\d+)!','!^(?<url>https://.*?/)(?:members/[-.\\w]+/#profile-post-|profile-posts/)(?<profile_post_id>\\d+)!','!^(?<url>https://.*?/)resources/(?:[-\\w]+\\.)?(?<resource_id>\\d+)!','!^(?<url>https://.*?/)threads/(?:[-\\w]+\\.)?(?<thread_id>\\d+)/(?:page-\\d+)?#?(?:post-(?<post_id>\\d+))?!','!^(?<url>https://.*?/)embed\\.php\\?content=(?<content_id>[-\\w]+)!'],['url'],[],['content_id'=>['s9e\\MediaSites\\Helper::filterIdentifier'],'host'=>['s9e\\MediaSites\\Helper::filterXenForoHost'],'post_id'=>['s9e\\MediaSites\\Helper::filterUint'],'profile_post_id'=>['s9e\\MediaSites\\Helper::filterUint'],'resource_id'=>['s9e\\MediaSites\\Helper::filterUint'],'thread_id'=>['s9e\\MediaSites\\Helper::filterUint'],'url'=>['s9e\\MediaSites\\Helper::filterUrl'],'xfmg_album_id'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'youku'=>[['!youku\\.com/v(?:_show|ideo)/id_(?<id>\\w+=*)!']],
		'youmaker'=>[['!youmaker\\.com/(?:embed|v(?:ideo)?)/(?<id>[-a-z0-9]+)!i']],
		'youtube'=>[['!youtube\\.com/(?:watch.*?v=|(?:embed|live|shorts|v)/|attribution_link.*?v%3D)(?<id>[-\\w]+)!','!youtube-nocookie\\.com/embed/(?<id>[-\\w]+)!','!youtu\\.be/(?<id>[-\\w]+)!','@[#&?]t(?:ime_continue)?=(?<t>\\d[\\dhms]*)@','![&?]list=(?<list>[-\\w]+)!'],[],[['extract'=>['@/embed/(?<id>[-\\w]+)\\?clip=(?<clip>[-\\w]+)&amp;clipt=(?<clipt>[-\\w]+)@'],'match'=>['@youtube\\.com/clip/.@']]],['id'=>['s9e\\MediaSites\\Helper::filterIdentifier'],'t'=>['s9e\\MediaSites\\Helper::filterTimestamp']]]
	];

	/**
	* Match given URL and return a media key
	*
	* @param  string          $url       Original URL
	* @param  string          $matchedId Unused
	* @param  BbCodeMediaSite $site      Media site entity
	* @param  string          $siteId    Site's ID
	* @return string|bool                Media key or FALSE
	*/
	public static function match($url, $matchedId, BbCodeMediaSite $site, $siteId)
	{
		if (empty(static::$sites[$siteId]))
		{
			return false;
		}

		$config = static::$sites[$siteId] + [[], [], [], []];
		$url    = self::normalizeUrl($url);
		$vars   = [];
		self::addNamedCaptures($vars, $url, $config[0]);
		foreach ($config[2] as $scrapeConfig)
		{
			$vars = self::scrape($vars, $url, $scrapeConfig);
		}

		$vars = self::filterVars($vars, $config[3]);
		if (empty($vars))
		{
			return false;
		}

		// Check that this match contains all of the required attributes
		foreach ($config[1] as $attrName)
		{
			if (!isset($vars[$attrName]))
			{
				return false;
			}
		}

		$callback = __CLASS__ . '::adjustVars' . ucfirst($siteId);
		if (is_callable($callback))
		{
			$vars = call_user_func($callback, $vars);
		}

		return self::serializeVars($vars, $siteId);
	}

	public static function findMatchInPage(string $url, array $where, MediaRepository $repository): ?array
	{
		$exprs = [
			'canonical' => 'link rel=["\']?canonical["\']? href|meta property=["\']?og:url["\']? content',
			'embedded'  => 'iframe src'
		];
		$regexp = '(<(?:' . str_replace(' ', '[^>]+?', implode('|', array_intersect_key($exprs, array_flip($where)))) . ')=["\']?([^"\']++))';

		$response = static::wget($url) ?: '';
		preg_match_all($regexp, $response, $m);
		foreach ($m[1] as $url)
		{
			$sites = $repository->findActiveMediaSites()->fetch();
			$match = $repository->urlMatchesMediaSiteList($url, $sites);
			if ($match)
			{
				return $match;
			}
		}

		return null;
	}

	/**
	* Capture substrings from a string using a set of regular expressions and add them to given array
	*
	* @param  array    &$vars    Associative array
	* @param  string    $string  Text to match
	* @param  string[]  $regexps List of regexps
	* @return bool               Whether any regexps matched the string
	*/
	protected static function addNamedCaptures(array &$vars, $string, array $regexps)
	{
		$matched = false;
		foreach ($regexps as $regexp)
		{
			if (preg_match($regexp, $string, $m))
			{
				$matched = true;
				foreach ($m as $k => $v)
				{
					// Add named captures to the vars
					if (!is_numeric($k) && $v !== '')
					{
						$vars[$k] = $v;
					}
				}
			}
		}

		return $matched;
	}

	protected static function adjustVarsFacebook(array $vars)
	{
		if (isset($vars['id'], $vars['type'], $vars['user']) && $vars['type'] === 'p')
		{
			$vars = ['id' => $vars['id'], 'posts' => 'posts', 'user' => $vars['user']];
		}

		return $vars;
	}

	protected static function adjustVarsFlickr(array $vars)
	{
		if (isset($vars['id']))
		{
			$vars['id'] = Flickr::base58_encode($vars['id']);
		}

		return $vars;
	}

	protected static function adjustVarsImgur(array $vars)
	{
		if (isset($vars['id']))
		{
			if (strlen($vars['id']) < 6)
			{
				$vars['id'] = 'a/' . $vars['id'];
			}
			$vars['id'] = str_replace('gallery/', 'a/', $vars['id']);
		}

		return $vars;
	}

	protected static function adjustVarsSoundcloud(array $vars)
	{
		if (isset($vars['id']))
		{
			$vars['id'] = trim($vars['id'], '/');
		}

		return $vars;
	}

	protected static function adjustVarsSpotify(array $vars)
	{
		if (isset($vars['id']))
		{
			$vars['id'] = strtr($vars['id'], '/', ':');
		}

		return $vars;
	}

	public static function convertMediaTag(string $url, string $markup, ?Unfurl $unfurl): string
	{
		if (preg_match('(^\\[MEDIA=(\\w++)\\]([^"\\[]++)\\[/MEDIA\\])i', $markup, $m))
		{
			$attr = '';
			if (isset($unfurl))
			{
				$attr = ' unfurl="true"';
				$unfurl->logPendingUnfurl($url);
			}

			$markup = '[URL' . $attr . ' media="' . $m[1] . ':' . $m[2] . '"]' . $url . '[/URL]';
		}

		return $markup;
	}

	/**
	* Filter an array of vars with through an array of callbacks
	*
	* @param  array      $vars    Original vars
	* @param  callable[] $filters Numerically-indexed array of callbacks
	* @return array               Filtered vars
	*/
	public static function filterVars(array $vars, array $filters)
	{
		foreach (array_intersect_key($filters, $vars) as $attrName => $callbacks)
		{
			foreach ($callbacks as $callback)
			{
				$vars[$attrName] = call_user_func($callback, $vars[$attrName]);
				if ($vars[$attrName] === false)
				{
					unset($vars[$attrName]);
					break;
				}
			}
		}

		return $vars;
	}

	protected static function getCurl()
	{
		if (!isset(self::$curl))
		{
			self::$curl = curl_init();
			curl_setopt(self::$curl, CURLOPT_ENCODING,       '');
			curl_setopt(self::$curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt(self::$curl, CURLOPT_HEADER,         true);

			$http = XF::config('http');
			if (!empty($http['proxy']))
			{
				curl_setopt(self::$curl, CURLOPT_PROXY, $http['proxy']);
			}
		}

		return self::$curl;
	}

	/**
	* Interpolate {@vars} in given string
	*
	* @param  string $str  Original string
	* @param  array  $vars Associative array
	* @return string       Interpolated string
	*/
	protected static function interpolateVars($str, array $vars)
	{
		return preg_replace_callback(
			'(\\{@(\\w+)\\})',
			function ($m) use ($vars)
			{
				return (isset($vars[$m[1]])) ? $vars[$m[1]] : '';
			},
			$str
		);
	}

	/**
	* Normalize the origin part of given URL to lowercase
	*/
	protected static function normalizeUrl(string $url): string
	{
		if (preg_match('(^(\\w++:/++[^/]++)(/.++))', $url, $m))
		{
			$url = strtolower($m[1]) . $m[2];
		}

		return $url;
	}

	/**
	* Replace variables in given string
	*
	* @param  string $format Original string
	* @param  array  $vars   Variables
	* @return string         Formatted string
	*/
	protected static function replaceVars($format, $vars)
	{
		return preg_replace_callback(
			'(\\$(\\w+))',
			function ($m) use ($vars)
			{
				return $vars[$m[1]];
			},
			$format
		);
	}

	/**
	* Scrape vars from given URL
	*
	* @param  string[] $vars
	* @param  string   $url
	* @param  array    $config
	* @return array
	*/
	protected static function scrape(array $vars, $url, array $config)
	{
		$scrapeVars = [];
		if (empty($config['match']) || self::addNamedCaptures($scrapeVars, $url, $config['match']))
		{
			if (isset($config['url']))
			{
				$url = self::interpolateVars($config['url'], $scrapeVars + $vars);
			}

			$headers = (isset($config['header'])) ? (array) $config['header'] : [];

			$response = '';
			try
			{
				$response = static::wget($url, $headers);
			}
			catch (Exception $e)
			{
				if (!empty(XF::$debugMode))
				{
					XF::logException($e, false, 'Scraping error: ');
				}
			}

			self::addNamedCaptures($vars, $response, $config['extract']);
		}

		return $vars;
	}

	/**
	* Serialize an array of vars
	*
	* @param  array  $vars
	* @param  string $siteId
	* @return string
	*/
	protected static function serializeVars(array $vars, $siteId)
	{
		ksort($vars);
		$keys = array_keys($vars);

		// If there's only one capture named "id" we store its value as-is
		if ($keys === ['id'] && preg_match('(^[-./:\\w]+$)D', $vars['id']))
		{
			return $vars['id'];
		}

		if (isset(static::$customFormats[$siteId]))
		{
			foreach (static::$customFormats[$siteId] as $format)
			{
				preg_match_all('(\\$(\\w+))', $format, $matches);
				$customKeys = $matches[1];
				sort($customKeys);

				if ($keys === $customKeys)
				{
					return self::replaceVars($format, $vars);
				}
			}
		}

		// If there are more than one capture, or it's not named "id", we store it as a series of
		// URL-encoded key=value pairs
		$pairs = [];
		foreach ($vars as $k => $v)
		{
			if ($v !== '')
			{
				$pairs[] = urlencode($k) . '=' . urlencode($v);
			}
		}

		// NOTE: XenForo silently nukes the mediaKey if it contains any HTML special characters,
		//       that's why we use ; rather than the standard &
		$str = implode(';', $pairs);

		// XenForo rejects values that contain '..'
		$str = str_replace('..', '%2E%2E', $str);

		return $str;
	}

	/**
	* Retrieve content from given URL
	*
	* @param  string   $url     Request URL
	* @param  string[] $headers Extra request headers
	* @return string            Full response (headers + body)
	*/
	protected static function wget($url, $headers = []): string
	{
		$url = preg_replace('(#.*)s', '', $url);
		$key = md5(serialize([$url, $headers]));
		if (!isset(self::$cache[$key]))
		{
			$prefix    = 'compress.zlib://';
			$cacheFile = File::getTempDir() . '/s9e.' . $key . '.html.gz';
			if (file_exists($cacheFile))
			{
				self::$cache[$key] = file_get_contents($prefix . $cacheFile);
			}
			else
			{
				$clientType = XF::options()->s9e_MediaSites_Scraping_Client ?? 'auto';
				if ($clientType === 'auto')
				{
					$clientType = function_exists('curl_exec') ? 'curl' : 'xenforo';
				}
				self::$cache[$key] = match ($clientType)
				{
					'curl'    => self::wgetCurl($url, $headers),
					'xenforo' => self::wgetGuzzle($url, $headers),
					default   => ''
				};

				if (self::$cache[$key] !== '')
				{
					file_put_contents($prefix . $cacheFile, self::$cache[$key]);
				}
			}
		}

		return self::$cache[$key];
	}

	/**
	* Retrieve content from given URL via cURL
	*
	* @param  string   $url     Request URL
	* @param  string[] $headers Extra request headers
	* @return string            Full response (headers + body)
	*/
	protected static function wgetCurl($url, $headers = []): string
	{
		$curl = self::getCurl();

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_URL,        $url);

		return (string) curl_exec($curl);
	}

	/**
	* Retrieve content from given URL via Guzzle
	*
	* @param  string   $url     Request URL
	* @param  string[] $headers Extra request headers
	* @return string            Full response (headers + body)
	*/
	protected static function wgetGuzzle(string $url, array $headers = []): string
	{
		$options = [];
		foreach ($headers as $header)
		{
			if (preg_match('(^([-\\w]++):\\s*+(.++))', $header, $m))
			{
				$options['headers'][$m[1]][] = $m[2];
			}
		}
		$response = XF::app()->http()->client()->get($url, $options);

		$return = '';
		foreach ($response->getHeaders() as $name => $values)
		{
			foreach ($values as $value)
			{
				$return .= $name . ': ' . $value . "\n";
			}
		}
		$return .= "\n" . $response->getBody();

		return $return;
	}
}