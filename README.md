# Tidewrack (mod_tidewrack)

**Sorts what washes up.** A lightweight Joomla module that pulls articles from chosen categories into filterable, sortable cards with seven layouts.

By Chaotic Fish Design Studio · Version 1.2.0 · Joomla 5 and 6 · PHP 8.2+ · GPL v2 or later

---

## What it does

- Pulls published articles from one or more categories (optionally including subcategories).
- Shows each as a card: intro image, title, a trimmed intro, date, reading time, and tags.
- Lets visitors filter by **admin-whitelisted tags** (Any or All), **categories** and **years**, search, sort, and reveal more.
- Seven layouts: **Grid**, **Front Page**, **Contact Sheet**, **Tide Line**, **Magazine List**, **Masonry**, **Table**. Any layout but Table can show **picture-only** cards.
- Filters above the cards, or in a sticky **sidebar** on the left or right.
- Articles open on their own page, or **in a pop-up** with Previous and Next.
- Cards reshuffle with a smooth animation when filters change (off under reduced motion).
- Filtered views can live in the page address, so they can be bookmarked and shared.
- Fun on top: generated art for image-less articles, a *Fresh catch* badge, *Cast a line* for a random pick, *Bring in the next tide*, *Beachcombed* read marks, and visiting staff (see *Extras*).

Weight: about 9 KB of JavaScript and 7 KB of CSS, gzipped (minified files load; readable sources ship alongside). No libraries. It relies on the template's Bootstrap 5 for form controls, buttons, `visually-hidden` and `stretched-link`.

## Requirements

- Joomla 5 or 6.
- PHP 8.2 or later (tested through 8.3; kept free of anything deprecated in 8.4 and 8.5).
- A template that loads Bootstrap 5 CSS (T4, Cassiopeia and friends).

The installer checks PHP and Joomla first and stops with a plain message if either is too old. Nothing is installed in that case.

## Install

1. Extensions → Install → upload `mod_tidewrack-1.2.0.zip`.
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

The pop-up (*When a Card Is Clicked → Open in a pop-up*) asks for one article at a time through `com_ajax`, and the answer goes through the same query: the module must be a published site Tidewrack the visitor can see, and the article must pass every rule above plus that module's own categories, featured and language settings. Asking for any other article ID gets "not available".

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
| Sort | Newest published | Published = Start Publishing (`publish_up`), falling back to Created. Also: oldest published, recently updated (`modified`), least recently updated, title A–Z, and **Article order**: the order set by dragging in Content: Articles, category by category (in category-tree order). |
| Featured First | No | Featured articles stay at the top whatever the sort, then the sort applies within each group. |
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
| Cast a Line Button | Yes | Picks a random article from the current filters, reveals it if it's beyond *Show more*, scrolls it into view, wiggles it and announces "Caught: …". |
| Category Filters | No | A row of category chips with live counts. Only shown when the loaded articles come from two or more categories. |
| Year Filters | No | A row of year chips, newest first, with live counts. Only shown when there are two or more years. |
| Year Filter Uses | Published | Which date files an article under a year: Published (Start Publishing, falling back to Created), Created or Modified. |
| Filter Position | Top | **Top**, **Left sidebar** or **Right sidebar**. See *Filters on the side*. |
| Sidebar Top Offset (px) | 16 | Space kept above the sticky sidebar, so a sticky site header doesn't cover it. |
| Shareable Filter Links | Yes | See *URL format* below. |

Picked values in one row widen each other (a category *or* another; a year *or* another; tags follow Any/All). The rows narrow each other: tags *and* category *and* year *and* search. Every chip's count shows what it would give with everything else as it is.

