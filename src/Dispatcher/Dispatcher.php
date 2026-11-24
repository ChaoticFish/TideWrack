<?php

/**
 * @package     Tidewrack
 * @copyright   (C) 2026 Chaotic Fish Design Studio
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFishDesign\Module\Tidewrack\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

final class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        $app    = $this->getApplication();

        /** @var \ChaoticFishDesign\Module\Tidewrack\Site\Helper\TidewrackHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('TidewrackHelper');

        $data['failed'] = false;

        try {
            $data['items'] = $helper->getItems($params, $app);
        } catch (\Throwable $e) {
            // Visitors see nothing broken; the details go to the log for the site owner.
            $helper::logError($e, 'module ' . (int) $data['module']->id);
            $data['items']  = [];
            $data['failed'] = true;
        }

        $data['tags']  = $helper->getFilterTags($data['items'], $params);
        $data['cats']  = (int) $params->get('show_cats', 0) ? $helper->getFilterCats($data['items']) : [];
        $data['years'] = (int) $params->get('show_years', 0) ? $helper->getFilterYears($data['items']) : [];

        // The pop-up fetches articles through com_ajax; Itemid keeps the page's language and menu context.
        $active        = $app->getMenu() ? $app->getMenu()->getActive() : null;
        $data['ajax']  = Uri::base(true) . '/index.php?option=com_ajax&module=tidewrack&method=article&format=json'
            . '&mid=' . (int) $data['module']->id . ($active ? '&Itemid=' . (int) $active->id : '');

        // Classic asset loading: WebAssetManager registration conflicted with T4 on the CFD sites.
        $doc   = $app->getDocument();
        $media = Uri::root(true) . '/media/mod_tidewrack/';
        $ver   = '1.2.0';
        $doc->addStyleSheet($media . 'css/tidewrack.min.css', ['version' => $ver]);
        $doc->addScript($media . 'js/tidewrack.min.js', ['version' => $ver], ['defer' => true]);

        // Script strings ride on the module root as data attributes (see tmpl/default.php),
        // so the script never depends on Joomla's core.js being loaded.
        $data['media'] = $media;

        return $data;
    }
}
