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
 * Doctrine Exception
 *
 * @uses       Zend_Exception
 * @category   Zend
 * @package    Doctrine
 * @subpackage Core
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
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
}