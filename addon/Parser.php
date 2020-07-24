<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

use XF;
use XF\BbCode\Helper\Flickr;
use XF\Entity\BbCodeMediaSite;

class Parser
{
	/**
	* @var string
	*/
	public static $cacheDir;

	/**
	* @var array
	*/
	protected static $customFormats = [
		'dailymotion' => ['$id:$t'],
		'facebook'    => ['$user/$posts/$id'],
		'twitch'      => ['$channel', 'clip:$clip_id', '$video_id:$t'],
		'vimeo'       => ['$id:$t'],
		'youtube'     => ['$id:$t']
	];

	/**
	* @var array
	*/
	protected static $sites = [
		'abcnews'=>[['!abcnews\\.go\\.com/(?:video/embed\\?id=|[^/]+/video/[^/]+-)(?<id>\\d+)!']],
		'amazon'=>[['#/(?:dp|gp/product)/(?<id>[A-Z0-9]+)#','#amazon\\.(?:co\\.)?(?<tld>ca|de|es|fr|in|it|jp|uk)#']],
		'anchor'=>[['@anchor.fm/[-\\w]+/episodes/(?:[-\\w]+-)(?<id>\\w+)(?![-\\w])@']],
		'audioboom'=>[['!audioboo(?:\\.f|m\\.co)m/(?:boo|post)s/(?<id>\\d+)!']],
		'audiomack'=>[['!audiomack\\.com/(?<mode>album|song)/(?<artist>[-\\w]+)/(?<title>[-\\w]+)!','!audiomack\\.com/(?<artist>[-\\w]+)/(?<mode>album|song)/(?<title>[-\\w]+)!']],
		'bandcamp'=>[[],[],[['extract'=>['!/album=(?<album_id>\\d+)!'],'match'=>['!bandcamp\\.com/album/.!']],['extract'=>['!"album_id":(?<album_id>\\d+)!','!"track_num":(?<track_num>\\d+)!','!/track=(?<track_id>\\d+)!'],'match'=>['!bandcamp\\.com/track/.!']]]],
		'bbcnews'=>[['@bbc\\.co(?:m|\\.uk)/news/av/embed/(?<id>[-\\\\\\w/]+)@'],[],[['extract'=>['@bbc\\.co(?:m|\\.uk)\\\\?/news\\\\?/av\\\\?/embed\\\\?/(?<id>[-\\\\\\w/]+)@'],'match'=>['@bbc\\.co(?:m|\\.uk)/news/(?:av(?!/embed)|video_and_audio)/\\w@']]],['id'=>['stripslashes']]],
		'bitchute'=>[['@bitchute\\.com/video/(?<id>\\w+)@']],
		'bleacherreport'=>[[],[],[['extract'=>['!id="video-(?<id>[-\\w]+)!','!video_embed\\?id=(?<id>[-\\w]+)!'],'match'=>['!/articles/.!']]]],
		'break'=>[['!break\\.com/video/.*-(?<id>\\d+)$!']],
		'brightcove'=>[['@link\\.brightcove\\.com/services/player/bcpid(?<bcpid>\\d+).*?bckey=(?<bckey>[-,~\\w]+).*?bctid=(?<bctid>\\d+)@','@players\\.brightcove\\.net/(?<bcpid>\\d+)/.*?videoId=(?<bctid>\\d+)@']],
		'castos'=>[['@(?<host>[-\\w]+)\\.castos\\.com/player/(?<id>\\d+)@'],[],[['extract'=>['@(?<host>[-\\w]+)\\.castos\\.com/player/(?<id>\\d+)@'],'match'=>['@castos\\.com/(?:podcasts/[^/]*+/)?episodes/.@']]]],
		'cbsnews'=>[['#cbsnews\\.com/videos?/(?!watch/)(?<id>[-\\w]+)#','#cbsnews\\.com/video/watch/\\?id=(?<id>\\d+)#']],
		'cnbc'=>[['!cnbc\\.com/gallery/\\?video=(?<id>\\d+)!'],[],[['extract'=>['!byGuid=(?<id>\\d+)!'],'match'=>['!cnbc\\.com/video/20\\d\\d/\\d\\d/\\d\\d/\\w!']]]],
		'cnn'=>[['!cnn.com/videos/(?<id>.*\\.cnn)!','!cnn\\.com/video/data/2\\.0/video/(?<id>.*\\.cnn)!']],
		'cnnmoney'=>[['!money\\.cnn\\.com/video/(?<id>.*\\.cnnmoney)!']],
		'codepen'=>[['!codepen\\.io/(?<user>[-\\w]+)/(?:details|embed|full|live|pen)/(?<id>\\w+)!']],
		'comedycentral'=>[[],[],[['extract'=>['!(?<id>mgid:arc:(?:episode|video):[.\\w]+:[-\\w]+)!'],'match'=>['!c(?:c|omedycentral)\\.com/(?:full-episode|video-clip)s/!']]]],
		'coub'=>[['!coub\\.com/view/(?<id>\\w+)!']],
		'dailymotion'=>[['!dai\\.ly/(?<id>[a-z0-9]+)!i','!dailymotion\\.com/(?:live/|swf/|user/[^#]+#video=|(?:related/\\d+/)?video/)(?<id>[a-z0-9]+)!i','!start=(?<t>\\d+)!']],
		'democracynow'=>[['!democracynow.org/(?:embed/)?(?<id>(?:\\w+/)?\\d+/\\d+/\\d+(?:/\\w+)?)!'],[],[['extract'=>["!democracynow\\.org/(?<id>(?:\\w+/)?\\d+/\\d+/\\d+(?:/\\w+)?)' rel='canonical!"],'match'=>['!m\\.democracynow\\.org/stories/\\d!']]]],
		'dumpert'=>[['!dumpert\\.nl/mediabase/(?<id>\\d+[/_]\\w+)!']],
		'eighttracks'=>[['!8tracks\\.com/[-\\w]+/(?<id>\\d+)(?=#|$)!'],[],[['extract'=>['!eighttracks://mix/(?<id>\\d+)!'],'match'=>['!8tracks\\.com/[-\\w]+/\\D!']]]],
		'espn'=>[['#video/(?:clip(?:\\?id=|/_/id/))?(?<id>\\d+)#']],
		'facebook'=>[['@/(?!(?:apps|developers|graph)\\.)[-\\w.]*facebook\\.com/(?:[/\\w]+/permalink|(?!marketplace/|pages/|groups/).*?)(?:/|fbid=|\\?v=)(?<id>\\d+)(?=$|[/?&#])@','@facebook\\.com/(?<user>[.\\w]+)/(?=(?:post|video)s?/)(?<type>[pv])@','@facebook\\.com/video/(?=post|video)(?<type>[pv])@','@facebook\\.com/watch/\\?(?<type>[pv])=@']],
		'flickr'=>[['@flickr\\.com/photos/[^/]+/(?<id>\\d+)@','@flic\\.kr/(?!p/)[^/]+/(?<id>\\d+)@'],[],[['extract'=>['@flickr\\.com/photos/[^/]+/(?<id>\\d+)@'],'match'=>["@flic\\.kr/p/(?'short'\\w+)@"],'url'=>'https://www.flickr.com/photo.gne?rb=1&short={@short}']]],
		'foxnews'=>[['!video\\.foxnews\\.com/v/(?<id>\\d+)!']],
		'foxsports'=>[[],[],[['extract'=>['@BKQ29B/(?<id>\\w+)@'],'match'=>['@/video/\\d@']]]],
		'funnyordie'=>[['!funnyordie\\.com/videos/(?<id>[0-9a-f]+)!']],
		'gamespot'=>[['!gamespot\\.com.*?/(?:events|videos)/.*?-(?<id>\\d+)/(?:[#?].*)?$!']],
		'gametrailers'=>[[],[],[['extract'=>['!embed/(?<id>\\d+)!'],'match'=>['!gametrailers\\.com/(?:full-episode|review|video)s/!']]]],
		'getty'=>[['!gty\\.im/(?<id>\\d+)!','!gettyimages\\.[.\\w]+/detail(?=/).*?/(?<id>\\d+)!','!#[-\\w]*picture-id(?<id>\\d+)$!'],[],[['extract'=>['!"height":[ "]*(?<height>\\d+)!','!"width":[ "]*(?<width>\\d+)!','!\\bid[=:][\'"]?(?<et>[-=\\w]+)!','!\\bsig[=:][\'"]?(?<sig>[-=\\w]+)!'],'match'=>['//'],'url'=>'http://embed.gettyimages.com/preview/{@id}']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'gfycat'=>[['#gfycat\\.com/(?!gaming|reactions|stickers|gifs/tag)(?:gifs/detail/|ifr(?:ame)?/)?(?<id>\\w+)#'],[],[['extract'=>['!/ifr/(?<id>\\w+)!'],'match'=>['#gfycat\\.com/(?!gaming|reactions|stickers|gifs/tag)(?:gifs/detail/|ifr(?:ame)?/)?[a-z]#'],'url'=>'https://gfycat.com/ifr/{@id}'],['extract'=>['!"height":(?<height>\\d+)!','!"width":(?<width>\\d+)!'],'match'=>['//'],'url'=>'https://api.gfycat.com/v1/oembed?url=https://gfycat.com/{@id}']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'gifs'=>[['!gifs\\.com/(?:gif/)?(?<id>\\w+)!'],[],[['extract'=>['!meta property="og:image:width" content="(?<width>\\d+)!','!meta property="og:image:height" content="(?<height>\\d+)!'],'match'=>['//'],'url'=>'https://gifs.com/gif/{@id}']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'giphy'=>[['!giphy\\.com/(?<type>gif|video|webp)\\w+/(?:[-\\w]+-)*(?<id>\\w+)!','!giphy\\.com/media/(?<id>\\w+)/\\w+\\.(?<type>gif|webp)!','!i\\.giphy\\.com/(?<id>\\w+)\\.(?<type>gif|webp)!'],[],[['extract'=>['!"height"\\s*:\\s*(?<height>\\d+)!','!"width"\\s*:\\s*(?<width>\\d+)!'],'match'=>['//'],'url'=>'https://giphy.com/services/oembed?url=https://media.giphy.com/media/{@id}/giphy.gif']],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'gist'=>[['!gist\\.github\\.com/(?<id>(?:\\w+/)?[\\da-f]+(?:/[\\da-f]+)?)!']],
		'globalnews'=>[['!globalnews\\.ca/video/(?<id>\\d+)!'],[],[['extract'=>['!globalnews\\.ca/video/(?<id>\\d+)!'],'match'=>['!globalnews\\.ca/video/rd/!']]]],
		'gofundme'=>[['@gofundme\\.com/(?<id>\\w+)(?![^#?])@']],
		'googledrive'=>[['!drive\\.google\\.com/.*?(?:file/d/|id=)(?<id>[-\\w]+)!']],
		'googleplus'=>[['!//plus\\.google\\.com/(?:u/\\d+/)?(?:\\+(?<name>[^/]+)|(?<oid>\\d+))/posts/(?<pid>\\w+)!'],[],[],['name'=>['urldecode']]],
		'googlesheets'=>[['@docs\\.google\\.com/spreadsheet(?:/ccc\\?key=|(?:[^e]/)+)(?<id>(?:e/)?[-\\w]+)@','@oid=(?<oid>\\d+)@','@#gid=(?<gid>\\d+)@','@/pub(?<type>chart)@']],
		'hudl'=>[['!hudl\\.com/athlete/(?<athlete>\\d+)/highlights/(?<highlight>[\\da-f]+)!','!hudl\\.com/video/\\d+/(?<athlete>\\d+)/(?<highlight>[\\da-f]+)!','@hudl\\.com/video/(?<id>\\w+)(?![\\w/])@'],[],[['extract'=>['!hudl\\.com/video/\\d+/(?<athlete>\\d+)/(?<highlight>[\\da-f]+)!','@hudl\\.com/video/(?<id>\\w+)(?![\\w/])@'],'match'=>['!hudl\\.com/v/!']]]],
		'hulu'=>[[],[],[['extract'=>['!eid=(?<id>[-\\w]+)!'],'match'=>['!hulu\\.com/watch/!']]]],
		'ign'=>[['!(?<id>https?://.*?ign\\.com/videos/.+)!i']],
		'imdb'=>[['!imdb\\.com/[/\\w]+/vi(?<id>\\d+)!']],
		'imgur'=>[['@imgur\\.com/(?<id>a/\\w+)@','@i\\.imgur\\.com/(?<id>\\w{5,7})[lms]?\\.@','@imgur\\.com/(?<id>\\w+)(?![\\w./])@'],[],[['extract'=>['@data-id="(?<id>[\\w/]+)"@'],'match'=>["@imgur\\.com/(?![art]/|user/)(?'path'(?:gallery/)?\\w+)(?![\\w.])@"],'url'=>'https://api.imgur.com/oembed.xml?url=/{@path}']]],
		'indiegogo'=>[['!indiegogo\\.com/projects/(?<id>[-\\w]+)!']],
		'instagram'=>[['!instagram\\.com/(?:p|tv)/(?<id>[-\\w]+)!']],
		'internetarchive'=>[[],[],[['extract'=>['!meta property="twitter:player" content="https://archive.org/embed/(?<id>[^/"]+)!','!meta property="og:video:width" content="(?<width>\\d+)!','!meta property="og:video:height" content="(?<height>\\d+)!'],'match'=>['!archive\\.org/(?:details|embed)/!']]],['height'=>['s9e\\MediaSites\\Helper::filterUint'],'id'=>['htmlspecialchars_decode'],'width'=>['s9e\\MediaSites\\Helper::filterUint']]],
		'izlesene'=>[['!izlesene\\.com/video/[-\\w]+/(?<id>\\d+)!']],
		'jwplatform'=>[['!jwplatform\\.com/\\w+/(?<id>[-\\w]+)!']],
		'kaltura'=>[['@/p(?:artner_id)?/(?<partner_id>\\d+)/@','@/sp/(?<sp>\\d+)/@','@/uiconf_id/(?<uiconf_id>\\d+)/@','@\\bentry_id[=/](?<entry_id>\\w+)@'],['entry_id','partner_id','uiconf_id'],[['extract'=>['@kaltura\\.com/+p/(?<partner_id>\\d+)/sp/(?<sp>\\d+)/\\w*/uiconf_id/(?<uiconf_id>\\d+)/.*?\\bentry_id=(?<entry_id>\\w+)@'],'match'=>['@kaltura\\.com/(?:media/t|tiny)/.@']]]],
		'khl'=>[[],[],[['extract'=>['!/feed/start/(?<id>[/\\w]+)!'],'match'=>['!video\\.khl\\.ru/(?:event|quote)s/\\d!']]]],
		'kickstarter'=>[['!kickstarter\\.com/projects/(?<id>[^/]+/[^/?]+)(?:/widget/(?:(?<card>card)|(?<video>video)))?!']],
		'libsyn'=>[[],[],[['extract'=>['!embed/episode/id/(?<id>\\d+)!'],'match'=>['@(?!\\.mp3)....$@']]]],
		'liveleak'=>[['!liveleak\\.com/(?:e/|view\\?i=)(?<id>\\w+)!'],[],[['extract'=>['!liveleak\\.com/e/(?<id>\\w+)!'],'match'=>['!liveleak\\.com/view\\?t=!']]]],
		'livestream'=>[['!livestream\\.com/accounts/(?<account_id>\\d+)/events/(?<event_id>\\d+)!','!/videos/(?<video_id>\\d+)!','!original\\.livestream\\.com/(?<channel>\\w+)/video\\?clipId=(?<clip_id>[-\\w]+)!'],[],[['extract'=>['!accounts/(?<account_id>\\d+)/events/(?<event_id>\\d+)!'],'match'=>['@livestream\\.com/(?!accounts/\\d+/events/\\d)@']],['extract'=>['!//original\\.livestream\\.com/(?<channel>\\w+)/video/(?<clip_id>[-\\w]+)!'],'match'=>['!livestre.am!']]]],
		'mailru'=>[[],[],[['extract'=>['!"itemId": ?"?(?<id>\\d+)!'],'match'=>['!my\\.mail\\.ru/\\w+/\\w+/video/\\w+/\\d!']]]],
		'medium'=>[['!medium\\.com/(?:s/)?[^/]*/(?:[-\\w]+-)?(?<id>[\\da-f]+)!']],
		'megaphone'=>[['@megaphone\\.fm/.*?\\?(?:e|selected)=(?<id>\\w+)@','@(?:dcs|player|traffic)\\.megaphone\\.fm/(?<id>\\w+)@','@megaphone\\.link/(?<id>\\w+)@']],
		'metacafe'=>[['!metacafe\\.com/watch/(?<id>\\d+)!']],
		'mixcloud'=>[['@mixcloud\\.com/(?!categories|tag)(?<id>[-\\w]+/[^/&]+)/@']],
		'mixer'=>[['#mixer.com/(?!browse/)(?<channel>\\w+)(?!\\?clip|\\w)(?:\\?vod=(?<vod>[-\\w]+))?#']],
		'mlb'=>[['#mlb\\.com/video/(?:[-\\w/]+/)?(?:c-|v|[-\\w]+-c)(?<id>\\d+)#']],
		'mrctv'=>[[],[],[['extract'=>['!mrctv\\.org/embed/(?<id>\\d+)!'],'match'=>['!mrctv\\.org/videos/.!']]]],
		'msnbc'=>[[],[],[['extract'=>['@embedded-video/(?!undefined)(?<id>\\w+)@'],'match'=>['@msnbc\\.com/[-\\w]+/watch/@','@on\\.msnbc\\.com/.@']]]],
		'natgeochannel'=>[['@channel\\.nationalgeographic\\.com/(?<id>[-/\\w]+/videos/[-\\w]+)@']],
		'natgeovideo'=>[[],[],[['extract'=>['@guid="(?<id>[-\\w]+)"@'],'match'=>['@video\\.nationalgeographic\\.com/(?:tv|video)/\\w@']]]],
		'nbcnews'=>[['!nbcnews\\.com/(?:widget/video-embed/|video/[-\\w]+?-)(?<id>\\d+)!']],
		'nbcsports'=>[[],[],[['extract'=>['!select/media/(?<id>\\w+)!'],'match'=>['!nbcsports\\.com/video/.!']]]],
		'nhl'=>[['#nhl\\.com/(?:\\w+/)?video(?:/(?![ct]-)[-\\w]+)?(?:/t-(?<t>\\d+))?(?:/c-(?<c>\\d+))?#']],
		'npr'=>[[],[],[['extract'=>['!player/embed/(?<i>\\d+)/(?<m>\\d+)!'],'header'=>'Cookie: trackingChoice=false; choiceVersion=1','match'=>['!npr\\.org/[/\\w]+/\\d+!','!n\\.pr/\\w!']]]],
		'nytimes'=>[['!nytimes\\.com/video/[a-z]+/(?:[a-z]+/)?(?<id>\\d+)!','!nytimes\\.com/video/\\d+/\\d+/\\d+/[a-z]+/(?<id>\\d+)!'],[],[['extract'=>['!/video/movies/(?<id>\\d+)!'],'match'=>["!nytimes\\.com/movie(?:s/movie)?/(?'playlist'\\d+)/[-\\w]+/trailers!"],'url'=>'http://www.nytimes.com/svc/video/api/playlist/{@playlist}?externalId=true']]],
		'orfium'=>[['@album/(?<album_id>\\d+)@','@playlist/(?<playlist_id>\\d+)@','@live-set/(?<set_id>\\d+)@','@track/(?<track_id>\\d+)@']],
		'pastebin'=>[['@pastebin\\.com/(?!u/)(?:\\w+(?:\\.php\\?i=|/))?(?<id>\\w+)@']],
		'pinterest'=>[['@pinterest.com/pin/(?<id>\\d+)@','@pinterest.com/(?!_/|discover/|explore/|news_hub/|pin/|search/)(?<id>[-\\w]+/[-\\w]+)@']],
		'podbean'=>[['!podbean\\.com/media/(?:player/|share/pb-)(?<id>[-\\w]+)!'],[],[['extract'=>['!podbean\\.com/media/player/(?<id>[-\\w]+)!'],'match'=>['@podbean\\.com/(?:media/shar)?e/(?!pb-)@']]]],
		'prezi'=>[['#//prezi\\.com/(?!(?:a(?:bout|mbassadors)|c(?:o(?:llaborate|mmunity|ntact)|reate)|exp(?:erts|lore)|ip(?:ad|hone)|jobs|l(?:ear|ogi)n|m(?:ac|obility)|pr(?:es(?:s|ent)|icing)|recommend|support|user|windows|your)/)(?<id>\\w+)/#']],
		'reddit'=>[['!(?<id>\\w+/comments/\\w+(?:/\\w+/\\w+)?)!']],
		'rutube'=>[['!rutube\\.ru/tracks/(?<id>\\d+)!'],[],[['extract'=>['!rutube\\.ru/play/embed/(?<id>\\d+)!'],'header'=>'User-agent: Mozilla/5.0 (X11; Linux x86_64; rv:62.0) Gecko/20100101 Firefox/62.0','match'=>["!rutube\\.ru/video/(?'vid'[0-9a-f]{32})!"],'url'=>'http://rutube.ru/api/oembed/?url=https://rutube.ru/video/{@vid}/']]],
		'scribd'=>[['!scribd\\.com/(?:mobile/)?(?:doc(?:ument)?|presentation)/(?<id>\\d+)!']],
		'sendvid'=>[['!sendvid\\.com/(?<id>\\w+)!']],
		'slideshare'=>[['!slideshare\\.net/[^/]+/[-\\w]+-(?<id>\\d{6,})$!'],[],[['extract'=>['!"presentationId":(?<id>\\d+)!'],'match'=>['@slideshare\\.net/[^/]+/\\w(?![-\\w]+-\\d{6,}$)@']]]],
		'soundcloud'=>[['@https?://(?:api\\.)?soundcloud\\.com/(?!pages/)(?<id>[-/\\w]+/[-/\\w]+|^[^/]+/[^/]+$)@i','@api\\.soundcloud\\.com/playlists/(?<playlist_id>\\d+)@','@api\\.soundcloud\\.com/tracks/(?<track_id>\\d+)(?:\\?secret_token=(?<secret_token>[-\\w]+))?@','@soundcloud\\.com/(?!playlists|tracks)[-\\w]+/[-\\w]+/(?=s-)(?<secret_token>[-\\w]+)@'],[],[['extract'=>['@soundcloud:tracks:(?<track_id>\\d+)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@soundcloud\\.com/(?!playlists/\\d|tracks/\\d)[-\\w]+/[-\\w]@']],['extract'=>['@soundcloud://playlists:(?<playlist_id>\\d+)@'],'header'=>'User-agent: PHP (not Mozilla)','match'=>['@soundcloud\\.com/\\w+/sets/@']]]],
		'sporcle'=>[['#sporcle.com/framed/.*?gid=(?<id>\\w+)#'],[],[['extract'=>['#encodedGameID\\W+(?<id>\\w+)#'],'match'=>['#sporcle\\.com/games/(?!\\w*category/)[-\\w]+/[-\\w]#']]]],
		'sportsnet'=>[[],[],[['extract'=>['/vid(?:eoId)?=(?<id>\\d+)/','/param name="@videoPlayer" value="(?<id>\\d+)"/'],'match'=>['//']]]],
		'spotify'=>[['!(?:open|play)\\.spotify\\.com/(?<id>(?:user/[-.\\w]+/)?(?:album|artist|episode|playlist|show|track)(?:[:/][-.\\w]+)+)!']],
		'spreaker'=>[['!spreaker\\.com/episode/(?<episode_id>\\d+)!'],[],[['extract'=>['!episode_id=(?<episode_id>\\d+)!','!show_id=(?<show_id>\\d+)!'],'match'=>["!(?'url'.+/(?:show/|user/.+/).+)!"],'url'=>'https://api.spreaker.com/oembed?format=json&url={@url}']]],
		'steamstore'=>[['!store.steampowered.com/app/(?<id>\\d+)!']],
		'stitcher'=>[['!/splayer/f/(?<fid>\\d+)/(?<eid>\\d+)!'],[],[['extract'=>['!data-eid="(?<eid>\\d+)!','!data-fid="(?<fid>\\d+)!'],'match'=>['!/(?:podcast/|s\\?)!']]]],
		'strawpoll'=>[['!strawpoll\\.me/(?<id>\\d+)!']],
		'streamable'=>[['!streamable\\.com/(?<id>\\w+)!']],
		'streamja'=>[['@streamja\\.com/(?!login|signup|terms|videos)(?<id>\\w+)@']],
		'teamcoco'=>[['!teamcoco\\.com/video/(?<id>\\d+)!'],[],[['extract'=>['!embed/v/(?<id>\\d+)!'],'match'=>['!teamcoco\\.com/video/\\D!']]]],
		'ted'=>[['#ted\\.com/(?<id>(?:talk|playlist)s/[-\\w]+(?:\\.html)?)(?![-\\w]|/transcript)#i']],
		'telegram'=>[['@//t.me/(?!addstickers/|joinchat/)(?<id>\\w+/\\d+)@']],
		'theatlantic'=>[['!theatlantic\\.com/video/index/(?<id>\\d+)!']],
		'theguardian'=>[['!theguardian\\.com/(?<id>\\w+/video/20(?:0[0-9]|1[0-7])[-/\\w]+)!']],
		'theonion'=>[['!theonion\\.com/video/[-\\w]+[-,](?<id>\\d+)!']],
		'tiktok'=>[['#tiktok\\.com/(?:@[.\\w]+/video|v|i18n/share/video)/(?<id>\\d+)#'],[],[['extract'=>['#tiktok\\.com/@[.\\w]+/video/(?<id>\\d+)#'],'header'=>'User-agent: PHP','match'=>['#//vm\\.tiktok\\.com/.#']]]],
		'tmz'=>[['@tmz\\.com/videos/(?<id>\\w+)@']],
		'tradingview'=>[['!tradingview\\.com/(?:chart/[^/]+|i)/(?<chart>\\w+)!','!tradingview\\.com/symbols/(?<symbol>[-:\\w]+)!']],
		'traileraddict'=>[[],[],[['extract'=>['@v\\.traileraddict\\.com/(?<id>\\d+)@'],'match'=>['@traileraddict\\.com/(?!tags/)[^/]+/.@']]]],
		'trendingviews'=>[['!trendingviews.co/(?:video|embed)/(?:[^/]+-)?(?<id>\\d+)!']],
		'tumblr'=>[['!(?<name>[-\\w]+)\\.tumblr\\.com/post/(?<id>\\d+)!'],[],[['extract'=>['!did=\\\\u0022(?<did>[-\\w]+)!','!embed\\\\/post\\\\/(?<key>[-\\w]+)!'],'header'=>'User-agent: curl','match'=>['!\\w\\.tumblr\\.com/post/\\d!'],'url'=>'https://www.tumblr.com/oembed/1.0?url=https://{@name}.tumblr.com/post/{@id}']]],
		'twentyfoursevensports'=>[['!247sports\\.com/PlayerSport/[-\\w]*?(?<player_id>\\d+)/Embed!'],[],[['extract'=>['!247sports\\.com/PlayerSport/[-\\w]*?(?<player_id>\\d+)/Embed!'],'header'=>'User-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36','match'=>['!247sports\\.com/Player/[-\\w]*?\\d!']],['extract'=>['!player_id%3D(?<video_id>\\d+)!'],'header'=>'User-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36','match'=>['!247sports\\.com/Video/.!']]]],
		'twitch'=>[['#twitch\\.tv/(?:videos|\\w+/v)/(?<video_id>\\d+)?#','#www\\.twitch\\.tv/(?!videos/)(?<channel>\\w+)(?:/clip/(?<clip_id>\\w+))?#','#t=(?<t>(?:(?:\\d+h)?\\d+m)?\\d+s)#','#clips\\.twitch\\.tv/(?:(?<channel>\\w+)/)?(?<clip_id>\\w+)#']],
		'twitter'=>[['@twitter\\.com/(?:#!/|i/)?\\w+/(?:status(?:es)?|tweet)/(?<id>\\d+)@']],
		'ustream'=>[['!ustream\\.tv/recorded/(?<vid>\\d+)!'],[],[['extract'=>['!embed/(?<cid>\\d+)!'],'match'=>['#ustream\\.tv/(?!explore/|platform/|recorded/|search\\?|upcoming$|user/)(?:channel/)?[-\\w]+#']]]],
		'vbox7'=>[['!vbox7\\.com/play:(?<id>[\\da-f]+)!']],
		'veoh'=>[['!veoh\\.com/(?:m/watch\\.php\\?v=|watch/)v(?<id>\\w+)!']],
		'vevo'=>[['!vevo\\.com/watch/(.*?/)?(?<id>[A-Z]+\\d+)!']],
		'videodetective'=>[['!videodetective\\.com/\\w+/[-\\w]+/(?:trailer/P0*)?(?<id>\\d+)!']],
		'vimeo'=>[['!vimeo\\.com/(?:channels/[^/]+/|video/)?(?<id>\\d+)!','!#t=(?<t>[\\dhms]+)!'],[],[],['t'=>['s9e\\MediaSites\\Helper::filterTimestamp']]],
		'vine'=>[['!vine\\.co/v/(?<id>[^/]+)!']],
		'vk'=>[['!vk(?:\\.com|ontakte\\.ru)/(?:[\\w.]+\\?z=)?video(?<oid>-?\\d+)_(?<vid>\\d+).*?hash=(?<hash>[0-9a-f]+)!','!vk(?:\\.com|ontakte\\.ru)/video_ext\\.php\\?oid=(?<oid>-?\\d+)&id=(?<vid>\\d+)&hash=(?<hash>[0-9a-f]+)!'],[],[['extract'=>['#meta property="og:video" content=".*?oid=(?<oid>-?\\d+).*?id=(?<vid>\\d+).*?hash=(?<hash>[0-9a-f]+)#'],'header'=>'User-agent: Mozilla/5.0 (X11; Linux x86_64; rv:62.0) Gecko/20100101 Firefox/62.0','match'=>['#^(?!.*?hash=)#']]]],
		'vocaroo'=>[['!voca(?:\\.ro|roo\\.com)/(?:i/)?(?<id>\\w+)!']],
		'vox'=>[['!vox.com/.*#ooid=(?<id>[-\\w]+)!']],
		'washingtonpost'=>[['#washingtonpost\\.com/video/c/\\w+/(?<id>[-0-9a-f]+)#','#washingtonpost\\.com/video/[-/\\w]+/(?<id>[-0-9a-f]+)_video\\.html#']],
		'wistia'=>[['!wistia.com/medias/(?<id>\\w+)!']],
		'wshh'=>[['!worldstarhiphop\\.com/featured/(?<id>\\d+)!'],[],[['extract'=>['!v: ?"?(?<id>\\d+)!'],'match'=>['!worldstarhiphop\\.com/(?:\\w+/)?video\\.php\\?v=\\w+!']]]],
		'wsj'=>[['@wsj\\.com/[^#]*#!(?<id>[-0-9A-F]{36})@','@wsj\\.com/video/[^/]+/(?<id>[-0-9A-F]{36})@'],[],[['extract'=>['@guid=(?<id>[-0-9A-F]{36})@'],'match'=>['@on\\.wsj\\.com/\\w@']]]],
		'xboxclips'=>[['@(?:gameclips\\.io|xboxclips\\.com)/(?!game/)(?<user>[^/]+)/(?!screenshots/)(?<id>[-0-9a-f]+)@']],
		'xboxdvr'=>[['!(?:gamer|xbox)dvr\\.com/gamer/(?<user>[^/]+)/video/(?<id>\\d+)!']],
		'youku'=>[['!youku\\.com/v(?:_show|ideo)/id_(?<id>\\w+=*)!']],
		'youtube'=>[['!youtube\\.com/(?:watch.*?v=|v/|attribution_link.*?v%3D)(?<id>[-\\w]+)!','!youtu\\.be/(?<id>[-\\w]+)!','@[#&?]t=(?<t>\\d[\\dhms]*)@','![&?]list=(?<list>[-\\w]+)!'],[],[['extract'=>['!/vi/(?<id>[-\\w]+)!'],'match'=>['!/shared\\?ci=!']]],['id'=>['s9e\\MediaSites\\Helper::filterIdentifier'],'t'=>['s9e\\MediaSites\\Helper::filterTimestamp']]]
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
		if (empty(self::$sites[$siteId]))
		{
			return false;
		}

		$config = self::$sites[$siteId] + [[], [], [], []];
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

	/**
	* Adjust Facebook vars
	*
	* @param  array $vars
	* @return array
	*/
	protected static function adjustVarsFacebook(array $vars)
	{
		if (isset($vars['id'], $vars['type'], $vars['user']) && $vars['type'] === 'p')
		{
			$vars = ['id' => $vars['id'], 'posts' => 'posts', 'user' => $vars['user']];
		}

		return $vars;
	}

	/**
	* Adjust Flickr vars
	*
	* @param  array $vars
	* @return array
	*/
	protected static function adjustVarsFlickr(array $vars)
	{
		if (isset($vars['id']))
		{
			$vars['id'] = Flickr::base58_encode($vars['id']);
		}

		return $vars;
	}

	/**
	* Adjust Imgur vars
	*
	* @param  array $vars
	* @return array
	*/
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
			$body    = self::wget($url, $headers);

			self::addNamedCaptures($vars, $body, $config['extract']);
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
		if ($keys === ['id'] && preg_match('(^[-./\\w]+$)D', $vars['id']))
		{
			return $vars['id'];
		}

		if (isset(self::$customFormats[$siteId]))
		{
			foreach (self::$customFormats[$siteId] as $format)
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
		return implode(';', $pairs);
	}

	/**
	* Retrieve content from given URL
	*
	* @param  string   $url     Request URL
	* @param  string[] $headers Extra request headers
	* @return string            Response body
	*/
	protected static function wget($url, $headers = [])
	{
		$url = preg_replace('(#.*)s', '', $url);

		// Return the content from the cache if applicable
		if (isset(self::$cacheDir) && file_exists(self::$cacheDir))
		{
			$cacheFile = self::$cacheDir . '/http.' . crc32(serialize([$url, $headers])) . '.html';
			if (file_exists($cacheFile))
			{
				return file_get_contents($cacheFile);
			}
		}

		$html = self::wgetCurl($url, $headers);
		if ($html && isset($cacheFile))
		{
			file_put_contents($cacheFile, $html);
		}

		return $html;
	}

	/**
	* Retrieve content from given URL via cURL
	*
	* @param  string   $url     Request URL
	* @param  string[] $headers Extra request headers
	* @return string            Response body
	*/
	protected static function wgetCurl($url, $headers = [])
	{
		static $curl;
		if (!isset($curl))
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_ENCODING,       '');
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$http = XF::config('http');
			if (!empty($http['proxy']))
			{
				curl_setopt($curl, CURLOPT_PROXY, $http['proxy']);
			}
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_URL,        $url);

		return curl_exec($curl);
	}
}