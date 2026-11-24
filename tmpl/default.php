<?php

/**
 * @package     Tidewrack
 * @copyright   (C) 2026 Chaotic Fish Design Studio
 * @license     GNU General Public License version 2 or later
 *
 * @var  object                    $module
 * @var  \Joomla\Registry\Registry $params
 * @var  array                     $items
 * @var  array                     $tags
 * @var  string                    $media
 * @var  array                     $cats
 * @var  array                     $years
 * @var  string                    $ajax
 * @var  bool                      $failed
 */

use ChaoticFishDesign\Module\Tidewrack\Site\Helper\TidewrackHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$extras = (int) $params->get('extras', 1);
// Nothing matches: Tancho or Ziggy reports in (one at random; the script re-picks each time it appears).
$mascots = static function (array $lines) use ($e, $media): string {
    $pick = array_rand($lines);
    $html = '<div class="twk-empty twk-empty--mascot">';

    foreach ($lines as $who => [$lead, $text]) {
        $html .= '<div class="twk-mascot" data-twk-mascot="' . $who . '"' . ($who === $pick ? '' : ' hidden') . '>'
            . '<img src="' . $e($media) . 'images/' . $who . '-face.webp" alt="" width="120" height="120">'
            . '<p><strong>' . $lead . '</strong> ' . $text . '</p></div>';
    }

    return $html . '</div>';
};

if (!empty($failed)) {
    // Something broke loading the articles (details in mod_tidewrack.errors.php). Only Super Users are told.
    $viewer = Factory::getApplication()->getIdentity();

    if ($viewer && $viewer->authorise('core.admin')) {
        echo '<div class="twk twk--none"><p class="alert alert-warning">' . Text::_('MOD_TIDEWRACK_FAILED') . '</p></div>';
    }

    return;
}

if (!$items) {
    echo '<div class="twk twk--none">' . ($extras
        ? $mascots([
            'tancho' => [Text::_('MOD_TIDEWRACK_NO_ITEMS'), Text::_('MOD_TIDEWRACK_NO_ITEMS_TANCHO')],
            'ziggy'  => [Text::_('MOD_TIDEWRACK_NO_ITEMS'), Text::_('MOD_TIDEWRACK_NO_ITEMS_ZIGGY')],
        ])
        : '<p class="twk-empty">' . Text::_('MOD_TIDEWRACK_NO_ITEMS') . '</p>') . '</div>';

    return;
}

$uid        = 'twk-' . (int) $module->id;
// The setting is twk_layout: Joomla's own Module Style field (Advanced tab) is named "style" and overwrote it.
$layouts    = ['grid', 'frontpage', 'contactsheet', 'tideline', 'list', 'masonry', 'table'];
$style      = (string) $params->get('twk_layout', '');
$style      = in_array($style, $layouts, true) ? $style : (in_array($params->get('style'), $layouts, true) ? $params->get('style') : 'grid');
$isTable    = $style === 'table';
$imgOnly    = !$isTable && $params->get('card_content', 'full') === 'image';
$imgBadges  = !$imgOnly || (int) $params->get('image_badges', 1);
$popup      = $params->get('click_action', 'page') === 'popup';
$side       = in_array($params->get('filter_position', 'top'), ['left', 'right'], true) ? $params->get('filter_position') : '';
$cols       = max(2, min(4, (int) $params->get('columns', 3)));
$hLevel     = max(2, min(4, (int) $params->get('heading_level', 3)));
$filtersOn  = (int) $params->get('show_filters', 1);
$cats       = $filtersOn && isset($cats) && count($cats) > 1 ? $cats : [];
$years      = $filtersOn && isset($years) && count($years) > 1 ? $years : [];
$showFilter = $filtersOn && $tags;
$showSearch = $filtersOn && (int) $params->get('show_search', 1);
$showSort   = (int) $params->get('sort_visitor', 1);
$showView   = !in_array($style, ['list', 'table'], true) && (int) $params->get('view_toggle', 0);
$modeToggle = $showFilter && (int) $params->get('mode_toggle', 1);
$showCounts = (int) $params->get('show_counts', 1);
$hasChips   = $showFilter || $cats || $years;
$showClear  = ($hasChips || $showSearch) && (int) $params->get('show_clear', 1);
$tagMode    = $params->get('tag_mode', 'any') === 'all' ? 'all' : 'any';
$sort       = $params->get('sort_default', 'pub_desc');
$dateType   = $params->get('date_type', 'published');
$dateFmt    = $params->get('date_format', 'M j, Y') ?: 'M j, Y';
$showTags   = (int) $params->get('show_tags', 1);
$showRead   = (int) $params->get('show_readtime', 1);
$showAuthor = (int) $params->get('show_author', 0);
$showCat    = (int) $params->get('show_category', 0);
$listCols   = (int) $params->get('list_columns', 1) === 2 ? 2 : 1;
$art        = in_array($params->get('fallback_art', 'tide'), ['gradient', 'tide', 'shore'], true) ? $params->get('fallback_art', 'tide') : 'tide';
$freshDays  = max(0, (int) $params->get('fresh_days', 14));
$freshFrom  = $freshDays ? time() - $freshDays * 86400 : PHP_INT_MAX;
$freshText  = $e(Text::_('MOD_TIDEWRACK_FRESH_BADGE'));
$showCast   = (int) $params->get('cast_line', 1);
$readMarks  = (int) $params->get('read_marks', 1);
$staffBtn   = $extras && (int) $params->get('extras_button', 1);
$staffText  = trim((string) $params->get('extras_button_text', '')) !== '' ? $e(trim((string) $params->get('extras_button_text'))) : Text::_('MOD_TIDEWRACK_STAFF');
$showImg    = $imgOnly || (int) $params->get('show_image', 1);
$showBadge  = (int) $params->get('featured_badge', 1);
$badgeText  = $e(Text::_('MOD_TIDEWRACK_FEATURED_BADGE'));
$hasBar     = $hasChips || $showSearch || $showSort || $showView || $showCast || $readMarks || $staffBtn;
$hasEndRow  = $modeToggle || $showClear || $readMarks || $staffBtn;
$side       = $hasBar ? $side : '';
$groupCount = (int) (bool) $showFilter + (int) (bool) $cats + (int) (bool) $years;

