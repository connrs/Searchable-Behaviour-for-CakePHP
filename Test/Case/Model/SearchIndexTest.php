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
        'core.apple',
        'core.author',
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
        $this->Apple = ClassRegistry::init('Apple');
        $this->Apple->Behaviors->load('Searchable.Searchable');
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
        $this->SearchIndex->searchModels(array('Author', 'Apple'));
        $result = $this->SearchIndex->find('all', array(
            'conditions' => "MATCH(SearchIndex.data) AGAINST('red apple')",
            'order' => array(
                'relevance' => 'desc'
            )
        ));
        $expected = array(
            'id' => 5,
            'association_key' => 1,
            'model' => 'Apple',
            'data' => 'Red 1.Red Apple 1',
            'created' => '2015-08-26',
            'modified' => '2015-08-26',
            'relevance' => '0.8376647233963013',
            'displayField' => 'name'
        );
        $this->assertEqual($expected, Hash::get($result, '0.SearchIndex'));
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
