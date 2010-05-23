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
 * Builder that extends the Doctrine Builder to add Zend Style specific features
 *
 * @author Benjamin Eberlei (kontakt@beberlei.de)
 */
class ZFDoctrine_Import_Builder extends Doctrine_Import_Builder
{
    protected function _getBaseClassName($className)
    {
        $pos = strpos($className, 'Model_');
        $prefix = substr($className, 0, $pos+6);
        $className = $prefix . "Base_" . substr($className, $pos+6);

        return $className;
    }

    protected function _getFileName($originalClassName, $definition)
    {
        $originalClassName = str_replace("Model_Base_", "Model_", $originalClassName);

        $pos = strpos($originalClassName, 'Model_');
        $originalClassName = substr($originalClassName, $pos+6);

        $file = $originalClassName . $this->_suffix;

        return $file;
    }

    /**
     * writeTableClassDefinition
     *
     * @return void
     */
    public function writeTableClassDefinition(array $definition, $path, $options = array())
    {
        $className = $definition['tableClassName'];
        $pos = strpos($className, "Model_");
        $fileName = substr($className, $pos+6) . $this->_suffix;
        $writePath = $path . DIRECTORY_SEPARATOR . $fileName;

        $content = $this->buildTableClassDefinition($className, $definition, $options);

        Doctrine_Lib::makeDirectories(dirname($writePath));

        Doctrine_Core::loadModel($className, $writePath);

        if ( ! file_exists($writePath)) {
            file_put_contents($writePath, $content);
        }
    }

    public function writeDefinition(array $definition)
    {
        if (isset($definition['is_main_class']) && $definition['is_main_class'] === true) {
            $definition['inheritance']['extends'] = $this->_getBaseClassName($definition['className']);
        }
        return parent::writeDefinition($definition);
    }
}