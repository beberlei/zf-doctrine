<?php

class ZFDoctrine_Tool_Context_SqlDirectory extends Zend_Tool_Project_Context_Filesystem_Directory
{
    /**
     * @var string
     */
    protected $_filesystemName = 'sql';

    /**
     * @return string
     */
    public function getName()
    {
        return 'SqlDirectory';
    }
}
