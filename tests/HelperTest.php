<?php

namespace s9e\MediaSites\Tests;

use PHPUnit\Framework\TestCase;
use XF;
use XF\Template\Templater;
use s9e\MediaSites\Helper;
use stdClass;

/**
* @covers s9e\MediaSites\Helper
*/
class HelperTest extends TestCase
{
	/**
	* @dataProvider getReplaceIframesTests
	*/
	public function testReplaceIframes($original, $expected)
	{
		$actual = $original;
		Helper::replaceIframes(new Templater, '', '', $actual);
		$actual = preg_replace('(<script>.*?</script>)s', '<script></script>', $actual);

		$this->assertEquals($expected, $actual);
	}

	public function getReplaceIframesTests()
	{
		return [
			[
				'',
				''
			],
			[
				'<iframe src="foo"></iframe>',
				'<iframe src="foo"></iframe>'
			],
			[
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" scrolling="no" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>',
				'<span data-s9e-mediaembed="twitch"><span><span data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","\\/\\/player.twitch.tv\\/?autoplay=false&amp;channel=twitch"]\'></span></span></span><script></script>'
			],
			[
				'<span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" scrolling="no" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch"><span><iframe allowfullscreen="" scrolling="no" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>',
				'<span data-s9e-mediaembed="twitch"><span><span data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","\\/\\/player.twitch.tv\\/?autoplay=false&amp;channel=twitch"]\'></span></span></span><span data-s9e-mediaembed="twitch"><span><span data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","\\/\\/player.twitch.tv\\/?autoplay=false&amp;channel=twitch"]\'></span></span></span><script></script>'
			],
			[
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px"></iframe>',
				'<span data-s9e-mediaembed="twitter" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px" data-s9e-mediaembed-iframe=\'["data-s9e-mediaembed","twitter","allowfullscreen","","onload","var a=Math.random();window.addEventListener(&#39;message&#39;,function(b){if(b.data.id==a)style.height=b.data.height+&#39;px&#39;});contentWindow.postMessage(&#39;s9e:&#39;+a,&#39;https:\\/\\/s9e.github.io&#39;)","scrolling","no","src","https:\\/\\/s9e.github.io\\/iframe\\/twitter.min.html#266031293945503744","style","background:url(https:\\/\\/abs.twimg.com\\/favicons\\/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px"]\'></span><script></script>'
			],
			[
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px" data-s9e-mediaembed-api="2"></iframe>',
				'<span data-s9e-mediaembed="twitter" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px" data-s9e-mediaembed-iframe=\'["data-s9e-mediaembed","twitter","allowfullscreen","","scrolling","no","src","https:\\/\\/s9e.github.io\\/iframe\\/twitter.min.html#266031293945503744","style","background:url(https:\\/\\/abs.twimg.com\\/favicons\\/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px","data-s9e-mediaembed-api","2"]\'></span><script></script>'
			],
			[
				'<span data-s9e-mediaembed="youtube"><span><iframe data-s9e-mediaembed-c2l="youtube" allowfullscreen="" scrolling="no" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>',
				'<span data-s9e-mediaembed="youtube"><span><span data-s9e-mediaembed-c2l="youtube" data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","https:\\/\\/www.youtube.com\\/embed\\/QH2-TGUlwu4"]\'></span></span></span><script></script>'
			],
			[
				'<span data-s9e-mediaembed="youtube"><span><iframe allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>',
				'<span data-s9e-mediaembed="youtube"><span><span style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","style","background:url(https:\\/\\/i.ytimg.com\\/vi\\/QH2-TGUlwu4\\/hqdefault.jpg) 50% 50% \\/ cover","src","https:\\/\\/www.youtube.com\\/embed\\/QH2-TGUlwu4"]\'></span></span></span><script></script>'
			],
			[
				// c2l attributes should not be saved in the placeholder
				'<span data-s9e-mediaembed="youtube"><span><iframe data-s9e-mediaembed-c2l="youtube" allowfullscreen="" scrolling="no" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>',
				'<span data-s9e-mediaembed="youtube"><span><span data-s9e-mediaembed-c2l="youtube" data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","https:\\/\\/www.youtube.com\\/embed\\/QH2-TGUlwu4"]\'></span></span></span><script></script>'
			],
			[
				// style's background should be moved to a c2l attribute
				'<span data-s9e-mediaembed="youtube"><span><iframe data-s9e-mediaembed-c2l="youtube" allowfullscreen="" scrolling="no" style="background:url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>',
				'<span data-s9e-mediaembed="youtube"><span><span data-s9e-mediaembed-c2l="youtube" data-s9e-mediaembed-c2l-background="url(https://i.ytimg.com/vi/QH2-TGUlwu4/hqdefault.jpg) 50% 50% / cover" data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","https:\\/\\/www.youtube.com\\/embed\\/QH2-TGUlwu4"]\'></span></span></span><script></script>'
			],
			[
				// Preserve the rest of the style
				'<span data-s9e-mediaembed="youtube"><span><iframe data-s9e-mediaembed-c2l="youtube" allowfullscreen="" scrolling="no" style="background:#000; outline:solid 1px red" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>',
				'<span data-s9e-mediaembed="youtube"><span><span data-s9e-mediaembed-c2l="youtube" data-s9e-mediaembed-c2l-background="#000" style="outline:solid 1px red" data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","style","outline:solid 1px red","src","https:\\/\\/www.youtube.com\\/embed\\/QH2-TGUlwu4"]\'></span></span></span><script></script>'
			],
			[
				// Replace the iframe's src
				'<span data-s9e-mediaembed="youtube"><span><iframe data-s9e-mediaembed-c2l="youtube" data-s9e-mediaembed-c2l-src="?autoplay=1" allowfullscreen="" scrolling="no" src="https://www.youtube.com/embed/QH2-TGUlwu4"></iframe></span></span>',
				'<span data-s9e-mediaembed="youtube"><span><span data-s9e-mediaembed-c2l="youtube" data-s9e-mediaembed-iframe=\'["allowfullscreen","","scrolling","no","src","?autoplay=1"]\'></span></span></span><script></script>'
			],
		];
	}

	/**
	* @dataProvider getMastodonHostsTests
	*/
	public function testMastodonHosts(string $hosts, string $value, false|string $expected)
	{
		XF::$options = new stdClass;
		XF::$options->s9e_MediaSites_MastodonHosts = $hosts;

		$this->assertEquals($expected, Helper::filterMastodonHost($value));
	}

	public function getMastodonHostsTests()
	{
		return [
			[
				'mastodon.social',
				'mastodon.social',
				'mastodon.social'
			],
			[
				'mastodon.social',
				'example.org',
				false
			],
			[
				"example.org\nmastodon.social",
				'example.org',
				'example.org'
			],
			[
				"example.org\nmastodon.social",
				'example.ORG',
				'example.org'
			],
		];
	}
}