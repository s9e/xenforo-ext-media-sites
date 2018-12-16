(function(prefix, document)
{
	// Zone in pixels above the viewport where iframes are loaded
	const ABOVE_SCREEN = 400;

	// Zone in pixels below the viewport where iframes are loaded
	const BELOW_SCREEN = 600;

	// Delay in milliseconds between scroll events and checking for visible iframes
	const REFRESH_DELAY = 32;

	// Iframe's position in relation to viewport
	const ABOVE   = 0;
	const VISIBLE = 1;
	const BELOW   = 2;

	var nodes   = document.querySelectorAll('iframe[' + prefix + 'src]'),
		i       = 0,
		iframes = [],
		top     = 0 - ABOVE_SCREEN,
		bottom  = 0,
		timeout = 0;
	while (i < nodes.length)
	{
		iframes.push(nodes[i++]);
	}

	// Wait until the document is loaded to leave the browser time to scroll to the URL's target
	if (document.readyState === 'complete')
	{
		init();
	}
	else
	{
		addEventListener('load', init);
	}

	function init()
	{
		prepareEvents(addEventListener);
		loadIframes();
	}

	function prepareEvents(fn)
	{
		fn('click',  scheduleLoading);
		fn('resize', scheduleLoading);
		fn('scroll', scheduleLoading);
	}

	function isInRange(iframe)
	{
		var rect = iframe.getBoundingClientRect();

		// Test for width to ensure the iframe isn't hidden in a spoiler
		if (rect.bottom < top || rect.top > bottom || !rect.width)
		{
			return false;
		}

		// Iframes in a non-expanded quotes are limited to a 270px width. This is not a perfect
		// indicator but it works well enough to cover the overwhelming majority of embeds
		if (rect.width === 270 && isHiddenInQuote(iframe, rect.top))
		{
			return false;
		}

		return true;
	}

	function isHiddenInQuote(iframe, top)
	{
		var parentNode = iframe.parentNode,
			block      = parentNode;
		while (parentNode.tagName !== 'BODY')
		{
			if (parentNode.className.indexOf('bbCodeBlock-expandContent') >= 0)
			{
				block = parentNode;
			}
			parentNode = parentNode.parentNode;
		}

		return (top > block.getBoundingClientRect().bottom);
	}

	function scheduleLoading()
	{
		clearTimeout(timeout);
		timeout = setTimeout(loadIframes, REFRESH_DELAY);
	}

	function loadIframe(iframe)
	{
		var contentWindow = iframe.contentWindow,
			src           = iframe.getAttribute(prefix + 'src');
		if (iframe.getAttribute(prefix + 'api') == 2)
		{
			iframe.onload = function ()
			{
				var channel = new MessageChannel,
					origin  = src.substr(0, src.indexOf('/', 8));
				contentWindow.postMessage('s9e:init', origin, [channel.port2]);
				channel.port1.onmessage = function (e)
				{
					var dimensions = ("" + e.data).split(' ');
					resizeIframe(iframe, dimensions[0], dimensions[1] || 0);
				};
			};
		}

		if (iframe.contentDocument)
		{
			// Replace the iframe's location if it still holds the empty document
			contentWindow.location.replace(src);
		}
		else if (iframe.onload)
		{
			// Mannually trigger the iframe's onload if the iframe was preloaded by the browser.
			// That can happen on Chrome when using back/forward navigation
			iframe.onload();
		}
	}

	function getIframePosition(iframe)
	{
		var rect = iframe.getBoundingClientRect();
		if (rect.bottom > innerHeight)
		{
			return BELOW;
		}

		var stickyHeader = document.querySelector('.p-navSticky'),
			headerHeight = (stickyHeader) ? stickyHeader.getBoundingClientRect().height : 0;
		if (rect.top < headerHeight)
		{
			return ABOVE;
		}

		return VISIBLE;
	}

	function resizeIframe(iframe, height, width)
	{
		var iframePosition = getIframePosition(iframe),
			oldDistance    = (iframePosition === ABOVE) ? getDistanceFromBottom() : 0,
			style          = iframe.style;

		// Temporarily disable transitions if the iframe isn't visible
		if (iframePosition !== VISIBLE)
		{
			style.transition = 'none';
			setTimeout(
				function ()
				{
					style.transition = '';
				},
				0
			);
		}

		style.height = height + 'px';
		if (width)
		{
			style.width = width + 'px';
		}

		if (oldDistance)
		{
			var newDistance = getDistanceFromBottom(),
				scrollDiff  = newDistance - oldDistance;
			if (scrollDiff)
			{
				scrollBy(0, scrollDiff);
			}
		}
	}

	function getDistanceFromBottom()
	{
		return document.documentElement.getBoundingClientRect().height - pageYOffset;
	}

	function loadIframes()
	{
		// Refresh the bottom fold
		bottom = innerHeight + BELOW_SCREEN;

		var newIframes = [];
		iframes.forEach(
			function (iframe)
			{
				if (isInRange(iframe))
				{
					loadIframe(iframe);
				}
				else
				{
					newIframes.push(iframe);
				}
			}
		);
		iframes = newIframes;

		if (!iframes.length)
		{
			prepareEvents(removeEventListener);
		}
	}
})('data-s9e-mediaembed-', document);