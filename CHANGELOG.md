# Changelog

All notable changes to Tidewrack (mod_tidewrack).

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
