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

        $data['items'] = $helper->getItems($params, $app);
        $data['tags']  = $helper->getFilterTags($data['items'], $params);

        // Classic asset loading: WebAssetManager registration conflicted with T4 on the CFD sites.
        $doc   = $app->getDocument();
        $media = Uri::root(true) . '/media/mod_tidewrack/';
        $ver   = '1.0.1';
        $doc->addStyleSheet($media . 'css/tidewrack.min.css', ['version' => $ver]);
        $doc->addScript($media . 'js/tidewrack.min.js', ['version' => $ver], ['defer' => true]);

        // Script strings ride on the module root as data attributes (see tmpl/default.php),
        // so the script never depends on Joomla's core.js being loaded.
        $data['media'] = $media;

        return $data;
    }
}
