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
 * Class for autogenerating forms based on Doctrine models
 * 
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class ZFDoctrine_Form_Model extends Zend_Form
{
    const RELATION_ONE = 'one';
    const RELATION_MANY = 'many';

    /**
     *
     * @var ZFDoctrine_Form_Model_Adapter_Interface
     */
    protected $_adapter = null;

    /**
     * PluginLoader for loading many relation forms
     */
    const FORM = 'form';

    /**
     * Which Zend_Form element types are associated with which doctrine type?
     * @var array
     */
    protected $_columnTypes = array(
        'integer' => 'text',
        'decimal' => 'text',
        'float' => 'text',
        'string' => 'text',
        'varchar' => 'text',
        'boolean' => 'checkbox',
        'timestamp' => 'text',
        'time' => 'text',
        'date' => 'text',
        'enum' => 'select',
    );

    /**
     * Default validators for doctrine column types
     * @var array
     */
    protected $_columnValidators = array(
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float'
    );

    /**
     * Field names listed in this array will not be shown in the form
     *
     * @var array
     */
    protected $_ignoreFields = array();

    /**
     * Use this to override field types for columns. key = column, value = field type
     * @var array
     */
    protected $_fieldTypes = array();

    /**
     * Field labels. key = column name, value = label
     * @var array
     */
    protected $_fieldLabels = array();

    /**
     * @var bool
     */
    protected $_generateManyFields = true;

    /**
     * Name of the model class
     * @var string
     */
    protected $_model = '';

    /**
     * @var string
     */
    protected $_relations = array();

    /**
     * @param array $options Options to pass to the Zend_Form constructor
     */
    public function __construct($options = null) {
        parent::__construct($options);

        if($this->_model == '') {
            throw new ZFDoctrine_DoctrineException('No model defined for form generation');
        }
        if($this->_adapter == null) {
            $this->setAdapter(new ZFDoctrine_Form_Model_Adapter_Doctrine());
        }

        $this->addElementPrefixPath('ZFDoctrine', 'ZFDoctrine');

        $this->_preGenerate();
        $this->_generateForm();
        $this->_postGenerate();
    }

    /**
     * @return ZFDoctrine_Form_Model_Adapter_Interface
     */
    public function getAdapter() {
        return $this->_adapter;
    }

    public function setAdapter(ZFDoctrine_Form_Model_Adapter_Interface $adapter) {
        $this->_adapter = $adapter;
        $this->_adapter->setTable($this->_model);
    }

    public function getModelName()
    {
        return $this->_model;
    }

    public function setOptions(array $options) {
        if(isset($options['model'])) {
            $this->_model = $options['model'];
        }

        //adapter must be set after the model
        if(isset($options['adapter'])) {
            $this->setAdapter($options['adapter']);
        }

        if(isset($options['ignoreFields'])) {
            $this->setIgnoreFields($options['ignoreFields']);
        }

        if(isset($options['columnTypes'])) {
            $this->setColumnTypes($options['columnTypes']);
        }

        if(isset($options['fieldLabels'])) {
            $this->setFieldLabels($options['fieldLabels']);
        }

        if (isset($options['fieldTypes'])) {
            $this->setFieldTypes($options['fieldTypes']);
        }

        if (isset($options['generateManyFields'])) {
            $this->setGenerateManyFields($options['generateManyFields']);
        }

        parent::setOptions($options);
    }

    public function setGenerateManyFields($flag)
    {
        $this->_generateManyFields = $flag;
    }

    public function setFieldLabels(array $labels) {
        $this->_fieldLabels = $labels;
    }

    public function setFieldTypes(array $types) {
        $this->_fieldTypes = $types;
    }

    public function setColumnTypes(array $types) {
        $this->_columnTypes = $types;
    }

    public function setIgnoreFields(array $columns) {
        $this->_ignoreFields = $columns;
    }


    /**
     * Override to provide custom pre-form generation logic
     */
    protected function _preGenerate() {
    }

    /**
     * Override to provide custom post-form generation logic
     */
    protected function _postGenerate() {
    }

    /**
     * Override to provide custom post-save logic
     */
    protected function _postSave($persist) {
    }

    public function getTable() {
        return $this->_adapter->getTable();
    }

    /**
     * Set the model instance for editing existing rows
     *
     * @param Doctrine_Record $instance
     */
    public function setRecord($instance)
    {
        $this->_adapter->setRecord($instance);
        foreach($this->_adapter->getColumns() as $name => $definition) {
            if($this->_isIgnoredField($name, $definition)) {
                continue;
            }

            if ($definition['foreignKey']) {
                $relatedId = $this->_adapter->getRelatedRecordId($instance, $name);
                $this->setDefault($name, $relatedId);
            } else {
                $this->setDefault($name, $this->_adapter->getRecordValue($name));
            }
        }

        if ($this->_generateManyFields) {
            foreach($this->_adapter->getManyRelations() as $alias => $relation) {
                if($this->_isIgnoredField($alias, $relation)) {
                    continue;
                }

                $defaults = array();
                foreach($this->_adapter->getRelatedRecords($alias) as $num => $rec) {
                    $defaults[] = $this->_adapter->getRecordIdentifier($rec);
                }
                $this->setDefault($alias, $defaults);
            }
        }
    }

    public function getRecord() {
        $inst = $this->_adapter->getRecord();
        if($inst == null) {
            $inst = $this->_adapter->getNewRecord();
            $this->_adapter->setRecord($inst);
        }

        return $inst;
    }

    /**
     * Generates the form
     */
    protected function _generateForm() {
        $this->_columnsToFields();
        if ($this->_generateManyFields) {
            $this->_manyRelationsToFields();
        }

        $this->addElement('submit', 'Save');
    }

    /**
     * Parses columns to fields
     */
    protected function _columnsToFields()
    {
        foreach($this->_adapter->getColumns() as $name => $definition) {
            if($this->_isIgnoredField($name, $definition)) {
                continue;
            }

            if(isset($this->_fieldTypes[$name])) {
                $type = $this->_fieldTypes[$name];
            } else if ($definition['foreignKey']) {
                $type = 'select';
            } else {
                $type = $this->_columnTypes[$definition['type']];
            }

            $field = $this->createElement($type, $name);
            if(isset($this->_fieldLabels[$name])) {
                $label = $this->_fieldLabels[$name];
            } else {
                $label = $name;
            }

            if(isset($this->_columnValidators[$definition['type']])) {
                $field->addValidator($this->_columnValidators[$definition['type']]);
            }

            if(isset($definition['notnull']) && $definition['notnull'] == true) {
                $field->setRequired(true);
            }

            if ($type != 'hidden') {
                $field->setLabel($label);
            }

            if($type == 'select' && $definition['type'] == 'enum') {
                foreach($definition['values'] as $text) {
                    $field->addMultiOption($text, ucwords($text));
                }
            } else if($definition['foreignKey'] && $field instanceof Zend_Form_Element_Multi) {
                $options = array('------');
                foreach ($this->_adapter->getAllRecords($definition['class']) AS $record) {
                    $options[$this->_adapter->getRecordIdentifier($record)] = (string)$record;
                }
                $field->setMultiOptions($options);
            }

            $this->addElement($field);
        }
    }

    /**
     * Parses relations to fields
     */
    protected function _manyRelationsToFields()
    {
        foreach($this->_adapter->getManyRelations() as $alias => $relation) {
            if($this->_isIgnoredField($alias, $relation)) {
                continue;
            }

            if(isset($this->_fieldLabels[$alias])) {
                $label = $this->_fieldLabels[$alias];
            } else {
                $label = $relation['model'];
            }

            $options = array('------');
            foreach($this->_adapter->getAllRecords($relation['model']) as $record) {
                $options[$this->_adapter->getRecordIdentifier($record)] = (string)$record;
            }
            $field = $this->createElement('multiselect', $alias);
            $field->setLabel($label);
            $field->setMultiOptions($options);

            $this->addElement($field);
        }
    }

    /**
     * Should this field be ignored in rendering?
     *
     * @param  string $name
     * @param  array $definition
     * @return bool
     */
    protected function _isIgnoredField($name, $definition) {
        if(isset($definition['primary']) && $definition['primary']) {
            return true;
        } else if (in_array($name, $this->_ignoreFields)) {
            return true;
        } else if (isset($definition['type']) && !isset($this->_columnTypes[$definition['type']])) {
            return true;
        }

        return false;
    }

    /**
     * Save the form data
     * @param bool $persist Save to DB or not
     * @return Doctrine_Record
     */
    public function save($persist = true) {
        $inst = $this->getRecord();

        $values = $this->getValues();
        $this->_adapter->setRecordValues($values);

        if($persist) {
            $this->_adapter->saveRecord();
        }

        foreach($this->getSubForms() as $subForm) {
            if ($subForm instanceof ZFDoctrine_Form_ModelSubForm) {
                $subForm->save($persist);
            }
        }

        $this->_postSave($persist);
        return $inst;
    }
}