### Display
| Setting | Default | Notes |
| --- | --- | --- |
| Layout | Grid | See *Layouts*. |
| Card Content | Full | **Full**, or **Picture only**: the picture alone, with the title over it on hover or keyboard focus (always on touch screens). Not for Table. |
| Badges on Pictures | Yes | Picture-only cards: keep the Featured and Fresh catch badges and the Beachcombed mark, or hide them. |
| When a Card Is Clicked | Go to the article | Or **Open in a pop-up**. See *Read it here*. |
| Columns (desktop) | 3 | 2–4. Drops to 2 under 992px, 1 under 576px. |
| Magazine List Columns | 1 | 1 or 2 rows of image-left cards. Two drop back to one below 992px. Also applies when visitors flip to List. |
| Visitor Cards/List Toggle | No | Lets visitors flip between your layout and Magazine List. |
| Initially Shown | 9 | 0 = show all, no Show More button. |
| Show More Adds | 6 | The button reads *Bring in the next tide*: a wave rolls across it and the new cards rise in one after another. |
| Title Heading Level | H3 | H2–H4, to fit the page outline. |
| Intro Length | 180 | Visible characters. Cut at a word, ellipsis added. 0 hides the intro. |
| Show Image | Yes | |
| No-Image Art | Tide | For articles with no image at all: **Tide** (layered waves that drift while the card is hovered or focused), **Shoreline** (sun, hills, birds) or **Gradient** (the colour wash from 1.0). Shapes and colours vary by article and never change for a given one. |
| Fresh Catch Badge (days) | 14 | Badge on articles published (Start Publishing) within this many days. 0 = off. A grease-pencil star in Contact Sheet. |
| Read Marks | Yes | Articles a visitor opened from the module (click or middle-click) get a *Beachcombed* mark. Stored in that visitor's browser only (`localStorage`, key `twk-beachcombed`, last 500), shared by every Tidewrack on the site. *Smooth the sand* clears them. |
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
| Extras | Yes | Hidden visitors, and Tancho's empty-state report. Turning it off makes the fish sad. |
| Staff Button | Yes | A visible button that sends a random member of the staff through. Only with Extras on. |
| Staff Button Text | *Do not press* | The words on that button. Empty = the default. |

## Intro text rules

1. `{plugin tags}` (loadmodule, Chaos Filet `{article …}`, etc.) are removed.
2. Everything is stripped except **bold**, *italic* and line breaks. `<b>`/`<i>` become `<strong>`/`<em>`. All attributes go. **Links are stripped**, because the whole card is already a link.
3. Text is cut to the character limit at a word boundary, an ellipsis is added, and any open `<strong>`/`<em>` is closed.
4. If the intro text is empty, the full text is used.

## Images

Fallback order: **intro image → full-text image → first `<img>` in the article → No-Image Art** (Tide, Shoreline or Gradient; colours and shapes come from the article ID, so each card always gets the same picture). The art is a small inline SVG, so nothing extra is downloaded.

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
| **Table** | Rows for long archives: a small thumbnail and the title, then Date, Category, Author, Reading time and Tags, each shown when its Display setting is on. The **Article** and **Date** headers sort (Date flips between newest and oldest) and say which way with `aria-sort`. On phones each row becomes a labelled block. |

Notes:
- **Masonry reads across the rows**: newest top-left, the next one beside it. The script lays it out on a fine CSS grid and re-measures as images load or the window resizes. Without JavaScript it falls back to plain CSS columns, which read *down* each column instead.
- The Contact Sheet scrawl uses **Caveat** when the site already loads it (the CFD sites do), then Marker Felt (Mac), Segoe Print (Windows) or the system's cursive font. Tidewrack loads no web font itself. `font-size-adjust` evens out the sizes between those fonts. Set `--twk-scrawl` on `.twk` to use another font.
- Layouts take their colours from Bootstrap variables, so light and dark colour modes both work.
- **Picture only** works with Grid, Front Page (the lead becomes a wide banner), Contact Sheet, Tide Line, Magazine List (it becomes a grid of pictures) and Masonry. Intro, meta and tags are left out of view but stay in the page, so search and filters work as before.

## Filters on the side

From 992px wide, *Filter Position: Left* or *Right* puts the whole filter bar in a panel beside the cards: search and sort at full width, each chip row under a small heading, and a **tide gauge** that fills with how many articles match ("7 of 24"). The panel sticks while the cards scroll (it scrolls on its own if it is taller than the window) and keeps *Sidebar Top Offset* clear above it. Cards lose a column to make room (3 → 2, 4 → 3). Below 992px the filters go back on top.

The panel uses Bootstrap's `--bs-tertiary-bg` and border colours, so it follows light and dark modes. Set `--twk-side` on `.twk` to change its width (default `17rem`). It is a region named *Filter articles*, and each chip row is a labelled group.

## Read it here

With *When a Card Is Clicked: Open in a pop-up*, clicking a card opens the article in a pop-up on the page:

