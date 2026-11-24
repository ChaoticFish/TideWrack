# Tidewrack (mod_tidewrack)

**Sorts what washes up.** A lightweight Joomla module that pulls articles from chosen categories into filterable, sortable cards with six layouts.

By Chaotic Fish Design Studio · Version 1.0.1 · Joomla 5.4+ and Joomla 6 · GPL v2 or later

---

## What it does

- Pulls published articles from one or more categories (optionally including subcategories).
- Shows each as a card: intro image, title, a trimmed intro, date, reading time, and tags.
- Lets visitors filter by **admin-whitelisted tags** (Any or All), search, sort, and reveal more.
- Six layouts: **Grid**, **Front Page**, **Contact Sheet**, **Tide Line**, **Magazine List**, **Masonry**.
- Cards reshuffle with a smooth animation when filters change (off under reduced motion).
- Filtered views can live in the page address, so they can be bookmarked and shared.

Weight: about 4.7 KB of JavaScript and 3.5 KB of CSS, gzipped (minified files load; readable sources ship alongside). No libraries. It relies on the template's Bootstrap 5 for form controls, buttons, `visually-hidden` and `stretched-link`.

## Install

1. Extensions → Install → upload `mod_tidewrack-1.0.1.zip`.
2. Content → Site Modules → New → **Tidewrack**.
3. Pick at least one category, assign a position and menu items, save.

`tidewrack-demo.html` in the package is a standalone demo page. It is not installed by Joomla. It loads Bootstrap from jsDelivr and needs nothing else, so it can go straight into `/files/demos/`.

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
| ShrinkRay Widths | `333,666,1000` | Match your ShrinkRay sizes. |
| ShrinkRay Output Folder | empty | Empty = copies beside originals. |

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

[ShrinkRay](https://chaoticfishdesign.com/downloads/send/4-cfd-developed/8-plugin-shrinkray) (`plg_system_shrinkray`) makes resized WebP copies named with the double-dash suffix, e.g. `photo--333.webp`. Tidewrack checks for those copies on disk and builds a `srcset` with matching `sizes` per layout. Copies that don't exist are skipped; if none exist, the original is used alone.

Where it looks, in order:
1. `<output folder>/<same sub-path as the original>/photo--333.webp`
2. `<output folder>/photo--333.webp`
3. With no output folder set: beside the original.

> **Unverified:** the lookup paths above are a guess at how ShrinkRay lays out its output folder. If your copies live somewhere else, tell me the pattern and I'll match it.

ShrinkRay's own page-rewrite mode may also rewrite these images. Both together are harmless: an `<img>` that already has a `srcset` should be left as is. *(Worth confirming on the live site.)*

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
- The Contact Sheet scrawl uses a system handwriting font (Marker Felt on Mac, Segoe Print on Windows). Other systems fall back to their default cursive font. No web font is loaded, to keep it light.
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
- Card images use the article's alt text. With no alt text set they get `alt=""` (decorative), because the title sits right beside them.

## Multiple instances

Any number of Tidewrack modules can share a page. Each runs independently with its own ID-scoped URL state.

## Files

```
mod_tidewrack.xml
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
media/images/kokido-peek--240.png
tidewrack-demo.html                 standalone demo (not installed)
CHANGELOG.md
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
- [ ] ShrinkRay copies produce a `srcset` (view source).
- [ ] Emoji / ampersand / quotes in titles display correctly.
- [ ] All six layouts at desktop and phone widths, light and dark.
- [ ] A shared filter link restores the view.
- [ ] Extras on and off.
