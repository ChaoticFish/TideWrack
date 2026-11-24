# Tidewrack (mod_tidewrack): roadmap

Current release: **1.2.0** (2026-11-24). Plain Joomla 5/6 site module, no package wrapper. Element `mod_tidewrack`, client `site`, `twk-` prefix. Built with its own `build.py`.

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

## 1.2.0 (released 2026-11-24)

Works on the live site, and updated through System → Update → Extensions without a hitch.

Install zip 130 KB, SHA-256 `d6357cfdef7347562b5bb4fcd1690f3fa39387d5c7b0b36de20015136691425e`; the update-server XML is generated from that exact zip. Upload the zip to jDownloads 11 and the XML files to `/updates/` together.

- **Read it here (pop-up):** `click_action` page/popup. `TidewrackHelper::articleAjax()` through `com_ajax` (`module=tidewrack&method=article&format=json&mid=&id=&Itemid=`). Looks the module up by ID (published, site, viewer's access), runs the board's own `visibleQuery()` plus `a.id`, so it never shows more than the board. Text through `content.prepare` (context `mod_tidewrack.popup`); relative src/href/srcset made root-relative (`absoluteUrls()`). Full Article Image on top (Intro Image only if the text has no picture). Failures are returned as exceptions, not thrown (com_ajax doesn't catch module helper exceptions; a returned Throwable becomes JSON success false). Native `<dialog>` appended inside the module root so it inherits colours/theme; Prev/Next over the current filtered order (arrow keys), "3 of 12", focus returns to the card ended on; counts for Beachcombed; fetch cached per visit.
- **Link to one article:** `open=` in the hash; `hashchange` re-reads state. Pop-up off: brings the card into view with the Cast a line wiggle.
- **Table layout** (`twk_layout=table`): separate markup (`<table>`, row header `th scope=row`, `tbody` is `data-twk-items`, rows are `data-twk-card`). Columns follow date_type/show_category/show_author/show_readtime/show_tags; 56px thumbnail when Show Image. Article and Date headers sort with `aria-sort`. Phones: labelled blocks (`data-label`).
- **Picture-only cards** (`card_content=image`, `image_badges`): CSS-driven (`twk--imgonly`, `twk--nobadges`); markup still carries meta/intro/tags for search. Caption on hover/focus, always on `(hover: none)` and no-image cards. Beachcombed mark on the picture (`.twk-readmark--pic`). Not for Table.
- **Filters on the side** (`filter_position` top/left/right, `sticky_offset` default 16): from 992px the module is a grid, bar spans rows 1–4 with `grid-template-rows:auto auto auto 1fr` so its height goes to the last row; sticky with its own scroll; tide gauge (`data-twk-gauge`); chip groups get labels; cols 3→2, 4→3. `--twk-side` sets width (17rem).
- **Category and year chips** (`show_cats`, `show_years`, `year_date` published/created/modified): `data-twk-facet` on chips; OR within a row, AND across rows; live counts per facet. Rows only render with 2+ values.
- **Article order** sort (`order_asc`): server ranks by category `lft` then `a.ordering`, passed as `data-twk-ord`. **Featured First** (`featured_first`) in SQL and in the browser sort.
- **Errors:** Dispatcher catches, `TidewrackHelper::logError()` writes `mod_tidewrack.errors.php`; visitors see nothing, Super Users a one-line alert.
- **Install check** (the first CFD extension with it): `script.php` has `private $expected = array(/*EXPECTED*/)`; `build.py` fills it with SHA-256 (CRLF→LF) of every install file except script.php when writing the install zip only. Preflight refuses and names missing/changed files.
- Demo: carries a cards render and a table render, swaps them live (`window.TidewrackBoot()`), fakes the pop-up fetch from sample articles.
- Cheat sheet rebuilt for 1.2.0 (chips, pop-up, picture-only, article order); its assets now live in `cheatsheet/assets/`.
- Landing page: hero copy, four new feature cards, Table in Layouts, specs (weight ~9 KB JS + 7 KB CSS gzipped, zip ~130 KB, pop-up access, install check, errors), 1.2.0 changelog.
- Tested: Chromium (every layout × top/left sidebar × full/picture-only with all four Extras, no errors, no horizontal overflow), pop-up by mouse and keyboard, deep links, Ctrl-click, touch captions, light/dark; SQL against SQLite with Joomla's database package 3.x (Joomla 5) and 4.x (Joomla 6): access rules, all sorts, featured first, years by each date, pop-up access by module/article. Fishie's hand test (2026-11-24): good in Chromium browsers, works on the live site, and updated through Joomla's updater.

## Ideas not yet built

- None parked right now.

## Known lessons

- Never name a module setting `style`: Joomla adds its own Module Style field (`params[style]`, Advanced tab) after the module's settings and it wins on save. That was the 1.0.0–1.0.2 "layout won't save" bug, fixed in 1.0.3 by renaming to `twk_layout` (the template still reads an old valid `style` value as a fallback). Joomla's Advanced tab names to avoid: `module_tag`, `bootstrap_size`, `header_tag`, `header_class`, `style`.
- Test a settings **save** in a real Joomla admin, not only the front end.
- Looping decorative motion must stop within 5 seconds or only run on hover/focus.
