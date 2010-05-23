<?php

class ZFDoctrine_Import_BuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ZFDoctrine_Import_Builder
     */
    private $builder;
    private $files = array();

    public function setUp()
    {
        $schema = new ZFDoctrine_Import_Schema();
        $defaultOptions = $schema->getOptions();

        $this->builder = new ZFDoctrine_Import_Builder();
        $this->builder->setOptions($defaultOptions);
    }

    public function tearDown()
    {
        foreach ($this->files AS $file) {
            unlink($file);
        }
    }

    public function testBuilderGeneratesBaseClass()
    {
        $tmpDir = sys_get_temp_dir();
        $this->files[] = $tmpDir . "/User.php";
        $this->files[] = $tmpDir . "/Base/User.php";

        $this->builder->setTargetPath($tmpDir);
        $this->builder->buildRecord($this->getSampleDefinition());

        $this->assertFilesAreGenerated();
    }

    public function testBuilderGeneratesTableClass()
    {
        $tmpDir = sys_get_temp_dir();
        $this->files[] = $tmpDir . "/UserTable.php";

        $this->builder->generateTableClasses(true);
        $this->builder->setTargetPath($tmpDir);
        $this->builder->buildRecord($this->getSampleDefinition());

        $this->assertFilesAreGenerated();
    }

    public function assertFilesAreGenerated()
    {
        foreach ($this->files AS $file) {
            $this->assertTrue(file_exists($file), "ZFDoctrine_Import_Builder::buildRecord() should generate " . $file);
        }
    }

    public function getSampleDefinition()
    {
        return array (
                'columns' =>
                array (
                        'username' =>
                        array (
                                'type' => 'string',
                                'name' => 'username',
                                'length' => '255',
                                'fixed' => NULL,
                                'primary' => NULL,
                                'default' => NULL,
                                'autoincrement' => NULL,
                                'sequence' => NULL,
                                'values' => NULL,
                        ),
                        'password' =>
                        array (
                                'type' => 'string',
                                'name' => 'password',
                                'length' => '255',
                                'fixed' => NULL,
                                'primary' => NULL,
                                'default' => NULL,
                                'autoincrement' => NULL,
                                'sequence' => NULL,
                                'values' => NULL,
                        ),
                        'contact_id' =>
                        array (
                                'type' => 'integer',
                                'name' => 'contact_id',
                                'length' => NULL,
                                'fixed' => NULL,
                                'primary' => NULL,
                                'default' => NULL,
                                'autoincrement' => NULL,
                                'sequence' => NULL,
                                'values' => NULL,
                        ),
                ),
                'relations' =>
                array (
                        'Contact' =>
                        array (
                                'class' => 'Addressbook_Model_Contact',
                                'local' => 'contact_id',
                                'foreign' => 'id',
                                'foreignAlias' => 'User',
                                'foreignType' => 0,
                                'type' => 0,
                                'alias' => 'Contact',
                                'key' => 'e4f8061a18e139244d8e5090f67c1178',
                        ),
                ),
                'abstract' => false,
                'className' => 'Model_User',
                'tableName' => 'model__user',
                'connection' => NULL,
                'indexes' =>
                array (
                ),
                'attributes' =>
                array (
                ),
                'templates' =>
                array (
                ),
                'actAs' =>
                array (
                ),
                'options' =>
                array (
                ),
                'package' => NULL,
                'inheritance' =>
                array (
                ),
                'detect_relations' => false,
                'connectionClassName' => 'Model_User',
                'modulePrefix' => 'Model_',
        );
    }
}