<?php
App::uses('Controller', 'Controller');
App::uses('Router', 'Routing');
App::uses('CakeResponse', 'Network');
App::uses('View', 'View');
App::uses('SearchableHelper', 'Searchable.View/Helper');

class SearchableHelperTest extends CakeTestCase {

    public function setUp() {
        parent::setUp();
        $this->Controller = new Controller();
        $this->View = new View($this->Controller);
        $this->Searchable = new SearchableHelper($this->View);
    }

    public function testviewLink() {

        // Test 1
        $data = array(
            "SearchIndex" => array(
                "model" => "SearchIndex",
                "displayField" => "id",
                "id" => 13,
                "association_key" => 4
            )
        );
        $result = $this->Searchable->viewLink($data);
        $expected = '/view/4';
        $this->assertContains($expected, $result);

        // Test 2
        $data = array(
            "SearchIndex" => array(
                "model" => "SearchIndex",
                "displayField" => "name",
                "id" => 13,
                "association_key" => 4,
                "name" => "Test"
            )
        );
        $result = $this->Searchable->viewLink($data);
        $expected = '/view/4';
        $this->assertContains($expected, $result);
    }

	public function tearDown() {
		parent::tearDown();
		unset($this->Searchable, $this->Controller);
	}
}
