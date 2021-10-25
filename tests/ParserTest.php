<?php

namespace s9e\MediaSites\Tests;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use XF\Entity\BbCodeMediaSite;
use s9e\MediaSites\Parser;

/**
* @covers s9e\MediaSites\Parser
*/
class ParserTest extends TestCase
{
	protected static $sites = [];
	public static function setUpBeforeClass(): void
	{
		Parser::$cacheDir = __DIR__ . '/.cache';

		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../addon/_data/bb_code_media_sites.xml');
		foreach ($dom->getElementsByTagName('site') as $site)
		{
			$siteId = $site->getAttribute('media_site_id');
			$regexp = $site->getElementsByTagName('match_urls')->item(0)->textContent;

			self::$sites[$siteId] = $regexp;
		}
	}

	public function testUnknown()
	{
		$this->assertFalse(Parser::match('', '', new BbCodeMediaSite, 'unknown'));
	}

	public function testMediaUrl()
	{
		$siteId   = 'youtube';
		$url      = 'https://www.youtube.com/watch?v=k-baHBzWe4k&list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1';
		$mediaKey = Parser::match($url, '', new BbCodeMediaSite, $siteId);
		$markup   = '[MEDIA=' . $siteId . ']' . $mediaKey . '[/MEDIA]';

		$this->assertEquals(
			'[URL media="' . $siteId . ':' . $mediaKey . '"]' . $url . '[/URL]',
			Parser::convertMediaTag($url, $markup, false)
		);
		if (strpos($markup, ' ') === false)
		{
			$this->markTestIncomplete();
		}
	}

	/**
	* @dataProvider getMatchTests
	*/
	public function testMatch($url, $expected)
	{
		$mediaKey = false;
		foreach (self::$sites as $siteId => $regexp)
		{
			if (!preg_match($regexp, $url, $m))
			{
				continue;
			}
			$mediaKey = Parser::match($url, $m['id'], new BbCodeMediaSite, $siteId);
			if ($mediaKey !== false)
			{
				break;
			}
		}

		$this->assertSame($expected, $mediaKey);
	}

