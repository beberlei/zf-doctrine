<?php

class ZFDoctrine_Application_Resource_DoctrineTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (method_exists('Doctrine_Manager', 'resetInstance')) { // as of 1.2ALPHA3
            Doctrine_Manager::resetInstance();
        }

        $this->application = new Zend_Application('testing');
        $this->bootstrap = new Zend_Application_Bootstrap_Bootstrap($this->application);
        Zend_Controller_Front::getInstance()->resetInstance();
    }

    public function testInitializationReturnsParablesApplicationResourceDoctrine()
    {
        $options = array();
        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $doctrine = $resource->init();

        $this->assertType('ZFDoctrine_Registry', $doctrine);
    }

    public function testInitializationInitializesManager()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_auto_accessor_override' => false,
                    'attr_auto_free_query_objects' => false,
                    'attr_autoload_table_classes' => false,
                    // 'attr_cascade_saves' => true,
                    // 'attr_collection_class' => 'Doctrine_Collection',
                    // 'attr_create_tables' => true,
                    'attr_decimal_places' => 2,
                    'attr_default_column_options' => array(
                        'type' => 'string',
                        'length' => 255,
                        'notnull' => true,
                    ),
                    'attr_default_identifier_options' => array(
                        'name' => '%s_id',
                        'type' => 'string',
                        'length' => 16,
                    ),
                    'attr_default_param_namespace' => 'doctrine',
                    // 'attr_def_text_length' => 4096,
                    // 'attr_def_varchar_length' => 255,
                    'attr_emulate_database' => false,
                    'attr_export' => 'export_all',
                    'attr_fkname_format' => "%s",
                    'attr_hydrate_overwrite' => true,
                    'attr_idxname_format' => "%s_idx",
                    'attr_load_references' => true,
                    'attr_model_loading' => 'model_loading_conservative',
                    'attr_portability' => 'portability_none',
                    // 'attr_query_class' => 'Doctrine_Query',
                    'attr_query_limit' => 'limit_records',
                    'attr_quote_identifier' => false,
                    'attr_recursive_merge_fixtures' => false,
                    'attr_seqcol_name' => 'id',
                    'attr_seqname_format' => "%s_seq",
                    // 'attr_table_class' => 'Doctrine_Table',
                    // 'attr_tblclass_format' => "",
                    'attr_tblname_format' => "%s",
                    'attr_throw_exceptions' => true,
                    'attr_use_dql_callbacks' => false,
                    'attr_use_native_enum' => false,
                    'attr_validate' => 'validate_none',
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $reflect = new ReflectionClass('Doctrine');
        $doctrineConstants = $reflect->getConstants();

        $manager = Doctrine_Manager::getInstance();
        foreach ($options['manager']['attributes'] as $key => $value) {
            $attrIdx = $doctrineConstants[strtoupper($key)];
            $attrVal = $value;

            if (is_string($value)) {
                $value = strtoupper($value);
                if (array_key_exists($value, $doctrineConstants)) {
                    $attrVal = $doctrineConstants[$value];
                }
            }

            $this->assertEquals($attrVal, $manager->getAttribute($attrIdx));
        }
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidManagerAttributeShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'invalid' => 1,
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    public function testInitializationInitializesManagerApcQueryCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'apc',
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        }
    }

    public function testInitializationInitializesManagerDbQueryCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'db',
                            'options' => array(
                                'dsn' => 'sqlite::memory:',
                                'tableName' => 'doctrine_query_cache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        }
    }

    public function testInitializationInitializesManagerMemcacheQueryCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'memcache',
                            'options' => array(
                                'servers' => array(
                                    'host' => 'localhost',
                                    'port' => 11211,
                                    'persistent' => true,
                                ),
                                'compression' => false,
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        }
    }

    public function testInitializationInitializesManagerXcacheQueryCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'xcache',
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        }
    }

    public function testInitializationInitializesManagerApcResultCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'apc',
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        }
    }

    public function testInitializationInitializesManagerDbResultCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'db',
                            'options' => array(
                                'dsn' => 'sqlite::memory:',
                                'tableName' => 'doctrine_result_cache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        }
    }

    public function testInitializationInitializesManagerMemcacheResultCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'memcache',
                            'options' => array(
                                'servers' => array(
                                    'host' => 'localhost',
                                    'port' => 11211,
                                    'persistent' => true,
                                ),
                                'compression' => false,
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        }
    }

    public function testInitializationInitializesManagerXcacheResultCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'xcache',
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        }
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedCacheDriverShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUnsupportedCacheDriverShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'array',
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedDbCacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidDbCacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedDbCacheDsnShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'tableName' => 'doctrine_cache',
                        ),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidDbCacheDsnShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'dsn' => '',
                        ),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedDbCacheTableNameShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'dsn' => 'sqlite::memory:',
                        ),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidDbCacheTableNameShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'dsn' => 'sqlite::memory:',
                            'tableName' => '',
                        ),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedMemcacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'memcache',
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidMemcacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'memcache',
                        'options' => array(),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    public function testInitializationInitializesConnections()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                    'attributes' => array(
                        'attr_auto_accessor_override' => false,
                        'attr_auto_free_query_objects' => false,
                        'attr_autoload_table_classes' => false,
                        // 'attr_cascade_saves' => true,
                        // 'attr_collection_class' => 'Doctrine_Collection',
                        // 'attr_create_tables' => true,
                        'attr_decimal_places' => 2,
                        'attr_default_column_options' => array(
                            'type' => 'string',
                            'length' => 255,
                            'notnull' => true,
                        ),
                        'attr_default_identifier_options' => array(
                            'name' => '%s_id',
                            'type' => 'string',
                            'length' => 16,
                        ),
                        'attr_default_param_namespace' => 'doctrine',
                        // 'attr_def_text_length' => 4096,
                        // 'attr_def_varchar_length' => 255,
                        'attr_emulate_database' => false,
                        'attr_export' => 'export_all',
                        'attr_fkname_format' => "%s",
                        'attr_hydrate_overwrite' => true,
                        'attr_idxname_format' => "%s_idx",
                        'attr_load_references' => true,
                        'attr_model_loading' => 'model_loading_conservative',
                        'attr_portability' => 'portability_none',
                        // 'attr_query_class' => 'Doctrine_Query',
                        'attr_query_limit' => 'limit_records',
                        'attr_quote_identifier' => false,
                        'attr_recursive_merge_fixtures' => false,
                        'attr_seqcol_name' => 'id',
                        'attr_seqname_format' => "%s_seq",
                        // 'attr_table_class' => 'Doctrine_Table',
                        // 'attr_tblclass_format' => "",
                        'attr_tblname_format' => "%s",
                        'attr_throw_exceptions' => true,
                        'attr_use_dql_callbacks' => false,
                        'attr_use_native_enum' => false,
                        'attr_validate' => 'validate_none',
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');

        $reflect = new ReflectionClass('Doctrine');
        $doctrineConstants = $reflect->getConstants();

        foreach ($options['connections']['demo']['attributes'] as $key => $value) {
            $attrIdx = $doctrineConstants[strtoupper($key)];
            $attrVal = $value;

            if (is_string($value)) {
                $value = strtoupper($value);
                if (array_key_exists($value, $doctrineConstants)) {
                    $attrVal = $doctrineConstants[$value];
                }
            }

            $this->assertEquals($attrVal, $conn->getAttribute($attrIdx));
        }
    }

    public function testInitializationInitializesConnectionApcQueryCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'apc',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        }
    }

    public function testInitializationInitializesConnectionDbQueryCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'db',
                                'options' => array(
                                    'dsn' => 'sqlite::memory:',
                                    'tableName' => 'doctrine_query_cache',
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        }
    }

    public function testInitializationInitializesConnectionMemcacheQueryCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'memcache',
                                'options' => array(
                                    'servers' => array(
                                        'host' => 'localhost',
                                        'port' => 11211,
                                        'persistent' => true,
                                    ),
                                    'compression' => false,
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        }
    }

    public function testInitializationInitializesConnectionXcacheQueryCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'xcache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        }
    }

    public function testInitializationInitializesConnectionApcResultCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'apc',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        }
    }

    public function testInitializationInitializesConnectionDbResultCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'db',
                                'options' => array(
                                    'dsn' => 'sqlite::memory:',
                                    'tableName' => 'doctrine_result_cache',
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        }
    }

    public function testInitializationInitializesConnectionMemcacheResultCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'memcache',
                                'options' => array(
                                    'servers' => array(
                                        'host' => 'localhost',
                                        'port' => 11211,
                                        'persistent' => true,
                                    ),
                                    'compression' => false,
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        }
    }

    public function testInitializationInitializesConnectionXcacheResultCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'xcache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        }
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidConnectionAttributeShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                    'attributes' => array(
                        'invalid' => 1,
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedDsnShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidDsnShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => '',
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /*
    public function testInitializationInitializesPaths()
    {
        $options = array(
            'paths' => array(
                'demo' => array(
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $paths = $resource->getPaths();

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $paths);
        $this->assertArrayHasKey('demo', $paths);
    }
    */

    /**
     * @expectedException Zend_Application_Resource_Exception
    public function testPassingInvalidPathsShouldRaiseException()
    {
        $options = array(
            'paths' => array(
                'demo' => null,
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }
     */

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingNonexistentPathShouldRaiseException()
    {
        $options = array(
            'paths' => array(
                'demo' => array(
                    'somekey' => 'somepath',
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    public function testPassingCorrectPathOptionsAndGettingThem()
    {
        $options = array(
            'paths' => array(
                'demo' => array(
                    'somekey' => dirname(__FILE__),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $paths = $resource->getPaths();

        $this->assertEquals($options['paths'], $paths);
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingInvalidListenerClassShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/_files/test.db',
                    'attributes' => array(
                        'listeners' => array(
                            'profiler' => 'Invalid_Listener',
                        ),
                    ),
                ),
            ),
        );

        $resource = new ZFDoctrine_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }
}
