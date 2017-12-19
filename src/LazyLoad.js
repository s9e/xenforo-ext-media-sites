(function(attrName)
{
	var nodes   = document.getElementsByTagName('iframe'),
		i       = nodes.length,
		iframes = [],
		top     = -200,
		bottom  = 0,
		timeout = 0;
	while (--i >= 0)
	{
		if (nodes[i].hasAttribute(attrName))
		{
			iframes.push(nodes[i]);
		}
	}

	addEventListener('scroll', scheduleLoading, { 'passive': true });
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
		timeout = setTimeout(loadIframes, 30);
	}

	function loadIframes()
	{
		// Refresh the bottom fold
		bottom = innerHeight + 600;

		var newIframes = [];
		iframes.forEach(
			function (iframe)
			{
				if (isVisible(iframe))
				{
					iframe.contentWindow.location.replace(iframe.getAttribute(attrName));
					iframe.removeAttribute(attrName)
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