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

class ZFDoctrine_View_Helper_ModelList extends Zend_View_Helper_Abstract
{
    /**
     * @var array
     */
    static private $_defaultOptions = array(
        'pageParamName'      => 'page',
        'paginationStyle'    => 'Sliding',
        'paginationScript'   => null,
        'addRecordAction'    => null,
        'editRecordAction'   => null,
        'deleteRecordAction' => null,
        'addRecordUrl'       => null,
        'editRecordUrl'      => null,
        'deleteRecordUrl'    => null,
        'itemsPerPage'       => 30,
        'listScript'         => null,
        'recordIdParam'      => 'id',
    );

    static private $_enabledView = array();

    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);
        $oid = spl_object_hash($view);
        if (!isset(self::$_enabledView[$oid])) {
            $view->addBasePath(dirname(__FILE__) . '/files');
            self::$_enabledView[$oid] = true;
        }
    }

    /**
     * @param string $modelName
     * @param array $options
     * @return string
     */
    public function modelList($modelName, array $options = array(), Doctrine_Query_Abstract $query = null)
    {
        $options = array_merge(self::$_defaultOptions, $options);

        if (!$options['paginationScript']) {
            $options['paginationScript'] = 'pagination.phtml';
        }

        $table = Doctrine_Core::getTable($modelName);

        if (!$query) {
          $query = $table->createQuery();
        }

        $adapter = new ZFDoctrine_Paginator_Adapter_DoctrineQuery($query);
        $paginator = new Zend_Paginator($adapter);

        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();
        $currentPage = $request->getParam('page', 1);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($options['itemsPerPage']);

        if (!isset($options['listScript'])) {
            $options['listScript'] = 'list.phtml';
        }

        if (!isset($options['showFieldNames'])) {
            $fieldNames = $this->getAutoFieldNames($table);
        } else {
            $fieldNames = $options['showFieldNames'];
        }

        if (isset($options['addRecordAction'])) {
            if (!isset($options['addRecordUrl'])) {
                $options['addRecordUrl'] = array();
            }

            $options['addRecordUrl']['action'] = $options['addRecordAction'];
        }

        if (isset($options['editRecordAction'])) {
            if (!isset($options['editRecordUrl'])) {
                $options['editRecordUrl'] = array();
            }

            $options['editRecordUrl']['action'] = $options['editRecordAction'];
        }

        if (isset($options['deleteRecordAction'])) {
            if (!isset($options['deleteRecordUrl'])) {
                $options['deleteRecordUrl'] = array();
            }

            $options['deleteRecordUrl']['action'] = $options['deleteRecordAction'];
        }

        return $this->view->partial($options['listScript'], array(
            'modelName'   => $modelName,
            'paginator'   => $paginator,
            'currentPage' => $currentPage,
            'options'     => $options,
            'fieldNames'  => $fieldNames,
        ));
    }

    /**
     * @return array
     */
    private function getAutoFieldNames($table) {
        $data = $table->getColumns();
        $cols = array();
        foreach($data as $name => $def) {
            $columnName = $table->getColumnName($name);
            $fieldName = $table->getFieldName($columnName);

            $cols[] = $fieldName;
        }

        return $cols;
    }
}