// A sidebar takes a column's worth of room from the cards (from 992px; see the stylesheet).
$colsWide = $side ? max(2, $cols - 1) : $cols;
$sizes = [
    'grid'         => '(min-width: 992px) ' . floor(100 / $colsWide) . 'vw, (min-width: 576px) 50vw, 100vw',
    'masonry'      => '(min-width: 992px) ' . floor(100 / $colsWide) . 'vw, (min-width: 576px) 50vw, 100vw',
    'contactsheet' => '(min-width: 992px) ' . floor(100 / $colsWide) . 'vw, (min-width: 576px) 50vw, 100vw',
    'frontpage'    => '(min-width: 992px) ' . floor(100 / $colsWide) . 'vw, (min-width: 576px) 50vw, 100vw',
    'tideline'     => '(min-width: 768px) 45vw, 100vw',
    'list'         => $imgOnly ? '(min-width: 576px) 50vw, 100vw' : ($listCols === 2 ? '(min-width: 992px) 20vw, (min-width: 576px) 240px, 100vw' : '(min-width: 576px) 240px, 100vw'),
    'table'        => '96px',
][$style];

$strings = [
    'showing'     => Text::_('MOD_TIDEWRACK_JS_SHOWING'),
    'showing-one' => Text::_('MOD_TIDEWRACK_JS_SHOWING_ONE'),
    'none'        => Text::_('MOD_TIDEWRACK_JS_NONE'),
    'filter-on'   => Text::_('MOD_TIDEWRACK_JS_FILTER_ON'),
    'caught'      => Text::_('MOD_TIDEWRACK_JS_CAUGHT'),
    'gauge'       => Text::_('MOD_TIDEWRACK_GAUGE'),
];

if ($popup) {
    $strings += [
        'close'   => Text::_('MOD_TIDEWRACK_POP_CLOSE'),
        'prev'    => Text::_('MOD_TIDEWRACK_POP_PREV'),
        'next'    => Text::_('MOD_TIDEWRACK_POP_NEXT'),
        'page'    => Text::_('MOD_TIDEWRACK_POP_PAGE'),
        'loading' => Text::_('MOD_TIDEWRACK_POP_LOADING'),
        'failed'  => Text::_('MOD_TIDEWRACK_POP_FAILED'),
        'pos'     => Text::_('MOD_TIDEWRACK_POP_POS'),
    ];
}

