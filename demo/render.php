<?php

/**
 * Tidewrack demo renderer — not installed. Runs the real tmpl/default.php outside Joomla with
 * small stand-ins for the Joomla classes it touches, so the demo always matches the module.
 *
 *   php demo/render.php <params-json>   → module HTML on stdout
 *   php demo/render.php --art           → JSON map of no-image art per article, per style
 *
 * Image paths come out as MEDIA/images/<file>; build.py swaps them for data URIs.
 */

namespace Joomla\Database {
    interface DatabaseAwareInterface {}
    trait DatabaseAwareTrait {}
}

namespace Joomla\CMS\Language {
    class Text
    {
        public static $s = [];

        public static function _($k)
        {
            return self::$s[$k] ?? $k;
        }

        public static function sprintf($k, ...$a)
        {
            return vsprintf(self::_($k), $a);
        }
    }
}

namespace Joomla\CMS\HTML {
    class HTMLHelper
    {
        public static function date($d, $f)
        {
            return gmdate($f, strtotime($d . ' UTC'));
        }
    }
}

namespace {
    \define('_JEXEC', 1);

    $root = \dirname(__DIR__);
    require $root . '/src/Helper/TidewrackHelper.php';

    use ChaoticFishDesign\Module\Tidewrack\Site\Helper\TidewrackHelper as H;

    foreach (parse_ini_file($root . '/language/en-GB/mod_tidewrack.ini', false, INI_SCANNER_RAW) as $k => $v) {
        \Joomla\CMS\Language\Text::$s[$k] = stripcslashes(trim($v, '"'));
    }

    $data = json_decode(file_get_contents(__DIR__ . '/sample.json'), true);

    if (($argv[1] ?? '') === '--art') {
        $map = [];
        foreach ($data['items'] as $it) {
            if (!$it['img']) {
                $map[$it['id']] = ['tide' => H::art('tide', $it['id']), 'shore' => H::art('shore', $it['id'])];
            }
        }
        echo json_encode($map);
        exit;
    }

    final class DemoParams
    {
        private $d;

        public function __construct(array $d)
        {
            $this->d = $d;
        }

        public function get($k, $def = null)
        {
            return $this->d[$k] ?? $def;
        }
    }

    // Shift the sample dates so the newest article is always three days old: the Fresh catch badge stays meaningful.
    $newest = max(array_map(static fn ($it) => strtotime($it['pub'] . ' UTC'), $data['items']));
    $shift  = (time() - 3 * 86400) - $newest;
    $stamp  = static fn ($d) => gmdate('Y-m-d H:i:s', min(time(), strtotime($d . ' UTC') + $shift));

    $tagIndex = [];
    foreach ($data['tags'] as $t) {
        $tagIndex[$t['id']] = (object) ($t + ['alias' => '']);
    }

    $params = new DemoParams(array_merge([
        'twk_layout' => 'grid', 'columns' => 3, 'view_toggle' => 1, 'initial_count' => 9, 'more_step' => 6,
        'intro_length' => 170, 'fresh_days' => 21, 'fallback_art' => 'tide', 'extras' => 1,
    ], json_decode($argv[1] ?? '{}', true) ?: []));

    $items = [];
    foreach ($data['items'] as $it) {
        $pub     = $stamp($it['pub']);
        $mod     = $stamp($it['mod']);
        $items[] = (object) [
            'id'       => $it['id'],
            'title'    => $it['title'],
            'link'     => '#article-' . $it['id'],
            'intro'    => H::cleanIntro($it['intro'], (int) $params->get('intro_length')),
            'search'   => H::plain($it['intro']),
            'image'    => $it['img'] ? (object) ['src' => $it['img'], 'alt' => '', 'width' => $it['w'], 'height' => $it['h'], 'srcset' => '', 'from' => 'intro'] : null,
            'pub'      => $pub,
            'mod'      => $mod,
            'pubTs'    => strtotime($pub . ' UTC'),
            'modTs'    => strtotime($mod . ' UTC'),
            'author'   => $it['author'],
            'category' => 'Sample Articles',
            'featured' => !empty($it['featured']),
            'readtime' => $it['read'],
            'tags'     => array_map(static fn ($id) => $tagIndex[$id], $it['tags']),
        ];
    }

    $tags = [];
    foreach ($items as $i) {
        foreach ($i->tags as $t) {
            $tags[$t->id] ??= (object) ['id' => $t->id, 'title' => $t->title, 'alias' => '', 'count' => 0];
            $tags[$t->id]->count++;
        }
    }
    uasort($tags, static fn ($a, $b) => strcasecmp($a->title, $b->title));
    $tags   = array_values($tags);
    $module = (object) ['id' => (int) $params->get('module_id', 101)];
    $media  = 'MEDIA/';

    include $root . '/tmpl/default.php';
}
