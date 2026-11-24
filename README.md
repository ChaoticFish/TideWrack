# Tidewrack (mod_tidewrack)

**Sorts what washes up.** A lightweight Joomla module that pulls articles from chosen categories into filterable, sortable cards with six layouts.

By Chaotic Fish Design Studio · Version 1.0.2 · Joomla 5 and 6 · PHP 8.2+ · GPL v2 or later

---

## What it does

- Pulls published articles from one or more categories (optionally including subcategories).
- Shows each as a card: intro image, title, a trimmed intro, date, reading time, and tags.
- Lets visitors filter by **admin-whitelisted tags** (Any or All), search, sort, and reveal more.
- Six layouts: **Grid**, **Front Page**, **Contact Sheet**, **Tide Line**, **Magazine List**, **Masonry**.
- Cards reshuffle with a smooth animation when filters change (off under reduced motion).
- Filtered views can live in the page address, so they can be bookmarked and shared.

Weight: about 5 KB of JavaScript and 4 KB of CSS, gzipped (minified files load; readable sources ship alongside). No libraries. It relies on the template's Bootstrap 5 for form controls, buttons, `visually-hidden` and `stretched-link`.

## Requirements

- Joomla 5 or 6.
- PHP 8.2 or later (tested through 8.3; kept free of anything deprecated in 8.4 and 8.5).
- A template that loads Bootstrap 5 CSS (T4, Cassiopeia and friends).

The installer checks PHP and Joomla first and stops with a plain message if either is too old. Nothing is installed in that case.

## Install

1. Extensions → Install → upload `mod_tidewrack-1.0.2.zip`.
2. Content → Site Modules → New → **Tidewrack**.
3. Pick at least one category, assign a position and menu items, save.

Updates come through Joomla's own updater (System → Update → Extensions) from the Chaotic Fish Design update server. A site that installed Tidewrack before 1.0.1's update-server build needs one manual install of a newer zip to start receiving them.

The standalone demo, `tidewrack-demo.html`, ships separately (and in the source zip) to keep the install zip small. It loads Bootstrap from jsDelivr and needs nothing else, so it can go straight into `/files/demos/`.

## Access control

Access is enforced in the database query, never in the template. A visitor only ever receives an article when **all** of these are true:

- Article state is Published.
- Start Publishing is empty or in the past; Finish Publishing is empty or in the future.
- The article's access level is one of the visitor's view levels.
- The article's category is published and visible to the visitor.
- **Every ancestor category** above it is also published and visible.
- Tags shown (as chips or on cards) are published, visible to the visitor, and on the whitelist.

The module has **no caching option on purpose**. A shared cache could hand one visitor's view-level results to another.

## Settings

### Source
| Setting | Default | Notes |
| --- | --- | --- |
| Categories | — | Required. Multiple allowed. |
| Include Subcategories | Yes | Every descendant of the chosen categories. |
| Featured Articles | Show | Show / Only featured / Hide featured. |
| Match Site Language | Yes | Multilingual sites only: current language + All. |
| Maximum Articles | 60 | 1–100. Everything filters in the browser. |
| Filter Tags (Whitelist) | empty | Only these tags become chips and card tags. Empty = every published tag on the loaded articles. |

### Sorting
| Setting | Default | Notes |
| --- | --- | --- |
| Sort | Newest published | Published = Start Publishing (`publish_up`), falling back to Created. Also: oldest, recently updated (`modified`), least recently updated, title A–Z. |
| Visitor Sort Menu | Yes | Visitors can re-sort; your default is selected on load. |

### Filtering
| Setting | Default | Notes |
| --- | --- | --- |
| Tag Filters | Yes | The chip bar. Hidden automatically when no tags are present. |
| Matching | Any | Default for multiple picked tags. |
| Visitor Any/All Toggle | Yes | |
| Tag Counts | Yes | Live: each chip shows how many articles it would show with the current search (and, in All mode, the tags already picked). Unpicked chips that would show nothing are dimmed and can't be picked. |
| Search Box | Yes | Matches title + intro text; every word must match. <kbd>Esc</kbd> clears it. |
| Clear Button | Yes | |
| Shareable Filter Links | Yes | See *URL format* below. |

### Display
| Setting | Default | Notes |
| --- | --- | --- |
| Layout | Grid | See *Layouts*. |
| Columns (desktop) | 3 | 2–4. Drops to 2 under 992px, 1 under 576px. |
| Visitor Cards/List Toggle | No | Lets visitors flip between your layout and Magazine List. |
| Initially Shown | 9 | 0 = show all, no Show More button. |
| Show More Adds | 6 | |
| Title Heading Level | H3 | H2–H4, to fit the page outline. |
| Intro Length | 180 | Visible characters. Cut at a word, ellipsis added. 0 hides the intro. |
| Show Image | Yes | |
| Date Shown | Published | Published / Modified / None. |
| Date Format | `M j, Y` | PHP date format. |
| Tags on Cards | Yes | Clicking one filters by it. |
| Reading Time | Yes | Words per minute default 200. |
| Author / Category | No | |
| Featured Badge | Yes | A small badge on featured articles' photos (above the title on text-only cards). In Contact Sheet it's a red grease-pencil circle. |