$sortOptions = [
    'pub_desc'  => 'MOD_TIDEWRACK_SORT_PUB_DESC',
    'pub_asc'   => 'MOD_TIDEWRACK_SORT_PUB_ASC',
    'mod_desc'  => 'MOD_TIDEWRACK_SORT_MOD_DESC',
    'mod_asc'   => 'MOD_TIDEWRACK_SORT_MOD_ASC',
    'title_asc' => 'MOD_TIDEWRACK_SORT_TITLE_ASC',
    'order_asc' => 'MOD_TIDEWRACK_SORT_ORDER_ASC',
];

$classes = ['twk', 'twk--' . $style, 'twk--cols-' . $cols];
$classes[] = $listCols === 2 ? 'twk--lcols-2' : '';
$classes[] = $imgOnly ? 'twk--imgonly' : '';
$classes[] = $imgOnly && !$imgBadges ? 'twk--nobadges' : '';
$classes[] = $side ? 'twk--side twk--side-' . $side : '';
$classes[] = $groupCount > 1 ? 'twk--groups' : '';
$classes[] = $popup ? 'twk--popup' : '';
$classes[] = (string) $params->get('moduleclass_sfx', '');

/** One row of filter chips: tags, categories or years. */
$chipGroup = static function (string $facet, string $label, string $aria, array $list) use ($e, $uid, $showCounts): string {
    $html = '<div class="twk-group twk-group--' . $facet . '">'
        . '<p class="twk-group-label" id="' . $uid . '-g-' . $facet . '">' . $e($label) . '</p>'
        . '<div class="twk-chips" role="group" aria-label="' . $e($aria) . '">';

    foreach ($list as $t) {
        $html .= '<button type="button" class="twk-chip" data-twk-chip="' . $e($t->id) . '" data-twk-facet="' . $facet . '" aria-pressed="false">'
            . $e($t->title) . ($showCounts ? ' <span class="twk-count" data-twk-count>' . (int) $t->count . '</span>' : '') . '</button>';
    }

    return $html . '</div></div>';
};

/** The picture area: photo, generated art or gradient, with badges. */
$mediaHtml = static function ($item, int $i, bool $fresh, string $extraClass = '') use ($e, $art, $sizes, $showBadge, $badgeText, $freshText, $readMarks): string {
    $img  = $item->image;
    $html = '<div class="twk-media' . $extraClass . (!$img && $art !== 'gradient' ? ' twk-media--art' : '') . '" style="--twk-hue:' . (($item->id * 47) % 360) . '">';

    if ($showBadge && $item->featured) {
        $html .= '<span class="twk-badge">' . $badgeText . '</span>';
    }

    if ($fresh) {
        $html .= '<span class="twk-badge twk-badge--fresh">' . $freshText . '</span>';
    }

    if ($readMarks) {
        // Picture-only cards have no meta line, so the Beachcombed mark sits on the picture (hidden otherwise).
        $html .= '<span class="twk-readmark twk-readmark--pic" data-twk-readmark hidden>' . Text::_('MOD_TIDEWRACK_READMARK') . '</span>';
    }

    if ($img) {
        $html .= '<img src="' . $e($img->src) . '" alt="' . $e($img->alt) . '"'
            . ($img->width ? ' width="' . $img->width . '"' : '')
            . ($img->height ? ' height="' . $img->height . '"' : '')
            . ($img->srcset ? ' srcset="' . $e($img->srcset) . '" sizes="' . $e($sizes) . '"' : ' data-shrinkray-sizes="' . $e($sizes) . '"')
            . ' loading="' . ($i < 3 ? 'eager' : 'lazy') . '" decoding="async">';
    } elseif ($art !== 'gradient') {
        $html .= TidewrackHelper::art($art, $item->id);
    } else {
        $html .= '<span class="twk-media-fallback" aria-hidden="true"></span>';
    }

    return $html . '</div>';
};

$readmarkHtml = '<span class="twk-readmark" data-twk-readmark hidden><svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 2C4.5 2 2 5 2 8.5 2 11 3.6 13 5 14h6c1.4-1 3-3 3-5.5C14 5 11.5 2 8 2Zm0 0v12M5 4.2 6.4 14M11 4.2 9.6 14"/></svg>' . Text::_('MOD_TIDEWRACK_READMARK') . '</span>';

