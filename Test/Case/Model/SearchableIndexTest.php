<?php
App::uses('SearchIndex', 'Model');

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
        'app.searchable_index',
    );


    /**
    * setUp method
    *
    * @return void
    */
    public function setUp()
    {
        parent::setUp();
        $this->SearchIndex = ClassRegistry::init('SearchIndex');
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
