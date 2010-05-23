<?php
class ZFDoctrine_Form_Model_Adapter_Doctrine implements ZFDoctrine_Form_Model_Adapter_Interface
{
    /**
     * @var Doctrine_Table
     */
    protected $_table = null;

    /**
     * @var Doctrine_Record
     */
    protected $_record = null;
    protected $_model = '';
    protected $_cols = null;
    protected $_relations = null;

    public function setTable($table) {
        $this->_table = Doctrine::getTable($table);
        $this->_model = $table;
    }

    public function getTable() {
        return $this->_table;
    }

    public function setRecord($record) {
        if(($record instanceof Doctrine_Record) === false)
            throw new InvalidArgumentException('Record not a Doctrine_Record');

        $this->_record = $record;

    }

    public function getRecord() {
        return $this->_record;
    }

    public function saveRecord() {
        $this->_record->save();
    }

    public function getRecordValue($name) {
        return $this->_record->$name;
    }

    public function setRecordValues($values)
    {
        $this->_record->fromArray($values);
    }

    /**
     * Return all columns as an array
     *
     * Array must contain 'type' for column type, 'notnull' true/false
     * for the column's nullability, and 'values' for enum values, 'primary'
     * true/false for primary key. Key = column's name
     *
     * @return array
     */
    public function getColumns() {
        $foreignKeyColumns = array();
        foreach ($this->_table->getRelations() AS $alias => $relation) {
            $localColumn = strtolower($relation['local']);
            $foreignKeyColumns[$localColumn] = $relation['class'];
        }

        $data = $this->_table->getColumns();
        $cols = array();
        foreach($data as $name => $def) {
            $isPrimary = (isset($def['primary'])) ? $def['primary'] : false;
            $isForeignKey = isset($foreignKeyColumns[strtolower($name)]);

            $columnName = $this->_table->getColumnName($name);
            $fieldName = $this->_table->getFieldName($columnName);

            $cols[$fieldName] = array(
                'type'          => $def['type'],
                'notnull'       => (isset($def['notnull'])) ? $def['notnull'] : false,
                'values'        => (isset($def['values'])) ? $def['values'] : array(),
                'primary'       => $isPrimary,
                'foreignKey'    => $isForeignKey,
                'class'         => ($isForeignKey) ? $foreignKeyColumns[strtolower($name)] : null,
            );
        }

        return $cols;
    }

    /**
     * Return relations as an array
     *
     * Array must contain 'type' for relation type, 'id' for the name
     * of the PK column of the related table, 'model' for the related class
     * name, 'notnull' for nullability. 'local' for the name of the local column
     * Key must be the alias of the relation column
     *
     * @return array
     */
    public function getManyRelations() {
        $rels = $this->_table->getRelations();
        $relations = array();

        foreach($rels as $rel) {
            $relation = array();

            if($rel->getType() == Doctrine_Relation::MANY && isset($rel['refTable'])) {
                $relation['id'] = $rel->getTable()->getIdentifier();
                $relation['model'] = $rel->getClass();
                $relation['local'] = $rel->getLocal();

                $definition = $this->_table->getColumnDefinition($rel->getLocal());
                $relation['notnull'] = (isset($definition['notnull']))
                        ? $definition['notnull']
                        : false;

                $relations[$rel->getAlias()] = $relation;
            }
        }

        return $relations;
    }

    /**
     *
     * @param Doctrine_Record $record
     * @param string $name
     * @return string
     */
    public function getRelatedRecordId($record, $name) {
        return $record->$name;
    }

    /**
     * Return the value of a record's primary key
     * @param Doctrine_Record $record
     * @return mixed
     */
    public function getRecordIdentifier($record) {
        $col = $record->getTable()->getIdentifier();
        return $record->$col;
    }

    /**
     * Get the records for a many-relation
     * @param string $name Name of the relation
     * @return array
     */
    public function getRelatedRecords($name) {
        return $this->_record->$name;
    }

    public function addManyRecord($name, $record) {
        $this->_record->{$name}[] = $record;
    }

    public function getAllRecords($class) {
        return Doctrine::getTable($class)->findAll();
    }

    public function deleteRecord($record) {
        $record->delete();
    }

    public function getNewRecord() {
        return new $this->_model;
    }
}
