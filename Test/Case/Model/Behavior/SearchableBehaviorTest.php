<?php
// Core Testing Models
require_once CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * SearchableBehavior Test Case
 http://stackoverflow.com/questions/19833495/how-to-mock-a-cakephp-behavior-for-unit-testing
 */
class SearchableBehaviorTest extends CakeTestCase {

    /**
    * Fixtures
    *
    * @var array
    */
    public $fixtures = array(
        'plugin.searchable.search_index',
        'core.author',
        'core.post'
    );

    /**
    * setUp method
    *
    * @return void
    */
    public function setUp()
    {
        parent::setUp();
        $this->Author = ClassRegistry::init('Author');
        $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex');
    }

    /**
    * tearDown method
    *
    * @return void
    */
    public function tearDown()
    {
        unset($this->Author);
        unset($this->SearchIndex);
        parent::tearDown();
    }

    public function testSearch()
    {
        $this->Author->Behaviors->load('Searchable.Searchable');
        $result = $this->Author->search('mariano');
        $this->assertNotEqual(0, sizeof($result));
    }

    public function testSave1()
    {
        $data = array(
            'Author' => array(
                'user' => 'matheus',
                'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                'created' => '2007-03-17 01:16:23',
                'updated' => '2007-03-17 01:18:31'
            )
        );

        $this->Author->Behaviors->load(
            'Searchable.Searchable',
            array('fields' => 'user')
        );

        // Test Saving Process
        $result = $this->Author->save($data);
        $error = print_r($this->Author->validationErrors, true);
        $this->assertNotEqual(false, $result);

        // Check if the save wrote a SearchIndex
        $result = $this->SearchIndex->find('first', array(
            'conditions' => "MATCH(SearchIndex.data) AGAINST('matheus' IN BOOLEAN MODE)"
        ));
        $this->assertEqual('matheus', $result['SearchIndex']['data']);
    }

    public function testSave2()
    {
        $stub = $this->getMock(
            'Author',
            array('indexData'),
            array(false, 'authors', 'test')
        );
        $stub->expects($this->any())
            ->method('indexData')
            ->will($this->returnValue('matheus2'));

        $data = array(
            'Author' => array(
                'user' => 'matheus2',
                'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                'created' => '2007-03-17 01:16:23',
                'updated' => '2007-03-17 01:18:31'
            )
        );
        $stub->Behaviors->load('Searchable.Searchable');

        // Test Saving Process
        $result = $stub->save($data);
        $error = print_r($stub->validationErrors, true);
        $this->assertNotEqual(false, $result, $error);

        // Check if the save wrote a SearchIndex
        $result = $this->SearchIndex->find('first', array(
            'conditions' => "MATCH(SearchIndex.data) AGAINST('matheus2' IN BOOLEAN MODE)"
        ));
        $this->assertEqual('matheus2', $result['SearchIndex']['data']);
    }

    public function testSave3()
    {
        $data = $this->Author->find('first');
        $data['Author']['user'] = 'matheus3';
        $data['Author']['password'] = 'iuhasdiaushd ab';

        $this->Author->Behaviors->load('Searchable.Searchable');

        // Test Saving Process
        $result = $this->Author->save($data);
        $error = print_r($this->Author->validationErrors, true);
        $this->assertNotEqual(false, $result, $error);

        // Check if the save wrote a SearchIndex
        $result = $this->SearchIndex->find('first', array(
            'conditions' => "MATCH(SearchIndex.data) AGAINST('matheus3' IN BOOLEAN MODE)"
        ));
        $this->assertContains('matheus3', $result['SearchIndex']['data']);
    }

    public function testDelete()
    {
        // Test Deletion Process
        $this->Author->Behaviors->load('Searchable.Searchable');
        $result = $this->Author->delete(1);
        $this->assertEqual(true, $result);

        // Check if the process deleted the associated SearchIndex
        $result = $this->SearchIndex->find('first', array(
            'conditions' => array('association_key' => 1)
        ));
        $this->assertEqual('0', sizeof($result));
    }

}
