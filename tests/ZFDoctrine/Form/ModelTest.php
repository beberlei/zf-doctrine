<?php

class ZFDoctrine_Form_ModelTest extends PHPUnit_Framework_TestCase
{
    private $_adapter;

    private $_columns = array();
    private $_relations = array();

    public function setUp() {
        parent::setUp();

        $this->_columns['User'] = array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'values' => array(),
                'primary' => true,
                'foreignKey' => false,
                'class' => false,
            ),
            'login' => array(
                'type' => 'string',
                'notnull' => true,
                'values' => array(),
                'primary' => false,
                'foreignKey' => false,
                'class' => false,
            ),
            'password' => array(
                'type' => 'string',
                'notnull' => true,
                'values' => array(),
                'primary' => false,
                'foreignKey' => false,
                'class' => false,
            )
        );

        $this->_relations['User'] = array();

        $this->_columns['Comment'] = array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'values' => array(),
                'primary' => true,
                'foreignKey' => false,
                'class' => false,
            ),
            'sender' => array(
                'type' => 'string',
                'notnull' => true,
                'values' => array(),
                'primary' => false,
                'foreignKey' => false,
                'class' => false,
            ),
            'article_id' => array(
                'type' => 'integer',
                'notnull' => true,
                'values' => array(),
                'primary' => false,
                'foreignKey' => true,
                'class' => 'Article',
            )
        );

        $this->_relations['Comment'] = array();

        $this->_columns['Article'] = array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'values' => array(),
                'primary' => true,
                'foreignKey' => false,
            ),
            'name' => array(
                'type' => 'string',
                'notnull' => true,
                'values' => array(),
                'primary' => false,
                'foreignKey' => false,
            )
        );

        $this->_relations['Article'] = array(
            'Article' => array(
                'id' => 'id',
                'model' => 'Article',
                'notnull' => false,
                'local' => 'article_id'
            )
        );
    }

    public function testNoModelFails() {
        $this->setExpectedException('ZFDoctrine_DoctrineException');
        $form = new ZFDoctrine_Form_Model();
    }

    private function _initAdapter($table) {
        $this->_adapter = $this->getMock('ZFDoctrine_Form_Model_Adapter_Interface');

        $this->_adapter->expects($this->any())
                ->method('setTable')
                ->with($this->equalTo($table));

        //Should be called on $form->getTable()
        $this->_adapter->expects($this->any())
                ->method('getTable')
                ->will($this->returnValue($table));

        $this->_adapter->expects($this->any())
                ->method('getColumns')
                ->will($this->returnValue($this->_columns[$table]));

        $this->_adapter->expects($this->any())
                ->method('getManyRelations')
                ->will($this->returnValue($this->_relations[$table]));
    }

    public function testTableLoading() {
        $this->_initAdapter('User');

        $form = new ZFDoctrine_Form_Model(array(
                        'model' => 'User',
                        'adapter' => $this->_adapter
        ));

        $this->assertEquals('User', $form->getTable());
    }

    public function testColumnIgnoring() {
        $this->_initAdapter('User');

        $form = new ZFDoctrine_Form_Model(array(
                        'model' => 'User',
                        'adapter' => $this->_adapter,
                        'ignoreFields' => array('login')
        ));

        $this->assertNull($form->getElement('login'));
        $this->assertNotNull($form->getElement('password'));
        $this->assertEquals('password', $form->getElement('password')->getName());
    }

    public function testPrimaryKeyIgnored() {
        $this->_initAdapter('User');

        $form = new ZFDoctrine_Form_Model(array(
                        'model' => 'User',
                        'adapter' => $this->_adapter
        ));

        $this->assertNull($form->getElement('id'));
    }

    public function testZendFormParametersPass() {
        $this->_initAdapter('User');

        $form = new ZFDoctrine_Form_Model(array(
                        'model' => 'User',
                        'action' => 'test',
                        'adapter' => $this->_adapter
        ));

        $this->assertEquals('test', $form->getAction());
    }

    public function testRecordLoading() {
        $this->_initAdapter('User');

        $this->_adapter->expects($this->any())
                ->method('getRecord')
                ->will($this->onConsecutiveCalls(false, true));

        $this->_adapter->expects($this->once())
                ->method('getNewRecord')
                ->will($this->returnValue(false));

        $form = new ZFDoctrine_Form_Model(array(
                        'model' => 'User',
                        'adapter' => $this->_adapter
        ));

        //First getRecord is set up to return false
        $this->assertFalse($form->getRecord());

        $user = array(
                'login' => 'Login',
                'password' => 'Password'
        );

        $this->_adapter->expects($this->once())
                ->method('setRecord')
                ->with($this->equalTo($user));

        //NOTE: will cause a wrong value to be inputted into the password field!
        $this->_adapter->expects($this->any())
                ->method('getRecordValue')
                ->will($this->returnValue('Login'));

        $form->setRecord($user);

        //Second getRecord is set up to return true
        $this->assertTrue($form->getRecord());

        $this->assertEquals('Login', $form->getElement('login')->getValue());
    }

    public function testRecordSaving() {
        $this->_initAdapter('User');

        $form = new ZFDoctrine_Form_Model(array(
                        'model' => 'User',
                        'adapter' => $this->_adapter
        ));

        $form->getElement('login')->setValue('Test');
        $form->getElement('password')->setValue('Test');

        //Should not get called if persist param is false
        $this->_adapter->expects($this->never())
                ->method('saveRecord');

        $form->save(false);

        $this->_initAdapter('User');
        $form->setAdapter($this->_adapter);

        $this->_adapter->expects($this->once())
                ->method('saveRecord');

        //Should get called twice as we set two values
        $this->_adapter->expects($this->once())
                ->method('setRecordValues')
                ->with($this->isType('array'));

        $record = $form->save();
    }

    public function testEventHooks() {
        $this->_initAdapter('User');

        $form = new ZFDoctrine_Form_ModelTest_Form(array(
            'model' => 'User',
            'adapter' => $this->_adapter
        ));

        $this->assertTrue($form->preGenerated);
        $this->assertTrue($form->postGenerated);
        $this->assertFalse($form->postSaved);

        $form->save(false);

        $this->assertTrue($form->postSaved);
    }

    public function testCreatingFormWithOneRelation() {
        $this->_initAdapter('Comment');

        $this->_adapter->expects($this->once())
                ->method('getAllRecords')
                ->with($this->equalTo('Article'))
                ->will($this->returnValue(array()));


        $form = new ZFDoctrine_Form_Model(array(
            'model' => 'Comment',
            'adapter' => $this->_adapter
        ));

        $elem = $form->getElement('article_id');
        $this->assertType('Zend_Form_Element', $elem);
        $this->assertEquals($elem->getName(), 'article_id');
    }

    public function testCreatingFormWithManyRelation() {
        $this->_initAdapter('Article');

        $this->_adapter->expects($this->once())
                ->method('getAllRecords')
                ->with($this->equalTo('Article'))
                ->will($this->returnValue(array()));

        $form = new ZFDoctrine_Form_Model(array(
            'model' => 'Article',
            'adapter' => $this->_adapter
        ));

        $elem = $form->getElement('Article');
        $this->assertType('Zend_Form_Element', $elem);
        $this->assertEquals($elem->getName(), 'Article');
    }

    public function testNotNullColumnsAreRequired() {
        $this->_initAdapter('Comment');

        $this->_adapter->expects($this->any())
                ->method('getAllRecords')
                ->with($this->equalTo('Article'))
                ->will($this->returnValue(array()));

        $form = new ZFDoctrine_Form_Model(array(
            'model' => 'Comment',
            'adapter' => $this->_adapter
        ));

        $this->assertType('Zend_Form_Element', $form->getElement('sender'));
        $this->assertFalse($form->getElement('sender')->isValid(''));
        $this->assertType('Zend_Form_Element', $form->getElement('article_id'));
        $this->assertFalse($form->getElement('article_id')->isValid(''));
    }

    public function testOneRelationSaving() {
        $this->_initAdapter('Comment');

        $article = array(
            'id' => 1,
            'name' => 'Test'
        );

        $this->_adapter->expects($this->once())
                ->method('getAllRecords')
                ->with($this->equalTo('Article'))
                ->will($this->returnValue(array($article)));

        $form = new ZFDoctrine_Form_Model(array(
            'model' => 'Comment',
            'adapter' => $this->_adapter
        ));

        $this->assertType('Zend_Form_Element', $form->getElement('article_id'));
        $form->getElement('article_id')->setValue(1);
        $form->getElement('sender')->setValue('Sender');

        $this->_adapter->expects($this->any())
                ->method('setRecordValues');

        $form->save();
    }
}

class ZFDoctrine_Form_ModelTest_Form extends ZFDoctrine_Form_Model {
    public $preGenerated = false;
    public $postGenerated = false;
    public $postSaved = false;

    protected function _preGenerate() {
        $this->preGenerated = true;
    }

    protected function _postGenerate() {
        $this->postGenerated = true;
    }

    protected function _postSave($persist) {
        $this->postSaved = true;
    }
}
