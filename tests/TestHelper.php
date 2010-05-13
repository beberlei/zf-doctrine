<?php

require_once 'PHPUnit/Framework.php';

if (!defined('ZEND_DOCTRINE_DC12_DIRECTORY')) {
    throw new InvalidArgumentException('The ZEND_DOCTRINE_DC12_DIRECTORY is not defined, but required for testing.');
}
if (!defined('ZEND_DOCTRINE_DC12_SFYAML')) {
    throw new InvalidArgumentException('The ZEND_DOCTRINE_DC12_SFYAML is not defined, but required for testing.');
}

set_include_path(
    dirname(__FILE__)."/../library" . PATH_SEPARATOR .
    ZEND_DOCTRINE_DC12_DIRECTORY . PATH_SEPARATOR . get_include_path()
);

if (defined('ZEND_LIBRARY') && strlen('ZEND_LIBRARY') > 0) {
    set_include_path(ZEND_LIBRARY . PATH_SEPARATOR . get_include_path());
}

require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Doctrine');
$loader->registerNamespace('ZFDoctrine');

require_once(ZEND_DOCTRINE_DC12_SFYAML);