$tagList = static function ($item) use ($e, $showFilter): string {
    $html = '<ul class="twk-tags">';

    foreach ($item->tags as $t) {
        $html .= $showFilter
            ? '<li><button type="button" class="twk-tag" data-twk-tagbtn="' . (int) $t->id . '" aria-label="' . $e(Text::sprintf('MOD_TIDEWRACK_TAG_FILTER', $t->title)) . '">' . $e($t->title) . '</button></li>'
            : '<li><span class="twk-tag">' . $e($t->title) . '</span></li>';
    }

    return $html . '</ul>';
};

/** Data the script filters and sorts on, shared by cards and table rows. */
$cardData = static function ($item) use ($e): string {
    return ' data-twk-card="' . $item->id . '"'
        . ' data-twk-tags=" ' . implode(' ', array_map(static fn ($t) => (int) $t->id, $item->tags)) . ' "'
        . ' data-twk-cat="' . (int) $item->catid . '"'
        . ' data-twk-year="' . (int) $item->year . '"'
        . ' data-twk-ord="' . (int) $item->ord . '"'
        . ' data-twk-feat="' . ($item->featured ? 1 : 0) . '"'
        . ' data-twk-pub="' . $item->pubTs . '"'
        . ' data-twk-mod="' . $item->modTs . '"'
        . ' data-twk-title="' . $e(mb_strtolower($item->title)) . '"'
        . ' data-twk-text="' . $e(mb_strtolower(mb_substr($item->search, 0, 1500))) . '"';
};
?>
<div class="<?php echo $e(trim(implode(' ', array_filter($classes)))); ?>"
    id="<?php echo $uid; ?>"
<?php if ($side) : ?>
    style="--twk-sticky-top:<?php echo max(0, (int) $params->get('sticky_offset', 16)); ?>px"
<?php endif; ?>
    data-twk
    data-twk-style="<?php echo $style; ?>"
    data-twk-initial="<?php echo (int) $params->get('initial_count', 9); ?>"
    data-twk-step="<?php echo max(1, (int) $params->get('more_step', 6)); ?>"
    data-twk-sort="<?php echo $e($sort); ?>"
    data-twk-mode="<?php echo $tagMode; ?>"
    data-twk-url="<?php echo (int) $params->get('url_state', 1); ?>"
    data-twk-extras="<?php echo $extras; ?>"
    data-twk-readmarks="<?php echo $readMarks; ?>"
    data-twk-featfirst="<?php echo (int) $params->get('featured_first', 0); ?>"
    data-twk-datesort="<?php echo $dateType === 'modified' ? 'mod' : 'pub'; ?>"
<?php if ($popup) : ?>
    data-twk-popup="<?php echo $e($ajax ?? ''); ?>"
<?php endif; ?>
    data-twk-media="<?php echo $e($media); ?>"
<?php foreach ($strings as $k => $v) : ?>
    data-twk-s-<?php echo $k; ?>="<?php echo $e($v); ?>"
<?php endforeach; ?>
>
<?php if ($hasBar) : ?>
    <div class="twk-bar" role="region" aria-labelledby="<?php echo $uid; ?>-bar" hidden>
        <div class="twk-bar-head">
            <p class="twk-bar-title" id="<?php echo $uid; ?>-bar"><?php echo Text::_('MOD_TIDEWRACK_BAR_LABEL'); ?></p>
            <div class="twk-gauge" aria-hidden="true"><span class="twk-gauge-text" data-twk-gauge-text></span><span class="twk-gauge-tank"><span class="twk-gauge-water" data-twk-gauge></span></span></div>
        </div>
        <div class="twk-bar-row">
<?php if ($showSearch) : ?>
            <div class="twk-search">
                <label class="visually-hidden" for="<?php echo $uid; ?>-q"><?php echo Text::_('MOD_TIDEWRACK_SEARCH_PLACEHOLDER'); ?></label>
                <input class="form-control form-control-sm" type="search" id="<?php echo $uid; ?>-q" data-twk-q placeholder="<?php echo $e(Text::_('MOD_TIDEWRACK_SEARCH_PLACEHOLDER')); ?>" autocomplete="off">
            </div>
<?php endif; ?>
<?php if ($showSort) : ?>
            <div class="twk-sort">
                <label class="visually-hidden" for="<?php echo $uid; ?>-sort"><?php echo Text::_('MOD_TIDEWRACK_SORT_LABEL'); ?></label>
                <select class="form-select form-select-sm" id="<?php echo $uid; ?>-sort" data-twk-sortsel>
