<?php
App::uses('SearchableBehavior', 'Model/Behavior');

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
        'app.cars',
    );

    /**
    * setUp method
    *
    * @return void
    */
    public function setUp()
    {
        parent::setUp();
        $this->Cars = ClassRegistry::init('Cars');
        $this->Searchable = new SearchableBehavior();
    }

    /**
    * tearDown method
    *
    * @return void
    */
    public function tearDown()
    {
        unset($this->Searchable);
        unset($this->Cars);
        parent::tearDown();
    }

    public function testSave()
    {
        // TODO: testSave
    }

}
