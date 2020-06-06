<?php

namespace s9e\MediaSites\Tests;

use PHPUnit\Framework\TestCase;
use XF\Template\Templater;
use s9e\MediaSites\Helper;

/**
* @covers s9e\MediaSites\Helper
*/
class HelperTest extends TestCase
{
	/**
	* @dataProvider getReplaceIframeSrcTests
	*/
	public function testReplaceIframeSrc($original, $expected)
	{
		$actual = $original;
		Helper::replaceIframeSrc(new Templater, '', '', $actual);
		$actual = preg_replace('(<script>.*?</script>)s', '<script></script>', $actual);

		$this->assertEquals($expected, $actual);
	}

	public function getReplaceIframeSrcTests()
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
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" data-s9e-mediaembed-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><script></script>'
			],
			[
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" data-s9e-mediaembed-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" data-s9e-mediaembed-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><script></script>'
			],
			[
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>',
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="if(!contentDocument){var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')}" scrolling="no" data-s9e-mediaembed-src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe><script></script>'
			],
		];
	}

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
				'<span data-s9e-mediaembed="twitter" data-s9e-mediaembed-iframe=\'["data-s9e-mediaembed","twitter","allowfullscreen","","onload","var a=Math.random();window.addEventListener(&#39;message&#39;,function(b){if(b.data.id==a)style.height=b.data.height+&#39;px&#39;});contentWindow.postMessage(&#39;s9e:&#39;+a,&#39;https:\\/\\/s9e.github.io&#39;)","scrolling","no","src","https:\\/\\/s9e.github.io\\/iframe\\/twitter.min.html#266031293945503744","style","background:url(https:\\/\\/abs.twimg.com\\/favicons\\/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px"]\' style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px"></span><script></script>'
			],
			[
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px" data-s9e-mediaembed-api="2"></iframe>',
				'<span data-s9e-mediaembed="twitter" data-s9e-mediaembed-iframe=\'["data-s9e-mediaembed","twitter","allowfullscreen","","scrolling","no","src","https:\\/\\/s9e.github.io\\/iframe\\/twitter.min.html#266031293945503744","style","background:url(https:\\/\\/abs.twimg.com\\/favicons\\/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px","data-s9e-mediaembed-api","2"]\' style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;height:186px;max-width:500px"></span><script></script>'
			],
		];
	}
}