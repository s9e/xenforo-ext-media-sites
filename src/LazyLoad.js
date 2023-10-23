((window, document, dataPrefix, classPrefix) =>
{
	// Delay in milliseconds between events and checking for visible elements
	const REFRESH_DELAY = 32;

	// Enum indicating an iframe's position in relation to viewport
	const ABOVE   = 0;
	const VISIBLE = 1;
	const BELOW   = 2;

	// Enum indicating the scrolling direction
	const SCROLL_DOWN = 0;
	const SCROLL_UP   = 1;

	// Max number of items in storage
	const STORAGE_MAX_SIZE = 100;

	let nodes   = document.querySelectorAll('span[' + dataPrefix + '-iframe]'),
		i       = 0,
		proxies = [],
		top     = 0,
		bottom  = window.innerHeight,
		timeout = 0,
		hasScrolled     = false,
		lastScrollY     = window.scrollY,
		scrollDirection = SCROLL_DOWN,
		activeMiniplayerSpan = null,
		documentElement      = document.documentElement,
		localStorage         = {};
	while (i < nodes.length)
	{
		proxies.push(nodes[i++]);
	}

	try
	{
		localStorage = window.localStorage;
	}
	catch
	{
	}

	// Start loading embeds immediately. It will let dynamic embeds be resized before the document
	// is fully loaded (and without a transition if readyState !== "complete")
	prepareEvents(window.addEventListener);
	refresh();

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
	* @param  {!Element} element
	* @return {boolean}
	*/
	function isInRange(element)
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

	function scheduleRefresh()
	{
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
			iframe.onload = onResizableIframeLoad;

			// Resize the iframe after it's been inserted in the page so it's resized the right way
			// (upward/downward) and with a transition if visible
			const storedDimensions = localStorage[getStorageKey(iframe.src)];
			if (typeof storedDimensions === 'string')
			{
				resizeIframeFromDimensions(iframe, storedDimensions);
			}
		}
	}

	function onResizableIframeLoad(e)
	{
		const channel    = new MessageChannel,
		      iframe     = /** @type {!HTMLIFrameElement} */ (e.target),
		      storageKey = getStorageKey(iframe.src);
		channel.port1.onmessage = (e) =>
		{
			const data = ('' + e.data);

			// Some content providers may send the content's height before everything (e.g. images)
			// is loaded. If we have a stored height for this iframe and we receive a smaller
			// number from the embed, we delay the resizing by a few seconds before setting the
			// height from whichever value is in storage at the time. This provides a grace period
			// for the embed to load more of its assets and set a more accurate height
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
				(iframe.getBoundingClientRect().height > +(data.split(' ')[0])) ? 5000 : 0
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
		const rect = iframe.getBoundingClientRect();
		if (rect.bottom > window.innerHeight)
		{
			return BELOW;
		}

		let top = -1;
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

	/**
	* @param  {string} selector
	* @param  {string} prop
	* @return {number}
	*/
	function getElementRectProperty(selector, prop)
	{
		const el = document.querySelector(selector);

		return (el) ? el.getBoundingClientRect()[prop] : -1;
	}

	/**
	* @param {!HTMLIFrameElement} iframe
	* @param {string}             data
	*/
	function resizeIframeFromDimensions(iframe, data)
	{
		const dimensions = data.split(' ');

		resizeIframe(iframe, dimensions[0], dimensions[1] || 0);
	}

	/**
	* @param {!HTMLIFrameElement} iframe
	* @param {number|string}      height
	* @param {number|string}      width
	*/
	function resizeIframe(iframe, height, width)
	{
		const iframePosition = getIframePosition(iframe),
		      expandUpward   = (iframePosition === ABOVE || (iframePosition === VISIBLE && scrollDirection === SCROLL_UP)),
		      oldDistance    = (expandUpward) ? getDistanceFromBottom() : 0,
		      style          = iframe.style;

		// Temporarily disable transitions if the document isn't fully loaded yet, the iframe isn't
		// visible, or we need to scroll the page
		if (iframePosition !== VISIBLE || expandUpward || document.readyState !== 'complete')
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

		style.height = height + 'px';
		if (width)
		{
			style.width = width + 'px';
		}

		if (expandUpward)
		{
			const newDistance = getDistanceFromBottom(),
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

	/**
	* @return {number}
	*/
	function getDistanceFromBottom()
	{
		// NOTE: scrollY has higher IE requirements than scrollBy()
		return getElementRectProperty('html', 'height') - window.scrollY;
	}

	function refresh()
	{
		// Don't load anything if the page is not visible
		if (document.visibilityState === 'hidden')
		{
			return;
		}

		if (lastScrollY === window.scrollY)
		{
			// Reset the scroll direction on click so that tweets expand downward when expanding a
			// quote after scrolling up
			scrollDirection = SCROLL_DOWN;
		}
		else
		{
			hasScrolled     = true;
			scrollDirection = (lastScrollY > (lastScrollY = window.scrollY)) ? SCROLL_UP : SCROLL_DOWN;
		}

		// Refresh the loading zone and extend it if we're done loading the page
		if (document.readyState === 'complete')
		{
			bottom = window.innerHeight * 2;
			top    = -bottom / ((scrollDirection === SCROLL_DOWN) ? 4 : 2);
		}

		const newProxies = [];
		proxies.forEach(
			(proxy) =>
			{
				if (isInRange(proxy))
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

	function handleMiniplayerClick(iframe, span)
	{
		const rect   = span.getBoundingClientRect(),
		      style  = iframe.style;

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
})(window, document, 'data-s9e-mediaembed', 's9e-miniplayer');