<?php
class AllApplicationTest extends CakeTestSuite
{
    public static function suite()
    {
        $suite = new CakeTestSuite('All application tests');
        $suite->addTestDirectoryRecursive(App::pluginPath('Searchable') . 'Test' . DS . 'Case' . DS);
        return $suite;
    }
}
