<?php
/**
 * SearchIndex Fixture
 */
class SearchIndexFixture extends CakeTestFixture {

    public $table = 'search_indices';

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
            'type' => 'string',
            'length' => 128,
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
            array(
                'association_key' => 1,
                'model' => 'Author',
                'data' => 'mariano',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 2,
                'model' => 'Author',
                'data' => 'nate',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 3,
                'model' => 'Author',
                'data' => 'larry',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 4,
                'model' => 'Author',
                'data' => 'garrett',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 1,
                'model' => 'Apple',
                'data' => 'Red 1.Red Apple 1',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 2,
                'model' => 'Apple',
                'data' => 'Bright Red 1.Bright Red Apple',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 3,
                'model' => 'Apple',
                'data' => 'blue green.green blue',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 4,
                'model' => 'Apple',
                'data' => 'Blue Green.Test Name',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 5,
                'model' => 'Apple',
                'data' => 'Green.Blue Green',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
            array(
                'association_key' => 6,
                'model' => 'Apple',
                'data' => 'My new appleOrange.My new apple',
                'created' => '2015-08-26',
                'modified' => '2015-08-26'
            ),
        );
        parent::init();
    }

    public function create($db)
    {
        $ok = parent::create($db);

        // Workaround: CakeSchema cannot create FULLTEXT fixtures, so we change the table manually after creation
        if($ok) {
            $query = "ALTER TABLE `{$this->table}` ADD FULLTEXT INDEX `data` (`data` ASC)";
            try {
                $db->rawQuery($query);
                $this->created[] = $db->configKeyName;
            } catch (Exception $e) {
                $msg = __d(
                    'cake_dev',
                    'Fixture creation for "%s" failed "%s"',
                    $this->table,
                    $e->getMessage()
                );
                CakeLog::error($msg);
                trigger_error($msg, E_USER_WARNING);
                return false;
            }
            return true;
        }
        return false;
    }

}
