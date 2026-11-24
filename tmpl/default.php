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
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

if (!$items) {
    echo '<div class="twk twk--empty"><p class="twk-empty">' . Text::_('MOD_TIDEWRACK_NO_ITEMS') . '</p></div>';

    return;
}

$uid        = 'twk-' . (int) $module->id;
// The setting is twk_layout: Joomla's own Module Style field (Advanced tab) is named "style" and overwrote it.
$layouts    = ['grid', 'frontpage', 'contactsheet', 'tideline', 'list', 'masonry'];
$style      = (string) $params->get('twk_layout', '');
$style      = in_array($style, $layouts, true) ? $style : (in_array($params->get('style'), $layouts, true) ? $params->get('style') : 'grid');
$cols       = max(2, min(4, (int) $params->get('columns', 3)));
$hLevel     = max(2, min(4, (int) $params->get('heading_level', 3)));
$showFilter = (int) $params->get('show_filters', 1) && $tags;
$showSearch = (int) $params->get('show_filters', 1) && (int) $params->get('show_search', 1);
$showSort   = (int) $params->get('sort_visitor', 1);
$showView   = $style !== 'list' && (int) $params->get('view_toggle', 0);
$modeToggle = $showFilter && (int) $params->get('mode_toggle', 1);
$showCounts = (int) $params->get('show_counts', 1);
$showClear  = ($showFilter || $showSearch) && (int) $params->get('show_clear', 1);
$tagMode    = $params->get('tag_mode', 'any') === 'all' ? 'all' : 'any';
$sort       = $params->get('sort_default', 'pub_desc');
$dateType   = $params->get('date_type', 'published');
$dateFmt    = $params->get('date_format', 'M j, Y') ?: 'M j, Y';
$showTags   = (int) $params->get('show_tags', 1);
$showRead   = (int) $params->get('show_readtime', 1);
$showAuthor = (int) $params->get('show_author', 0);
$showCat    = (int) $params->get('show_category', 0);
$extras     = (int) $params->get('extras', 1);
$showImg    = (int) $params->get('show_image', 1);
$showBadge  = (int) $params->get('featured_badge', 1);
$badgeText  = $e(Text::_('MOD_TIDEWRACK_FEATURED_BADGE'));
$hasBar     = $showFilter || $showSearch || $showSort || $showView;

$sizes = [
    'grid'         => '(min-width: 992px) ' . floor(100 / $cols) . 'vw, (min-width: 576px) 50vw, 100vw',
    'masonry'      => '(min-width: 992px) ' . floor(100 / $cols) . 'vw, (min-width: 576px) 50vw, 100vw',
    'contactsheet' => '(min-width: 992px) ' . floor(100 / $cols) . 'vw, (min-width: 576px) 50vw, 100vw',
    'frontpage'    => '(min-width: 992px) ' . floor(100 / $cols) . 'vw, (min-width: 576px) 50vw, 100vw',
    'tideline'     => '(min-width: 768px) 45vw, 100vw',
    'list'         => '(min-width: 576px) 240px, 100vw',
][$style];

$strings = [
    'showing'   => Text::_('MOD_TIDEWRACK_JS_SHOWING'),
    'showing-one' => Text::_('MOD_TIDEWRACK_JS_SHOWING_ONE'),
    'none'      => Text::_('MOD_TIDEWRACK_JS_NONE'),
    'filter-on' => Text::_('MOD_TIDEWRACK_JS_FILTER_ON'),
];

$sortOptions = [
    'pub_desc'  => 'MOD_TIDEWRACK_SORT_PUB_DESC',
    'pub_asc'   => 'MOD_TIDEWRACK_SORT_PUB_ASC',
    'mod_desc'  => 'MOD_TIDEWRACK_SORT_MOD_DESC',
    'mod_asc'   => 'MOD_TIDEWRACK_SORT_MOD_ASC',
    'title_asc' => 'MOD_TIDEWRACK_SORT_TITLE_ASC',
];
?>
<div class="twk twk--<?php echo $style; ?> twk--cols-<?php echo $cols; ?><?php echo $e($params->get('moduleclass_sfx', '') ? ' ' . $params->get('moduleclass_sfx') : ''); ?>"
    id="<?php echo $uid; ?>"
    data-twk
    data-twk-style="<?php echo $style; ?>"
    data-twk-initial="<?php echo (int) $params->get('initial_count', 9); ?>"
    data-twk-step="<?php echo max(1, (int) $params->get('more_step', 6)); ?>"
    data-twk-sort="<?php echo $e($sort); ?>"
    data-twk-mode="<?php echo $tagMode; ?>"
    data-twk-url="<?php echo (int) $params->get('url_state', 1); ?>"
    data-twk-extras="<?php echo $extras; ?>"
    data-twk-media="<?php echo $e($media); ?>"
