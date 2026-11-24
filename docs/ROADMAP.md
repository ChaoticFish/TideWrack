# Tidewrack (mod_tidewrack): roadmap

Current release: **1.1.1** (2026-11-10). Plain Joomla 5/6 site module, no package wrapper. Element `mod_tidewrack`, client `site`, `twk-` prefix. Built with its own `build.py`.

## 1.1.1

- "Nothing matches" (and an empty category) shows a round photo of **Tancho** ("Found mud.") or **Ziggy** in his pirate hat ("No treasure here."), picked at random each time it appears. Portraits `media/images/tancho-face.webp` and `ziggy-face.webp` (240px, cropped from the Team_CFD photos). The drawn `tancho.svg` stays for the trotting Extra.

## 1.1.0 (the fun release)

- Magazine List Columns (1/2) — the parked item, now a setting (`list_columns`).
- No-Image Art: Tide (default) / Shoreline / Gradient — inline SVG from `TidewrackHelper::art()`, varies by article ID, waves drift only on hover/focus (WCAG 2.2.2).
- Fresh catch badge (`fresh_days`, default 14), Cast a line (`cast_line`), Bring in the next tide (Show More label + staggered reveal), Beachcombed read marks (`read_marks`, localStorage `twk-beachcombed`, "Smooth the sand" clears).
- Extras: Hawke eats a tag chip (`hawke`), Tancho trots the Tide Line spine or along the cards (`tancho`). Hawke/Tancho art is Wayfin's goat and horse in CFD orange (the real Tancho is a paint: white with a brown head).
- Staff button (`extras_button`, `extras_button_text`, default "Do not press") summons a random staff member.
- Demo is rendered from the real template: `demo/render.php` + `demo/shell.html` + `demo/sample.json`; `build.py` uses terser + csso when installed.

## Docs

- **Cheat sheet (PDF):** "Writing articles for Tidewrack", 2 pages, for article writers, styled after the Pecking Order training docx. Built from HTML with Chromium by `cheatsheet/build.py` (logo from the PO docx, wave art from `art()`, mascots from `media/images/`). Uses the module defaults (14 days, 180 characters, 60 articles, newest Start Publishing first); rebuild when those or field names change.

## Landing page

- Live slots: hero `{loadmoduleid 140}`, second (Layouts section) `{loadmoduleid 139}`. Demo links relative: `/files/demos/tidewrack-demo.html`. jDownloads: module is file 11 (`/downloads/send/4-cfd-developed/11-module-tidewrack`, `{jd_file file==11}`), cheat sheet is file 22 (`/downloads/send/7-training/22-cheat-sheet-tidewrack`, `{jd_file file==22}`). Cheat sheet links from the hero buttons and its own "For your writers" section.

## Planned for the next update (1.2.0)

Collecting items; build them together.

### Filters on the side

Setting: **Filters: Top / Left / Right** (default Top). Prompted by a site that did it with custom CSS. Bar and cards are already siblings inside `.twk`, so it's mostly CSS: a wrapper class plus a grid.

- Drop back to Top below ~992px.
- Card columns drop by one in the narrower card area (Grid, Masonry, Front Page).
- Chips stack and wrap in the sidebar; search and sort go full width.
- Sticky sidebar with a settable top offset (T4's sticky header).
- Check every layout in the narrower area, especially Tide Line's spine and Contact Sheet.
- Check the Extras that measure the card area (wrasse lane, trotting Tancho) and Cast a line's scroll.
- README recipe for finer tuning through a CSS variable such as `--twk-side` (sidebar width).

### Image-only cards

Decided: a card setting that works with every layout, not a seventh layout. The card is just the picture and links to the article.

- **Title on hover/focus:** the title shows as a caption over the picture when the card is hovered or tabbed to (and stays visible on touch screens, which have no hover). It is always there for screen readers as the link's name.
- **Badges and marks: a setting.** Featured, Fresh catch and the Beachcombed mark can be shown on the picture or hidden, so the card can be purely the image.
- Articles with no picture: the generated wave art with the title over it, so the card isn't a blank tile.
- Filters, search and sort still work: tags and intro stay in the data attributes, they just aren't shown.
- Best fits: Grid, Masonry, Contact Sheet. Check how Tide Line and Magazine List look with it.
- Picture shape follows the layout's crop (square to 2:1); the cheat sheet's picture advice already covers this.

### Read it here (pop-up)

Setting **When a card is clicked: Go to the article / Open in a pop-up** (default: go to the article).

- Native `<dialog>`: the article title as its heading, a close button, Esc and backdrop click close it, focus goes back to the card.
- An **Open this article on its own page** link in the pop-up.
- Fetch the article when clicked, not up front (putting every full text in the page, hidden, is too heavy at 100 articles). Server side: run the text through `content.prepare` like a normal article view, with the same access checks as the board's query (state, dates, access, every parent category). Probably a `com_ajax` module call; to confirm when building.
- Pairs with image-only cards (picture wall → read in place).
- Works with Beachcombed marks (opening in the pop-up counts as read).

### Link to one article

Add the open article to the address-bar state, e.g. `#twk-12:open=345`, so a shared link opens the board with that article in the pop-up (or scrolls to and highlights its card when the pop-up is off). Closing the pop-up removes it. Reacts to `hashchange` as well as page load.

### Two more sorts

- **Oldest first** (`publish_up` ascending).
- **Article order**: the order editors set by dragging in Joomla's Articles list (`a.ordering`), so a board can be hand-ordered. Across several categories it sorts by category first; check that reads sensibly.
- Both in the admin default and the visitor sort menu.

### Errors to a log

If the query or rendering throws, visitors get nothing broken: the module shows a plain one-line message (or nothing; decide when building) and the details go to `mod_tidewrack.errors.php` in Joomla's log folder through `Log::addLogger`. Super Users could see a short "see the log" note.

### Table layout (to confirm)

A Table layout: rows of title, date, category, tags (and optional thumbnail). Would be a seventh layout, after image-only cards were deliberately kept out of the layout list, so confirm before building. Notes if built: real `<table>` with a caption and column headers, the reshuffle animation on rows, collapses to stacked rows on phones, Cards/List toggle still works.

## Known lessons

- Never name a module setting `style`: Joomla adds its own Module Style field (`params[style]`, Advanced tab) after the module's settings and it wins on save. That was the 1.0.0–1.0.2 "layout won't save" bug, fixed in 1.0.3 by renaming to `twk_layout` (the template still reads an old valid `style` value as a fallback). Joomla's Advanced tab names to avoid: `module_tag`, `bootstrap_size`, `header_tag`, `header_class`, `style`.
- Test a settings **save** in a real Joomla admin, not only the front end.
- Looping decorative motion must stop within 5 seconds or only run on hover/focus.
