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

class ZFDoctrine_Tool_ResponseImportListener implements ZFDoctrine_Import_ImportListener
{
    /**
     * @var Zend_Tool_Framework_Client_Response 
     */
    private $_response = null;

    private $_builtCount = 0;

    public function __construct(Zend_Tool_Framework_Client_Response $response)
    {
        $this->_response = $response;
    }

    /**
     * @param string $className
     * @param string $moduleName
     */
    public function notifyRecordBuilt($className, $moduleName)
    {
        if ($moduleName == null) {
            $this->_response->appendContent("[Doctrine] Generated record '$className'.", array('color' => 'green'));
        } else {
            $this->_response->appendContent("[Doctrine] Generated record '$className' in Module '$moduleName'.", array('color' => 'green'));
        }
        $this->_builtCount++;
    }

    public function notifyImportCompleted()
    {
        $this->_response->appendContent(
            '[Doctrine] Successfully generated '.$this->_builtCount.' record classes.', array('color' => 'green')
        );
    }
}