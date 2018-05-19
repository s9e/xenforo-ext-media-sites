<?php

/**
* @package   s9e\MediaSites
* @copyright Copyright (c) 2017-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\MediaSites;

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
		'dailymotion' => [
			[['id', 't'],       '$id:$t']
		],
		'facebook' => [
			[['id', 'post', 'user'], '$user/posts/$id']
		],
		'twitch'      => [
			[['channel'],       '$channel'],
			[['clip_id'],       'clip:$clip_id'],
			[['t', 'video_id'], '$video_id:$t']
		],
		'vimeo'      => [
			[['id', 't'],       '$id:$t']
		],
		'youtube'     => [
			[['id', 't'],       '$id:$t']
		]
	];

	/**
	* @var array
	*/
	protected static $sites = [
		'abcnews'=>[['!abcnews\\.go\\.com/(?:video/embed\\?id=|[^/]+/video/[^/]+-)(?<id>\\d+)!']],
		'amazon'=>[['#/(?:dp|gp/product)/(?<id>[A-Z0-9]+)#','#amazon\\.(?:co\\.)?(?<tld>ca|de|es|fr|in|it|jp|uk)#']],
		'audioboom'=>[['!audioboo(?:\\.f|m\\.co)m/(?:boo|post)s/(?<id>\\d+)!']],
		'audiomack'=>[['!audiomack\\.com/(?<mode>album|song)/(?<id>[-\\w]+/[-\\w]+)!']],
		'bandcamp'=>[[],[],[['extract'=>['!/album=(?<album_id>\\d+)!'],'match'=>['!bandcamp\\.com/album/.!']],['extract'=>['!"album_id":(?<album_id>\\d+)!','!"track_num":(?<track_num>\\d+)!','!/track=(?<track_id>\\d+)!'],'match'=>['!bandcamp\\.com/track/.!']]]],
		'bbcnews'=>[[],[],[['extract'=>['!bbc\\.com\\\\/news\\\\/av\\\\/embed\\\\/(?<id>[-\\\\\\w/]+)!'],'match'=>['!bbc\\.com/news/\\w!']]],['id'=>['stripslashes']]],
		'blab'=>[['#blab\\.im/(?!about$|live$|replay$|scheduled$|search\\?)(?<id>[-\\w]+)#']],
		'bleacherreport'=>[[],[],[['extract'=>['!id="video-(?<id>[-\\w]+)!'],'match'=>['!/articles/.!']]]],
		'break'=>[['!break\\.com/video/.*-(?<id>\\d+)$!']],
		'brightcove'=>[[],[],[['extract'=>['!meta name="twitter:player" content=".*?bcpid(?<bcpid>\\d+).*?bckey=(?<bckey>[-,~\\w]+).*?bctid=(?<bctid>\\d+)!'],'match'=>['!bcove\\.me/.!','!link\\.brightcove\\.com/services/player/!']]]],
		'cbsnews'=>[['#cbsnews\\.com/videos?/(?!watch/)(?<id>[-\\w]+)#','#cbsnews\\.com/video/watch/\\?id=(?<id>\\d+)#']],
		'cnbc'=>[['!cnbc\\.com/gallery/\\?video=(?<id>\\d+)!']],
		'cnn'=>[['!cnn.com/videos/(?<id>.*\\.cnn)!','!cnn\\.com/video/data/2\\.0/video/(?<id>.*\\.cnn)!']],
		'cnnmoney'=>[['!money\\.cnn\\.com/video/(?<id>.*\\.cnnmoney)!']],
		'collegehumor'=>[['!collegehumor\\.com/(?:video|embed)/(?<id>\\d+)!']],
		'comedycentral'=>[[],[],[['extract'=>['!(?<id>mgid:arc:(?:episode|video):[.\\w]+:[-\\w]+)!'],'match'=>['!c(?:c|omedycentral)\\.com/(?:full-episode|video-clip)s/!']]]],
		'coub'=>[['!coub\\.com/view/(?<id>\\w+)!']],
		'dailymotion'=>[['!dai\\.ly/(?<id>[a-z0-9]+)!i','!dailymotion\\.com/(?:live/|swf/|user/[^#]+#video=|(?:related/\\d+/)?video/)(?<id>[a-z0-9]+)!i','!start=(?<t>\\d+)!']],
		'democracynow'=>[['!democracynow.org/(?:embed/)?(?<id>(?:\\w+/)?\\d+/\\d+/\\d+(?:/\\w+)?)!'],[],[['extract'=>["!democracynow\\.org/(?<id>(?:\\w+/)?\\d+/\\d+/\\d+(?:/\\w+)?)' rel='canonical!"],'match'=>['!m\\.democracynow\\.org/stories/\\d!']]]],
		'dumpert'=>[['!dumpert\\.nl/mediabase/(?<id>\\d+[/_]\\w+)!']],
		'eighttracks'=>[['!8tracks\\.com/[-\\w]+/(?<id>\\d+)(?=#|$)!'],[],[['extract'=>['!eighttracks://mix/(?<id>\\d+)!'],'match'=>['!8tracks\\.com/[-\\w]+/\\D!']]]],
		'espn'=>[['#video/(?:clip(?:\\?id=|/_/id/))?(?<id>\\d+)#']],
		'facebook'=>[['@/(?!(?:apps|developers|graph)\\.)[-\\w.]*facebook\\.com/(?:[/\\w]+/permalink|(?!pages/|groups/).*?)(?:/|fbid=|\\?v=)(?<id>\\d+)(?=$|[/?&#])@','@facebook\\.com/(?<user>\\w+)/(?<type>post|video)s?/@','@facebook\\.com/video/(?<type>post|video)\\.php@']],
		'flickr'=>[['@flickr\\.com/photos/[^/]+/(?<id>\\d+)@','@flic\\.kr/(?!p/)[^/]+/(?<id>\\d+)@'],[],[['extract'=>['@flickr\\.com/photos/[^/]+/(?<id>\\d+)@'],'match'=>["@flic\\.kr/p/(?'short'\\w+)@"],'url'=>'https://www.flickr.com/photo.gne?rb=1&short={@short}']]],
		'foratv'=>[[],[],[['extract'=>['!embed\\?id=(?<id>\\d+)!'],'match'=>['!fora\\.tv/\\d+/\\d+/\\d+/.!']]]],
		'foxnews'=>[['!video\\.foxnews\\.com/v/(?<id>\\d+)!']],
		'foxsports'=>[[],[],[['extract'=>['@BKQ29B/(?<id>\\w+)@'],'match'=>['@/video/\\d@']]]],
		'funnyordie'=>[['!funnyordie\\.com/videos/(?<id>[0-9a-f]+)!']],
		'gamespot'=>[['!gamespot\\.com.*?/(?:events|videos)/.*?-(?<id>\\d+)/(?:[#?].*)?$!']],
		'gametrailers'=>[[],[],[['extract'=>['!embed/(?<id>\\d+)!'],'match'=>['!gametrailers\\.com/(?:full-episode|review|video)s/!']]]],
		'getty'=>[['!gty\\.im/(?<id>\\d+)!','!gettyimages\\.[.\\w]+/detail(?=/).*?/(?<id>\\d+)!','!#[-\\w]*picture-id(?<id>\\d+)$!'],[],[['extract'=>['!"height":[ "]*(?<height>\\d+)!','!"width":[ "]*(?<width>\\d+)!','!\\bid[=:][\'"]?(?<et>[-=\\w]+)!','!\\bsig[=:][\'"]?(?<sig>[-=\\w]+)!'],'match'=>['//'],'url'=>'http://embed.gettyimages.com/preview/{@id}']],['height'=>['s9e\\MediaSites\\Parser::filterUint'],'width'=>['s9e\\MediaSites\\Parser::filterUint']]],
		'gfycat'=>[['!gfycat\\.com/(?:gifs/detail/)?(?<id>\\w+)!'],[],[['extract'=>['!meta [^>]*?="\\w+:\\w+:height" content="(?<height>\\d+)!','!meta [^>]*?="\\w+:\\w+:width" content="(?<width>\\d+)!'],'match'=>['//'],'url'=>'https://gfycat.com/ifr/{@id}']],['height'=>['s9e\\MediaSites\\Parser::filterUint'],'width'=>['s9e\\MediaSites\\Parser::filterUint']]],
		'gifs'=>[['!gifs\\.com/(?:gif/)?(?<id>\\w+)!'],[],[['extract'=>['!meta property="og:image:width" content="(?<width>\\d+)!','!meta property="og:image:height" content="(?<height>\\d+)!'],'match'=>['//'],'url'=>'https://gifs.com/gif/{@id}']],['height'=>['s9e\\MediaSites\\Parser::filterUint'],'width'=>['s9e\\MediaSites\\Parser::filterUint']]],
		'gist'=>[['!gist\\.github\\.com/(?<id>(?:\\w+/)?[\\da-f]+(?:/[\\da-f]+)?)!']],
		'globalnews'=>[['!globalnews\\.ca/video/(?<id>\\d+)!']],
		'gofundme'=>[['@gofundme\\.com/(?<id>\\w+)(?![^#?])@']],
		'googledrive'=>[['!drive\\.google\\.com/.*?(?:file/d/|id=)(?<id>[-\\w]+)!']],
		'googleplus'=>[['!//plus\\.google\\.com/(?:u/\\d+/)?(?:\\+(?<name>[^/]+)|(?<oid>\\d+))/posts/(?<pid>\\w+)!'],[],[],['name'=>['urldecode']]],
		'googlesheets'=>[['@docs\\.google\\.com/spreadsheet(?:/ccc\\?key=|s/d/)(?!e/)(?<id>[-\\w]+)[^#]*(?:#gid=(?<gid>\\d+))?@']],
		'healthguru'=>[[],[],[['extract'=>['!healthguru\\.com/embed/(?<id>\\w+)!'],'match'=>['!healthguru\\.com/(?:content/)?video/.!']]]],
		'hudl'=>[['!hudl\\.com/athlete/(?<athlete>\\d+)/highlights/(?<highlight>[\\da-f]+)!','!hudl\\.com/video/\\d+/(?<athlete>\\d+)/(?<highlight>[\\da-f]+)!'],[],[['extract'=>['!hudl\\.com/video/\\d+/(?<athlete>\\d+)/(?<highlight>[\\da-f]+)!'],'match'=>['!hudl\\.com/v/!']]]],
		'hulu'=>[[],[],[['extract'=>['!eid=(?<id>[-\\w]+)!'],'match'=>['!hulu\\.com/watch/!']]]],
		'humortvnl'=>[['!humortv\\.vara\\.nl/\\w+\\.(?<id>[-.\\w]+)\\.html!']],
		'ign'=>[['!(?<id>https?://.*?ign\\.com/videos/.+)!i']],
		'imdb'=>[['!imdb\\.com/[/\\w]+/vi(?<id>\\d+)!']],
		'imgur'=>[['@imgur\\.com/(?<id>a/\\w+)@','@i\\.imgur\\.com/(?<id>\\w{5,7})[lms]?\\.@','@imgur\\.com/(?<id>\\w+)(?![\\w./])@'],[],[['extract'=>['@data-id="(?<id>[\\w/]+)"@'],'match'=>["@imgur\\.com/(?![art]/|user/)(?'path'(?:gallery/)?\\w+)(?![\\w.])@"],'url'=>'https://api.imgur.com/oembed.xml?url=/{@path}']]],
		'indiegogo'=>[['!indiegogo\\.com/projects/(?<id>[-\\w]+)!']],
		'instagram'=>[['!instagram\\.com/p/(?<id>[-\\w]+)!']],
		'internetarchive'=>[[],[],[['extract'=>['!meta property="twitter:player" content="https://archive.org/embed/(?<id>[^/"]+)!','!meta property="og:video:width" content="(?<width>\\d+)!','!meta property="og:video:height" content="(?<height>\\d+)!'],'match'=>['!archive\\.org/details/!']]],['height'=>['s9e\\MediaSites\\Parser::filterUint'],'width'=>['s9e\\MediaSites\\Parser::filterUint']]],
		'izlesene'=>[['!izlesene\\.com/video/[-\\w]+/(?<id>\\d+)!']],
		'jwplatform'=>[['!jwplatform\\.com/\\w+/(?<id>[-\\w]+)!']],
		'khl'=>[[],[],[['extract'=>['!/feed/start/(?<id>[/\\w]+)!'],'match'=>['!video\\.khl\\.ru/(?:event|quote)s/\\d!']]]],
		'kickstarter'=>[['!kickstarter\\.com/projects/(?<id>[^/]+/[^/?]+)(?:/widget/(?:(?<card>card)|(?<video>video)))?!']],
		'kissvideo'=>[['!kissvideo\\.click/[^_]*_(?<id>[0-9a-f]+)!']],
		'libsyn'=>[[],[],[['extract'=>['!embed/episode/id/(?<id>\\d+)!'],'match'=>['@(?!\\.mp3)....$@']]]],
		'livecap'=>[['!livecap.tv/[st]/(?<channel>\\w+)/(?<id>\\w+)!']],
		'liveleak'=>[['!liveleak\\.com/(?:e/|view\\?i=)(?<id>\\w+)!'],[],[['extract'=>['!liveleak\\.com/e/(?<id>\\w+)!'],'match'=>['!liveleak\\.com/view\\?t=!']]]],
		'livestream'=>[['!livestream\\.com/accounts/(?<account_id>\\d+)/events/(?<event_id>\\d+)!','!/videos/(?<video_id>\\d+)!','!original\\.livestream\\.com/(?<channel>\\w+)/video\\?clipId=(?<clip_id>[-\\w]+)!'],[],[['extract'=>['!accounts/(?<account_id>\\d+)/events/(?<event_id>\\d+)!'],'match'=>['@livestream\\.com/(?!accounts/\\d+/events/\\d)@']],['extract'=>['!//original\\.livestream\\.com/(?<channel>\\w+)/video/(?<clip_id>[-\\w]+)!'],'match'=>['!livestre.am!']]]],
		'mailru'=>[[],[],[['extract'=>['!"itemId": ?"?(?<id>\\d+)!'],'match'=>['!my\\.mail\\.ru/\\w+/\\w+/video/\\w+/\\d!']]]],
		'medium'=>[['!medium\\.com/[^/]*/(?:[-\\w]+-)?(?<id>[\\da-f]+)!']],
		'metacafe'=>[['!metacafe\\.com/watch/(?<id>\\d+)!']],
		'mixcloud'=>[['@mixcloud\\.com/(?!categories|tag)(?<id>[-\\w]+/[^/&]+)/@']],
		'mlb'=>[['#mlb\\.com/video/(?:[-\\w/]+/)?(?:c-|v)(?<id>\\d+)#']],
		'mrctv'=>[[],[],[['extract'=>['!mrctv\\.org/embed/(?<id>\\d+)!'],'match'=>['!mrctv\\.org/videos/.!']]]],
		'msnbc'=>[[],[],[['extract'=>['@property="nv:videoId" content="(?<id>\\w+)@','@guid"?[=:]"?(?<id>\\w+)@'],'match'=>['@msnbc\\.com/[-\\w]+/watch/@','@on\\.msnbc\\.com/.@']]]],
		'natgeochannel'=>[['@channel\\.nationalgeographic\\.com/(?<id>[-/\\w]+/videos/[-\\w]+)@']],
		'natgeovideo'=>[[],[],[['extract'=>['@guid="(?<id>[-\\w]+)"@'],'match'=>['@video\\.nationalgeographic\\.com/(?:tv|video)/\\w@']]]],
		'nbcnews'=>[['!nbcnews\\.com/(?:widget/video-embed/|video/[-\\w]+?-)(?<id>\\d+)!']],
		'nbcsports'=>[[],[],[['extract'=>['!select/media/(?<id>\\w+)!'],'match'=>['!nbcsports\\.com/video/.!']]]],
		'nhl'=>[['#nhl\\.com/(?:\\w+/)?video(?:/(?![ct]-)[-\\w]+)?(?:/t-(?<t>\\d+))?(?:/c-(?<c>\\d+))?#']],
		'npr'=>[[],[],[['extract'=>['!player/embed/(?<i>\\d+)/(?<m>\\d+)!'],'match'=>['!npr\\.org/[/\\w]+/\\d+!','!n\\.pr/\\w!']]]],
		'nytimes'=>[['!nytimes\\.com/video/[a-z]+/(?:[a-z]+/)?(?<id>\\d+)!','!nytimes\\.com/video/\\d+/\\d+/\\d+/[a-z]+/(?<id>\\d+)!'],[],[['extract'=>['!/video/movies/(?<id>\\d+)!'],'match'=>["!nytimes\\.com/movie(?:s/movie)?/(?'playlist'\\d+)/[-\\w]+/trailers!"],'url'=>'http://www.nytimes.com/svc/video/api/playlist/{@playlist}?externalId=true']]],
		'orfium'=>[['@album/(?<album_id>\\d+)@','@playlist/(?<playlist_id>\\d+)@','@live-set/(?<set_id>\\d+)@','@track/(?<track_id>\\d+)@']],
		'pastebin'=>[['@pastebin\\.com/(?!u/)(?:\\w+(?:\\.php\\?i=|/))?(?<id>\\w+)@']],
		'pinterest'=>[['@pinterest.com/pin/(?<id>\\d+)@','@pinterest.com/(?!_/|discover/|explore/|news_hub/|pin/|search/)(?<id>[-\\w]+/[-\\w]+)@']],
		'playstv'=>[['!plays\\.tv/video/(?<id>\\w+)!'],[],[['extract'=>['!plays\\.tv/video/(?<id>\\w+)!'],'match'=>['!plays\\.tv/s/!']]]],
		'podbean'=>[['!podbean\\.com/media/(?:player/|share/pb-)(?<id>[-\\w]+)!'],[],[['extract'=>['!podbean\\.com/media/player/(?<id>[-\\w]+)!'],'match'=>['@podbean\\.com/(?:media/shar)?e/(?!pb-)@']]]],
		'prezi'=>[['#//prezi\\.com/(?!(?:a(?:bout|mbassadors)|c(?:o(?:llaborate|mmunity|ntact)|reate)|exp(?:erts|lore)|ip(?:ad|hone)|jobs|l(?:ear|ogi)n|m(?:ac|obility)|pr(?:es(?:s|ent)|icing)|recommend|support|user|windows|your)/)(?<id>\\w+)/#']],
		'reddit'=>[['!(?<id>\\w+/comments/\\w+(?:/\\w+/\\w+)?)!']],
		'rutube'=>[['!rutube\\.ru/tracks/(?<id>\\d+)!'],[],[['extract'=>['!rutube\\.ru/play/embed/(?<id>\\d+)!'],'match'=>['!rutube\\.ru/video/[0-9a-f]{32}!']]]],
		'scribd'=>[['!scribd\\.com/(?:mobile/)?doc(?:ument)?/(?<id>\\d+)!']],
		'slideshare'=>[['!slideshare\\.net/[^/]+/[-\\w]+-(?<id>\\d{6,})$!'],[],[['extract'=>['!"presentationId":(?<id>\\d+)!'],'match'=>['@slideshare\\.net/[^/]+/\\w(?![-\\w]+-\\d{6,}$)@']]]],
		'soundcloud'=>[['@https?://(?:api\\.)?soundcloud\\.com/(?!pages/)(?<id>[-/\\w]+/[-/\\w]+|^[^/]+/[^/]+$)@i','@api\\.soundcloud\\.com/playlists/(?<playlist_id>\\d+)@','@api\\.soundcloud\\.com/tracks/(?<track_id>\\d+)(?:\\?secret_token=(?<secret_token>[-\\w]+))?@','@soundcloud\\.com/(?!playlists|tracks)[-\\w]+/[-\\w]+/(?=s-)(?<secret_token>[-\\w]+)@'],[],[['extract'=>['@soundcloud:tracks:(?<track_id>\\d+)@'],'match'=>['@soundcloud\\.com/(?!playlists/\\d|tracks/\\d)[-\\w]+/[-\\w]@']],['extract'=>['@soundcloud://playlists:(?<playlist_id>\\d+)@'],'match'=>['@soundcloud\\.com/\\w+/sets/@']]]],
		'sportsnet'=>[[],[],[['extract'=>['/vid(?:eoId)?=(?<id>\\d+)/','/param name="@videoPlayer" value="(?<id>\\d+)"/'],'match'=>['//']]]],
		'spotify'=>[['!(?:open|play)\\.spotify\\.com/(?<id>(?:album|artist|track|user)(?:[:/][-.\\w]+)+)!']],
		'steamstore'=>[['!store.steampowered.com/app/(?<id>\\d+)!']],
		'stitcher'=>[[],[],[['extract'=>['!data-eid="(?<eid>\\d+)!','!data-fid="(?<fid>\\d+)!'],'match'=>['!/podcast/!']]]],
		'strawpoll'=>[['!strawpoll\\.me/(?<id>\\d+)!']],
		'streamable'=>[['!streamable\\.com/(?<id>\\w+)!']],
		'teamcoco'=>[['!teamcoco\\.com/video/(?<id>\\d+)!'],[],[['extract'=>['!embed/v/(?<id>\\d+)!'],'match'=>['!teamcoco\\.com/video/.!']]]],
		'ted'=>[['#ted\\.com/(?<id>(?:talk|playlist)s/[-\\w]+(?:\\.html)?)(?![-\\w]|/transcript)#i']],
		'telegram'=>[['@//t.me/(?!addstickers/|joinchat/)(?<id>\\w+/\\d+)@']],
		'theatlantic'=>[['!theatlantic\\.com/video/index/(?<id>\\d+)!']],
		'theguardian'=>[['!theguardian\\.com/(?<id>\\w+/video/[-/\\w]+)!']],
		'theonion'=>[['!theonion\\.com/video/[-\\w]+[-,](?<id>\\d+)!']],
		'tinypic'=>[['!tinypic\\.com/player\\.php\\?v=(?<id>\\w+)&s=(?<s>\\d+)!','!tinypic\\.com/r/(?<id>\\w+)/(?<s>\\d+)!'],[],[['extract'=>['!file=(?<id>\\w+)&amp;s=(?<s>\\d+)!'],'match'=>['!tinypic\\.com/(?:m|usermedia)/!']]]],
		'tmz'=>[['@tmz\\.com/videos/(?<id>\\w+)@']],
		'traileraddict'=>[[],[],[['extract'=>['@v\\.traileraddict\\.com/(?<id>\\d+)@'],'match'=>['@traileraddict\\.com/(?!tags/)[^/]+/.@']]]],
		'tumblr'=>[['!(?<name>[-\\w]+)\\.tumblr\\.com/post/(?<id>\\d+)!'],[],[['extract'=>['!did=\\\\u0022(?<did>[-\\w]+)!','!embed\\\\/post\\\\/(?<key>[-\\w]+)!'],'match'=>['!\\w\\.tumblr\\.com/post/\\d!'],'url'=>'http://www.tumblr.com/oembed/1.0?url=http://{@name}.tumblr.com/post/{@id}']]],
		'twitch'=>[['#twitch\\.tv/(?:videos|\\w+/v)/(?<video_id>\\d+)?#','#www\\.twitch\\.tv/(?!videos/)(?<channel>\\w+)#','#t=(?<t>(?:(?:\\d+h)?\\d+m)?\\d+s)#','#clips\\.twitch\\.tv/(?:(?<channel>\\w+)/)?(?<clip_id>\\w+)#']],
		'twitter'=>[['@twitter\\.com/(?:#!/)?\\w+/status(?:es)?/(?<id>\\d+)@']],
		'ustream'=>[['!ustream\\.tv/recorded/(?<vid>\\d+)!'],[],[['extract'=>['!embed/(?<cid>\\d+)!'],'match'=>['#ustream\\.tv/(?!explore/|platform/|recorded/|search\\?|upcoming$|user/)(?:channel/)?[-\\w]+#']]]],
		'vbox7'=>[['!vbox7\\.com/play:(?<id>[\\da-f]+)!']],
		'veoh'=>[['!veoh\\.com/(?:m/watch\\.php\\?v=|watch/)v(?<id>\\w+)!']],
		'vevo'=>[['!vevo\\.com/watch/(.*?/)?(?<id>[A-Z]+\\d+)!']],
		'viagame'=>[['!viagame\\.com/channels/[^/]+/(?<id>\\d+)!']],
		'videodetective'=>[['!videodetective\\.com/\\w+/[-\\w]+/(?:trailer/P0*)?(?<id>\\d+)!']],
		'videomega'=>[['!videomega\\.tv/\\?ref=(?<id>\\w+)!']],
		'vimeo'=>[['!vimeo\\.com/(?:channels/[^/]+/|video/)?(?<id>\\d+)!','!#t=(?<t>[\\dhms]+)!'],[],[],['t'=>['s9e\\MediaSites\\Parser::filterTimestamp']]],
		'vine'=>[['!vine\\.co/v/(?<id>[^/]+)!']],
		'vk'=>[['!vk(?:\\.com|ontakte\\.ru)/(?:[\\w.]+\\?z=)?video(?<oid>-?\\d+)_(?<vid>\\d+)!','!vk(?:\\.com|ontakte\\.ru)/video_ext\\.php\\?oid=(?<oid>-?\\d+)&id=(?<vid>\\d+)&hash=(?<hash>[0-9a-f]+)!'],[],[['extract'=>['!embed_hash(?:=|":")(?<hash>[0-9a-f]+)!'],'match'=>['!vk.*?video-?\\d+_\\d+!'],'url'=>'http://vk.com/video{@oid}_{@vid}']]],
		'vocaroo'=>[['!vocaroo\\.com/i/(?<id>\\w+)!']],
		'vox'=>[['!vox.com/.*#ooid=(?<id>[-\\w]+)!']],
		'washingtonpost'=>[['#washingtonpost\\.com/video/c/\\w+/(?<id>[-0-9a-f]+)#','#washingtonpost\\.com/video/[-/\\w]+/(?<id>[-0-9a-f]+)_video\\.html#']],
		'wshh'=>[['!worldstarhiphop\\.com/featured/(?<id>\\d+)!'],[],[['extract'=>['!v: ?"?(?<id>\\d+)!'],'match'=>['!worldstarhiphop\\.com/(?:\\w+/)?video\\.php\\?v=\\w+!']]]],
		'wsj'=>[['@wsj\\.com/[^#]*#!(?<id>[-0-9A-F]{36})@','@wsj\\.com/video/[^/]+/(?<id>[-0-9A-F]{36})@'],[],[['extract'=>['@guid=(?<id>[-0-9A-F]{36})@'],'match'=>['@on\\.wsj\\.com/\\w@']]]],
		'xboxclips'=>[['@xboxclips\\.com/(?<user>[^/]+)/(?!screenshots/)(?<id>[-0-9a-f]+)@']],
		'xboxdvr'=>[['!xboxdvr\\.com/gamer/(?<user>[^/]+)/video/(?<id>\\d+)!']],
		'yahooscreen'=>[['!screen\\.yahoo\\.com/(?:[-\\w]+/)?(?<id>[-\\w]+)\\.html!']],
		'youku'=>[['!youku\\.com/v(?:_show|ideo)/id_(?<id>\\w+)!']],
		'youtube'=>[['!youtube\\.com/(?:watch.*?v=|v/|attribution_link.*?v%3D)(?<id>[-\\w]+)!','!youtu\\.be/(?<id>[-\\w]+)!','@[#&?]t=(?<t>\\d[\\dhms]*)@','!&list=(?<list>[-\\w]+)!'],[],[['extract'=>['!/vi/(?<id>[-\\w]+)!'],'match'=>['!/shared\\?ci=!']]],['id'=>['s9e\\MediaSites\\Parser::filterIdentifier'],'t'=>['s9e\\MediaSites\\Parser::filterTimestamp']]]
	];

	/**
	* Match given URL and return a media key
	*
	* @param  string          $url       Original URL
	* @param  string          $matchedId Unused
	* @param  BbCodeMediaSite $site      Unused
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
					// Add named captures to the vars without overwriting existing vars
					if (!is_numeric($k) && !isset($vars[$k]) && $v !== '')
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
		if (isset($vars['type'], $vars['user']) && $vars['type'] === 'post')
		{
			$vars = ['id' => $vars['id'], 'post' => 'post', 'user' => $vars['user']];
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
	* Filter an identifier value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	protected static function filterIdentifier($attrValue)
	{
		return (preg_match('/^[-0-9A-Za-z_]+$/D', $attrValue)) ? $attrValue : false;
	}

	/**
	* Filter a timestamp value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	protected static function filterTimestamp($attrValue)
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
	protected static function filterUint($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_INT, [
			'options' => ['min_range' => 0]
		]);
	}

	/**
	* Filter an array of vars with through an array of callbacks
	*
	* @param  array      $vars    Original vars
	* @param  callable[] $filters Numerically-indexed array of callbacks
	* @return array               Filtered vars
	*/
	protected static function filterVars(array $vars, array $filters)
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

			self::addNamedCaptures($vars, self::wget($url), $config['extract']);
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
			foreach (self::$customFormats[$siteId] as list($customKeys, $format))
			{
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
	* @param  string $url Target URL
	* @return string      Response body
	*/
	protected static function wget($url)
	{
		$url = preg_replace('(#.*)s', '', $url);

		// Return the content from the cache if applicable
		if (isset(self::$cacheDir) && file_exists(self::$cacheDir))
		{
			$cacheFile = self::$cacheDir . '/http.' . crc32($url) . '.html';
			if (file_exists($cacheFile))
			{
				return file_get_contents($cacheFile);
			}
		}

		$html = self::wgetCurl($url);
		if ($html && isset($cacheFile))
		{
			file_put_contents($cacheFile, $html);
		}

		return $html;
	}

	/**
	* Retrieve content from given URL via cURL
	*
	* @param  string $url Target URL
	* @return string      Response body
	*/
	protected static function wgetCurl($url)
	{
		static $curl;
		if (!isset($curl))
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_ENCODING,       '');
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_USERAGENT,      'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0');
		}
		curl_setopt($curl, CURLOPT_URL, $url);

		return curl_exec($curl);
	}
}