- The article comes from `index.php?option=com_ajax&module=tidewrack&method=article&format=json&mid=<module>&id=<article>&Itemid=<page>`, fetched on click and kept for the rest of the visit. Its text is run through Joomla's content plugins, like an article page; relative image and link paths are made root-relative so they work on any page. The Full Article Image goes on top, as on the article page.
- **Previous** and **Next** (or <kbd>←</kbd> <kbd>→</kbd>) step through the articles that match the current filters, in the current order, with "3 of 12" between them.
- *Open this article on its own page* is always at the bottom, and Ctrl/Cmd-click, Shift-click and middle-click on a card still open the page. Without JavaScript the cards are ordinary links.
- <kbd>Esc</kbd>, the × button or a click outside closes it, and focus goes back to the card you ended on. The page behind doesn't scroll while it is open.
- Opening an article in the pop-up counts for the Beachcombed mark.
- If the request fails, the pop-up says so and offers the article's own page.
- Scripts inside article text (for example the email-cloaking plugin's) don't run inside the pop-up; that text shows as the plugin left it.

## URL format

With Shareable Filter Links on, state is written to the hash:

```
#twk-<module id>:tags=3,7;mode=all;cats=12;years=2025,2024;q=goat;sort=mod_desc;view=list;open=345
```

Only non-default values are written; clearing everything removes the hash. `open=` names the article in the pop-up: a shared link opens it there, or, with the pop-up off, brings its card into view with a wiggle. Pasting a different Tidewrack link into the same page (or going back to one) applies it without a reload. Updates use `history.replaceState`, so they don't add history entries or fire `hashchange` (it won't wake Wayfin or the template's anchor-scroll handler). Opening a shared link scrolls the module into view.

## Extras

Switched on by default, off under **Advanced → Extras**.

| Trigger | What happens |
| --- | --- |
| Type `fishie` or `claude` | The cleaner wrasse swims right to left *behind* the page: he only shows through the card photos in his lane, disappearing behind margins and text between them. Each photo card wiggles as he nibbles past. With images turned off, he swims freely across the module instead. |
| Type `kokido` | Kokido pops up from the bottom of a random on-screen card's photo with a springy bounce. Point at him or tap him and he ducks. He leaves on his own after 5 seconds. |
| Type `hawke` | Hawke walks onto a card from its right edge, eats one of its tag chips, chews, and wanders off. The chip grows back. |
| Type `tancho` | Tancho trots down the Tide Line spine in Tide Line, or along the top of the cards in every other layout (never across a sidebar), kicking up dust. |
| **Do not press** button | One of the four at random. The wording is a setting. |
| Long-press **Clear filters** (touch) | One of the four at random. |
| Filters match nothing | Tancho ("Found mud.") or Ziggy in his pirate hat ("No treasure here.") reports in, picked at random each time. An empty category gets one of them too. |

Typing inside the search box or any form field never triggers them. Only one extra appears at a time. Under `prefers-reduced-motion`, they fade in and out in place instead of moving.

## Accessibility

- Works without JavaScript: every card is rendered server-side and shown; the filter bar stays hidden until the script loads.
- Chips, Any/All and Cards/List are real buttons with `aria-pressed`.
- The card is clickable through the title link (`stretched-link`), so there is one link per card for screen readers. The intro text and tag buttons sit above that click area: the intro stays selectable and can be read by point-and-read tools such as A11y Toolbar's Read Aloud, and tags stay separately clickable.
- A visually hidden live region announces "Showing 5 of 12 articles" after each change.
- **Show more** moves keyboard focus to the first newly revealed card.
- Focus rings are visible on every control; the card outlines itself when its link has focus.
- **A11y Toolbar** (`mod_a11ytoolbar`): under High Contrast, pressed chips, tags, Any/All buttons and the Featured badge invert to the contrast palette, and focus rings switch to its link colour. Under Reading Overlay, pressed states keep their colours and Contact Sheet keeps its dark film. Dimmed chips also get a dashed border, so they never rely on fading alone.
- Card images use the article's alt text. With no alt text set they get `alt=""` (decorative), because the title sits right beside them.

## When something goes wrong

If loading the articles throws an error (a database problem, a bug), visitors see nothing broken: the module simply doesn't show. Super Users see a one-line note, and the details go to `mod_tidewrack.errors.php` in the site's log folder (Global Configuration → Logging). Pop-up errors are logged the same way and the visitor is offered the article's own page.

## Install check

The install zip's `script.php` carries a list of every file in the zip with its SHA-256, written by `build.py`. Before anything is copied, the installer compares the unpacked zip with that list (Windows line endings are allowed) and installs nothing if a file is missing or changed, naming the files. That catches a download cut short, or a transfer that unpacks and repacks archives and drops some file types. In the source zip the list is empty, which skips the check.

## Multiple instances

Any number of Tidewrack modules can share a page. Each runs independently with its own ID-scoped URL state.

## Files

```
mod_tidewrack.xml
script.php                          install checks (PHP 8.2+, Joomla 5+, every file present)
services/provider.php
src/Dispatcher/Dispatcher.php
src/Helper/TidewrackHelper.php      query, access control, tags, intro cleaning, images, srcset, pop-up endpoint
tmpl/default.php
language/en-GB/mod_tidewrack.ini
language/en-GB/mod_tidewrack.sys.ini
media/css/tidewrack.css            readable source
media/css/tidewrack.min.css        loaded on the site
media/js/tidewrack.js              readable source
media/js/tidewrack.min.js          loaded on the site
media/images/cleaner-wrasse.svg
media/images/kokido-peek--240.webp
media/images/hawke.svg, tancho.svg  Wayfin's goat and horse (Extras)
media/images/tancho-face.webp, ziggy-face.webp  "nothing matches" portraits
README.md, CHANGELOG.md, LICENSE.txt
```

Source zip only (not installed): `build.py`, `demo/` (`render.php`, `shell.html`, `sample.json`) and the built `tidewrack-demo.html`.

## Building

```
npm i -g terser csso-cli      # or: pip install rjsmin rcssmin
python3 build.py              # build, then release through the CFD update server
python3 build.py --no-release # build only
```

`build.py` checks the version matches in the manifest, Dispatcher, CSS and JS headers, README and CHANGELOG; checks CSS property order; minifies (terser + csso when installed, as in Wayfin's build, else the Python minifiers); renders the demo from the real template with PHP (`demo/render.php` fills `demo/shell.html` with `demo/sample.json`, shifting the sample dates so the newest is always three days old) and inlines every image; and writes `dist/mod_tidewrack-<version>.zip`, `dist/mod_tidewrack-<version>-source.zip` and `dist/tidewrack-demo.html`. It then calls `cfd_update.release('mod_tidewrack', …)` from the shared generator, found through `CFD_UPDATES` (default `../cfd-updates`); if the generator isn't there, it says so and stops after the build.

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
- [ ] Image fallback: full-text image, body image, No-Image Art each appear on the right articles.
- [ ] ShrinkRay copies produce a `srcset` pointing into `images/_sized/…` (view source), including for an image in a subfolder.
- [ ] A11y Toolbar: High Contrast shows which chips are pressed; Reading Overlay keeps pressed chips and Contact Sheet readable.
- [ ] Emoji / ampersand / quotes in titles display correctly.
- [ ] Each layout **saves** (pick it, Save, reopen: still selected) and shows on the site; Advanced → Module Style still saves independently.
- [ ] All seven layouts at desktop and phone widths, light and dark.
- [ ] A shared filter link restores the view.
- [ ] Extras on and off. With Extras on: `hawke` (chip eaten, grows back), `tancho` (Tide Line spine, and along the cards elsewhere), the *Do not press* button, and Tancho on a search that matches nothing. Change the button text and save.
- [ ] No-Image Art: Tide, Shoreline and Gradient on the articles without images; waves drift only while hovering.
- [ ] Fresh catch shows on articles newer than the day setting; 0 hides it.
- [ ] Cast a line: lands on an article that matches the current filters, including one beyond *Show more*.
- [ ] Open an article, come back: *Beachcombed* shows; *Smooth the sand* clears it.
- [ ] Magazine List Columns 2: two columns on desktop, one below 992px.
- [ ] Install on PHP below 8.2 or Joomla 4 is refused with a message (if you have such a test site).
- [ ] Install check: delete a file from an unpacked copy, rezip, install: refused, naming the file. The real zip installs.
- [ ] Pop-up: opens the article with images and plugins working; Previous/Next follow the filters; Esc returns to the card; Ctrl-click opens the page.
- [ ] Pop-up and access: a Registered article never loads logged out, even by editing the `id` in the request. An article from a category the module doesn't use is "not available".
- [ ] Pop-up on a page with a menu item assigned only to some pages, and on a multilingual site.
- [ ] A link with `open=` opens the pop-up; with the pop-up off, it brings the card into view.
- [ ] Table: sorts by Article and Date; phone view shows labelled blocks; columns follow the Display settings.
- [ ] Picture only, in each layout: title on hover and Tab; Badges on Pictures off hides badges and the Beachcombed mark.
- [ ] Sidebar left and right at 1280px, 1000px and 900px (back on top); sticky under a sticky header with the offset; light and dark.
- [ ] Category and year chips: counts, combining with tags and search, shared links.
- [ ] Article order matches the drag order in Content: Articles; Featured First keeps featured at the top in every sort.
- [ ] Break the query (e.g. a bad category ID in the database): visitors see nothing, Super Users see the note, the log file has the details.
