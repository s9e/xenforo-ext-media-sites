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

		$this->assertEquals($expected, $actual);
	}

	public function getReplaceIframeSrcTests()
	{
		$script = '<script>(function(d){function f(b){b("click",e);b("resize",e);b("scroll",e)}function e(){clearTimeout(g);g=setTimeout(h,32)}function h(){k=innerHeight+600;var b=[];a.forEach(function(c){var a=c.getBoundingClientRect();-200<a.bottom&&a.top<k&&a.width?(c.contentWindow.location.replace(c.getAttribute(d)),c.removeAttribute(d)):b.push(c)});a=b;a.length||f(removeEventListener)}for(var l=document.querySelectorAll("iframe["+d+"]"),m=l.length,a=[],k=0,g=0;0<=--m;)a.push(l[m]);f(addEventListener);h()})("data-s9e-lazyload-src")</script>';

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
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="data:text/html," data-s9e-lazyload-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>' . $script
			],
			[
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="data:text/html," data-s9e-lazyload-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="data:text/html," data-s9e-lazyload-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>' . $script
			],
			[
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>',
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="if(!hasAttribute(\'data-s9e-lazyload-src\')){var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')}" scrolling="no" src="data:text/html," data-s9e-lazyload-src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>' . $script
			],
		];
	}
}