	public function getMatchTests()
	{
		return [
			[
				'https://www.bbc.co.uk/news/live/world-54505193',
				false
			],
			[
				'http://us.cnn.com/video/data/2.0/video/bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn.html',
				'bestoftv/2013/10/23/vo-nr-prince-george-christening-arrival.cnn'
			],
			[
				'http://money.cnn.com/video/technology/2014/05/20/t-twitch-vp-on-future.cnnmoney/',
				'technology/2014/05/20/t-twitch-vp-on-future.cnnmoney'
			],
			[
				'http://dai.ly/x5e9eog',
				'x5e9eog'
			],
			[
				'http://www.dailymotion.com/video/x5e9eog',
				'x5e9eog'
			],
			[
				'http://www.dailymotion.com/video/x5e9eog?start=33',
				'x5e9eog:33'
			],
			[
				'https://www.facebook.com/FacebookDevelopers/posts/10151471074398553',
				'FacebookDevelopers/posts/10151471074398553'
			],
			[
				'https://www.facebook.com/video/video.php?v=10150451523596807',
				'id=10150451523596807;type=v'
			],
			[
				'https://www.facebook.com/photo.php?fbid=10152476416772631',
				'10152476416772631'
			],
			[
				'https://www.facebook.com/ign/videos/10153762113196633/',
				'id=10153762113196633;type=v;user=ign'
			],
			[
				'https://www.facebook.com/southamptonfc/videos/vb.220396037973624/1357764664236750/',
				'id=1357764664236750;type=v;user=southamptonfc'
			],
			[
				// Value automatically adjusted to match XenForo 2.0's format
				'https://www.flickr.com/photos/8757881@N04/2971804544/lightbox/',
				'5wBgXo'
			],
			[
				'https://flic.kr/8757881@N04/2971804544',
				'5wBgXo'
			],
			[
				'https://flic.kr/p/5wBgXo',
				'5wBgXo'
			],
			[
				'http://gfycat.com/SereneIllfatedCapybara',
				'height=338;id=SereneIllfatedCapybara;width=600'
			],
			[
				'http://gfycat.com/sereneillfatedcapybara',
				'height=338;id=SereneIllfatedCapybara;width=600'
			],
			[
				'http://imgur.com/AsQ0K3P',
				'AsQ0K3P'
			],
			[
				'http://imgur.com/a/9UGCL',
				'a/9UGCL'
			],
			[
				'http://imgur.com/gallery/9UGCL',
				'a/9UGCL'
			],
			[
				'http://i.imgur.com/u7Yo0Vy.gifv',
				'u7Yo0Vy'
			],
			[
				'http://i.imgur.com/UO1UrIx.mp4',
				'UO1UrIx'
			],
			[
				'https://odysee.com/Deni-Juric-Goal-2-0-ŠIBENIK-vs-SLAVEN-Apr21:8726b01100463c4e254a38c3108ef3e05791aeda',
				'id=8726b01100463c4e254a38c3108ef3e05791aeda;name=Deni-Juric-Goal-2-0-%25C5%25A0IBENIK-vs-SLAVEN-Apr21'
			],
			[
				'https://archive.org/embed/deadco2018-08-25',
				'height=50;id=deadco2018-08-25%26playlist%3D1%26twitterv%3D01;width=300'
			],
			[
				'http://www.npr.org/blogs/goatsandsoda/2015/02/11/385396431/the-50-most-effective-ways-to-transform-the-developing-world',
				'i=385396431;m=385396432'
			],
			[
				'http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/',
				'pics/comments/304rms'
			],
			[
				'http://www.reddit.com/r/pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl',
				'pics/comments/304rms/cats_reaction_to_seeing_the_ceiling_fan_move_for/cpp2kkl'
			],
			[
				// XenForo 2.2 doesn't actually support those
				'http://api.soundcloud.com/tracks/98282116',
				'tracks/98282116#track_id=98282116'
			],
			[
				// XenForo 2.2 with extra info tacked after #
				'https://soundcloud.com/tenaciousd/rock-is-dead',
				'tenaciousd/rock-is-dead#track_id=44564712'
			],
			[
				// XenForo 2.2 with extra info tacked after #
				'https://soundcloud.com/tenaciousd/sets/rize-of-the-fenix/',
				'tenaciousd/sets/rize-of-the-fenix#playlist_id=1919974;track_id=44564704'
			],
			[
				// XenForo 2.2
				'https://open.spotify.com/track/0GjSbSr86nsOLJsibU2cjh',
				'track:0GjSbSr86nsOLJsibU2cjh'
			],
			[
				'https://www.spreaker.com/user/bitcoinpodcasts/blockstreams-bitcoin-primer-ep-1',
				'episode_id=20872603'
			],
			[
				'https://www.spreaker.com/show/the-unhashed-podcast-bitcoin-blockchain-',
				'show_id=3478708'
			],
			[
				'https://www.tiktok.com/@lauren.feagans/video/6789430799839104261',
				'6789430799839104261'
			],
			[
				'http://www.twitch.tv/twitch',
				'twitch'
			],
			[
				'http://www.twitch.tv/twitch/v/29415830?t=17m17s',
				'channel=twitch;t=17m17s;video_id=29415830'
			],
			[
				'https://www.twitch.tv/videos/29415830?t=17m17s',
				'29415830:17m17s'
			],
			[
				'https://clips.twitch.tv/NeighborlyBetterJellyfishWTRuck',
				'clip:NeighborlyBetterJellyfishWTRuck'
			],
			[
				'https://clips.twitch.tv/twitch/HorribleWoodpeckerHassanChop',
				'channel=twitch;clip_id=HorribleWoodpeckerHassanChop'
			],
			[
				'http://vimeo.com/67207222',
				'67207222'
			],
			[
				'http://vimeo.com/67207222#t=90',
				'67207222:90'
			],
			[
				'http://vimeo.com/67207222#t=1m30s',
				'67207222:90'
			],
			[
				'http://vimeo.com/67207222#t=smh',
				'67207222'
			],
			[
				'https://www.youtube.com/watch?v=QH2-TGUlwu4',
				'QH2-TGUlwu4'
			],
			[
				'https://youtu.be/QH2-TGUlwu4?t=95',
				'QH2-TGUlwu4:95'
			],
			[
				'https://youtu.be/QH2-TGUlwu4?t=1m35s',
				'QH2-TGUlwu4:95'
			],
			[
				// XenForo 2.2
				'https://www.youtube.com/watch?v=k-baHBzWe4k&list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1',
				'k-baHBzWe4k, list: PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1'
			],
			[
				// XenForo 2.2
				'https://www.youtube.com/watch?v=k-baHBzWe4k&list=PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1&t=1m30s',
				'k-baHBzWe4k:90, list: PL590L5WQmH8cGD7hVGK_YvAUWdXKfGLJ1'
			],
			[
				'https://www.youtube.com/about',
				false
			],
		];
	}
}