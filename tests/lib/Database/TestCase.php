<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Test\Database\ArrayDataSet;
use Illuminate\Database\Capsule\Manager;
use PHPUnit_Extensions_Database_DataSet_CompositeDataSet as CompositeDataSet;
use PHPUnit_Extensions_Database_DataSet_IDataSet as DataSetInterface;
use PHPUnit_Extensions_Database_DataSet_YamlDataSet as YamlDataSet;
use PHPUnit_Extensions_Database_TestCase as TestCaseBase;

/**
 * Utilities, helpers, & configuration for database unit tests.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
abstract class TestCase extends TestCaseBase
{
    /**
     * Directory test fixtures should be stored in.
     *
     * @var string
     */
    protected static $fixtureDir;

    /**
     * Default test fixutre(s) to load on all unit tests.
     *
     * @var array
     */
    protected static $baseFixtures = [];

    /**
     * Set up default fixtures
     */
    public static function setupBeforeClass()
    {
        static::$fixtureDir = realpath(__DIR__ . '/../../_fixtures');

        static::$baseFixtures = ['TestModels.yml'];
    }

    /**
     * Get the PDO connection for the test database.
     *
     * @return void
     */
    public function getConnection()
    {
        $pdo = Manager::getPdo();

        return $this->createDefaultDBConnection($pdo, ':memory:');
    }

    /**
     * Create the default dataset from fixutres.
     *
     * All fixutres are assumed to be in YAML format because, honestly, why would you use anything else?
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        $compositeSet = new CompositeDataSet();

        foreach (static::$baseFixtures as $filename) {
            $compositeSet->addDataSet($this->createYamlDataSet($filename));
        }

        return $compositeSet;
    }

    /**
     * Helper to load a YAML fixture from the static::$fixtureDir directory.
     *
     * @param string $fileName
     *
     * @return PHPUnit_Extensions_Database_DataSet_YamlDataSet
     */
    public function createYamlDataSet($fileName)
    {
        return new YamlDataSet(static::$fixtureDir . '/' . $fileName);
    }

    /**
     * Helper to compile a array dataset.
     *
     * @param string $tableName
     * @param array $rows
     *
     * @return BeBat\PolyTree\Test\Database\ArrayDataSet
     */
    public function createSimpleArrayDataSet($tableName, array $rows = [])
    {
        return $this->createArrayDataSet([$tableName => $rows]);
    }

    /**
     * Helper to create an array dataset.
     *
     * @param array $data ['table_name' => [[ 'row 1 values' ], [ 'row 2 values' ], ...]]
     * @return void
     */
    public function createArrayDataSet(array $data)
    {
        return new ArrayDataSet($data);
    }

    /**
     * Combine a data set with the defualt fixtures and re-initialize the database.
     *
     * @param PHPUnit_Extensions_Database_DataSet_IDataSet $newData
     */
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

    /**
     * Generate a dataset from an array and retrieve a single table from it.
     *
     * @param string $tableName
     * @param array $rows
     *
     * @return PHPUnit_Extensions_Database_DataSet_ITableMetaData
     */
    public function createTableFromArray($tableName, array $rows)
    {
        return $this->createSimpleArrayDataSet($tableName, $rows)->getTable($tableName);
    }

    /**
     * Lookup actual values stored in a database table.
     *
     * @param string $tableName
     * @param array    $orderBy columns to order by
     * @param string       $dir sort direction (default: ASC)
     * @param array    $columns columns to select (default *)
     *
     * @return PHPUnit_Extensions_Database_DataSet_ITable
     */
    public function getActualTableValues($tableName, array $orderBy = [], $dir = 'ASC', array $columns = ['*'])
    {
        $sql = 'SELECT ' . implode(',', $columns) . ' FROM ' . $tableName;

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . implode(',', $orderBy) . ' ' . $dir;
        }

        return $this->getConnection()->createQueryTable($tableName, $sql);
    }
}
