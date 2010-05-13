<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Doctrine
 * @subpackage Core
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Core Zend Doctrine integrations
 *
 * @uses       Doctrine_Core
 * @category   Zend
 * @package    Doctrine
 * @subpackage Core
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZFDoctrine_Core extends Doctrine_Core
{
    /**
     * @var int
     */
    const MODEL_LOADING_ZEND = 4;

    /**
     * @var array
     */
    static private $_modelDirs = null;

    /**
     * @param array|string $directories
     * @return array
     */
    static public function loadModels($directories, $modelLoading = null, $classPrefix = null)
    {
        $manager = Doctrine_Manager::getInstance();

        $modelLoading = ($modelLoading != null) ? $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING) : $modelLoading;

        if ($modelLoading == self::MODEL_LOADING_ZEND) {
            return self::loadAllZendModels();
        } else {
            return parent::loadModels($directories, $modelLoading, $classPrefix);
        }
    }

    /**
     * Return module name to module models directory.
     *
     * @return array
     */
    static public function getAllModelDirectories()
    {
        if (self::$_modelDirs == null) {
            $front = Zend_Controller_Front::getInstance();
            $modules = $front->getControllerDirectory();
            $controllerDirName = $front->getModuleControllerDirectoryName();

            $modelDirectories = array();
            foreach ((array)$modules AS $module => $controllerDir) {
                $modelDir = str_replace( $controllerDirName, '',  $controllerDir) . DIRECTORY_SEPARATOR . '/models';
                $modelDirectories[$module] = $modelDir;
            }
            self::$_modelDirs = $modelDirectories;
        }
        return self::$_modelDirs;
    }

    /**
     *
     * @param array|string $moduleDirectories
     * @return array
     */
    static public function loadAllZendModels()
    {
        $front = Zend_Controller_Front::getInstance();
        $defaultModule = $front->getDefaultModule();

        $loadedModels = array();
        foreach (self::getAllModelDirectories() AS $module => $modelDir) {
            $moduleName = self::_formatModuleName($module);

            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelDir),
                        RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($it AS $file) {
                if (substr($file->getFileName(), -4) !== ".php") {
                    continue;
                }

                $className = str_replace($modelDir . DIRECTORY_SEPARATOR, "", $file->getPathName());
                $className = str_replace(DIRECTORY_SEPARATOR, '_', $className);
                $className = substr($className, 0, strpos($className, '.'));
                if ($module !== $defaultModule) {
                    $className = $moduleName."_Model_".$className;
                } else {
                    $className = "Model_".$className;
                }

                if (strpos($className, '_Base_') === false && substr($className, -5) !== 'Table') {
                    self::loadModel($className, $file->getPathName());
                    $loadedModels[$className] = $className;
                }
            }
        }

        return $loadedModels;
    }

    /**
     * Format a module name to the module class prefix
     *
     * @param  string $name
     * @return string
     */
    static protected function _formatModuleName($name)
    {
        $name = strtolower($name);
        $name = str_replace(array('-', '.'), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }
}