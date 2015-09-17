<?php
App::uses('Controller', 'Controller');
App::uses('View', 'View');
App::uses('SearchableHelper', 'View/Helper');

class SearchableHelperTest extends CakeTestCase {
    public function setUp() {
        parent::setUp();
        $Controller = new Controller();
        $View = new View($Controller);
        $this->Searchable = new SearchableHelper($View);
    }

    public function testviewLink() {
        // TODO: testViewLink
    }
}
