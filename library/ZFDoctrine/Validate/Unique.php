<?php
class ZFDoctrine_Validate_Unique extends Zend_Validate_Abstract
{
    /** Error constants
     */
    const ERROR_RECORD_FOUND    = 'recordFound';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_RECORD_FOUND    => 'A record matching "%value%" was found.',
    );

    /**
     * Model name
     * @var string
     */
    private $_model;

    /**
     * Returns the field names
     *
     * @var array
     */
    private $_fields;

    /**
     * Returns the model name
     *
     * @return string Model name
     */
    public function getModel ()
    {
        return $this->_model;
    }

    /**
     * Returns the fields
     *
     * @return array Fields
     */
    public function getFields ()
    {
        return $this->_fields;
    }

    /**
     * Returns true if multiple fields are configured
     *
     * @return boolean True if multiple fields are configured
     */
    private function hasMultipleFields()
    {
        return count($this->_fields) > 1;
    }

    /**
     * Sets the model name
     *
     * @param string $model Model name
     */
    public function setModel($model)
    {
        $this->_model = $model;
    }

    /**
     * Sets the field names
     *
     * @param array $fields Field names
     */
    public function setFields ($fields)
    {
      $this->_fields = $fields;
    }

    /**
     * Constructor
     *
     * The following option keys are supported:
     * 'model'  => The model to validate against
     * 'fields' => The fields to check for a match
     *
     * @param array|Zend_Config $options Options to use for this validator
     */
    public function __construct($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (func_num_args() > 1) {
            $options        = func_get_args();
            $temp['model']  = array_shift($options);
            $temp['fields'] = array_shift($options);

            $options = $temp;
        }

        if (!array_key_exists('model', $options)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Model option missing!');
        }
        $this->setModel($options['model']);

        if (!array_key_exists('fields', $options)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Fields option missing!');
        }
        if (!is_array($options['fields'])) {
            $options['fields'] = array($options['fields']);
        }

        $this->setFields($options['fields']);
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * @param mixed $value Value
     * @param array $context Context
     * @return boolean True if $value is valid in context of $context
     * @throws Zend_Valid_Exception If validation of $value is impossible
     * @see Zend_Validate_Interface::isValid()
     */
    public function isValid($value, $context = null)
    {
        if ($this->hasMultipleFields() && is_null($context)) {
            throw new Zend_Validate_Exception('Multiple fields configured but no context passed.');
        }

        $table = ZFDoctrine_Core::getTable($this->getModel());

        $fields = $this->getFields();

        $method = 'findOneBy'.implode(array_map('ucfirst', $fields), 'And');

        if (!$this->hasMultipleFields()) {
            $record = call_user_func(array($table, $method), $value);
        } else {
            foreach ($this->getFields() as $f) {
                if (!isset($context[$f])) {
                    throw new Zend_Validate_Exception(sprintf('Field "%s" not in context.', $f));
                }
            }

            $params = $context;
            foreach (array_keys($params) as $key) {
                if (!in_array($key, $fields)) {
                    unset($params[$key]);
                }
            }

            $record = call_user_func_array(array($table, $method), $params);
        }

        // if no object or if we're updating the object, it's ok
        if (!$record || $this->isUpdate($record, $context)) {
          return true;
        }

        $this->_setValue($value);
        $this->_error(self::ERROR_RECORD_FOUND);

        return false;
    }

    /**
     * Returns whether the object is being updated.
     *
     * @param Doctrine_Record Doctrine record
     * @param array An array of values
     *
     * @return bool True if the object is being updated, false otherwise
     */
    protected function isUpdate(Doctrine_Record $record, $values)
    {

        // check each primary key column
        foreach ($this->getPrimaryKeys() as $column) {
            if (!isset($values[$column]) || $record->$column != $values[$column]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the primary keys for the model.
     *
     * @return array An array of primary keys
     */
    protected function getPrimaryKeys()
    {
        $primaryKeys = Doctrine_Core::getTable($this->getModel())->getIdentifier();

        if (!is_array($primaryKeys)) {
            $primaryKeys = array($primaryKeys);
        }

        return $primaryKeys;
    }
}