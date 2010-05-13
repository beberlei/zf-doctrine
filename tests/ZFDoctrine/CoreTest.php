<?php

class ZFDoctrine_CoreTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Zend_Controller_Front::getInstance()->resetInstance();
    }

    public function testLoadModelsZendStyle()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->addControllerDirectory(dirname(__FILE__)."/_files/controllers");
        $front->addModuleDirectory(dirname(__FILE__)."/_files/modules");

        $models = ZFDoctrine_Core::loadAllZendModels();

        $this->assertEquals(2, count($models));
        $this->assertContains('Model_User', $models);
        $this->assertContains('Blog_Model_Post', $models);
    }
}