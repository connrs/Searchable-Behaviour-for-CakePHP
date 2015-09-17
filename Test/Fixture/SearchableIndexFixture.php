<?php
/**
 * SearchableIndex Fixture
 */
class SearchableIndexFixture extends CakeTestFixture {

    /**
    * Fields
    *
    * @var array
    */
    public $fields = array(
        'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'key' => 'primary'),
        'association_key' => array(
            'type' => 'string',
            'null' => false,
            'default' => null,
            'length' => 36,
            'collate' => 'utf8_unicode_ci',
            'charset' => 'utf8'
        ),
        'model' => array(
            'type' => 'string',
            'null' => false,
            'default' => null,
            'length' => 128,
            'collate' => 'utf8_unicode_ci',
            'charset' => 'utf8'
        ),
        'data' => array(
            'type' => 'date',
            'null' => false,
            'default' => null
        ),
        'created' => array(
            'type' => 'date',
            'null' => false,
            'default' => null
        ),
        'modified' => array(
            'type' => 'date',
            'null' => false,
            'default' => null
        ),
        'indexes' => array(
            'PRIMARY' => array(
                'column' => 'id',
                'unique' => 1
            ),
            'association_key' => array(
                'column' => array('association_key', 'model'),
                'unique' => 0
            ),
            'data' => array(
                'column' => 'data',
                'unique' => 0
            )
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
