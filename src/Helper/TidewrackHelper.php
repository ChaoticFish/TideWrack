<?php

/**
 * @package     Tidewrack
 * @copyright   (C) 2026 Chaotic Fish Design Studio
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFishDesign\Module\Tidewrack\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

final class TidewrackHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /** Directory listings of ShrinkRay output folders, keyed by folder. */
    private array $copyCache = [];

    /** Tags kept in intro text. <b> and <i> are normalised to <strong> and <em>. */
    private const KEEP = ['strong', 'em', 'br'];

    /**
     * Load the articles this visitor is allowed to see.
     *
     * Access control is enforced in SQL, never in the template:
     * article state, publish window, article access level, and the published
     * state + access level of the article's category AND every ancestor category.
     */
    public function getItems(Registry $params, CMSApplicationInterface $app): array
    {
        $catIds = array_values(array_filter(array_map('intval', (array) $params->get('catid', []))));

        if (!$catIds) {
            return [];
        }

        $db     = $this->getDatabase();
        $user   = $app->getIdentity();
        $levels = $user ? array_values(array_unique(array_map('intval', $user->getAuthorisedViewLevels()))) : [1];
        $levels = $levels ?: [1];
        $now    = Factory::getDate()->toSql();
        $limit  = max(1, min(100, (int) $params->get('max_items', 60)));

        $query = $this->newQuery()
            ->select([
                'a.id', 'a.title', 'a.alias', 'a.catid', 'a.introtext', 'a.fulltext', 'a.images',
                'a.created', 'a.created_by_alias', 'a.featured', 'a.language',
                'COALESCE(a.publish_up, a.created) AS pub_date',
                'COALESCE(a.modified, a.created) AS mod_date',
                $db->quoteName('c.title', 'category_title'),
                $db->quoteName('u.name', 'author_name'),
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'c'), 'c.id = a.catid')
            ->join('LEFT', $db->quoteName('#__users', 'u'), 'u.id = a.created_by')
            ->where('a.state = 1')
            ->whereIn($db->quoteName('a.access'), $levels)
            ->where('c.published = 1')
            ->whereIn($db->quoteName('c.access'), $levels)
            ->where('(a.publish_up IS NULL OR a.publish_up <= :now1)')
            ->where('(a.publish_down IS NULL OR a.publish_down > :now2)')
            ->bind(':now1', $now)
            ->bind(':now2', $now);

        // Every ancestor category must also be published and visible to this visitor.
        $ancestors = $this->newQuery()
            ->select('1')
            ->from($db->quoteName('#__categories', 'p'))
            ->where('p.lft < c.lft')
            ->where('p.rgt > c.rgt')
            ->where('p.level > 0')
            ->where($db->quoteName('p.extension') . ' = ' . $db->quote('com_content'))
            ->where('(p.published <> 1 OR p.access NOT IN (' . implode(',', $levels) . '))');
        $query->where('NOT EXISTS (' . $ancestors . ')');

        // Category scope, optionally with every descendant.
        if ((int) $params->get('subcats', 1)) {
            $bounds = $this->newQuery()
                ->select(['lft', 'rgt'])
                ->from($db->quoteName('#__categories'))
                ->whereIn($db->quoteName('id'), $catIds);
            $ranges = $db->setQuery($bounds)->loadObjectList();
            $or     = [];

            foreach ($ranges as $r) {
                $or[] = '(c.lft >= ' . (int) $r->lft . ' AND c.rgt <= ' . (int) $r->rgt . ')';
            }

            $query->where($or ? '(' . implode(' OR ', $or) . ')' : '1 = 0');
        } else {
            $query->whereIn($db->quoteName('a.catid'), $catIds);
        }

        switch ($params->get('featured', 'show')) {
            case 'only':
                $query->where('a.featured = 1');
                break;
            case 'hide':
                $query->where('a.featured = 0');
                break;
        }

        if ((int) $params->get('lang_filter', 1) && Multilanguage::isEnabled()) {
            $query->whereIn($db->quoteName('a.language'), [$app->getLanguage()->getTag(), '*'], ParameterType::STRING);
        }

        $order = [
            'pub_desc'  => 'pub_date DESC',
            'pub_asc'   => 'pub_date ASC',
            'mod_desc'  => 'mod_date DESC',
            'mod_asc'   => 'mod_date ASC',
            'title_asc' => 'a.title ASC',
        ];
        $query->order($order[$params->get('sort_default', 'pub_desc')] ?? $order['pub_desc'])
            ->order('a.id DESC')
            ->setLimit($limit);

        $rows = $db->setQuery($query)->loadObjectList();

        if (!$rows) {
            return [];
        }

        $tagMap = $this->loadTags(array_map(static fn ($r) => (int) $r->id, $rows), $levels, $params);
        $items  = [];

        foreach ($rows as $row) {
            $items[] = $this->buildItem($row, $tagMap[(int) $row->id] ?? [], $params);
        }

        return $items;
    }

    /** createQuery() on Joomla 5+/6; getQuery(true) is deprecated there but kept as a fallback. */
    private function newQuery()
    {
        $db = $this->getDatabase();

        return method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);
    }

    /** Whitelisted, published, visible tags for the loaded articles: [articleId => [tagId => tag]]. */
    private function loadTags(array $ids, array $levels, Registry $params): array
    {
        $db    = $this->getDatabase();
        $white = array_values(array_filter(array_map('intval', (array) $params->get('tag_whitelist', []))));
        $type  = 'com_content.article';

        $query = $this->newQuery()
            ->select(['m.content_item_id', 't.id', 't.title', 't.alias'])
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't'), 't.id = m.tag_id')
            ->where($db->quoteName('m.type_alias') . ' = :type')
            ->whereIn($db->quoteName('m.content_item_id'), $ids)
            ->where('t.published = 1')
            ->whereIn($db->quoteName('t.access'), $levels)
            ->order('t.title ASC')
            ->bind(':type', $type);

        if ($white) {
            $query->whereIn($db->quoteName('t.id'), $white);
        }

        $map = [];

        foreach ($db->setQuery($query)->loadObjectList() as $t) {
            $map[(int) $t->content_item_id][(int) $t->id] = (object) [
                'id'    => (int) $t->id,
                'title' => $t->title,
                'alias' => $t->alias,
            ];
        }

        return $map;
    }

    /** Distinct filter tags across the loaded items, with counts, A–Z. */
    public function getFilterTags(array $items, Registry $params): array
    {
        $tags = [];

        foreach ($items as $item) {
            foreach ($item->tags as $t) {
                if (!isset($tags[$t->id])) {
                    $tags[$t->id] = (object) ['id' => $t->id, 'title' => $t->title, 'alias' => $t->alias, 'count' => 0];
                }
                $tags[$t->id]->count++;
            }
        }

        uasort($tags, static fn ($a, $b) => strcasecmp($a->title, $b->title));

        return array_values($tags);
    }

    private function buildItem(object $row, array $tags, Registry $params): object
    {
        $intro = (string) $row->introtext;
        $full  = (string) $row->fulltext;
        $body  = trim(strip_tags($intro)) !== '' ? $intro : $full;
        $wpm   = max(100, (int) $params->get('wpm', 200));
        $words = str_word_count(strip_tags(self::stripShortcodes($intro . ' ' . $full)));

        return (object) [
            'id'       => (int) $row->id,
            'title'    => $row->title,
            'link'     => Route::_(RouteHelper::getArticleRoute($row->id . ':' . $row->alias, $row->catid, $row->language)),
            'intro'    => self::cleanIntro($body, (int) $params->get('intro_length', 180)),
            'search'   => self::plain($body),
            'image'    => (int) $params->get('show_image', 1) ? $this->resolveImage($row, $params) : null,
            'pub'      => $row->pub_date,
            'mod'      => $row->mod_date,
            'pubTs'    => strtotime($row->pub_date . ' UTC') ?: 0,
            'modTs'    => strtotime($row->mod_date . ' UTC') ?: 0,
            'author'   => $row->created_by_alias ?: (string) $row->author_name,
            'category' => $row->category_title,
            'featured' => (int) $row->featured === 1,
            'readtime' => max(1, (int) ceil($words / $wpm)),
            'tags'     => array_values($tags),
        ];
    }

    /**
     * Intro image → full-text image → first <img> in the article → null (template draws a gradient).
     */
    private function resolveImage(object $row, Registry $params): ?object
    {
        $images = json_decode((string) $row->images, true) ?: [];
        $found  = null;

        foreach (['intro', 'fulltext'] as $slot) {
            if (!empty($images['image_' . $slot])) {
                $found = ['src' => $images['image_' . $slot], 'alt' => (string) ($images['image_' . $slot . '_alt'] ?? ''), 'from' => $slot];
                break;
            }
        }

        if (!$found && preg_match('/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1[^>]*>/is', $row->introtext . $row->fulltext, $m)) {
            $alt   = preg_match('/\balt\s*=\s*(["\'])(.*?)\1/is', $m[0], $a) ? html_entity_decode($a[2], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
            $found = ['src' => html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'alt' => $alt, 'from' => 'body'];
        }

        if (!$found) {
            return null;
        }

        $clean  = HTMLHelper::cleanImageURL($found['src']);
        $path   = ltrim((string) $clean->url, '/');
        $remote = (bool) preg_match('#^(https?:)?//#i', $path);

        $img = (object) [
            'src'    => $remote ? $clean->url : Uri::root(true) . '/' . $path,
            'alt'    => $found['alt'],
            'width'  => (int) ($clean->attributes['width'] ?? 0),
            'height' => (int) ($clean->attributes['height'] ?? 0),
            'srcset' => '',
            'from'   => $found['from'],
        ];

        if (!$remote && (int) $params->get('srcset', 1)) {
            $img->srcset = $this->shrinkraySrcset($path, $img->width, $params);
        }

        return $img;
    }

    /**
     * Build a srcset from ShrinkRay's copies when they exist on disk.
     *
     * ShrinkRay writes images/<sub/path>/photo.jpg as <output folder>/<sub/path>/photo--<width>.webp,
     * mirroring the folders inside images/ (default output folder: images/_sized). Only images inside
     * images/ are converted, so anything else is left alone. Copies are found by listing the folder,
     * so the widths don't need to be configured; the optional width list narrows them down.
     */
    private function shrinkraySrcset(string $path, int $origWidth, Registry $params): string
    {
        $path = rawurldecode((string) strtok($path, '?#'));

        if (strpos($path, 'images/') !== 0) {
            return '';
        }

        $folder = trim(str_replace('\\', '/', (string) $params->get('srcset_folder', 'images/_sized')), '/ ');
        $folder = $folder !== '' ? $folder : 'images/_sized';

        if (strpos($path, $folder . '/') === 0 || preg_match('#(^|/)\.\.?(/|$)#', $folder)) {
            return '';
        }

        $sub  = trim(\dirname(substr($path, \strlen('images/'))), './');
        $dir  = $folder . ($sub !== '' ? '/' . $sub : '');
        $name = pathinfo($path, PATHINFO_FILENAME);
        $only = array_filter(array_map('intval', preg_split('/[\s,]+/', (string) $params->get('srcset_sizes', ''), -1, PREG_SPLIT_NO_EMPTY)));
        $set  = [];

        foreach ($this->listCopies($dir) as $file) {
            if (!preg_match('/^(.+)--(\d+)\.webp$/', $file, $m) || $m[1] !== $name) {
                continue;
            }

            $w = (int) $m[2];

            if ($only && !\in_array($w, $only, true)) {
                continue;
            }

            $set[$w] = $this->webPath($dir . '/' . $file) . ' ' . $w . 'w';
        }

        if (!$set) {
            return '';
        }

        ksort($set);

        if ($origWidth > max(array_keys($set))) {
            $set[$origWidth] = $this->webPath($path) . ' ' . $origWidth . 'w';
        }

        return implode(', ', $set);
    }

    /** File names in one ShrinkRay output folder, listed once per request. */
    private function listCopies(string $dir): array
    {
        if (!isset($this->copyCache[$dir])) {
            $full                  = JPATH_ROOT . '/' . $dir;
            $this->copyCache[$dir] = is_dir($full) ? (scandir($full) ?: []) : [];
        }

        return $this->copyCache[$dir];
    }

    private function webPath(string $rel): string
    {
        return Uri::root(true) . '/' . implode('/', array_map('rawurlencode', explode('/', $rel)));
    }

    /** Remove {plugin tags} so shortcodes never leak into a summary. */
    public static function stripShortcodes(string $html): string
    {
        return preg_replace('/\{\/?[a-z][a-z0-9_-]*(?:\s[^{}]*)?\}/i', '', $html) ?? $html;
    }

    /** Plain text for client-side search. */
    public static function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags(self::stripShortcodes($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * Strip everything but <strong>, <em>, <br> (attributes removed), cut to $limit visible
     * characters at a word boundary, append an ellipsis, and close any tag left open.
     */
    public static function cleanIntro(string $html, int $limit): string
    {
        $html = self::stripShortcodes($html);
        $html = preg_replace('#<(script|style|template|noscript)\b.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#</(p|div|li|h[1-6]|blockquote|tr)>#i', ' ', $html) ?? '';
        $html = strip_tags($html, '<strong><b><em><i><br>');
        $html = preg_replace_callback(
            '#<(/?)(strong|b|em|i|br)\b[^>]*>#i',
            static function ($m) {
                $tag = strtolower($m[2]);
                $tag = ['b' => 'strong', 'i' => 'em'][$tag] ?? $tag;

                return $tag === 'br' ? ($m[1] ? '' : '<br>') : '<' . $m[1] . $tag . '>';
            },
            $html
        ) ?? '';

        $parts = preg_split('#(<[^>]+>)#', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $out   = '';
        $open  = [];
        $count = 0;
        $cut   = false;
        $lastBr = false;

        foreach ($parts as $part) {
            if ($part[0] === '<') {
                if ($part === '<br>') {
                    if ($count > 0 && !$lastBr) {
                        $out   .= '<br>';
                        $lastBr = true;
                    }
                    continue;
                }

                $closing = $part[1] === '/';
                $tag     = trim($part, '</>');

                if (!\in_array($tag, self::KEEP, true)) {
                    continue;
                }

                if ($closing) {
                    $idx = array_search($tag, array_reverse($open, true), true);
                    if ($idx === false) {
                        continue;
                    }
                    // Close back down to the matching tag, keeping nesting valid.
                    while ($open) {
                        $top  = array_pop($open);
                        $out .= '</' . $top . '>';
                        if ($top === $tag) {
                            break;
                        }
                    }
                } else {
                    $open[] = $tag;
                    $out   .= $part;
                }
                continue;
            }

            $text = html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/u', ' ', $text) ?? '';

            if ($count === 0) {
                $text = ltrim($text);
            }

            if ($text === '') {
                continue;
            }

            $lastBr = false;
            $len    = mb_strlen($text);

            if ($limit > 0 && $count + $len > $limit) {
                $room  = $limit - $count;
                $piece = mb_substr($text, 0, $room + 1);
                $space = mb_strrpos($piece, ' ');
                // Prefer a word boundary unless it would throw away most of the room.
                $piece = ($space !== false && $space > $room * 0.6) ? mb_substr($piece, 0, $space) : mb_substr($piece, 0, $room);
                $out  .= htmlspecialchars(rtrim($piece, " \t,.;:-–—"), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cut   = true;
                break;
            }

            $out   .= htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $count += $len;
        }

        while ($open) {
            $out .= '</' . array_pop($open) . '>';
        }

        // Drop empty wrappers and trailing breaks left behind by the cut.
        $out = preg_replace('#<(strong|em)>\s*</\1>#', '', $out) ?? $out;
        $out = preg_replace('#(?:\s*<br>)+\s*$#', '', $out) ?? $out;
        $out = rtrim($out);

        if ($cut) {
            $out = preg_replace('#((?:</(?:strong|em)>)*)$#', '…$1', $out, 1) ?? $out . '…';
        }

        return $out;
    }
}
