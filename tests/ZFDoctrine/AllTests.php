<?php

require_once "CoreTest.php";
require_once "Import/SchemaTest.php";
require_once "Application/Resource/DoctrineTest.php";

class ZFDoctrine_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTestSuite('ZFDoctrine_Application_Resource_DoctrineTest');
        $suite->addTestSuite('ZFDoctrine_CoreTest');
        $suite->addTestSuite('ZFDoctrine_Import_SchemaTest');

        return $suite;
    }
}