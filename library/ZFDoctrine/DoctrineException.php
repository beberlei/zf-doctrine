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

class ZFDoctrine_DoctrineException extends Zend_Exception
{
    static public function doctrineNotFound()
    {
        return new self('Could not find Doctrine library in project and include path.');
    }

    /**
     * @param string $className
     * @return ZFDoctrine_DoctrineException
     */
    static public function invalidZendModel($className)
    {
        return new self('Found an invalid model class '.$className.' which is not following the required Zend style, i.e '.
            'Model_ClassName for the default module or ModuleName_Model_ClassName for the models in non-default modules.');
    }

    static public function unknownModule($moduleName, $className)
    {
        return new self(
            "Unknown Zend Controller Module '".$moduleName."' inflected from model class '".$className."'. ".
            "Have you configured your front-controller to include modules?");
    }

    static public function invalidLibraryPath($path)
    {
        return new self("Invalid library path specified, " . $path . " could not be found.");
    }

    static public function libraryPathMissing()
    {
        return new self("To use the ZEND_SINGLE_LIBRARY model loading mode you have to specify ".
            "a library path using ZFDoctrine_Core::setSingleLibraryPath()");
    }

    static public function invalidZendStyle()
    {
        return new self('Invalid Zend Model Loading Style configured.');
    }
}