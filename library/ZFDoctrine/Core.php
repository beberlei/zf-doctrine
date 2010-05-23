<?php
/**
 * ZFDoctrine
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

/**
 * Core Zend Doctrine integrations
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ZFDoctrine_Core extends Doctrine_Core
{
    /**
     * @var int
     */
    const MODEL_LOADING_ZEND = 4;

    /**
     * @var int
     */
    const MODEL_LOADING_ZEND_SINGLE_LIBRARY = 5;

    /**
     * @var int
     */
    const MODEL_LOADING_ZEND_MODULE_LIBRARY = 6;

    /**
     * @var array
     */
    static private $_modelDirs = null;

    /**
     * @var string
     */
    static private $_singleLibraryPath = null;

    static public function resetModelDirectories()
    {
        self::$_modelDirs = null;
    }

    /**
     * @param string $path
     */
    static public function setSingleLibraryPath($path)
    {
        if (!file_exists($path) || !is_dir($path)) {
            throw ZFDoctrine_DoctrineException::invalidLibraryPath($path);
        }

        $manager = Doctrine_Manager::getInstance();
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, self::MODEL_LOADING_ZEND_SINGLE_LIBRARY);
        self::$_singleLibraryPath = $path;
        self::resetModelDirectories();
    }

    /**
     * @param array|string $directories
     * @return array
     */
    static public function loadModels($directories, $modelLoading = null, $classPrefix = null)
    {
        $manager = Doctrine_Manager::getInstance();

        $modelLoading = ($modelLoading != null) ? $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING) : $modelLoading;

        $zendStyles = array(self::MODEL_LOADING_ZEND, self::MODEL_LOADING_ZEND_SINGLE_LIBRARY, self::MODEL_LOADING_ZEND_MODULE_LIBRARY);
        if (in_array($modelLoading, $zendStyles)) {
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
        $manager = Doctrine_Manager::getInstance();
        $modelLoading = $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING);

        if (self::$_modelDirs == null) {
            $manager = Doctrine_Manager::getInstance();

            $front = Zend_Controller_Front::getInstance();
            $modules = $front->getControllerDirectory();
            $modelDirectories = array();

            // For all model styles make sure that they end with a / in the directory name!!
            if ($modelLoading == self::MODEL_LOADING_ZEND) {
                $controllerDirName = $front->getModuleControllerDirectoryName();
                foreach ((array)$modules AS $module => $controllerDir) {
                    $modelDir = str_replace( $controllerDirName, '',  $controllerDir) .
                                DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;
                    $modelDirectories[$module] = $modelDir;
                }
            } else if ($modelLoading == self::MODEL_LOADING_ZEND_MODULE_LIBRARY) {
                $controllerDirName = $front->getModuleControllerDirectoryName();
                foreach ((array)$modules AS $module => $controllerDir) {
                    $modelDir = str_replace( $controllerDirName, '',  $controllerDir) .
                                DIRECTORY_SEPARATOR . 'library' .
                                DIRECTORY_SEPARATOR . self::_formatModuleName($module) .
                                DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR;
                    $modelDirectories[$module] = $modelDir;
                }
            } else if ($modelLoading == self::MODEL_LOADING_ZEND_SINGLE_LIBRARY) {
                if (!self::$_singleLibraryPath) {
                    throw ZFDoctrine_DoctrineException::libraryPathMissing();
                }

                foreach ((array)$modules AS $module => $controllerDir) {
                    $modelDirectories[$module] = self::$_singleLibraryPath . DIRECTORY_SEPARATOR .
                                                 self::_formatModuleName($module) . DIRECTORY_SEPARATOR .
                                                'Model' . DIRECTORY_SEPARATOR;
                }
            } else {
                throw ZFDoctrine_DoctrineException::invalidZendStyle();
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

        $manager = Doctrine_Manager::getInstance();
        $modelLoading = $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING);

        $loadedModels = array();
        foreach (self::getAllModelDirectories() AS $module => $modelDir) {
            $moduleName = self::_formatModuleName($module);

            if (!file_exists($modelDir)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelDir),
                        RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($it AS $file) {
                if (substr($file->getFileName(), -4) !== ".php") {
                    continue;
                }

                $className = str_replace($modelDir, "", $file->getPathName());
                $className = str_replace(DIRECTORY_SEPARATOR, '_', $className);
                $className = substr($className, 0, strpos($className, '.'));
                $className = $moduleName."_Model_".$className;

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