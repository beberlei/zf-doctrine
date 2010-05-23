<?php

class ZFDoctrine_CoreTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // singleton-mania! ;)
        Zend_Controller_Front::getInstance()->resetInstance();
        Doctrine_Manager::resetInstance();
        ZFDoctrine_Core::resetModelDirectories();
    }

    public function testLoadModelsZendStyle()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->addControllerDirectory(dirname(__FILE__)."/_files/controllers");
        $front->addModuleDirectory(dirname(__FILE__)."/_files/modules");

        $manager = Doctrine_Manager::getInstance();
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, ZFDoctrine_Core::MODEL_LOADING_ZEND);

        $models = ZFDoctrine_Core::loadAllZendModels();

        $this->assertEquals(2, count($models));
        var_dump($models);
        $this->assertContains('Default_Model_User', $models);
        $this->assertContains('Blog_Model_Post', $models);
    }

    public function testLoadModelsZendSingleLibraryStyle()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->addControllerDirectory(dirname(__FILE__)."/_files/controllers");
        $front->addModuleDirectory(dirname(__FILE__)."/_files/modules");

        ZFDoctrine_Core::setSingleLibraryPath(dirname(__FILE__) . "/_files/library");

        $directories = ZFDoctrine_Core::getAllModelDirectories();
        $models = ZFDoctrine_Core::loadAllZendModels();

        $this->assertEquals(2, count($models));
        $this->assertContains('Default_Model_Group', $models);
        $this->assertContains('Blog_Model_Category', $models);
    }
}