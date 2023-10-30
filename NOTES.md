This document contains notes related to development, for future reference and in no particular order.


### Adding a link when using a custom `[URL]` BBCode to display media

Could be enabled:

 1. Unconditionally.
 2. If `autoEmbedMedia` is set to add a link.
 3. Via its own option.

(1) and (2) ends up with people asking for an option down the line.


The markup for the link can be provided:

 1. As HTML, via a template.
    1. Hard to edit.
    2. The link won't follow rules associated with `[URL]` tags, `rel` won't be correct, extensions that track outgoing links may not work.
 2. As HTML, as an option.
 3. As BBCode.
    1. Using `autoEmbedMedia.linkBbCode` means this feature depends on `autoEmbedMedia`.
	   1. Disabling auto-embed *resets* `autoEmbedMedia.linkBbCode` to its default value. That's own togglable textboxes work in XenForo.
 4. No markup at all, just render both the original `[URL]` BBCode and the embedded media. Use CSS for styling.
    1. Hard to configure.


BBCode pitfalls:

 1. The BBCode suffix could be infinitely recursive if `[URL media=...]` is used.
 2. BBCode injection via the URL. Square brackets and quotes can be escaped to mitigate.
 3. Long URLs are not shortened. That's how `autoEmbedMedia` behaves as well.


### Lazy loading

#### Special cases

 - An embed can be within the viewport's geometry without being visible:
     - Hidden in a quote → `isInVisibleRangeOfBlock()`
     - Hidden in a spoiler → `isInRange()`
     - Hidden behind the sticky header, or a footer

 - After scrolling up to a previous post, expanding a quote block should not cause a quoted embed to be resized upwards. This is addressed in `refresh()`.

 - When using an intradocument link to a previous post, dynamically-sized embeds should not expand upwards. This is addressed via a pair of `navigate` and `navigatesuccess` event that temporarily set a `inNavigation` variable that suppresses the `expandUpward` logic.

 - When an iframe above the viewport is resized, Firefox will automatically adjust the window's scrolling position `window.scrollY` so that content doesn't get pushed out of the viewport. Other browsers need to be adjusted manually.


#### Page load timeline

What happens when we navigate to a specific post (at the bottom of a ~5 screens tall page) from the thread list:

 - Chromium:
     1. `document.readyState` changes to `interactive`
     2. `scroll` event
     3. `document.readyState` changes to `complete`
     4. `load` event

 - Firefox:
     1. `document.readyState` changes to `interactive`
     2. `scroll` event
     3. `document.readyState` changes to `complete`
     4. `scroll` event
     5. `load` event
     6. `scroll` event × 3

The number of scroll events on Firefox depends on the height of the page. It probably also depends on whether/how smooth scrolling is enabled.
