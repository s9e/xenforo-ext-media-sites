((window, document, hash, dataPrefix, classPrefix) =>
{
	// Delay in milliseconds between events and checking for visible elements
	const REFRESH_DELAY = 32;

	// Delay in milliseconds between the last event and assuming the navigation's scroll is complete
	const NAVIGATION_DELAY = 200;

	// Enum indicating an iframe's position in relation to visible range (viewport minus header)
	const ABOVE   = 0;
	const VISIBLE = 1;
	const BELOW   = 2;

	// Enum indicating the scrolling direction
	const SCROLL_DOWN = 0;
	const SCROLL_UP   = 1;

	// Max number of items in storage
	const STORAGE_MAX_SIZE = 100;

	let activeMiniplayerSpan = null,
		documentElement      = document.documentElement,
		inNavigation         = false,
		lastScrollY          = 0,
		localStorage         = {},
		navigationTimeout    = 0,
		proxies              = [...document.querySelectorAll('span[' + dataPrefix + '-iframe]')],
		scrollDirection      = SCROLL_DOWN,
		timeout              = 0;

	try
	{
		localStorage = window.localStorage;
	}
	catch
	{
	}

	// NOTE: declaring loadIframes() at the top prevents Closure Compiler from unnecesserily copying
	//       it in startNavigation()
	function loadIframes(loadingRange)
	{
		const newProxies = [], [top, bottom] = loadingRange;
		proxies.forEach(
			(proxy) =>
			{
				if (isInRange(proxy, top, bottom))
				{
					if (proxy.hasAttribute(dataPrefix + '-c2l'))
					{
						prepareClickToLoad(proxy);
					}
					else
					{
						loadIframe(proxy);
					}
				}
				else
				{
					newProxies.push(proxy);
				}
			}
		);
		proxies = newProxies;

		if (!proxies.length)
		{
			prepareEvents(window.removeEventListener);
		}
	}

	/**
	* @param {!Function} fn
	*/
	function prepareEvents(fn)
	{
		['click', 'load', 'resize', 'scroll', 'visibilitychange'].forEach(
			(type) => fn(type, scheduleRefresh, { 'capture': true, 'passive': true })
		);
	}

	/**
	* The loading range is the zone where lazy content is loaded: visible viewport + extra buffer
	*
	* @return {!Array<number>}
	*/
	function getLoadingRange()
	{
		// Load an extra viewport's worth at the bottom, and between a quarter and half the
		// viewport's height at the top depending on whether we're scrolling down or up
		let bottom = window.innerHeight * 2,
			top    = -bottom / ((scrollDirection === SCROLL_DOWN) ? 4 : 2);

		return [top, bottom];
	}

	/**
	* The target range starts wherever the target from the URI fragment (hash) starts up to a
	* viewport's height below. If there is no valid URI fragment, the visible range is returned
	*
	* @param  {string} contentSelector
	* @return {!Array<number>}
	*/
	function getTargetRange(contentSelector)
	{
		// Use the top of the URL's target as the boundary
		let top = document.querySelector(contentSelector)?.getBoundingClientRect().top ?? 0;

		// NOTE: this range may be smaller than the viewport's height if the target is so
		//       low on the page that it's not at the top of the viewport. It should not
		//       be an issue as the loading zone will be refreshed once the browser
		//       scrolls to the target
		return [top, top + window.innerHeight];
	}

	/**
	* @return {!Array<number>}
	*/
	function getVisibleRange()
	{
		// Adjust the *visible* range to exclude the sticky header. In theory we could also exclude
		// footer notices but sticky footers have a much lesser impact compared to sticky headers,
		// as the latter determines in which direction an iframe should be resized
		let top    = document.querySelector('.p-navSticky').getBoundingClientRect().bottom ?? 0,
			bottom = window.innerHeight;

		return [top, bottom];
	}

	/**
	* @param  {!Element} element
	* @param  {number}   top
	* @param  {number}   bottom
	* @return {boolean}
	*/
	function isInRange(element, top, bottom)
	{
		const rect = element.getBoundingClientRect();

		// Test for width to ensure the element isn't hidden in a spoiler
		if (rect.bottom < top || rect.top > bottom || !rect.width)
		{
			return false;
		}

		return isInVisibleRangeOfBlock(element, rect.top);
	}

	/**
	* @param  {!Element} element
	* @param  {number}   top
	* @return {boolean}
	*/
	function isInVisibleRangeOfBlock(element, top)
	{
		let parentElement = element.parentElement,
			block         = parentElement;
		while (parentElement)
		{
			if (/bbCodeBlock-expandContent/.test(parentElement.className))
			{
				block = parentElement;
			}
			parentElement = parentElement.parentElement;
		}

		return (top <= block.getBoundingClientRect().bottom);
	}

	function scheduleRefresh(e)
	{
		let target = e.target, m;
		if (e.type === 'click' && target.tagName === 'A')
		{
			// Assume that any A element with a data-content-selector attribute will scroll to the
			// target selector
			const contentSelector = target.dataset['contentSelector'] ?? '';
			if (/^#[-\w]+$/.test(contentSelector))
			{
				startNavigation(contentSelector);
			}
			else if ((m = /(.*)(#[-\w]+)$/.exec(target.href))
			      && m[1] === document.baseURI.replace(/#.*/, ''))
			{
				startNavigation(m[2]);
			}
		}
		else if (inNavigation)
		{
			scheduleNavigationEnd();
		}

		window.clearTimeout(timeout);
		timeout = window.setTimeout(refresh, REFRESH_DELAY);
	}

	/**
	* @param {!HTMLSpanElement} proxy
	*/
	function loadIframe(proxy)
	{
		let i      = -1,
			iframe = /** @type {!HTMLIFrameElement} */ (document.createElement('iframe')),
			values = /** @type {!Array<string>}     */ (JSON.parse(proxy.getAttribute(dataPrefix + '-iframe')));
		while (++i < values.length)
		{
			iframe.setAttribute(values[i], values[++i]);
		}
		iframe['loading'] = 'eager';

		prepareMiniplayer(iframe, proxy.parentNode);
		proxy.replaceWith(iframe);

		if (iframe.getAttribute(dataPrefix + '-api') == 2)
		{
			iframe.onload = () => prepareResizableIframe(iframe);

			// Resize the iframe after it's been inserted in the page so it's resized the right way
			// (upward/downward) and with a transition if visible
			const storedDimensions = localStorage[getStorageKey(iframe.src)];
			if (typeof storedDimensions === 'string')
			{
				resizeIframeFromDimensions(iframe, storedDimensions);
			}
		}
	}

	function prepareResizableIframe(iframe)
	{
		const channel    = new MessageChannel,
		      storageKey = getStorageKey(iframe.src);
		channel.port1.onmessage = (e) =>
		{
			const data = ('' + e.data);

			// Some content providers may send the content's height before everything (e.g. images)
			// is loaded. If we receive a smaller height than current iframe's, we delay the resizing
			// by a few seconds before setting the height from whichever value is in storage at the
			// time. This provides a grace period for the embed to load more of its assets and set a
			// more accurate height
			window.setTimeout(
				() =>
				{
					// Local storage may theoretically get pruned between the timer's creation and
					// its execution, so we use data as a fallback if there's no stored value
					resizeIframeFromDimensions(iframe, localStorage[storageKey] || data);
				},
				// We use the iframe's current height rather than the stored value because some
				// providers (e.g. Twitter) send the same bogus value multiple times and we would
				// compare the stored bogus value against a new instance of the same value
				(iframe.getBoundingClientRect().height > +(data.split(' ')[0])) ? 4000 : 0
			);
			storeIframeData(storageKey, data);
		};
		iframe.contentWindow.postMessage('s9e:init', '*', [channel.port2]);
	}

	/**
	* @param  {!HTMLIFrameElement} iframe
	* @return {number}
	*/
	function getIframePosition(iframe)
	{
		// To determine an iframe's position, we use the visible range which is adjusted for the
		// sticky header. An iframe that starts under (on the Z axis) the header is considered
		// to be above the visible range
		const rect          = iframe.getBoundingClientRect(),
		      [top, bottom] = getVisibleRange();

		if (rect.bottom > bottom)
		{
			return BELOW;
		}
		if (rect.top < top)
		{
			return ABOVE;
		}

		return VISIBLE;
	}

	/**
	* @param {!HTMLIFrameElement} iframe
	* @param {string}             dimensions Space-separated height and optionally width
	*/
	function resizeIframeFromDimensions(iframe, dimensions)
	{
		const [height, width] = [...dimensions.split(' '), 0],
		      style = iframe.style;
		if (style.height === height + 'px' && (!width || style.width === width + 'px'))
		{
			// Ignore redundant resizings. Those mostly happen with Twitter
			return;
		}

		// There are cases where an iframe should expand "upward" without pushing down other content.
		// However, if the iframe is fully visible because we've just navigated to it via a link then
		// we don't want it to be pushed outside the viewport either
		const iframePosition = getIframePosition(iframe),
		      expandUpward   = (iframePosition === ABOVE || (iframePosition === VISIBLE && scrollDirection === SCROLL_UP && !inNavigation)),
		      oldDistance    = (expandUpward) ? getDistanceFromBottom() : 0;

		// Temporarily disable transitions if the iframe isn't fully visible, if we need to scroll
		// the page to expand upward, or if the document isn't fully loaded yet or we're navigating
		// links and either way we'd rather not spend time animating things
		if (iframePosition !== VISIBLE || expandUpward || inNavigation || document.readyState !== 'complete')
		{
			style.transition = 'none';
			window.setTimeout(
				() =>
				{
					style.transition = '';
				},
				// Setting the delay to 0 seems to have no effect on Firefox
				REFRESH_DELAY
			);
		}

		// Update the current scrolling position before resizing the iframe
		lastScrollY = window.scrollY;
		style.height = height + 'px';
		if (width)
		{
			style.width = width + 'px';
		}

		// Do not try to expand upward if we scrolled to the top
		if (expandUpward && lastScrollY > 0)
		{
			// If we've resized an iframe that's above the viewport and we're suddenly farther away
			// from the bottom of the page, it means that everything that was in view got pushed
			// down and we need to scroll down a little bit to catch up
			const newDistance = getDistanceFromBottom(),
			      scrollDiff  = newDistance - oldDistance;
			if (scrollDiff)
			{
				window.scrollBy({ behavior: 'instant', top: scrollDiff });
			}
		}

		// Update lastScrollY regardless of how the iframe was resized so we guarantee we have the
		// correct value
		lastScrollY = window.scrollY;
	}

	/**
	* @return {number}
	*/
	function getDistanceFromBottom()
	{
		return documentElement.scrollHeight - window.scrollY;
	}

	function refresh()
	{
		// Events that cause a refresh without scrolling the page (e.g. click) will cause the scroll
		// direction to reset to SCROLL_DOWN. We also set it to SCROLL_DOWN during (and immediately
		// after) navigation in case we scrolled up to a dynamic embed; We don't want it to expand
		// upward, posssibly outside the viewport
		scrollDirection = (lastScrollY > (lastScrollY = window.scrollY) && !inNavigation) ? SCROLL_UP : SCROLL_DOWN;

		// Don't load anything unless the page is visible and ready. There is no benefit to loading
		// iframes during the 'interactive' state
		if (document.visibilityState !== 'hidden' && document.readyState === 'complete')
		{
			loadIframes(getLoadingRange());
		}
	}

	function handleMiniplayerClick(iframe, span)
	{
		const rect  = span.getBoundingClientRect(),
		      style = iframe.style;

		style.bottom = (documentElement.clientHeight - rect.bottom) + 'px';
		style.height = rect.height + 'px';
		style.width  = rect.width + 'px';

		if (documentElement.dir === 'rtl')
		{
			// XenForo flips all layout in RTL mode
			style.left = rect.left + 'px';
		}
		else
		{
			style.right = (documentElement.clientWidth - rect.right) + 'px';
		}

		// Force a layout calc by calling iframe.offsetHeight (Firefox/Chromium)
		// and make sure it's not considered dead code by Closure Compiler
		if (iframe.offsetHeight && /inactive/.test(span.className))
		{
			span.className = classPrefix + '-active-tn';
			iframe.removeAttribute('style');

			activeMiniplayerSpan?.click();
			activeMiniplayerSpan = span;
		}
		else
		{
			span.className = classPrefix + '-inactive-tn';
			activeMiniplayerSpan = null;
		}
	}

	/**
	* @param {!HTMLSpanElement} proxy
	*/
	function prepareClickToLoad(proxy)
	{
		if (proxy.hasAttribute(dataPrefix + '-c2l-background'))
		{
			// Set the background on the proxy's wrapper if applicable
			let span = /** @type {!HTMLSpanElement} */ ((proxy.hasAttribute(dataPrefix)) ? proxy : proxy.parentElement.parentElement);
			span.style.background = proxy.getAttribute(dataPrefix + '-c2l-background');
		}
		proxy.onclick = (e) =>
		{
			// Don't let the click be handled as a miniplayer-related click
			e.stopPropagation();
			loadIframe(proxy);
		};
	}

	function prepareMiniplayer(iframe, span)
	{
		if (iframe.hasAttribute(dataPrefix) || span.hasAttribute('style'))
		{
			return;
		}

		span.className = classPrefix + '-inactive';
		span.onclick   = () => handleMiniplayerClick(iframe, span);

		// NOTE: Chrome doesn't seem to support iframe.ontransitionend
		iframe.addEventListener(
			'transitionend',
			() =>
			{
				if (/-tn/.test(span.className))
				{
					span.className = span.className.replace('-tn', '');
					iframe.removeAttribute('style');
				}
			}
		);
	}

	/**
	* @param  {string} url
	* @return {string}
	*/
	function getStorageKey(url)
	{
		// "https://s9e.github.io/iframe/2/twitter.min.html#1493638827008737282#theme=dark"
		// should become "s9e/2/twitter#1493638827008737282"
		return url.replace(/.*?ifram(e\/\d+\/\w+)[^#]*(#[^#]+).*/, 's9$1$2');
	}

	/**
	* @param {string} storageKey
	* @param {string} data
	*/
	function storeIframeData(storageKey, data)
	{
		try
		{
			// Clean up local storage some ~10% of the time
			if (Math.random() < .1)
			{
				pruneLocalStorage();
			}
			localStorage[storageKey] = data;
		}
		catch
		{
		}
	}

	function pruneLocalStorage()
	{
		if (!(localStorage instanceof Storage))
		{
			return;
		}

		// If the storage exceeds the maximum size, remove roughly half the entries created by
		// this script, selected randomly. We do not need an elaborate eviction strategy, we just
		// need to make some room
		let i = localStorage.length || 0;
		if (i > STORAGE_MAX_SIZE)
		{
			while (--i >= 0)
			{
				const storageKey = localStorage.key(i) || '';
				if (/^s9e\//.test(storageKey) && Math.random() < .5)
				{
					localStorage.removeItem(storageKey);
				}
			}
		}
	}

	function startNavigation(destinationHash)
	{
		if (document.querySelector(destinationHash))
		{
			inNavigation = true;
			scheduleNavigationEnd();
			loadIframes(getTargetRange(destinationHash));
		}
	}

	function scheduleNavigationEnd()
	{
		window.clearTimeout(navigationTimeout);
		navigationTimeout = window.setTimeout(
			() =>
			{
				inNavigation = false;
			},
			NAVIGATION_DELAY
		);
	}

	// Start loading embeds immediately. It will let dynamic embeds be resized before the document
	// is fully loaded, and without a transition if readyState !== "complete". We manually select
	// the target range and leave the visible range to event handlers
	if (hash)
	{
		startNavigation(hash);
	}

	// If the navigation failed, load iframes in the visible range
	if (!inNavigation)
	{
		loadIframes(getVisibleRange());
	}

	prepareEvents(window.addEventListener);

	// Listen for intra-document navigation so we can immediately start loading embeds in the target
	// range and resize them the correct way
	/** @suppress {strictMissingProperties} */
	window.navigation?.addEventListener(
		'navigate',
		(e) =>
		{
			const destination = e['destination'];
			if (!destination['sameDocument'])
			{
				return;
			}

			let m = /#[-\w]+$/.exec(destination['url']);
			if (m)
			{
				startNavigation(m[0]);
			}
		}
	);
})(window, document, location.hash, 'data-s9e-mediaembed', 's9e-miniplayer');