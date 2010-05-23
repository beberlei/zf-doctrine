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

class ZFDoctrine_Registry
{
    /**
     * @var Doctrine_Manager
     */
    protected $_manager = null;
    protected $_connections = array();
    protected $_generateModelOptions = array();
    protected $_paths = array();

    /**
     * @param Doctrine_Manager $manager
     * @param array $connections
     * @param array $paths
     * @param array $generateModelOptions
     */
    public function __construct(Doctrine_Manager $manager, array $connections, array $paths, array $generateModelOptions)
    {
        $this->_manager = $manager;
        $this->_connections = $connections;
        $this->_generateModelOptions = $generateModelOptions;
        $this->_paths = $paths;
    }

    /**
     * @return Doctrine_Manager
     */
    public function getManager()
    {
        return $this->_manager;
    }

    public function getConnections()
    {
        return $this->_connections;
    }

    public function getGenerateModelOptions()
    {
        return $this->_generateModelOptions;
    }

    public function getModelPath()
    {
        if (isset($this->_paths['model_path'])) {
            return $this->_paths['model_path'];
        }
        return null;
    }
}