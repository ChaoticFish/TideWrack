<?php

/**
 * @package     Tidewrack
 * @copyright   (C) 2026 Chaotic Fish Design Studio
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\ChaoticFishDesign\\Module\\Tidewrack'));
        $container->registerServiceProvider(new HelperFactory('\\ChaoticFishDesign\\Module\\Tidewrack\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
