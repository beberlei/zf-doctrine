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
 * @package    Zend_Doctrine
 * @subpackage Tool
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Doctrine Tool Provider
 *
 * @uses       Zend_Tool_Project_Provider_Abstract
 * @uses       Zend_Tool_Framework_Provider_Pretendable
 * @category   Zend
 * @package    Zend_Doctrine
 * @subpackage Tool
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZFDoctrine_Tool_DoctrineProvider extends Zend_Tool_Project_Provider_Abstract
    implements Zend_Tool_Framework_Provider_Pretendable
{

    /**
     * @var ZFDoctrine_Registry
     */
    protected $_doctrineRegistry = null;

    /**
     * @return array
     */
    public function getContextClasses()
    {
        return array(
            'ZFDoctrine_Tool_Context_DataFixturesDirectory',
            'ZFDoctrine_Tool_Context_MigrationsDirectory',
            'ZFDoctrine_Tool_Context_SqlDirectory',
            'ZFDoctrine_Tool_Context_YamlSchemaDirectory',
        );
    }

    /**
     * @param  string $dsn
     * @param  bool $withResourceDirectories
     * @return void
     */
    public function createProject($dsn)
    {
        $profile = $this->_loadProfileRequired();

        $applicationConfigResource = $profile->search('ApplicationConfigFile');

        if (!$applicationConfigResource) {
            throw new Zend_Tool_Project_Exception('A project with an application config file is required to use this provider.');
        }

        $zc = $applicationConfigResource->getAsZendConfig();

        if (isset($zc->resources) && isset($zf->resources->doctrine)) {
            $this->_registry->getResponse()->appendContent('A Doctrine resource already exists in this project\'s application configuration file.');
            return;
        }

        /* @var $applicationConfigResource Zend_Tool_Project_Context_Zf_ApplicationConfigFile */
        $applicationConfigResource->addStringItem('resources.doctrine.connections.default.dsn', $dsn, 'production', '"'.$dsn.'"');
        $applicationConfigResource->create();
        $applicationConfigResource->addStringItem('pluginpaths.ZFDoctrine_Application_Resource', 'Zend/Doctrine/Application/Resource', 'production');
        $applicationConfigResource->create();
        $applicationConfigResource->addStringItem('autoloadernamespaces[]', "Doctrine", 'production');
        $applicationConfigResource->create();

        if ($this->_registry->getRequest()->isPretend()) {
            $this->_print('Would enable Doctrine support by adding resource string.');
        } else {
            $this->_print('Enabled Doctrine Zend_Application resource in project.');
        }

        $configsDirectory = $profile->search(array('configsDirectory'));

        if ($configsDirectory == null) {
            throw new Exception("No Config directory in Zend Tool Project.");
        }

        $globalResources = array('YamlSchemaDirectory', 'DataFixturesDirectory', 'MigrationsDirectory', 'SqlDirectory');
        $changes = false;
        foreach ($globalResources AS $resourceName) {
            if (!$profile->search(array('configsDirectory', $resourceName))) {
                if ($this->_registry->getRequest()->isPretend()) {
                    $this->_print("Would add ".$resourcenName." to the application config directory.");
                } else {
                    $resource = $configsDirectory->createResource($resourceName);
                    if (!$resource->exists()) {
                        $resource->create();
                        $this->_print('Created Resource: '.$resourceName);
                    } else {
                        $this->_print('Registered Resource: '.$resourceName);
                    }
                    $changes = true;
                }
            }
        }

        if ($changes) {
            $profile->storeToFile();
        }
    }

    public function buildAll($force = false)
    {
        $this->createDb();
        $this->createTables();
    }

    public function buildAllLoad($force = false)
    {
        $this->buildAll($force);
        $this->loadData(false);
    }

    public function buildAllReload($force = false)
    {
        $this->dropDb($force);
        $this->buildAllLoad();
    }

    public function createDb()
    {
        $doctrine = $this->_getDoctrineRegistry();
        
        foreach ($doctrine->getConnections() as $name => $connection) {
            try {
                $connection->createDatabase();
                $this->_print(
                    "Successfully created database for connection named '" . $name . "'",
                    array('color' => 'green')
                );
            } catch (Exception $e) {
                $this->_print(
                    "Error creating database for connection '".$name."': ".$e->getMessage(),
                    array('color' => array('white', 'bgRed'))
                );
            }
        }
    }

    public function dropDb($force = false)
    {
        if ($force == false) {
            $confirmed = $this->_registry
                              ->getClient()
                              ->promptInteractiveInput('Are you sure you wish to irrevocably drop your databases? (y/n)')->getContent();
            if (strtolower($confirmed) != "y") {
                $this->_print('Dropping databases was aborted.');
                return;
            }
        }

        $doctrine = $this->_getDoctrineRegistry();
        
        foreach ($doctrine->getConnections() as $name => $connection) {
            try {
                $connection->dropDatabase();
                $this->_print("Successfully dropped database for connection named '" . $name . "'", array('color' => 'green'));
            } catch (Exception $e) {
                $this->_print("Error dropping database '".$name."': ". $e->getMessage(), array('color' => array('white', 'bgRed')));
            }
        }
    }

    public function createTables()
    {
        $this->_initDoctrineResource();
        $this->_loadDoctrineModels();

        try {
            $models = ZFDoctrine_Core::getLoadedModels();
            $models = ZFDoctrine_Core::filterInvalidModels($models);

            $export = Doctrine_Manager::connection()->export;
            $export->exportClasses($models);

            $this->_print("Successfully created tables from model.", array('color' => 'green'));
        } catch(Exception $e) {
            $this->_print("Error while creating tables from model: ".$e->getMessage(), array('color' => array('white', 'bgRed')));
        }
    }


    public function generateSql()
    {
        $this->_loadDoctrineModels();
        $sqlDir = $this->_getSqlDirectoryPath();

        $sql = Doctrine_Core::generateSqlFromModels();

        $sqlSchemaFile = $sqlDir . DIRECTORY_SEPARATOR . "schema_".date('Ymd_His').".sql";
        file_put_contents($sqlSchemaFile, $sql);

        $this->_print('Successfully written SQL for the current schema to disc.', array('color' => 'green'));
        $this->_print('Destination File: '.$sqlSchemaFile);
    }

    public function dql()
    {

    }

    public function loadData($append = false)
    {
        $this->_loadDoctrineModels();
        Doctrine_Core::loadData($this->_getDataFixtureDirectoryPath(), $append);

        $this->_print('Successfully loaded data from fixture.', array('color' => 'green'));
    }

    public function dumpData($individualFiles = false)
    {
        $this->_loadDoctrineModels();

        $fixtureDir = $this->_getDataFixtureDirectoryPath();

        Doctrine_Core::dumpData($fixtureDir, $individualFiles);

        $this->_print('Successfully dumped current database contents into fixture directory.', array('color' => 'green'));
        $this->_print('Destination Directory: ' . $fixtureDir);
    }

    public function generateModelsYaml()
    {
        $doctrine = $this->_getDoctrineRegistry();
        $modelsPath = $this->_loadDoctrineModels();

        $import = $this->_createImportSchema();
        $import->setOptions($doctrine->getGenerateModelOptions());
        $import->importSchema($this->_getYamlDirectoryPath(), 'yml', $doctrine->getModelPath());
    }

    protected function _createImportSchema()
    {
        $manager = Doctrine_Manager::getInstance();
        $modelLoading = $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING);

        $listener = false;
        if ($modelLoading !== ZFDoctrine_Core::MODEL_LOADING_ZEND) {
            $import = new Doctrine_Import_Schema();
        } else {
            $response = $this->_registry->getResponse();
            $listener = new ZFDoctrine_Tool_ResponseImportListener($response);
            $import = new ZFDoctrine_Import_Schema();
            $import->setListener($listener);
        }
        return $import;
    }

    public function generateYamlModels()
    {
        $this->_loadDoctrineModels();

        $yamlDir = $this->_getYamlDirectoryPath();
        Doctrine_Core::generateYamlFromModels($yamlDir);

        $this->_print('Successfully generated yaml schema files from model.', array('color' => 'green'));
        $this->_print('Destination Directory: ' . $yamlDir);
    }

    public function generateYamlDb()
    {
        $this->_initDoctrineResource();

        $yamlDir = $this->_getYamlDirectoryPath();
        Doctrine_Core::generateYamlFromDb($yamlDir);

        $this->_print('Succsesfully generated yaml schema files from database.', array('color' => 'green'));
        $this->_print('Destination Directory: ' . $yamlDir);
    }

    public function generateMigration($className)
    {
        $this->_loadDoctrineModels();

        $migratePath = $this->_getMigrationsDirectoryPath();

        Doctrine_Core::generateMigrationClass($className, $migratePath);

        $this->_print('Successfully generated migration class '.$className.'.', array('color' => 'green'));
        $this->_print('Destination Directory: '.$migratePath);
    }

    public function generateMigrationsDb()
    {
        
    }

    public function generateMigrationsDiff()
    {

    }

    public function generateMigrationsModels()
    {
        
    }

    protected function _loadDoctrineModels()
    {
        $this->_initDoctrineResource();

        $manager = Doctrine_Manager::getInstance();
        ZFDoctrine_Core::loadModels(
            $this->_getDoctrineRegistry()->getModelPath(),
            $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING)
        );
    }

    protected function _getYamlDirectoryPath()
    {
        $yamlDir = $this->_loadProfileRequired()->search('YamlSchemaDirectory');
        if (!$yamlDir) {
            throw new Exception("No YAML Schema Directory path is configured in your ZF project.");
        }
        return $yamlDir->getPath();
    }

    protected function _getDataFixtureDirectoryPath()
    {
        $fixtureDir = $this->_loadProfileRequired()->search('DataFixturesDirectory');
        if (!$fixtureDir) {
            throw new Exception('No data fixture directory is configured in your ZF project.');
        }
        return $fixtureDir->getPath();
    }

    protected function _getMigrationsDirectoryPath()
    {
        $migrateDir = $this->_loadProfileRequired()->search('MigrationsDirectory');
        if (!$migrateDir) {
            throw new Exception('No migrations directory is configured in your ZF project.');
        }
        return $migrateDir->getPath();
    }

    protected function _getSqlDirectoryPath()
    {
        $sqlDir = $this->_loadProfileRequired()->search('SqlDirectory');
        if (!$sqlDir) {
            throw new Exception('No sql directory is configured in your ZF project.');
        }
        return $sqlDir->getPath();
    }

    protected function _initDoctrineResource()
    {
        if($this->_doctrineRegistry != null) {
            return;
        }
        
        $profile = $this->_loadProfileRequired();

        /* @var $app Zend_Application */
        $app = $profile->search('BootstrapFile')->getApplicationInstance();
        $app->bootstrap();
        $this->_doctrineRegistry = $app->getBootstrap()->getContainer()->doctrine;
    }

    /**
     * @return ZFDoctrine_Registry
     */
    protected function _getDoctrineRegistry()
    {
        if ($this->_doctrineRegistry == null) {
            $this->_initDoctrineResource();
        }
        return $this->_doctrineRegistry;
    }

    /**
     * @param string $line
     * @param array $decoratorOptions
     */
    protected function _print($line, array $decoratorOptions = array())
    {
        $this->_registry->getResponse()->appendContent("[Doctrine] " . $line, $decoratorOptions);
    }
}