### Images
| Setting | Default | Notes |
| --- | --- | --- |
| ShrinkRay srcset | Yes | Adds a `srcset` when ShrinkRay copies exist. |
| ShrinkRay Output Folder | `images/_sized` | ShrinkRay's Output Folder setting. Empty also means `images/_sized`. |
| Only These Widths | empty | Optional filter. Empty = every copy found for that image. |

### Advanced
| Setting | Default | Notes |
| --- | --- | --- |
| Extras | Yes | Hidden visitors. Turning it off makes the fish sad. |

## Intro text rules

1. `{plugin tags}` (loadmodule, Chaos Filet `{article …}`, etc.) are removed.
2. Everything is stripped except **bold**, *italic* and line breaks. `<b>`/`<i>` become `<strong>`/`<em>`. All attributes go. **Links are stripped**, because the whole card is already a link.
3. Text is cut to the character limit at a word boundary, an ellipsis is added, and any open `<strong>`/`<em>` is closed.
4. If the intro text is empty, the full text is used.

## Images

Fallback order: **intro image → full-text image → first `<img>` in the article → colour gradient** (the hue is derived from the article ID, so it is stable).

Width and height from Joomla's media field are written to the `<img>` to prevent layout shift. The first three cards load eagerly; the rest lazy-load.

### Pairs nicely with ShrinkRay

