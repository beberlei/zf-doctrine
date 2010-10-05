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

require_once dirname(__FILE__) . "/../DoctrineException.php";

/**
 * Doctrine Tool Provider
 *
 * @author Benjamin Eberlei (kontakt@beberlei.de)
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
    public function createProject($dsn, $zendProjectStyle = false, $libraryPerModule = false, $singleLibrary = false)
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

        $projectStyle = "model_loading_zend";
        if ($singleLibrary) {
            $projectStyle = "model_loading_zend_single_library";
        } else if ($libraryPerModule) {
            $projectStyle = "model_loading_zend_module_library";
        } else if ($zendProjectStyle) {
            $projectStyle = "model_loading_zend";
        }

        /* @var $applicationConfigResource Zend_Tool_Project_Context_Zf_ApplicationConfigFile */
        $applicationConfigResource->addStringItem('resources.doctrine.connections.default.dsn', $dsn, 'production', '"'.$dsn.'"');
        $applicationConfigResource->create();
        $applicationConfigResource->addStringItem('resources.doctrine.manager.attributes.attr_model_loading', $projectStyle, 'production', '"' . $projectStyle . "'");
        $applicationConfigResource->create();
        $applicationConfigResource->addStringItem('pluginpaths.ZFDoctrine_Application_Resource', 'ZFDoctrine/Application/Resource', 'production');
        $applicationConfigResource->create();
        $applicationConfigResource->addStringItem('autoloadernamespaces[]', "Doctrine", 'production');
        $applicationConfigResource->create();
        $applicationConfigResource->addStringItem('autoloadernamespaces[]', "ZFDoctrine", 'production');
        $applicationConfigResource->create();

        if ($this->_registry->getRequest()->isPretend()) {
            $this->_print('Would enable Doctrine support by adding resource string.');
        } else {
            $this->_print('Enabled Doctrine Zend_Application resource in project.', array('color' => 'green'));
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
                        $this->_print('Created Resource: '.$resourceName, array('color' => 'green'));
                    } else {
                        $this->_print('Registered Resource: '.$resourceName, array('color' => 'green'));
                    }
                    $changes = true;
                }
            }
        }

        if ($changes) {
            $profile->storeToFile();
        }
    }

    public function buildProject($force = false, $load=false, $reload=false)
    {
        if ($reload) {
            $this->dropDatabase($force);
        }
        $this->createDatabase();
        $this->createTables();
        if ($load) {
            $this->loadData(false);
        }
    }

    public function createDatabase()
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

    public function dropDatabase($force = false)
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

            $queries = $export->exportSortedClassesSql($models);
            $countSql = 0;
            foreach ($queries as $connectionName => $sql) {
                $countSql += count($sql);
            }

            if ($countSql) {
                $export->exportClasses($models);

                $this->_print("Successfully created tables from model.", array('color' => 'green'));
                $this->_print("Executing " . $countSql . " queries for " . count($queries) . " connections.", array('color' => 'green'));
            } else {
                $this->_print("No sql queries found to create tables from.", array('color' => array('white', 'bgRed')));
                $this->_print("Have you generated a model from your YAML schema?", array('color' => array('white', 'bgRed')));
            }
        } catch(Exception $e) {
            $this->_print("Error while creating tables from model: ".$e->getMessage(), array('color' => array('white', 'bgRed')));

            if ($this->_registry->getRequest()->isDebug()) {
                $this->_print($e->getTraceAsString());
            }
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

    public function generateModelsFromYaml()
    {
        $doctrine = $this->_getDoctrineRegistry();
        $this->_loadDoctrineModels();

        $import = $this->_createImportSchema();
        $import->setOptions($doctrine->getGenerateModelOptions());
        $import->importSchema($this->_getYamlDirectoryPath(), 'yml', $doctrine->getModelPath());
    }

    protected function _createImportSchema()
    {
        $manager = Doctrine_Manager::getInstance();
        $modelLoading = $manager->getAttribute(Doctrine_Core::ATTR_MODEL_LOADING);

        $listener = false;
        $zendStyles = array(ZFDoctrine_Core::MODEL_LOADING_ZEND, ZFDoctrine_Core::MODEL_LOADING_ZEND_SINGLE_LIBRARY, ZFDoctrine_Core::MODEL_LOADING_ZEND_MODULE_LIBRARY);
        if (!in_array($modelLoading, $zendStyles)) {
            $import = new Doctrine_Import_Schema();
        } else {
            $response = $this->_registry->getResponse();
            $listener = new ZFDoctrine_Tool_ResponseImportListener($response);
            $import = new ZFDoctrine_Import_Schema();
            $import->setListener($listener);
        }
        return $import;
    }

    public function generateYamlFromModels()
    {
        $doctrine = $this->_getDoctrineRegistry();
        $this->_loadDoctrineModels();

        $yamlDir = $this->_getYamlDirectoryPath();
        Doctrine_Core::generateYamlFromModels($yamlDir, null);

        $this->_print('Successfully generated yaml schema files from model.', array('color' => 'green'));
        $this->_print('Destination Directory: ' . $yamlDir);
    }

    public function generateYamlFromDatabase()
    {
        $this->_initDoctrineResource();

        $yamlDir = $this->_getYamlDirectoryPath();
        Doctrine_Core::generateYamlFromDb($yamlDir);

        $this->_print('Succsesfully generated yaml schema files from database.', array('color' => 'green'));
        $this->_print('Destination Directory: ' . $yamlDir);
    }

    public function generateMigration($className=null, $dFromDatabase=false, $mFromModels=false)
    {

        $this->_initDoctrineResource();

        $migratePath = $this->_getMigrationsDirectoryPath();

        if ($className) {
            Doctrine_Core::generateMigrationClass($className, $migratePath);

            $this->_print('Successfully generated migration class '.$className.'.', array('color' => 'green'));
            $this->_print('Destination Directory: '.$migratePath);
        } else if ($dFromDatabase) {

            $yamlSchemaPath = $this->_getYamlDirectoryPath();
            $migration = new Doctrine_Migration($migrationsPath);
            $result1 = false;
            if ( ! count($migration->getMigrationClasses())) {
                $result1 = Doctrine_Core::generateMigrationsFromDb($migrationsPath);
            }
            $connections = array();
            foreach (Doctrine_Manager::getInstance() as $connection) {
                $connections[] = $connection->getName();
            }
            $changes = Doctrine_Core::generateMigrationsFromDiff($migrationsPath, $connections, $yamlSchemaPath);
            $numChanges = count($changes, true) - count($changes);
            $result = ($result1 || $numChanges) ? true:false;

            if ($result) {
                $this->_print('Generated migration classes from the database successfully.');
            } else {
                throw new Exception('Could not generate migration classes from database');
            }
        } else if ($mFromModels) {
            $this->_loadDoctrineModels();

            Doctrine_Core::generateMigrationsFromModels($migrationsPath, null);

            $this->_print('Generated migration classes from the model successfully.');
        }
    }

    public function executeMigration($toVersion = null)
    {
        $this->_initDoctrineResource();

        $currentVersion = $this->getCurrentMigrationVersion();

        $migratePath = $this->_getMigrationsDirectoryPath();
        $newVersion = Doctrine_Core::migrate($migratePath, $toVersion);

        $this->_print('Migrated from version ' . $currentVersion . ' to ' . $newVersion);
    }

    public function showMigration()
    {
        $this->_initDoctrineResource();

        $this->_print('The current migration version is: ' . $this->getCurrentMigrationVersion());
    }

    public function show()
    {
        $this->_loadDoctrineModels();

        $modules = ZFDoctrine_Core::getAllModelDirectories();
        
        $this->_print('Current Doctrine Model Directories:');
        foreach ($modules AS $module => $directory) {
            $this->_print(' * Module ' . $module . ': ' . $directory);
        }
        $this->_registry->getResponse()->appendContent('');
        
        $models = ZFDoctrine_Core::getLoadedModels();
        $this->_print('All current models:');
        foreach ($models AS $class) {
            $this->_print(' * ' . $class);
        }

        $this->_registry->getResponse()->appendContent('');

        $this->showMigration();
    }

    protected function getCurrentMigrationVersion()
    {
        $migratePath = $this->_getMigrationsDirectoryPath();
        $migration = new Doctrine_Migration($migratePath);
        return $migration->getCurrentVersion();
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

        $container = $app->getBootstrap()->getContainer();
        if (!isset($container->doctrine)) {
            throw new ZFDoctrine_DoctrineException('There is no "doctrine" resource enabled in the current project.');
        }

        $this->_doctrineRegistry = $container->doctrine;
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
