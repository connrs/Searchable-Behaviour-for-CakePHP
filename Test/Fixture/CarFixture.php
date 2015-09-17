<?php
/**
 * Car Fixture
 */
class CarFixture extends CakeTestFixture {

    /**
    * Fields
    *
    * @var array
    */
    public $fields = array(
        'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'key' => 'primary'),
        // TODO: fields
        ),
        'tableParameters' => array(
            'charset' => 'utf8',
            'collate' => 'utf8_unicode_ci',
            'engine' => 'MyISAM'
        )
    );

    public function init()
    {
        $this->records = array(
            // TODO: records
        );
        parent::init();
    }

}