[ShrinkRay](https://chaoticfishdesign.com/downloads/send/4-cfd-developed/8-plugin-shrinkray) (`plg_system_shrinkray`) makes resized copies of images inside `images/`, mirroring their folders in its output folder:

```
images/news/2026/hawke.jpg  →  images/_sized/news/2026/hawke--333.webp
                               images/_sized/news/2026/hawke--666.webp …
```

Tidewrack lists that folder (once per folder per page), takes every `hawke--<width>.webp` it finds and builds a `srcset` with `sizes` matched to the layout. The original is added as the largest candidate when it is wider than every copy. Images outside `images/`, remote images and images with no copies are left as they are. AVIF copies are not used; ShrinkRay's own page rewrite serves those.

- Set ShrinkRay's **Size By** to **Width**. The numbers in the file names become `w` descriptors.
- If ShrinkRay's page rewrite is on, it may also process these images. When Tidewrack found no copies it adds `data-shrinkray-sizes`, which ShrinkRay reads, so its markup still gets the layout's `sizes`.

## Layouts

| Layout | What it looks like |
| --- | --- |
| **Grid** | Even cards in 2–4 columns. The safe baseline. |
| **Front Page** | The first visible story runs across the top, image left and serif headline right; the rest sit below in borderless newspaper columns under a double rule. |
| **Contact Sheet** | A dark film strip with sprocket holes, frame numbers (`1A`, `2A`…) and a `▸ TWK 400` edge mark. Titles are a yellow grease-pencil scrawl; hover circles a frame in red. |
| **Tide Line** | A vertical timeline. Cards alternate left and right of a date spine, staggered so they interlock. Single column with the spine on the left on phones. |
| **Magazine List** | Image-left, text-right rows. Stacks on phones. |
| **Masonry** | Staggered cards at their natural heights, tucking up under shorter neighbours. **Reads across the rows**, like Grid. |

Notes:
- **Masonry reads across the rows**: newest top-left, the next one beside it. The script lays it out on a fine CSS grid and re-measures as images load or the window resizes. Without JavaScript it falls back to plain CSS columns, which read *down* each column instead.
- The Contact Sheet scrawl uses **Caveat** when the site already loads it (the CFD sites do), then Marker Felt (Mac), Segoe Print (Windows) or the system's cursive font. Tidewrack loads no web font itself. `font-size-adjust` evens out the sizes between those fonts. Set `--twk-scrawl` on `.twk` to use another font.
- Layouts take their colours from Bootstrap variables, so light and dark colour modes both work.

## URL format

With Shareable Filter Links on, state is written to the hash:

```
#twk-<module id>:tags=3,7;mode=all;q=goat;sort=mod_desc;view=list
```

Only non-default values are written; clearing everything removes the hash. Updates use `history.replaceState`, so they don't add history entries or fire `hashchange` (it won't wake Wayfin or the template's anchor-scroll handler). Opening a shared link scrolls the module into view.

## Extras

Hidden visitors, switched on by default and off under **Advanced → Extras**.

| Trigger | What happens |
| --- | --- |
| Type `fishie` or `claude` | The cleaner wrasse swims right to left *behind* the page: he only shows through the card photos in his lane, disappearing behind margins and text between them. Each photo card wiggles as he nibbles past. With images turned off, he swims freely across the module instead. |
| Type `kokido` | Kokido pops up from the bottom of a random on-screen card's photo with a springy bounce. Point at him or tap him and he ducks. He leaves on his own after 5 seconds. |
| Long-press **Clear filters** (touch) | One of the two at random. |

Typing inside the search box or any form field never triggers them. Only one extra appears at a time. Under `prefers-reduced-motion`, both fade in and out in place instead of moving.

## Accessibility

- Works without JavaScript: every card is rendered server-side and shown; the filter bar stays hidden until the script loads.
- Chips, Any/All and Cards/List are real buttons with `aria-pressed`.
- The card is clickable through the title link (`stretched-link`), so there is one link per card for screen readers. The intro text and tag buttons sit above that click area: the intro stays selectable and can be read by point-and-read tools such as A11y Toolbar's Read Aloud, and tags stay separately clickable.
- A visually hidden live region announces "Showing 5 of 12 articles" after each change.
- **Show more** moves keyboard focus to the first newly revealed card.
- Focus rings are visible on every control; the card outlines itself when its link has focus.
- **A11y Toolbar** (`mod_a11ytoolbar`): under High Contrast, pressed chips, tags, Any/All buttons and the Featured badge invert to the contrast palette, and focus rings switch to its link colour. Under Reading Overlay, pressed states keep their colours and Contact Sheet keeps its dark film. Dimmed chips also get a dashed border, so they never rely on fading alone.
- Card images use the article's alt text. With no alt text set they get `alt=""` (decorative), because the title sits right beside them.

## Multiple instances

Any number of Tidewrack modules can share a page. Each runs independently with its own ID-scoped URL state.

## Files

```
mod_tidewrack.xml
script.php                          install checks (PHP 8.2+, Joomla 5+)
services/provider.php
src/Dispatcher/Dispatcher.php
src/Helper/TidewrackHelper.php      query, access control, tags, intro cleaning, images, srcset
tmpl/default.php
language/en-GB/mod_tidewrack.ini
language/en-GB/mod_tidewrack.sys.ini
media/css/tidewrack.css            readable source
media/css/tidewrack.min.css        loaded on the site
media/js/tidewrack.js              readable source
media/js/tidewrack.min.js          loaded on the site
media/images/cleaner-wrasse.svg
media/images/kokido-peek--240.webp
README.md, CHANGELOG.md, LICENSE.txt
```

Source zip only (not installed): `build.py` and `tidewrack-demo.html`.

## Building

```
pip install rjsmin rcssmin
python3 build.py              # build, then release through the CFD update server
python3 build.py --no-release # build only
```

`build.py` checks the version matches in the manifest, Dispatcher, CSS and JS headers, README and CHANGELOG; checks CSS property order; minifies; re-inlines the CSS, JS and Extras images into the demo; and writes `dist/mod_tidewrack-<version>.zip`, `dist/mod_tidewrack-<version>-source.zip` and `dist/tidewrack-demo.html`. It then calls `cfd_update.release('mod_tidewrack', …)` from the shared generator, found through `CFD_UPDATES` (default `../cfd-updates`); if the generator isn't there, it says so and stops after the build.

```
```

Assets load with classic `addStyleSheet()`/`addScript()` rather than the Web Asset Manager, which conflicted with T4 on the CFD sites. Data attributes all use the `data-twk-` prefix, so Bootstrap 4→5 shims that rename `data-*` attributes leave them alone.

## Testing checklist

Against the Sample Articles category (ID 13):

- [ ] Registered-access article is invisible when logged out, visible when logged in.
- [ ] Future `publish_up` and past `publish_down` articles never appear.
- [ ] Child-category article appears only with Include Subcategories on.
- [ ] The non-whitelisted tag never shows as a chip or on a card.
- [ ] Any vs. All give different results with two tags picked.
- [ ] Recently updated sorts differently from Newest published.
- [ ] Intro keeps bold/italic, drops the link; short intro has no ellipsis; long intro is cut.
- [ ] Image fallback: full-text image, body image, gradient each appear on the right articles.
- [ ] ShrinkRay copies produce a `srcset` pointing into `images/_sized/…` (view source), including for an image in a subfolder.
- [ ] A11y Toolbar: High Contrast shows which chips are pressed; Reading Overlay keeps pressed chips and Contact Sheet readable.
- [ ] Emoji / ampersand / quotes in titles display correctly.
- [ ] All six layouts at desktop and phone widths, light and dark.
- [ ] A shared filter link restores the view.
- [ ] Extras on and off.
- [ ] Install on PHP below 8.2 or Joomla 4 is refused with a message (if you have such a test site).