<?php foreach ($sortOptions as $val => $key) : ?>
                    <option value="<?php echo $val; ?>"<?php echo $val === $sort ? ' selected' : ''; ?>><?php echo Text::_($key); ?></option>
<?php endforeach; ?>
                </select>
            </div>
<?php endif; ?>
<?php if ($showView) : ?>
            <div class="twk-view btn-group btn-group-sm" role="group" aria-label="<?php echo $e(Text::_('MOD_TIDEWRACK_VIEW_LABEL')); ?>">
                <button type="button" class="btn btn-outline-secondary" data-twk-view="<?php echo $style; ?>" aria-pressed="true"><?php echo Text::_('MOD_TIDEWRACK_VIEW_CARDS'); ?></button>
                <button type="button" class="btn btn-outline-secondary" data-twk-view="list" aria-pressed="false"><?php echo Text::_('MOD_TIDEWRACK_VIEW_LIST'); ?></button>
            </div>
<?php endif; ?>
<?php if ($showCast) : ?>
            <button type="button" class="btn btn-sm btn-outline-secondary twk-cast" data-twk-cast><svg class="twk-cast-hook" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M9 1v8.5a3.5 3.5 0 1 1-7 0V8l2 2"/><circle cx="9" cy="1.6" r="1.1"/></svg><?php echo Text::_('MOD_TIDEWRACK_CAST'); ?></button>
<?php endif; ?>
        </div>
<?php if ($showFilter) : ?>
        <?php echo $chipGroup('tag', Text::_('MOD_TIDEWRACK_GROUP_TAGS'), Text::_('MOD_TIDEWRACK_FILTER_LABEL'), $tags); ?>

<?php endif; ?>
<?php if ($cats) : ?>
        <?php echo $chipGroup('cat', Text::_('MOD_TIDEWRACK_GROUP_CATS'), Text::_('MOD_TIDEWRACK_FILTER_CATS'), $cats); ?>

<?php endif; ?>
<?php if ($years) : ?>
        <?php echo $chipGroup('year', Text::_('MOD_TIDEWRACK_GROUP_YEARS'), Text::_('MOD_TIDEWRACK_FILTER_YEARS'), $years); ?>

<?php endif; ?>
<?php if ($hasEndRow) : ?>
        <div class="twk-bar-row twk-bar-row--end">
<?php if ($modeToggle) : ?>
            <div class="twk-mode btn-group btn-group-sm" role="group" aria-label="<?php echo $e(Text::_('MOD_TIDEWRACK_TAGMODE_LABEL')); ?>">
                <button type="button" class="btn btn-outline-secondary" data-twk-modebtn="any" aria-pressed="<?php echo $tagMode === 'any' ? 'true' : 'false'; ?>"><?php echo Text::_('MOD_TIDEWRACK_TAGMODE_ANY_SHORT'); ?></button>
                <button type="button" class="btn btn-outline-secondary" data-twk-modebtn="all" aria-pressed="<?php echo $tagMode === 'all' ? 'true' : 'false'; ?>"><?php echo Text::_('MOD_TIDEWRACK_TAGMODE_ALL_SHORT'); ?></button>
            </div>
<?php endif; ?>
<?php if ($showClear) : ?>
            <button type="button" class="btn btn-sm btn-link twk-clear" data-twk-clear disabled><?php echo Text::_('MOD_TIDEWRACK_CLEAR'); ?></button>
<?php endif; ?>
<?php if ($staffBtn) : ?>
            <button type="button" class="btn btn-sm btn-link twk-staff" data-twk-staff><svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 1.5 9.4 6.6 14.5 8 9.4 9.4 8 14.5 6.6 9.4 1.5 8 6.6 6.6Z"/></svg><?php echo $staffText; ?></button>
<?php endif; ?>
<?php if ($readMarks) : ?>
            <button type="button" class="btn btn-sm btn-link twk-forget" data-twk-forget hidden><?php echo Text::_('MOD_TIDEWRACK_FORGET'); ?><span class="visually-hidden"> <?php echo Text::_('MOD_TIDEWRACK_FORGET_SR'); ?></span></button>
<?php endif; ?>
        </div>
