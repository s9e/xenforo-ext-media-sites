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
		$this->assertSame(
			'<div class="blockMessage blockMessage--error blockMessage--iconic">Template <b>public:_media_site_embed_foo</b> not found. Try rebuilding or reinstalling the s9e/MediaSites add-on.</div>',
			Renderer::render('foo', [], 'foo')
		);
	}

	/**
	* @dataProvider getRenderTests
	*/
	public function testRender($siteId, $mediaKey, $expected, $options = [], $styleProperties = [])
	{
		XF::$options         = (object) $options;
		XF::$styleProperties = $styleProperties;
		$this->assertEquals($expected, Renderer::render($mediaKey, [], $siteId));
	}

	public function getRenderTests()
	{
		return [
			[
				'amazon',
				'B002MUC0ZY',
				'<span data-s9e-mediaembed="amazon" style="width:120px"><span style="padding-bottom:200%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat" src="//ws-na.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=1&amp;t=&amp;asins=B002MUC0ZY"></iframe></span></span>',
				['s9e_MediaSites_AMAZON_ASSOCIATE_TAG' => null]
			],
			[
				'amazon',
				'B002MUC0ZY',
				'<span data-s9e-mediaembed="amazon" style="width:120px"><span style="padding-bottom:200%"><iframe allowfullscreen="" scrolling="no" style="background:url(https://www.amazon.com/favicon.ico) center no-repeat" src="//ws-na.assoc-amazon.com/widgets/cm?l=as1&amp;f=ifr&amp;o=1&amp;t=foo-20&amp;asins=B002MUC0ZY"></iframe></span></span>',
				['s9e_MediaSites_AMAZON_ASSOCIATE_TAG' => 'foo-20']
			],
			[
				'gfycat',
				'height=338;id=SereneIllfatedCapybara;width=600',
				'<span data-s9e-mediaembed="gfycat" style="width:600px"><span style="padding-bottom:63.666666666667%;padding-bottom:calc(56.333333333333% + 44px)"><iframe allowfullscreen="" scrolling="no" src="//gfycat.com/ifr/SereneIllfatedCapybara?hd=1"></iframe></span></span>'
			],
			[
				'dailymotion',
				'x5e9eog',
				'<span data-s9e-mediaembed="dailymotion"><span><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/x5e9eog"></iframe></span></span>'
			],
			[
				'dailymotion',
				'x5e9eog:33',
				'<span data-s9e-mediaembed="dailymotion"><span><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/x5e9eog?start=33"></iframe></span></span>'
			],
			[
				'dailymotion',
				'id=x5e9eog;t=33',
				'<span data-s9e-mediaembed="dailymotion"><span><iframe allowfullscreen="" scrolling="no" src="//www.dailymotion.com/embed/video/x5e9eog?start=33"></iframe></span></span>'
			],
			[
				'facebook',
				'FacebookDevelopers/posts/10151471074398553',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" style="height:360px" data-s9e-mediaembed-api="2" src="https://s9e.github.io/iframe/2/facebook.min.html#p10151471074398553"></iframe>'
			],
			[
				'facebook',
				'10151471074398553',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" style="height:360px" data-s9e-mediaembed-api="2" src="https://s9e.github.io/iframe/2/facebook.min.html#10151471074398553"></iframe>'
			],
			[
				'facebook',
				'id=10150451523596807;type=video',
				'<iframe data-s9e-mediaembed="facebook" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" style="height:360px" data-s9e-mediaembed-api="2" src="https://s9e.github.io/iframe/2/facebook.min.html#video10150451523596807"></iframe>'
			],
			[
				'flickr',
				'2971804544',
				'<span data-s9e-mediaembed="flickr" style="width:500px"><span style="padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://www.flickr.com/photos/_/2971804544/player/"></iframe></span></span>'
			],
			[
				'flickr',
				'5wBgXo',
				'<span data-s9e-mediaembed="flickr" style="width:500px"><span style="padding-bottom:100%"><iframe allowfullscreen="" scrolling="no" src="https://www.flickr.com/photos/_/2971804544/player/"></iframe></span></span>'
			],
			[
				'gifs',
				'zm4DLy',
				'<span data-s9e-mediaembed="gifs" style="width:640px"><span style="padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="//gifs.com/embed/zm4DLy"></iframe></span></span>'
			],
			[
				'imgur',
				'id=9UGCL;type=album',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;{let s=this.style,d=e.data.split(\' \');s.height=d[0]+\'px\';s.width=d[1]+\'px\'};this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" style="height:400px;width:542px" data-s9e-mediaembed-api="2" src="https://s9e.github.io/iframe/2/imgur.min.html#a/9UGCL"></iframe>'
			],
			[
				'imgur',
				'a/9UGCL',
				'<iframe data-s9e-mediaembed="imgur" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;{let s=this.style,d=e.data.split(\' \');s.height=d[0]+\'px\';s.width=d[1]+\'px\'};this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" style="height:400px;width:542px" data-s9e-mediaembed-api="2" src="https://s9e.github.io/iframe/2/imgur.min.html#a/9UGCL"></iframe>'
			],
			[
				'reddit',
				'path=%2Fr%2Fpics%2Fcomments%2F304rms%2F',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/reddit.min.html#pics/comments/304rms#theme=" style="height:165px;width:800px" data-s9e-mediaembed-api="2"></iframe>'
			],
			[
				'reddit',
				'pics/comments/304rms',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/reddit.min.html#pics/comments/304rms#theme=" style="height:165px;width:800px" data-s9e-mediaembed-api="2"></iframe>'
			],
			[
				'reddit',
				'path=%2Fr%2Fpics%2Fcomments%2F304rms%2Fcats_reaction_to_seeing_the_ceiling_fan_move_for%2Fcpp2kkl',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/reddit.min.html#pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl#theme=" style="height:165px;width:800px" data-s9e-mediaembed-api="2"></iframe>'
			],
			[
				'reddit',
				'pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl',
				'<iframe data-s9e-mediaembed="reddit" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/reddit.min.html#pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl#theme=" style="height:165px;width:800px" data-s9e-mediaembed-api="2"></iframe>'
			],
			[
				'soundcloud',
				'id=tracks%2F98282116;track_id=98282116',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/98282116%3Fsecret_token%3D" style="height:166px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'id=andrewbird%2Fthree-white-horses;track_id=59509713',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/59509713%3Fsecret_token%3D" style="height:166px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'id=tenaciousd%2Fsets%2Frize-of-the-fenix%2F;playlist_id=1919974;track_id=44564704',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/1919974%3Fsecret_token%3D" style="height:450px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'playlists/1919974',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/1919974%3Fsecret_token%3D" style="height:450px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'tracks/98282116',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/98282116%3Fsecret_token%3D" style="height:166px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'andrewbird/three-white-horses',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/andrewbird/three-white-horses" style="height:166px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'tenaciousd/sets/rize-of-the-fenix',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/tenaciousd/sets/rize-of-the-fenix" style="height:450px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'tenaciousd/sets/rize-of-the-fenix#playlist_id=1919974;track_id=44564704',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/playlists/1919974%3Fsecret_token%3D" style="height:450px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'cnn/newsday062413#t=2:10',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/cnn/newsday062413" style="height:166px;width:900px"></iframe>'
			],
			[
				'soundcloud',
				'tenaciousd/rock-is-dead#track_id=44564712',
				'<iframe data-s9e-mediaembed="soundcloud" allowfullscreen="" scrolling="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/44564712%3Fsecret_token%3D" style="height:166px;width:900px"></iframe>'
			],
			[
				'spreaker',
				'episode_id=20872603',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" scrolling="no" src="https://widget.spreaker.com/player?episode_id=20872603&amp;show_id=&amp;theme=" style="height:200px;width:900px"></iframe>'
			],
			[
				'spreaker',
				'show_id=3478708',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" scrolling="no" src="https://widget.spreaker.com/player?episode_id=&amp;show_id=3478708&amp;theme=" style="height:400px;width:900px"></iframe>'
			],
			[
				'spreaker',
				'episode_id=20872603',
				'<iframe data-s9e-mediaembed="spreaker" allowfullscreen="" scrolling="no" src="https://widget.spreaker.com/player?episode_id=20872603&amp;show_id=&amp;theme=dark" style="height:200px;width:900px"></iframe>',
				[],
				['styleType' => 'dark']
			],
			[
				'tumblr',
				'did=5f3b4bc6718317df9c2b1e77c20839ab94f949cd;id=104191225637;key=uFhWDPKj-bGU0ZlDAnUyxg;name=mrbenvey',
				'<iframe data-s9e-mediaembed="tumblr" allowfullscreen="" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+\'px\';this.contentWindow.postMessage(\'s9e:init\',\'*\',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/tumblr.min.html#uFhWDPKj-bGU0ZlDAnUyxg/104191225637" style="height:300px;width:520px" data-s9e-mediaembed-api="2"></iframe>'
			],
			[
				'twitch',
				'twitch',
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" onload="this.contentWindow.postMessage(\'\',\'*\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=;t=;video_id=" data-s9e-mediaembed-api="2"></iframe></span></span>'
			],
			[
				'twitch',
				'channel=twitch',
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" onload="this.contentWindow.postMessage(\'\',\'*\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=;t=;video_id=" data-s9e-mediaembed-api="2"></iframe></span></span>'
			],
			[
				'twitch',
				'channel=twitch;t=17m17s;video_id=29415830',
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" onload="this.contentWindow.postMessage(\'\',\'*\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=;t=17m17s;video_id=29415830" data-s9e-mediaembed-api="2"></iframe></span></span>'
			],
			[
				'twitch',
				'29415830:17m17s',
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" onload="this.contentWindow.postMessage(\'\',\'*\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=;clip_id=;t=17m17s;video_id=29415830" data-s9e-mediaembed-api="2"></iframe></span></span>'
			],
			[
				'twitch',
				'clip:NeighborlyBetterJellyfishWTRuck',
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" onload="this.contentWindow.postMessage(\'\',\'*\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=;clip_id=NeighborlyBetterJellyfishWTRuck;t=;video_id=" data-s9e-mediaembed-api="2"></iframe></span></span>'
			],
			[
				'twitch',
				'channel=twitch;clip_id=HorribleWoodpeckerHassanChop',
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" onload="this.contentWindow.postMessage(\'\',\'*\')" scrolling="no" src="https://s9e.github.io/iframe/2/twitch.min.html#channel=twitch;clip_id=HorribleWoodpeckerHassanChop;t=;video_id=" data-s9e-mediaembed-api="2"></iframe></span></span>'
			],
			[
				'vimeo',
				'67207222',
				'<span data-s9e-mediaembed="vimeo"><span><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/67207222"></iframe></span></span>'
			],
			[
				'vimeo',
				'67207222:90',
				'<span data-s9e-mediaembed="vimeo"><span><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/67207222#t=90"></iframe></span></span>'
			],
			[
				'vimeo',
				'67207222:1m30s',
				'<span data-s9e-mediaembed="vimeo"><span><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/67207222#t=90"></iframe></span></span>'
			],
			[
				// XenForo 2.2.9
				'vimeo',
				'703260668:0994c4644c',
				'<span data-s9e-mediaembed="vimeo"><span><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/703260668?h=0994c4644c"></iframe></span></span>'
			],
			[
				'vimeo',
				'703260668:0994c4644c:11s',
				'<span data-s9e-mediaembed="vimeo"><span><iframe allowfullscreen="" scrolling="no" src="//player.vimeo.com/video/703260668?h=0994c4644c#t=11"></iframe></span></span>'
			],
			[
				// From XenForo 1.x add-on, BBcode MediaSites Pack 1.1.9_11
				'wistia',
				'thoughtworks.wistia.com/medias/b6al55s35k',
				'<xf:if is="$type==\'audio\'"><iframe data-s9e-mediaembed="wistia" allowfullscreen="" scrolling="no" src="https://fast.wistia.net/embed/iframe/b6al55s35k" style="height:218px;width:900px"></iframe><xf:else/><span data-s9e-mediaembed="wistia"><span><iframe allowfullscreen="" scrolling="no" src="https://fast.wistia.net/embed/iframe/b6al55s35k"></iframe></span></span></xf:if>'
			],
			[
				'youtube',
				'QH2-TGUlwu4',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>'
			],
			[
				'youtube',
				'QH2-TGUlwu4:95',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/QH2-TGUlwu4?start=95"></iframe></span></span>'
			],
			[
				'youtube',
				'id=QH2-TGUlwu4;m=1;s=35',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/QH2-TGUlwu4?start=95"></iframe></span></span>'
			],
			[
				'youtube',
				'id=QH2-TGUlwu4;t=95',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/QH2-TGUlwu4?start=95"></iframe></span></span>'
			],
			[
				'youtube',
				'id=k-baHBzWe4k;list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/k-baHBzWe4k/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/k-baHBzWe4k?list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1"></iframe></span></span>'
			],
			[
				'youtube',
				'id=k-baHBzWe4k ',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi//hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/"></iframe></span></span>'
			],
			[
				// XenForo 2.2
				'youtube',
				'k-baHBzWe4k, list: PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/k-baHBzWe4k/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/k-baHBzWe4k?list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1"></iframe></span></span>'
			],
			[
				// XenForo 2.2
				'youtube',
				'k-baHBzWe4k:90, list: PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1',
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/k-baHBzWe4k/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/k-baHBzWe4k?list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1&amp;start=90"></iframe></span></span>'
			],
		];
	}
}