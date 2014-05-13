<?php
/**
 * ZFDoctrine
 *
 * Copyright (c) 2010, Benjamin Eberlei
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the ZFDoctrine nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Benjamin Eberlei BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class ZFDoctrine_Controller_Helper_ModelForm extends Zend_Controller_Action_Helper_Abstract
{
    private $_recordIdParam = null;

    public function getRecordIdParam() {
        return $this->_recordIdParam;
    }

    public function setRecordIdParam($param)
    {
        $this->_recordIdParam = $param;
        return $this;
    }

    /**
     * @param ZFDoctrine_Form_Model $form
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     */
    public function direct(ZFDoctrine_Form_Model $form, $action = null, $controller = null, $module = null, array $params = array())
    {
        $this->handleForm($form, $action, $controller, $module, $params);
    }

    /**
     * Handle Create or Update Workflow of a ZFDoctrine_Form_Model instance
     *
     * @throws ZFDoctrine_DoctrineException
     * @param ZFDoctrine_Form_Model $form
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return void
     */
    public function handleForm(ZFDoctrine_Form_Model $form, $action = null, $controller = null, $module = null, array $params = array())
    {
        $actionController = $this->getActionController();
        $request = $actionController->getRequest();

        if (!$action) {
            $action = $this->getActionController();
        }

        $id = null;
        $actionParams = array();
        if ($this->getRecordIdParam()) {
            $id = $request->getParam($this->getRecordIdParam());
            if ($id) {
                $actionParams = array($this->getRecordIdParam() => $id);
                $table = Doctrine_Core::getTable($form->getModelName());
                $record = $table->find($id);
                if (!$record) {
                    throw new ZFDoctrine_DoctrineException("Cannot find record with given id.");
                }
                $actionController->view->assign('recordId', $id);
                $actionController->view->assign('record', $record);
                $form->setRecord($record);
            }
        }

        $urlHelper = $actionController->getHelper('url');
        $form->setMethod('post');
        $form->setAction($urlHelper->simple(
            $request->getActionName(),
            $request->getControllerName(),
            $request->getModuleName(),
            $actionParams
        ));

        if ($request->isPost() && $form->isValid($request->getParams())) {
            $form->save();

            $redirector = $actionController->getHelper('redirector');
            return $redirector->gotoSimple($action, $controller, $module, $params);
        }

        $actionController->view->assign('form', $form);
    }
}