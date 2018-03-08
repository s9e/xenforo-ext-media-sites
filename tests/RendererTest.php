<?php

namespace s9e\MediaSites\Tests;

use PHPUnit\Framework\TestCase;
use XF;
use s9e\MediaSites\Renderer;

/**
* @covers s9e\MediaSites\Renderer
*/
class RendererTest extends TestCase
{
	public function testUnknown()
	{
		$this->assertSame('', Renderer::render('foo', [], 'foo'));
	}

	/**
	* @dataProvider getRenderTests
	*/
	public function testRender($siteId, $mediaKey, $expected, $options = [])
	{
		XF::$options = (object) $options;
		$this->assertEquals($expected, Renderer::render($mediaKey, [], $siteId));
	}

	public function getRenderTests()
	{
		return [
			[
				'amazon',
				'B002MUC0ZY',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&amp;OneJS=1&amp;Operation=GetAdHtml&amp;MarketPlace=US&amp;ad_type=product_link&amp;tracking_id=-20&amp;marketplace=amazon&amp;region=US&amp;asins=B002MUC0ZY&amp;show_border=true&amp;link_opens_in_new_window=true"></iframe></span></span>'
			],
			[
				'amazon',
				'B002MUC0ZY',
				'<span data-s9e-mediaembed="amazon" style="display:inline-block;width:100%;max-width:120px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:200%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&amp;OneJS=1&amp;Operation=GetAdHtml&amp;MarketPlace=US&amp;ad_type=product_link&amp;tracking_id=foo-20&amp;marketplace=amazon&amp;region=US&amp;asins=B002MUC0ZY&amp;show_border=true&amp;link_opens_in_new_window=true"></iframe></span></span>',
				['s9e_MediaSites_AMAZON_ASSOCIATE_TAG' => 'foo-20']
			],
			[
				'gfycat',
				'height=338;id=SereneIllfatedCapybara;width=600',
				'<span data-s9e-mediaembed="gfycat" style="display:inline-block;width:100%;max-width:600px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.333333333333%"><iframe allowfullscreen="" scrolling="no" src="//gfycat.com/iframe/SereneIllfatedCapybara" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'
			],
			[
				'dailymotion',
				'x5e9eog',
				'<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/x5e9eog"></iframe></span></span>'
			],
			[
				'dailymotion',
				'x5e9eog:33',
				'<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/x5e9eog?start=33"></iframe></span></span>'
			],
			[
				'dailymotion',
				'id=x5e9eog;t=33',
				'<span data-s9e-mediaembed="dailymotion" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//www.dailymotion.com/embed/video/x5e9eog?start=33"></iframe></span></span>'
			],
			[
				'facebook',
				'FacebookDevelopers/posts/10151471074398553',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/facebook.min.html#post10151471074398553" style="border:0;height:360px;max-width:640px;width:100%"></iframe>'
			],
			[
				'facebook',
				'10151471074398553',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/facebook.min.html#10151471074398553" style="border:0;height:360px;max-width:640px;width:100%"></iframe>'
			],
			[
				'facebook',
				'id=10150451523596807;type=video',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/facebook.min.html#video10150451523596807" style="border:0;height:360px;max-width:640px;width:100%"></iframe>'
			],
			[
				'flickr',
				'2971804544',
				'<span data-s9e-mediaembed="flickr" style="display:inline-block;width:100%;max-width:500px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://www.flickr.com/photos/_/2971804544/player/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'
			],
			[
				'flickr',
				'5wBgXo',
				'<span data-s9e-mediaembed="flickr" style="display:inline-block;width:100%;max-width:500px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://www.flickr.com/photos/_/2971804544/player/" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'
			],
			[
				'imgur',
				'id=9UGCL;type=album',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" onload="var b=Math.random();window.addEventListener(\'message\',function(a){a.data.id==b&amp;&amp;(style.height=a.data.height+\'px\',style.width=a.data.width+\'px\')});contentWindow.postMessage(\'s9e:\'+b,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/imgur.min.html#9UGCL" style="border:0;height:450px;max-width:100%;width:568px"></iframe>'
			],
			[
				'imgur',
				'a/9UGCL',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" onload="var b=Math.random();window.addEventListener(\'message\',function(a){a.data.id==b&amp;&amp;(style.height=a.data.height+\'px\',style.width=a.data.width+\'px\')});contentWindow.postMessage(\'s9e:\'+b,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/imgur.min.html#a/9UGCL" style="border:0;height:450px;max-width:100%;width:568px"></iframe>'
			],
			[
				'reddit',
				'path=%2Fr%2Fpics%2Fcomments%2F304rms%2F',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/reddit.min.html#pics/comments/304rms" style="border:0;height:165px;max-width:800px;width:100%"></iframe>'
			],
			[
				'reddit',
				'pics/comments/304rms',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/reddit.min.html#pics/comments/304rms" style="border:0;height:165px;max-width:800px;width:100%"></iframe>'
			],
			[
				'reddit',
				'path=%2Fr%2Fpics%2Fcomments%2F304rms%2Fcats_reaction_to_seeing_the_ceiling_fan_move_for%2Fcpp2kkl',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/reddit.min.html#pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl" style="border:0;height:165px;max-width:800px;width:100%"></iframe>'
			],
			[
				'reddit',
				'pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/reddit.min.html#pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl" style="border:0;height:165px;max-width:800px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'id=tracks%2F98282116;track_id=98282116',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/98282116&amp;secret_token=" style="border:0;height:166px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'id=andrewbird%2Fthree-white-horses;track_id=59509713',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/59509713&amp;secret_token=" style="border:0;height:166px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'id=tenaciousd%2Fsets%2Frize-of-the-fenix%2F;playlist_id=1919974;track_id=44564704',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/1919974" style="border:0;height:450px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'playlists/1919974',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/1919974" style="border:0;height:450px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'tracks/98282116',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/98282116&amp;secret_token=" style="border:0;height:166px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'andrewbird/three-white-horses',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/andrewbird/three-white-horses" style="border:0;height:166px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'tenaciousd/sets/rize-of-the-fenix',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/tenaciousd/sets/rize-of-the-fenix" style="border:0;height:450px;max-width:900px;width:100%"></iframe>'
			],
			[
				'soundcloud',
				'cnn/newsday062413#t=2:10',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/cnn/newsday062413" style="border:0;height:166px;max-width:900px;width:100%"></iframe>'
			],
			[
				'spotify',
				'user:ozmoetr:playlist:4yRrCWNhWOqWZx5lmFqZvt',
				'<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allow="encrypted-media" allowfullscreen="" scrolling="no" src="https://open.spotify.com/embed/user/ozmoetr/playlist/4yRrCWNhWOqWZx5lmFqZvt" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'
			],
			[
				'spotify',
				'path=user%3Aozmoetr%3Aplaylist%3A4yRrCWNhWOqWZx5lmFqZvt',
				'<span data-s9e-mediaembed="spotify" style="display:inline-block;width:100%;max-width:400px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:100%"><iframe allow="encrypted-media" allowfullscreen="" scrolling="no" src="https://open.spotify.com/embed/user:ozmoetr:playlist:4yRrCWNhWOqWZx5lmFqZvt" style="border:0;height:100%;left:0;position:absolute;width:100%"></iframe></span></span>'
			],
			[
				'tumblr',
				'did=5f3b4bc6718317df9c2b1e77c20839ab94f949cd;id=104191225637;key=uFhWDPKj-bGU0ZlDAnUyxg;name=mrbenvey',
				'<iframe data-s9e-mediaembed="tumblr" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/tumblr.min.html#uFhWDPKj-bGU0ZlDAnUyxg/104191225637" style="border:0;height:180px;max-width:520px;width:100%"></iframe>'
			],
			[
				'twitch',
				'twitch',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>'
			],
			[
				'twitch',
				'channel=twitch',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>'
			],
			[
				'twitch',
				'channel=twitch;t=17m17s;video_id=29415830',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;video=v29415830&amp;time=17m17s"></iframe></span></span>'
			],
			[
				'twitch',
				'29415830:17m17s',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;video=v29415830&amp;time=17m17s"></iframe></span></span>'
			],
			[
				'twitch',
				'clip:NeighborlyBetterJellyfishWTRuck',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//clips.twitch.tv/embed?autoplay=false&amp;clip=NeighborlyBetterJellyfishWTRuck"></iframe></span></span>'
			],
			[
				'twitch',
				'channel=twitch;clip_id=HorribleWoodpeckerHassanChop',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//clips.twitch.tv/embed?autoplay=false&amp;clip=twitch/HorribleWoodpeckerHassanChop"></iframe></span></span>'
			],
			[
				'vimeo',
				'67207222',
				'<span data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.vimeo.com/video/67207222"></iframe></span></span>'
			],
			[
				'vimeo',
				'67207222:90',
				'<span data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.vimeo.com/video/67207222#t=90"></iframe></span></span>'
			],
			[
				'vimeo',
				'67207222:1m30s',
				'<span data-s9e-mediaembed="vimeo" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.vimeo.com/video/67207222#t=1m30s"></iframe></span></span>'
			],
			[
				'youtube',
				'QH2-TGUlwu4',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>'
			],
			[
				'youtube',
				'QH2-TGUlwu4:95',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4?start=95"></iframe></span></span>'
			],
			[
				'youtube',
				'id=QH2-TGUlwu4;m=1;s=35',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4?start=95"></iframe></span></span>'
			],
			[
				'youtube',
				'id=QH2-TGUlwu4;t=95',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/QH2-TGUlwu4?start=95"></iframe></span></span>'
			],
			[
				'youtube',
				'id=k-baHBzWe4k;list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1',
				'<span data-s9e-mediaembed="youtube" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/k-baHBzWe4k/hqdefault.jpg) 50% 50% / cover;border:0;height:100%;left:0;position:absolute;width:100%" src="https://www.youtube.com/embed/k-baHBzWe4k?list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1"></iframe></span></span>'
			],
		];
	}
}