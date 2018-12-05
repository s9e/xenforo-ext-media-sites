(function(addEventListener, prefix, document)
{
	// Zone in pixels above the viewport where iframes are loaded
	const ABOVE_SCREEN = 400;

	// Zone in pixels below the viewport where iframes are loaded
	const BELOW_SCREEN = 600;

	// Zone in pixels at the top of the viewport that is expected to be obstructed by the header
	const HEADER_HEIGHT = 30;

	// Delay in milliseconds between scroll events and checking for visible iframes
	const REFRESH_DELAY = 32;

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
		return (rect.bottom > top && rect.top < bottom && rect.width);
	}

	function scheduleLoading()
	{
		clearTimeout(timeout);
		timeout = setTimeout(loadIframes, REFRESH_DELAY);
	}

	function getBodyHeight()
	{
		return document.body.getBoundingClientRect().height;
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
					var dimensions = ("" + e.data).split(' '),
						style      = iframe.style,
						oldHeight  = (iframe.getBoundingClientRect().top < HEADER_HEIGHT) ? getBodyHeight() : 0;
					if (oldHeight)
					{
						style.transition = 'none';
					}

					style.height = dimensions[0] + 'px';
					if (dimensions[1])
					{
						style.width = dimensions[1] + 'px';
					}

					if (oldHeight)
					{
						scrollBy(0, getBodyHeight() - oldHeight);
						style.transition = '';
					}
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
})(addEventListener, 'data-s9e-mediaembed-', document);