<?php declare(strict_types=1);

namespace s9e\MediaSites\Tests;

use ArrayObject;
use PHPUnit\Framework\Attributes\DataProvider;
use XF;
use XF\Entity\BbCodeMediaSite;
use XF\Repository\BbCodeMediaSite as MediaRepository;
use s9e\MediaSites\Parser;

/**
* @covers s9e\MediaSites\Parser
*/
class ParserTest extends AbstractParserTest
{
	public function getParserClass(): string
	{
		return Parser::class;
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
			Parser::convertMediaTag($url, $markup, null)
		);
		if (strpos($markup, ' ') === false)
		{
			$this->markTestIncomplete();
		}
	}

	public static function getMatchTests(): array
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
				// Ignore the pfbid value when reconstructing the backward compatible media key
				'https://www.facebook.com/VICE/posts/pfbid02XdVziPTwhmPU9XzBqkRvU5o7NPXUicAJgVy8kf1a1W51hU7EmgMmCigo9rZWxCjDl',
				'VICE/posts/6037626766270531'
			],
			[
				'https://www.facebook.com/permalink.php?story_fbid=10152253595081467&id=58617016466',
				'story_fbid=10152253595081467:id=58617016466'
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
				'https://mastodon.social/@HackerNewsBot/100181134752056592',
				'host=mastodon.social;id=100181134752056592;name=HackerNewsBot',
				function ()
				{
					XF::$options = new ArrayObject;
				}
			],
			[
				'https://mastodon.social/@SwiftOnSecurity@infosec.exchange/109579438826193099',
				false,
				function ()
				{
					XF::$options = new ArrayObject;
				}
			],
			[
				'https://mastodon.social/@SwiftOnSecurity@infosec.exchange/109579438826193099',
				'host=infosec.exchange;id=109579438603578302;name=SwiftOnSecurity',
				function ()
				{
					XF::$options = new ArrayObject(['s9e_MediaSites_MastodonHosts' => "infosec.exchange\nmastodon.social"], ArrayObject::ARRAY_AS_PROPS);
				}
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
				// XenForo 2.2.9
				// https://xenforo.com/community/threads/public-spotify-playlists-not-detected-correctly.204482/
				'https://open.spotify.com/playlist/37i9dQZF1DZ06evO47cwRq',
				'playlist:37i9dQZF1DZ06evO47cwRq'
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
//			[
//				'https://vm.tiktok.com/TTPdrc3YBJ?1',
//				'7050192414379691270'
//			],
//			[
//				'https://vm.tiktok.com/TTPdrc3YBJ?2',
//				'7050192414379691270',
//				['http' => ['s9e.client' => 'guzzle']]
//			],
			[
				'https://robotnik-mun.tumblr.com/post/701775547181793280/mmn2-1210-1215',
				'did=feacbe79ff845db2148047f37f21c5bec627f7bd;id=701775547181793280;key=GQNraxr5FGOXb18PnuWwQQ;name=robotnik-mun'
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
				// XenForo 2.2.9
				// https://xenforo.com/community/threads/vimeo-video-embed-doesnt-work-for-urls-with-a-key-specified.199071/
				'https://vimeo.com/703260668/0994c4644c',
				'703260668:0994c4644c'
			],
			[
				'https://vimeo.com/703260668/0994c4644c#t=11',
				'703260668:0994c4644c:11'
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
			[
				'HTTPS://youtu.be/QH2-TGUlwu4',
				'QH2-TGUlwu4'
			],
			[
				'https://YOUTU.BE/QH2-TGUlwu4',
				'QH2-TGUlwu4'
			],
			[
				'https://www.youtube.com/clip/UgkxNVVfF_kOXFsQs_mPrM4K53fao72UV_x4?' . uniqid(''),
				'clip=UgkxNVVfF_kOXFsQs_mPrM4K53fao72UV_x4;clipt=EIbDsAEYha6xAQ;id=UGMkPfHDnfM',
				function ()
				{
					XF::$options = new ArrayObject(['s9e_MediaSites_Scraping_Client' => 'curl'], ArrayObject::ARRAY_AS_PROPS);
				}
			],
			[
				'https://www.youtube.com/clip/UgkxNVVfF_kOXFsQs_mPrM4K53fao72UV_x4?' . uniqid(''),
				'clip=UgkxNVVfF_kOXFsQs_mPrM4K53fao72UV_x4;clipt=EIbDsAEYha6xAQ;id=UGMkPfHDnfM',
				function ()
				{
					XF::$options = new ArrayObject(['s9e_MediaSites_Scraping_Client' => 'xenforo'], ArrayObject::ARRAY_AS_PROPS);
				}
			],
			[
				'https://www.youtube.com/clip/UgkxNVVfF_kOXFsQs_mPrM4K53fao72UV_x4?' . uniqid(''),
				false,
				function ()
				{
					XF::$options = new ArrayObject(['s9e_MediaSites_Scraping_Client' => 'none'], ArrayObject::ARRAY_AS_PROPS);
				}
			],
		];
	}

	#[DataProvider('getFindInPageTests')]
	public function testFindInPage(string $url, array $where, ?string $expectedUrl): void
	{
		$willReturn = [];
		if (isset($expectedUrl))
		{
			$willReturn[$expectedUrl] = ['url' => $expectedUrl];
		}

		$repository = new MediaRepository($willReturn);
		$actual     = static::getParserClass()::findMatchInPage($url, $where, $repository);
		if (isset($expectedUrl))
		{
			$this->assertSame($willReturn[$expectedUrl], $actual);
		}
		else
		{
			$this->assertNull($actual);
		}
	}

	public static function getFindInPageTests(): array
	{
		return [
			[
				'https://barefootheartmusic.com/track/the-longing',
				['canonical'],
				'https://barefootheart.bandcamp.com/track/the-longing'
			],
			[
				'https://barefootheartmusic.com/track/the-longing',
				['embedded'],
				null
			],
			[
				'https://barefootheartmusic.com/track/the-longing',
				['canonical', 'embedded'],
				'https://barefootheart.bandcamp.com/track/the-longing'
			],
			[
				'https://digg.com/digg-vids/link/jon-stewart-confronted-the-deputy-secretary-of-defense-kathleen-hicks-on-the-defense-budget-and-things-got-spicy-Rvz8KHMzvD',
				['embedded'],
				'https://www.youtube.com/embed/50MusF365U0'
			],
		];
	}
}