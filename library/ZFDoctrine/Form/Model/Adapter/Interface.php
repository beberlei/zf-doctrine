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

interface ZFDoctrine_Form_Model_Adapter_Interface
{
    /**
     * Return all columns as an array
     *
     * Array must contain 'type' for column type, 'notnull' true/false
     * for the column's nullability, and 'values' for enum values, 'primary'
     * true/false for primary key. Key = column's name
     *
     * @return array
     */
    public function getColumns();

    /**
     * Return relations as an array
     *
     * Array must contain 'type' for relation type, 'id' for the name
     * of the PK column of the related table, 'model' for the related model
     * name, 'notnull' for nullability. 'local' for the name of the local column
     * Key must be the alias of the relation column
     *
     * @return array
     */
    public function getManyRelations();

    /**
     * Return a related object, or null if not found
     * @param mixed $record the record where to look at
     * @param string $name name of the relation
     * @return mixed
     */
    public function getRelatedRecordId($record, $name);

    /**
     * Return the value of a record's unique identifier
     * @param mixed $record
     * @return mixed
     */
    public function getRecordIdentifier($record);

    /**
     * Get the records for a many-relation
     * @param string $name Name of the relation
     * @return array
     */
    public function getRelatedRecords($name);

    /**
     * Get records for a related class
     * 
     * @param array $class of the related model to retrieve all records from.
     * 
     * @return array array of records
     */
    public function getAllRecords($class);

    /**
     * Add a new record to a many-relation
     * @param string $name name of the relation
     * @param mixed $record the new record
     */
    public function addManyRecord($name, $record);

    /**
     * Delete a record
     * @param mixed $record
     */
    public function deleteRecord($record);

    /**
     * Set the table
     * @param string $table
     */
    public function setTable($table);

    /**
     * Returns the table
     * @return mixed
     */
    public function getTable();

    /**
     * set the record
     * @param mixed $instance
     */
    public function setRecord($instance);

    /**
     * Return the record
     * @return mixed|null Null on failure
     */
    public function getRecord();

    /**
     * Return a new instance of the record for this form
     * @return mixed
     */
    public function getNewRecord();

    /**
     * Save the record
     */
    public function saveRecord();

    /**
     * Return the value of a column
     * @param string $column name of the column
     * @return string
     */
    public function getRecordValue($column);

    /**
     * Set all the values retrieved from {@see getValues()} of Zend_Form.
     * 
     * @param array $values
     */
    public function setRecordValues($values);
}
