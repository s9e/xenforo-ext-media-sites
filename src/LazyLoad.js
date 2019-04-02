(function (window, document, dataPrefix, classPrefix)
{
	// Delay in milliseconds between events and checking for visible iframes
	const REFRESH_DELAY = 32;

	// Enum indicating an iframe's position in relation to viewport
	const ABOVE   = 0;
	const VISIBLE = 1;
	const BELOW   = 2;

	// Enum indicating the scrolling direction
	const SCROLL_DOWN = 0;
	const SCROLL_UP   = 1;

	var nodes   = document.querySelectorAll('iframe[' + dataPrefix + '-src]'),
		i       = 0,
		iframes = [],
		top     = 0,
		bottom  = 0,
		timeout = 0,
		hasScrolled     = false,
		lastScrollY     = 0,
		scrollDirection = SCROLL_DOWN;
	while (i < nodes.length)
	{
		iframes.push(nodes[i++]);
	}

	// Give the browser some time to scroll to the URL's target if the document is loading
	if (document.readyState === 'complete')
	{
		init();
	}
	else
	{
		window.addEventListener('load', init);

		// Ensure we still initialize within 3s even if the browser is stuck loading other assets
		setTimeout(init, 3000);
	}

	function init()
	{
		// Prevent multiple executions by testing whether bottom has been set
		if (!bottom)
		{
			// Initialize the last scroll position at current scroll position
			lastScrollY = window.scrollY;
			prepareEvents(window.addEventListener);
			loadIframes();
		}
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
			if (/bbCodeBlock-expandContent/.test(parentNode.className))
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
			src           = iframe.getAttribute(dataPrefix + '-src');
		if (iframe.getAttribute(dataPrefix + '-api') == 2)
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

		prepareMiniplayer(iframe);
	}

	function getIframePosition(iframe)
	{
		var rect = iframe.getBoundingClientRect();
		if (rect.bottom > window.innerHeight)
		{
			return BELOW;
		}

		var top = -1;
		if (!hasScrolled && location.hash)
		{
			// If the page hasn't been scrolled, use the top of the URL's target as the boundary
			top = getElementRectProperty(location.hash, 'top');
		}
		if (top < 0)
		{
			// Otherwise, use the bottom of the sticky header as the boundary
			top = getElementRectProperty('.p-navSticky', 'bottom');
		}

		return (rect.top < top) ? ABOVE : VISIBLE;
	}

	function getElementRectProperty(selector, prop)
	{
		var el = document.querySelector(selector);

		return (el) ? el.getBoundingClientRect()[prop] : -1;
	}

	function resizeIframe(iframe, height, width)
	{
		var iframePosition = getIframePosition(iframe),
			expandUpward   = (iframePosition === ABOVE || (iframePosition === VISIBLE && scrollDirection === SCROLL_UP)),
			oldDistance    = (expandUpward) ? getDistanceFromBottom() : 0,
			style          = iframe.style;

		// Temporarily disable transitions if the iframe isn't visible or we need to scroll the page
		if (iframePosition !== VISIBLE || expandUpward)
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

		if (expandUpward)
		{
			var newDistance = getDistanceFromBottom(),
				scrollDiff  = newDistance - oldDistance;
			if (scrollDiff)
			{
				window.scrollBy(0, scrollDiff);
			}

			// Update lastScrollY regardless of scrollDiff because some browsers (Firefox?) may
			// automatically preserve the scrolling position when an element's height change
			lastScrollY = window.scrollY;
		}
	}

	function getDistanceFromBottom()
	{
		// NOTE: scrollY has higher IE requirements than scrollBy()
		return getElementRectProperty('html', 'height') - window.scrollY;
	}

	function loadIframes()
	{
		if (lastScrollY !== window.scrollY)
		{
			hasScrolled     = true;
			scrollDirection = (lastScrollY > (lastScrollY = window.scrollY)) ? SCROLL_UP : SCROLL_DOWN;
		}

		// Refresh the loading zone. Extend it by an extra screen at the bottom and between half a
		// screen and a full screen at the top depending on whether we're scrolling down or up
		bottom = window.innerHeight * 2;
		top    = -bottom / ((scrollDirection === SCROLL_DOWN) ? 4 : 2);

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
			prepareEvents(window.removeEventListener);
		}
	}

	var activeMiniplayerSpan = null;
	function handleMiniplayerClick(e)
	{
		var span   = e.target,
			iframe = span.firstChild,
			rect   = span.getBoundingClientRect(),
			root   = document.documentElement,
			style  = iframe.style;

		style.bottom = (root.clientHeight - rect.bottom) + 'px';
		style.height = rect.height + 'px';
		style.right  = (root.clientWidth - rect.right) + 'px';
		style.width  = rect.width + 'px';

		// Force a layout calc on Firefox
		iframe.offsetHeight;

		if (/inactive/.test(span.className))
		{
			span.className = classPrefix + '-active-tn';
			iframe.removeAttribute('style');

			if (activeMiniplayerSpan)
			{
				activeMiniplayerSpan.click();
			}
			activeMiniplayerSpan = span;
		}
		else
		{
			span.className = classPrefix + '-inactive-tn';
			activeMiniplayerSpan = null;
		}
	}

	function handleMiniplayerTransition(e)
	{
		var iframe = e.target,
			span   = iframe.parentNode;

		if (/-tn/.test(span.className))
		{
			span.className = span.className.replace('-tn', '');
			iframe.removeAttribute('style');
		}
	}

	function prepareMiniplayer(iframe)
	{
		var span = iframe.parentNode;
		if (iframe.hasAttribute(dataPrefix) || span.hasAttribute('style'))
		{
			return;
		}

		span.className = classPrefix + '-inactive';
		span.onclick   = handleMiniplayerClick;

		// NOTE: Chrome doesn't seem to support iframe.ontransitionend
		iframe.addEventListener('transitionend', handleMiniplayerTransition);
	}
})(window, document, 'data-s9e-mediaembed', 's9e-miniplayer');