<?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isTable) : ?>
    <div class="twk-table-wrap">
        <table class="twk-table">
            <caption class="visually-hidden"><?php echo Text::_('MOD_TIDEWRACK_TABLE_CAPTION'); ?></caption>
            <thead>
                <tr>
                    <th scope="col" data-twk-th="title"><button type="button" class="twk-th-sort" data-twk-thsort="title"><?php echo Text::_('MOD_TIDEWRACK_TH_TITLE'); ?><svg viewBox="0 0 10 12" aria-hidden="true" focusable="false"><path d="M5 1 8.5 5h-7Zm0 10L1.5 7h7Z"/></svg></button></th>
<?php if ($dateType !== 'none') : ?>
                    <th scope="col" data-twk-th="date"><button type="button" class="twk-th-sort" data-twk-thsort="date"><?php echo Text::_('MOD_TIDEWRACK_TH_DATE'); ?><svg viewBox="0 0 10 12" aria-hidden="true" focusable="false"><path d="M5 1 8.5 5h-7Zm0 10L1.5 7h7Z"/></svg></button></th>
<?php endif; ?>
<?php if ($showCat) : ?>
                    <th scope="col"><?php echo Text::_('MOD_TIDEWRACK_TH_CATEGORY'); ?></th>
<?php endif; ?>
<?php if ($showAuthor) : ?>
                    <th scope="col"><?php echo Text::_('MOD_TIDEWRACK_TH_AUTHOR'); ?></th>
<?php endif; ?>
<?php if ($showRead) : ?>
                    <th scope="col"><?php echo Text::_('MOD_TIDEWRACK_TH_READ'); ?></th>
<?php endif; ?>
<?php if ($showTags) : ?>
                    <th scope="col"><?php echo Text::_('MOD_TIDEWRACK_TH_TAGS'); ?></th>
<?php endif; ?>
                </tr>
            </thead>
            <tbody class="twk-items" data-twk-items>
<?php foreach ($items as $i => $item) :
    $date  = $dateType === 'modified' ? $item->mod : $item->pub;
    $fresh = $item->pubTs >= $freshFrom;
    ?>
                <tr class="twk-card twk-row<?php echo $item->featured ? ' twk-card--featured' : ''; ?><?php echo $fresh ? ' twk-card--fresh' : ''; ?>"<?php echo $cardData($item); ?>>
                    <th scope="row" class="twk-cell-title">
                        <div class="twk-row-head">
<?php if ($showImg) : ?>
                            <?php echo $mediaHtml((object) (['featured' => false] + (array) $item), $i, false, ' twk-thumb'); ?>

<?php endif; ?>
                            <div class="twk-row-text">
                                <a class="twk-link stretched-link" href="<?php echo $e($item->link); ?>"><?php echo $e($item->title); ?></a>
<?php if (($showBadge && $item->featured) || $fresh || $readMarks) : ?>
                                <span class="twk-badges">
<?php if ($showBadge && $item->featured) : ?>
                                    <span class="twk-badge twk-badge--inline"><?php echo $badgeText; ?></span>
<?php endif; ?>
<?php if ($fresh) : ?>
                                    <span class="twk-badge twk-badge--inline twk-badge--fresh"><?php echo $freshText; ?></span>
<?php endif; ?>
<?php if ($readMarks) : ?>
                                    <?php echo $readmarkHtml; ?>

<?php endif; ?>
                                </span>
<?php endif; ?>
                            </div>
                        </div>
                    </th>
<?php if ($dateType !== 'none') : ?>
                    <td class="twk-cell-date" data-label="<?php echo $e(Text::_('MOD_TIDEWRACK_TH_DATE')); ?>"><time class="twk-date" datetime="<?php echo $e(HTMLHelper::date($date, 'c')); ?>"><?php echo $e(HTMLHelper::date($date, $dateFmt)); ?></time></td>
<?php endif; ?>
<?php if ($showCat) : ?>
                    <td class="twk-cell-cat" data-label="<?php echo $e(Text::_('MOD_TIDEWRACK_TH_CATEGORY')); ?>"><?php echo $e($item->category); ?></td>
<?php endif; ?>
<?php if ($showAuthor) : ?>
                    <td class="twk-cell-author" data-label="<?php echo $e(Text::_('MOD_TIDEWRACK_TH_AUTHOR')); ?>"><?php echo $e($item->author); ?></td>
