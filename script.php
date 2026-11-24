<?php

/**
 * @package     Tidewrack
 * @copyright   (C) 2026 Chaotic Fish Design Studio
 * @license     GNU General Public License version 2 or later
 *
 * Install checks: refuses PHP below 8.2 and Joomla below 5, with a plain message.
 * Kept to old PHP syntax (no typed properties, promotion, match, etc.) so this file
 * itself still parses on the old servers it is meant to turn away.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class () implements InstallerScriptInterface {
                /** @var string */
                private $minimumPhp = '8.2.0';

                /** @var string */
                private $minimumJoomla = '5.0.0';

                public function install(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    if ($type === 'uninstall') {
                        return true;
                    }

                    $this->loadLanguage($adapter);

                    if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
                        $this->refuse(
                            'MOD_TIDEWRACK_INSTALL_PHP',
                            'Tidewrack needs PHP %1$s or later. This server runs PHP %2$s. Ask your host to upgrade PHP, then install again.',
                            $this->minimumPhp,
                            PHP_VERSION
                        );

                        return false;
                    }

                    if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
                        $this->refuse(
                            'MOD_TIDEWRACK_INSTALL_JOOMLA',
                            'Tidewrack needs Joomla %1$s or later. This site runs Joomla %2$s.',
                            $this->minimumJoomla,
                            JVERSION
                        );

                        return false;
                    }

                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    return true;
                }

                /** The module's own language file is not loaded yet during install; load it from the package. */
                private function loadLanguage(InstallerAdapter $adapter)
                {
                    try {
                        $source = $adapter->getParent()->getPath('source');
                        $lang   = Factory::getApplication()->getLanguage();
                        $lang->load('mod_tidewrack.sys', $source, null, true) || $lang->load('mod_tidewrack.sys', JPATH_SITE, null, true);
                    } catch (\Throwable $e) {
                        // The English fallback in refuse() covers this.
                    }
                }

                private function refuse($key, $fallback, $need, $have)
                {
                    $text = Text::_($key);
                    $text = $text === $key ? $fallback : $text;

                    Factory::getApplication()->enqueueMessage(sprintf($text, $need, $have), 'error');
                }
            }
        );
    }
};
