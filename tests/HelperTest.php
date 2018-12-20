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
		$script = '<script>(function(d,g,k){function l(){f||(p(d.addEventListener),q())}function p(a){a("click",m);a("resize",m);a("scroll",m)}function m(){clearTimeout(r);r=setTimeout(q,32)}function y(a){var c=a.contentWindow,e=a.getAttribute(k+"src");2==a.getAttribute(k+"api")&&(a.onload=function(){var b=new MessageChannel;c.postMessage("s9e:init",e.substr(0,e.indexOf("/",8)),[b.port2]);b.port1.onmessage=function(b){b=(""+b.data).split(" ");z(a,b[0],b[1]||0)}});if(a.contentDocument)c.location.replace(e);else if(a.onload)a.onload()}function A(a){a=a.getBoundingClientRect();if(a.bottom>d.innerHeight)return 2;var c=g.querySelector(".p-navSticky");c=c?c.getBoundingClientRect().height:0;return a.top<c?0:1}function z(a,c,e){var b=A(a),t=0===b?g.documentElement.getBoundingClientRect().height-d.scrollY:0,f=a.style;1!==b&&(f.transition="none",setTimeout(function(){f.transition=""},0));f.height=c+"px";e&&(f.width=e+"px");t&&(a=g.documentElement.getBoundingClientRect().height-d.scrollY-t)&&d.scrollBy(0,a)}function q(){n!==d.scrollY&&(u=n>(n=d.scrollY)?1:0);f=2*d.innerHeight;v=-f/(0===u?4:2);var a=[];h.forEach(function(c){var e=c.getBoundingClientRect(),b;if(!(b=e.bottom<v||e.top>f||!e.width)&&(b=270===e.width)){for(var d=b=c.parentNode;"BODY"!==b.tagName;)0<=b.className.indexOf("bbCodeBlock-expandContent")&&(d=b),b=b.parentNode;b=e.top>d.getBoundingClientRect().bottom}b?a.push(c):y(c)});h=a;h.length||p(d.removeEventListener)}for(var w=g.querySelectorAll("iframe["+k+"src]"),x=0,h=[],v=0,f=0,n=0,u=0,r=0;x<w.length;)h.push(w[x++]);"complete"===g.readyState?l():(d.addEventListener("load",l),setTimeout(l,3E3))})(window,document,"data-s9e-mediaembed-")</script>';

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
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" data-s9e-mediaembed-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>' . $script
			],
			[
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>',
				'<span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" data-s9e-mediaembed-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span><span data-s9e-mediaembed="twitch" style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" style="border:0;height:100%;left:0;position:absolute;width:100%" data-s9e-mediaembed-src="//player.twitch.tv/?autoplay=false&amp;channel=twitch"></iframe></span></span>' . $script
			],
			[
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')" scrolling="no" src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>',
				'<iframe data-s9e-mediaembed="twitter" allowfullscreen="" onload="if(!contentDocument){var a=Math.random();window.addEventListener(\'message\',function(b){if(b.data.id==a)style.height=b.data.height+\'px\'});contentWindow.postMessage(\'s9e:\'+a,\'https://s9e.github.io\')}" scrolling="no" data-s9e-mediaembed-src="https://s9e.github.io/iframe/twitter.min.html#266031293945503744" style="background:url(https://abs.twimg.com/favicons/favicon.ico) no-repeat 50% 50%;border:0;height:186px;max-width:500px;width:100%"></iframe>' . $script
			],
		];
	}
}