<?php endif; ?>
<?php if ($showRead) : ?>
                    <td class="twk-cell-read" data-label="<?php echo $e(Text::_('MOD_TIDEWRACK_TH_READ')); ?>"><?php echo Text::sprintf('MOD_TIDEWRACK_READTIME', $item->readtime); ?></td>
<?php endif; ?>
<?php if ($showTags) : ?>
                    <td class="twk-cell-tags" data-label="<?php echo $e(Text::_('MOD_TIDEWRACK_TH_TAGS')); ?>"><?php echo $item->tags ? $tagList($item) : ''; ?></td>
<?php endif; ?>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else : ?>
    <div class="twk-items" data-twk-items>
<?php foreach ($items as $i => $item) :
    $date  = $dateType === 'modified' ? $item->mod : $item->pub;
    $fresh = $item->pubTs >= $freshFrom;
    ?>
        <article class="twk-card<?php echo $item->featured ? ' twk-card--featured' : ''; ?><?php echo $showImg && !$item->image ? ' twk-card--noimg' : ''; ?><?php echo $showImg ? '' : ' twk-card--textonly'; ?><?php echo $fresh ? ' twk-card--fresh' : ''; ?>"<?php echo $cardData($item); ?>>
<?php if ($showImg) : ?>
            <?php echo $mediaHtml($item, $i, $fresh); ?>

<?php endif; ?>
            <div class="twk-body">
<?php if (($showBadge && $item->featured || $fresh) && !$showImg) : ?>
                <p class="twk-badges">
<?php if ($showBadge && $item->featured) : ?>
                    <span class="twk-badge twk-badge--inline"><?php echo $badgeText; ?></span>
<?php endif; ?>
<?php if ($fresh) : ?>
                    <span class="twk-badge twk-badge--inline twk-badge--fresh"><?php echo $freshText; ?></span>
<?php endif; ?>
                </p>
<?php endif; ?>
<?php if ($dateType !== 'none' || $showRead || $showAuthor || $showCat || $readMarks) : ?>
                <p class="twk-meta">
<?php if ($dateType !== 'none') : ?>
                    <time class="twk-date" datetime="<?php echo $e(HTMLHelper::date($date, 'c')); ?>"><?php echo $e(HTMLHelper::date($date, $dateFmt)); ?></time>
<?php endif; ?>
<?php if ($showRead) : ?>
                    <span class="twk-read"><?php echo Text::sprintf('MOD_TIDEWRACK_READTIME', $item->readtime); ?></span>
<?php endif; ?>
<?php if ($showAuthor && $item->author) : ?>
                    <span class="twk-author"><?php echo $e($item->author); ?></span>
<?php endif; ?>
<?php if ($showCat) : ?>
                    <span class="twk-cat"><?php echo $e($item->category); ?></span>
<?php endif; ?>
<?php if ($readMarks) : ?>
                    <?php echo $readmarkHtml; ?>

<?php endif; ?>
                </p>
<?php endif; ?>
                <h<?php echo $hLevel; ?> class="twk-title"><a class="twk-link stretched-link" href="<?php echo $e($item->link); ?>"><?php echo $e($item->title); ?></a></h<?php echo $hLevel; ?>>
<?php if ($item->intro !== '') : ?>
                <div class="twk-intro"><?php echo $item->intro; ?></div>
<?php endif; ?>
<?php if ($showTags && $item->tags) : ?>
                <?php echo $tagList($item); ?>

<?php endif; ?>
            </div>
        </article>
<?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($extras) : ?>
    <div class="twk-empty-wrap" data-twk-empty hidden><?php echo $mascots([
        'tancho' => [Text::_('MOD_TIDEWRACK_NONE_LEAD'), Text::_('MOD_TIDEWRACK_NONE_TANCHO')],
        'ziggy'  => [Text::_('MOD_TIDEWRACK_NONE_ZIGGY_LEAD'), Text::_('MOD_TIDEWRACK_NONE_ZIGGY')],
    ]); ?></div>
<?php else : ?>
    <p class="twk-empty" data-twk-empty hidden><?php echo Text::_('MOD_TIDEWRACK_JS_NONE'); ?></p>
<?php endif; ?>
    <div class="twk-more-wrap">
        <button type="button" class="btn btn-outline-primary twk-more" data-twk-more hidden><?php echo Text::_('MOD_TIDEWRACK_JS_MORE'); ?></button>
    </div>
    <p class="visually-hidden" aria-live="polite" data-twk-status></p>
</div>
