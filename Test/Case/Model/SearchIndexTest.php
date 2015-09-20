<?php
// Core Testing Models
require_once CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

App::uses('SearchIndex', 'Searchable.Model');

/**
 * SearchIndex Test Case
 */
class SearchIndexTest extends CakeTestCase {

    /**
    * Fixtures
    *
    * @var array
    */
    public $fixtures = array(
        'plugin.searchable.search_index',
        'core.author',
        'core.article'
    );


    /**
    * setUp method
    *
    * @return void
    */
    public function setUp()
    {
        parent::setUp();
        $this->SearchIndex = ClassRegistry::init('Searchable.SearchIndex');
        $this->Author = ClassRegistry::init('Author');
        $this->Author->Behaviors->load('Searchable.Searchable');
    }

    public function testFind1()
    {
        // Test 1
        $result = $this->SearchIndex->find('all', array(
            'conditions' => "MATCH(SearchIndex.data) AGAINST('mariano' IN BOOLEAN MODE)"
        ));
        $error = print_r($result, true);
        $this->assertEqual(1, sizeof($result), $error);
    }

    public function testFind2()
    {
        // Test 2
        $this->SearchIndex->searchModels(array('Author', 'Article'));
        $result = $this->SearchIndex->find('all', array(
            'conditions' => "MATCH(SearchIndex.data) AGAINST('nate' IN BOOLEAN MODE)"
        ));
        $error = print_r($result, true);
        $this->assertEqual(1, sizeof($result), $error);
    }

    public function testFind3()
    {
        // Test 3
        $this->SearchIndex->searchModels('Author');
        $result = $this->SearchIndex->find('all', array(
            'conditions' => array()
        ));
        $error = print_r($result, true);
        $this->assertEqual(4, sizeof($result), $error);
    }

    public function testFind4()
    {
        // Test 4
        $this->SearchIndex->searchModels('Author');
        $result = $this->SearchIndex->find('all');
        $error = print_r($result, true);
        $this->assertEqual(4, sizeof($result), $error);
    }

    public function testFuzzyize()
    {
        $result = $this->SearchIndex->fuzzyize('    ');
        $this->assertEqual('\s*', $result);
    }

    /**
    * tearDown method
    *
    * @return void
    */
    public function tearDown()
    {
        unset($this->SearchIndex);

        parent::tearDown();
    }

}
