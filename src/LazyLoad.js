(function(attrName)
{
	// Zone in pixels above the viewport where static iframes are considered visible
	const ABOVE_SCREEN = 200;

	// Zone in pixels below the viewport where iframes are considered visible
	const BELOW_SCREEN = 600;

	// Delay in milliseconds between scroll events and checking for visible iframes
	const REFRESH_DELAY = 32;

	var nodes   = document.querySelectorAll('iframe[' + attrName + ']'),
		i       = 0,
		iframes = [],
		top     = 0 - ABOVE_SCREEN,
		bottom  = 0,
		timeout = 0;
	while (i < nodes.length)
	{
		iframes.push(nodes[i++]);
	}

	prepareEvents(addEventListener);
	loadIframes();

	function prepareEvents(fn)
	{
		fn('click',  scheduleLoading);
		fn('resize', scheduleLoading);
		fn('scroll', scheduleLoading);
	}

	function isVisible(iframe)
	{
		var rect = iframe.getBoundingClientRect();

		// Test for width to ensure the iframe isn't hidden in a spoiler
		return (rect.bottom > (iframe.hasAttribute('onload') ? 0 : top) && rect.top < bottom && rect.width);
	}

	function scheduleLoading()
	{
		clearTimeout(timeout);
		timeout = setTimeout(loadIframes, REFRESH_DELAY);
	}

	function loadIframes()
	{
		// Refresh the bottom fold
		bottom = innerHeight + BELOW_SCREEN;

		var newIframes = [];
		iframes.forEach(
			function (iframe)
			{
				if (isVisible(iframe))
				{
					iframe.contentWindow.location.replace(iframe.getAttribute(attrName));
					iframe.removeAttribute(attrName);
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
})('data-s9e-lazyload-src');