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

class ZFDoctrine_Application_Resource_Doctrine extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var array
     */
    protected $_paths = array();

    /**
     * @var array
     */
    protected $_managerOptions = array();

    /**
     * @var array
     */
    protected $_connectionOptions = array();

    /**
     * @var array
     */
    protected $_generateModelOptions = array();

    /**
     * Build DSN string from an array
     *
     * @param   array $dsn
     * @return  string
     */
    protected function _buildDsnFromArray(array $dsn)
    {
        $options = null;
        if (array_key_exists('options', $dsn)) {
            $options = http_build_query($dsn['options']);
        }

        return sprintf('%s://%s:%s@%s/%s?%s',
            $dsn['adapter'],
            $dsn['user'],
            $dsn['pass'],
            $dsn['hostspec'],
            $dsn['database'],
            $options);
    }

    /**
     * Set attributes for a Doctrine_Configurable instance
     *
     * @param   Doctrine_Configurable $object
     * @param   array $attributes
     * @return  void
     * @throws  Zend_Application_Resource_Exception
     */
    protected function _setAttributes(Doctrine_Configurable $object, array $attributes)
    {
        $reflect = new ReflectionClass('ZFDoctrine_Core');
        $doctrineConstants = $reflect->getConstants();

        $attributes = array_change_key_case($attributes, CASE_UPPER);

        foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $doctrineConstants)) {
                throw new Zend_Application_Resource_Exception("Invalid attribute $key.");
            }

            $attrIdx = $doctrineConstants[$key];
            $attrVal = $value;

            if (Doctrine_Core::ATTR_QUERY_CACHE == $attrIdx) {
                $attrVal = $this->_getCache($value);
            } elseif (Doctrine_Core::ATTR_RESULT_CACHE == $attrIdx) {
                $attrVal = $this->_getCache($value);
            } else {
                if (is_string($value)) {
                    $value = strtoupper($value);
                    if (array_key_exists($value, $doctrineConstants)) {
                        $attrVal = $doctrineConstants[$value];
                    }
                }
            }

            $object->setAttribute($attrIdx, $attrVal);
        }
    }

    /**
     *
     * @param Doctrine_Configurable $object
     * @param array $attributes
     * @return void
     */
    protected function _setHydrators(Doctrine_Configurable $object, array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (!isset($key)) {
                throw new Zend_Application_Resource_Exception('No name for hydrator defined. ' . $value);
            }
            $object->registerHydrator($key, $value);
        }
    }

    /**
     * Set connection listeners
     *
     * @param   Doctrine_Connection_Common $conn
     * @param   array $options
     * @return  void
     * @throws  Zend_Application_Resource_Exception
     */
    protected function _setConnectionListeners(Doctrine_Connection_Common $conn, array $options)
    {
        foreach ($options as $alias => $class) {
            if (!class_exists($class)) {
                throw new Zend_Application_Resource_Exception("$class does not exist.");
            }

            $conn->addListener(new $class(), $alias);
        }
    }

    /**
     * Retrieve a Doctrine_Cache instance
     *
     * @param   array $options
     * @return  Doctrine_Cache
     * @throws  Zend_Application_Resource_Exception
     */
    protected function _getCache(array $options)
    {
        if (!array_key_exists('driver', $options)) {
            throw new Zend_Application_Resource_Exception('Undefined cache driver.');
        }

        switch ($options['driver'])
        {
            case 'apc':
                return new Doctrine_Cache_Apc();

            case 'db':
                if (!array_key_exists('options', $options)) {
                    throw new Zend_Application_Resource_Exception('Undefined db cache options.');
                }

                if (empty($options['options'])) {
                    throw new Zend_Application_Resource_Exception('Invalid db cache options.');
                }

                if (!array_key_exists('dsn', $options['options'])) {
                    throw new Zend_Application_Resource_Exception("Undefined db cache DSN.");
                }

                if (empty($options['options']['dsn'])) {
                    throw new Zend_Application_Resource_Exception("Invalid db cache DSN.");
                }

                if (!array_key_exists('tableName', $options['options'])) {
                    throw new Zend_Application_Resource_Exception("Undefined db cache table name.");
                }

                if (empty($options['options']['tableName'])) {
                    throw new Zend_Application_Resource_Exception("Invalid db cache table name.");
                }

                $dsn = (is_array($options['options']['dsn']))
                    ? $this->_buildDsnFromArray($options['options']['dsn'])
                    : $options['options']['dsn'];

                $cacheConn = Doctrine_Manager::connection($dsn);

                $cache = new Doctrine_Cache_Db(array(
                    'connection' => $cacheConn,
                    'tableName' => $options['options']['tableName'],
                ));

                return $cache;

            case 'memcache':
                if (!array_key_exists('options', $options)) {
                    throw new Zend_Application_Resource_Exception('Undefined memcache options.');
                }

                if (empty($options['options'])) {
                    throw new Zend_Application_Resource_Exception('Invalid memcache options.');
                }

                return new Doctrine_Cache_Memcache($options['options']);

            case 'xcache':
                return new Doctrine_Cache_Xcache();

            default:
                throw new Zend_Application_Resource_Exception('Unsupported cache driver.');
        }
    }

    /**
     * Set manager level attributes
     *
     * @param   array $options
     * @return  ZFDoctrine_Application_Resource_Doctrine
     */
    public function setManager(array $options)
    {
        $this->_managerOptions = array_change_key_case($options, CASE_LOWER);
    }

    /**
     * Set connections and connection level attributes
     *
     * @param   array $options
     * @return  ZFDoctrine_Application_Resource_Doctrine
     * @throws  Zend_Application_Resource_Exception
     */
    public function setConnections(array $options)
    {
        $options = array_change_key_case($options, CASE_LOWER);
        $this->_connectionOptions = $options;

        return $this;
    }

    public function setGenerateModels(array $options)
    {
        $this->_generateModelOptions = $options;
        
        return $this;
    }

    protected function _initConnections()
    {
        $connections = array();
        foreach($this->_connectionOptions as $key => $value) {
            if (!is_array($value)) {
                throw new Zend_Application_Resource_Exception("Invalid connection on $key.");
            }

            if (!array_key_exists('dsn', $value)) {
                throw new Zend_Application_Resource_Exception("Undefined DSN on $key.");
            }

            if (empty($value['dsn'])) {
                throw new Zend_Application_Resource_Exception("Invalid DSN on $key.");
            }

            $dsn = (is_array($value['dsn']))
                ? $this->_buildDsnFromArray($value['dsn'])
                : $value['dsn'];

            $conn = Doctrine_Manager::connection($dsn, $key);
            
            if (array_key_exists('charset', $value)) {
                $conn->setCharset($value['charset']);
            }

            if (array_key_exists('attributes', $value)) {
                $this->_setAttributes($conn, $value['attributes']);
            }

            if (array_key_exists('listeners', $value)) {
                $this->_setConnectionListeners($conn, $value['listeners']);
            }

            $connections[$key] = $conn;
        }

        return $connections;
    }

    /**
     * @return Doctrine_Manager
     */
    protected function _initManager()
    {
        $manager = Doctrine_Manager::getInstance();
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, ZFDoctrine_Core::MODEL_LOADING_ZEND); // default
        

        if (array_key_exists('hydrators', $this->_managerOptions)) {
            $this->_setHydrators($manager, $this->_managerOptions['hydrators']);
        }

        if (array_key_exists('attributes', $this->_managerOptions)) {
            $this->_setAttributes($manager, $this->_managerOptions['attributes']);
        }

        return $manager;
    }

    /**
     * Initialize Doctrine paths
     *
     * @param   array $options
     * @return  ZFDoctrine_Application_Resource_Doctrine
     * @throws  Zend_Application_Resource_Exception
     */
    protected function setPaths(array $options)
    {
        $options = array_change_key_case($options, CASE_LOWER);

        foreach ($options as $key => $value) {
            if (!is_array($value)) {
                throw new Zend_Application_Resource_Exception("Invalid paths on $key.");
            }

            $this->_paths[$key] = array();

            foreach ($value as $subKey => $subVal) {
                if (!empty($subVal)) {
                    $path = realpath($subVal);

                    if (!is_dir($path)) {
                        throw new Zend_Application_Resource_Exception("$subVal does not exist.");
                    }

                    $this->_paths[$key][$subKey] = $path;
                }
            }
        }

        return $this;
    }

    /**
     * Retrieve paths
     *
     * @return  array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return  ZFDoctrine_Application_Resource_Doctrine
     * @throws  Zend_Application_Resource_Exception
     */
    public function init()
    {
        if (!class_exists('Doctrine_Core')) {
            throw ZFDoctrine_DoctrineException::doctrineNotFound();
        }
        spl_autoload_register(array('Doctrine_Core', 'autoload'));

        $manager = $this->_initManager();
        $connections = $this->_initConnections();

        return new ZFDoctrine_Registry($manager, $connections, $this->_paths, $this->_generateModelOptions);
    }
}
