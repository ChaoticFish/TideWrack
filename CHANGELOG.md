# Changelog

All notable changes to Tidewrack (mod_tidewrack).

## 1.1.1 — 2026-11-10

### Changed
- When nothing matches, a round photo of **Tancho** ("Found mud.") or **Ziggy** in his pirate hat ("No treasure here.") reports in, picked at random each time, in place of the drawn Tancho. Same for an empty category. Both portraits are small WebPs (8 and 10 KB) that only load when shown.

## 1.1.0 — 2026-11-10

### Added
- **No-image art:** articles with no image get a generated picture instead of a plain wash: *Tide* (layered waves that drift while you hover the card) or *Shoreline* (sun, hills and birds). Shapes and colours vary by article and stay the same for each. *Gradient* keeps the old look. Default: Tide.
- **Fresh catch badge** on articles published within a set number of days (default 14; 0 turns it off). A grease-pencil star in Contact Sheet.
- **Cast a line:** a button that picks a random article from the current filters, brings it into view, gives it a wiggle and announces it.
- **Bring in the next tide:** Show More is renamed, a wave rolls across the button, and new cards rise in one after another.
- **Beachcombed marks:** articles a visitor has opened get a small mark, stored only in their browser; *Smooth the sand* clears them.
- **Magazine List columns:** one or two (two drop back to one below 992px).
- **Tancho reports in** when nothing matches the filters ("Found mud."), and when a category is empty. Part of Extras.
- **Extras:** Hawke walks onto a card and eats a tag chip (it grows back); Tancho trots down the Tide Line spine, or along the top of the cards in other layouts. Type `hawke` or `tancho`.
- **Staff button:** a visible button, labelled *Do not press* by default (the wording is a setting), that sends a random member of the staff through.

### Changed
- `build.py` renders the demo from the real template (`demo/render.php`, `demo/shell.html`, `demo/sample.json`) and uses terser and csso when installed, like Wayfin's build.

## 1.0.3 — 2026-11-10

### Fixed
- **Layout wouldn't save.** The Layout setting was named `style`, the same name as Joomla's own Module Style field on the Advanced tab, which Joomla adds to every module after the module's settings. On save Joomla's value won, so every layout fell back to Grid. The setting is now `twk_layout`. After updating, open the module, pick the layout again and save; the Module Style field is Joomla's again and works normally.

## 1.0.2 — 2026-11-10

### Added
- Install checks: Tidewrack refuses PHP below 8.2 and Joomla below 5, with a plain message, before anything is installed.
- CFD update server: updates arrive through Joomla's updater.
- `LICENSE.txt` (GPL v2), which the manifest already pointed to.
- `build.py`: version check, CSS order check, minify, demo refresh, install and source zips, update-server release.
- High Contrast and Reading Overlay support for A11y Toolbar: pressed chips, tags, buttons and the Featured badge stay visible, and Contact Sheet stays readable under the overlay.

### Changed
- Contact Sheet's handwriting uses Caveat when the site loads it, with sizes evened out across fallback fonts. Override with `--twk-scrawl`.
- Dimmed tag chips get a dashed border as well as fading.
- Kokido is a WebP now (15 KB instead of 72 KB).
- The demo page ships beside the install zip instead of inside it.
- Docs say Joomla 5 and 6, PHP 8.2+.

### Fixed
- ShrinkRay `srcset` now follows ShrinkRay's real layout (`images/_sized/<path inside images>/name--333.webp`), so images in subfolders get their copies. Copies are found by listing the folder, so the widths no longer need to be typed in; the width list is now an optional filter. Sites upgrading from 1.0.1 keep their saved `333,666,1000`, which still works; clear it to use every copy.

## 1.0.1 — 2026-10-09

### Fixed
- Intro text sits above the card's click area, so point-and-read tools (A11y Toolbar's Read Aloud) read it instead of only the link, and the text can be selected. Clicking the intro no longer opens the article; the photo, title and meta still do.
- Active Any/All and Cards/List buttons now always use white text, even when a template restyles Bootstrap's outline buttons.

## 1.0.0 — 2026-10-09

Stable.

### Added
- **Live tag counts:** each chip shows how many articles it would show with the current search and picked tags; chips that would show nothing are dimmed and can't be picked.
- **Featured badge** on featured articles, with a grease-pencil version in Contact Sheet. On by default.
- <kbd>Esc</kbd> clears the search box.
- Minified script and stylesheet; readable sources ship alongside.

### Changed
- **Masonry now reads across the rows** instead of down each column, while keeping natural card heights. It falls back to CSS columns without JavaScript.

## 0.1.1 — 2026-10-09

### Changed
- The wrasse now swims behind the page: he shows only through the card photos in his lane and disappears behind margins and text. Falls back to a free swim when images are off.
- Kokido sits 15% lower when he peeks, so the angled bottom of the cutout stays hidden below the photo edge.

### Fixed
- Live status said "Showing 1 of 1 articles"; now "Showing 1 of 1 article".

### Docs
- Masonry's down-the-column reading order is called out in the README, the layouts table and the demo page.

## 0.1.0 — 2026-10-09

First build.

### Added
- Articles from multiple categories, with optional subcategories, featured filter, language matching and a 1–100 cap.
- Access control in SQL: article state, publish window, article access, and published state + access of the category and every ancestor category. No module caching option.
- Admin tag whitelist for filter chips and card tags.
- Visitor tag filtering (Any/All), search, sort menu, clear button and Show More.
- Default sort by published (`publish_up`), modified, or title; visitor sort menu optional.
- Shareable filter state in the URL hash via `history.replaceState`.
- Six layouts: Grid, Front Page, Contact Sheet, Tide Line, Magazine List, Masonry; optional visitor Cards/List toggle.
- Animated reshuffle on filter and sort changes, disabled under reduced motion.
- Intro text cleaning: keeps bold, italic and line breaks; strips links, attributes and `{plugin tags}`; cuts at a word with an ellipsis and balanced tags.
- Image fallback chain: intro → full text → first body image → gradient.
- ShrinkRay `srcset` when `--<width>.webp` copies exist.
- Reading time, date, author and category meta.
- Extras: the cleaner wrasse (`fishie` / `claude`) and Kokido (`kokido`), plus a long-press trigger for touch. Admin switch to turn them off.
- Standalone demo page.
