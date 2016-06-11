<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Test\ArrayDataSet;
use Illuminate\Database\Capsule\Manager;
use PHPUnit_Extensions_Database_TestCase as TestCaseBase;
use PHPUnit_Extensions_Database_DataSet_YamlDataSet as YamlDataSet;

abstract class TestCase extends TestCaseBase
{
    protected static $fixturePrefix;

    public static function setupBeforeClass()
    {
        static::$fixturePrefix = realpath(__DIR__ . '/../_fixtures');
    }

    public function getConnection()
    {
        $pdo = Manager::getPdo();
        return $this->createDefaultDBConnection($pdo, ':memory:');
    }

    public function getDataSet()
    {
        return $this->createYamlDataSet('TestModelTable.yml');
    }

    public function createYamlDataSet($fileName)
    {
        return new YamlDataSet(static::$fixturePrefix . '/' . $fileName);
    }

    public function createSimpleArrayDataSet($tableName, array $rows = array())
    {
        return $this->createArrayDataSet([$tableName => $rows]);
    }

    public function createArrayDataSet(array $data)
    {
        return new ArrayDataSet($data);
    }
}