<?php foreach ($strings as $k => $v) : ?>
    data-twk-s-<?php echo $k; ?>="<?php echo $e($v); ?>"
<?php endforeach; ?>
>
<?php if ($hasBar) : ?>
    <div class="twk-bar" hidden>
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
        </div>
<?php if ($showFilter) : ?>
        <div class="twk-chips" role="group" aria-label="<?php echo $e(Text::_('MOD_TIDEWRACK_FILTER_LABEL')); ?>">
<?php foreach ($tags as $t) : ?>
            <button type="button" class="twk-chip" data-twk-chip="<?php echo (int) $t->id; ?>" aria-pressed="false"><?php echo $e($t->title); ?><?php if ($showCounts) : ?> <span class="twk-count" data-twk-count><?php echo (int) $t->count; ?></span><?php endif; ?></button>
<?php endforeach; ?>
        </div>
<?php endif; ?>
<?php if ($modeToggle || $showClear) : ?>
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
        </div>
<?php endif; ?>
    </div>
<?php endif; ?>

    <div class="twk-items" data-twk-items>
<?php foreach ($items as $i => $item) :
    $tagIds = array_map(static fn ($t) => (int) $t->id, $item->tags);
    $date   = $dateType === 'modified' ? $item->mod : $item->pub;
    $img    = $item->image;
    $hue    = ($item->id * 47) % 360;
    ?>
        <article class="twk-card<?php echo $item->featured ? ' twk-card--featured' : ''; ?><?php echo $showImg && !$img ? ' twk-card--noimg' : ''; ?><?php echo $showImg ? '' : ' twk-card--textonly'; ?>"
            data-twk-card="<?php echo $item->id; ?>"
            data-twk-tags=" <?php echo implode(' ', $tagIds); ?> "
            data-twk-pub="<?php echo $item->pubTs; ?>"
            data-twk-mod="<?php echo $item->modTs; ?>"
            data-twk-title="<?php echo $e(mb_strtolower($item->title)); ?>"
            data-twk-text="<?php echo $e(mb_strtolower(mb_substr($item->search, 0, 1500))); ?>">
<?php if ($showImg) : ?>
            <div class="twk-media" style="--twk-hue:<?php echo $hue; ?>">
<?php if ($showBadge && $item->featured) : ?>
                <span class="twk-badge"><?php echo $badgeText; ?></span>
<?php endif; ?>
<?php if ($img) : ?>
                <img src="<?php echo $e($img->src); ?>" alt="<?php echo $e($img->alt); ?>"<?php
                    echo $img->width ? ' width="' . $img->width . '"' : '';
                    echo $img->height ? ' height="' . $img->height . '"' : '';
                    echo $img->srcset ? ' srcset="' . $e($img->srcset) . '" sizes="' . $e($sizes) . '"' : ' data-shrinkray-sizes="' . $e($sizes) . '"';
                ?> loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>" decoding="async">
<?php else : ?>
                <span class="twk-media-fallback" aria-hidden="true"></span>
<?php endif; ?>
            </div>
<?php endif; ?>
            <div class="twk-body">
<?php if ($showBadge && $item->featured && !$showImg) : ?>
                <span class="twk-badge twk-badge--inline"><?php echo $badgeText; ?></span>
<?php endif; ?>
<?php if ($dateType !== 'none' || $showRead || $showAuthor || $showCat) : ?>
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
                </p>
<?php endif; ?>
                <h<?php echo $hLevel; ?> class="twk-title"><a class="twk-link stretched-link" href="<?php echo $e($item->link); ?>"><?php echo $e($item->title); ?></a></h<?php echo $hLevel; ?>>
<?php if ($item->intro !== '') : ?>
                <div class="twk-intro"><?php echo $item->intro; ?></div>
<?php endif; ?>
<?php if ($showTags && $item->tags) : ?>
                <ul class="twk-tags">
<?php foreach ($item->tags as $t) : ?>
<?php if ($showFilter) : ?>
                    <li><button type="button" class="twk-tag" data-twk-tagbtn="<?php echo (int) $t->id; ?>" aria-label="<?php echo $e(Text::sprintf('MOD_TIDEWRACK_TAG_FILTER', $t->title)); ?>"><?php echo $e($t->title); ?></button></li>
<?php else : ?>
                    <li><span class="twk-tag"><?php echo $e($t->title); ?></span></li>
<?php endif; ?>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </div>
        </article>
<?php endforeach; ?>
    </div>

    <p class="twk-empty" data-twk-empty hidden><?php echo Text::_('MOD_TIDEWRACK_JS_NONE'); ?></p>
    <div class="twk-more-wrap">
        <button type="button" class="btn btn-outline-primary twk-more" data-twk-more hidden><?php echo Text::_('MOD_TIDEWRACK_JS_MORE'); ?></button>
    </div>
    <p class="visually-hidden" aria-live="polite" data-twk-status></p>
</div>
