<?php

/**
 * @package     Tidewrack
 * @copyright   (C) 2026 Chaotic Fish Design Studio
 * @license     GNU General Public License version 2 or later
 *
 * Install checks: refuses PHP below 8.2 and Joomla below 5, with a plain message, and refuses a copy of
 * the zip with missing or changed files (build.py writes the expected SHA-256 list below).
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

                /**
                 * Every installed file (path => SHA-256 with line endings as \n), written by build.py.
                 * Empty in the source, which skips the check.
                 *
                 * @var array
                 */
                private $expected = array(/*EXPECTED*/);

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

                    if (!$this->complete($adapter)) {
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

                /**
                 * Compare the unpacked zip with the list made when it was built. A transfer that drops or
                 * rewrites files (some unpack and repack archives) is caught here, before anything is copied.
                 */
                private function complete(InstallerAdapter $adapter)
                {
                    if (!$this->expected) {
                        return true;
                    }

                    try {
                        $source = rtrim($adapter->getParent()->getPath('source'), '/\\');
                    } catch (\Throwable $e) {
                        return true;
                    }

                    $bad = array();

                    foreach ($this->expected as $path => $hash) {
                        $file = $source . '/' . $path;
                        $data = is_file($file) ? file_get_contents($file) : false;

                        if ($data === false) {
                            $bad[] = $path . ' (missing)';
                        } elseif (hash('sha256', str_replace("\r\n", "\n", $data)) !== $hash) {
                            $bad[] = $path . ' (changed)';
                        }
                    }

                    if (!$bad) {
                        return true;
                    }

                    $more = count($bad) > 12 ? ' … +' . (count($bad) - 12) : '';
                    $text = Text::_('MOD_TIDEWRACK_INSTALL_INCOMPLETE');
                    $text = $text === 'MOD_TIDEWRACK_INSTALL_INCOMPLETE'
                        ? 'This copy of the Tidewrack zip is incomplete, so nothing was installed. Download the zip again and install it as it is, without unpacking it first. Files: %s'
                        : $text;

                    Factory::getApplication()->enqueueMessage(sprintf($text, htmlspecialchars(implode(', ', array_slice($bad, 0, 12)) . $more, ENT_QUOTES, 'UTF-8')), 'error');

                    return false;
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
