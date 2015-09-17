<?php
class FullPluginTest extends CakeTestSuite
{
    public static function suite()
    {
        $suite = new CakeTestSuite('All application tests');
        $suite->addTestDirectoryRecursive(TESTS . 'Case');
        return $suite;
    }
}
