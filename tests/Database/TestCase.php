<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Test\ArrayDataSet;
use Illuminate\Database\Capsule\Manager;
use PHPUnit_Extensions_Database_TestCase as TestCaseBase;
use PHPUnit_Extensions_Database_DataSet_CompositeDataSet as CompositeDataSet;
use PHPUnit_Extensions_Database_DataSet_IDataSet as DataSetInterface;
use PHPUnit_Extensions_Database_DataSet_YamlDataSet as YamlDataSet;

abstract class TestCase extends TestCaseBase
{
    protected static $fixturePrefix;

    protected static $baseFixtures = array();

    public static function setupBeforeClass()
    {
        static::$fixturePrefix = realpath(__DIR__ . '/../_fixtures');

        static::$baseFixtures = array('TestModels.yml');
    }

    public function getConnection()
    {
        $pdo = Manager::getPdo();
        return $this->createDefaultDBConnection($pdo, ':memory:');
    }

    public function getDataSet()
    {
        $compositeSet = new CompositeDataSet();

        foreach (static::$baseFixtures as $filename) {
            $compositeSet->addDataSet($this->createYamlDataSet($filename));
        }

        return $compositeSet;
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

    public function appendDataSet(DataSetInterface $newData)
    {
        $compositeSet = new CompositeDataSet();

        foreach (static::$baseFixtures as $filename) {
            $compositeSet->addDataSet($this->createYamlDataSet($filename));
        }

        $compositeSet->addDataSet($newData);

        $this->getDatabaseTester()->setDataSet($compositeSet);

        // Run databaseTester's onSetUp to reload DB with our new data
        $this->getDatabaseTester()->onSetUp();
    }

    public function createTableFromArray($tableName, $rows)
    {
        return $this->createSimpleArrayDataSet($tableName, $rows)->getTable($tableName);
    }

    public function getActualTableValues($tableName, array $orderBy = [], $dir = 'ASC', array $columns = ['*'])
    {
        $sql = 'SELECT ' . implode(',', $columns) . ' FROM ' . $tableName;

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . implode(',', $orderBy) . ' ' . $dir;
        }

        return $this->getConnection()->createQueryTable($tableName, $sql);
    }
}
