<?php

class ZFDoctrine_Tool_Context_DataFixturesDirectory extends Zend_Tool_Project_Context_Filesystem_Directory
{
    /**
     * @var string
     */
    protected $_filesystemName = 'fixtures';

    /**
     * @return string
     */
    public function getName()
    {
        return 'DataFixturesDirectory';
    }
}
