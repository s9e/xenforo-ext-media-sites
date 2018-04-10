(function(attrName)
{
	/* @const Zone in pixels above the visible area where iframes are considered visible */
	const ABOVE_SCREEN = 200;

	/* @const Zone in pixels below the visible area where iframes are considered visible */
	const BELOW_SCREEN = 600;

	/* @const Delay in milliseconds between scroll events and checking for visible iframes */
	const REFRESH_DELAY = 32;

	var nodes   = document.getElementsByTagName('iframe'),
		i       = nodes.length,
		iframes = [],
		top     = -ABOVE_SCREEN,
		bottom  = 0,
		timeout = 0;
	while (--i >= 0)
	{
		if (nodes[i].hasAttribute(attrName))
		{
			iframes.push(nodes[i]);
		}
	}

	addEventListener('scroll', scheduleLoading);
	addEventListener('resize', scheduleLoading);
	loadIframes();

	function isVisible(iframe)
	{
		var rect = iframe.getBoundingClientRect();

		return (rect.bottom > top && rect.top < bottom);
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
			removeEventListener('scroll', scheduleLoading);
			removeEventListener('resize', scheduleLoading);
		}
	}
})('data-s9e-lazyload-src');