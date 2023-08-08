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
