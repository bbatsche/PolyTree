<?php

namespace BeBat\PolyTree\Test;

use PHPUnit_Extensions_Database_DataSet_AbstractDataSet as AbstractDataSet;
use PHPUnit_Extensions_Database_DataSet_DefaultTable as DefaultTable;
use PHPUnit_Extensions_Database_DataSet_DefaultTableIterator as DefaultTableIterator;
use PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData as DefaultTableMetaData;

class ArrayDataSet extends AbstractDataSet
{
    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $tableName => $rows) {
            $columns = [];

            if (isset($rows[0])) {
                $columns = array_keys($rows[0]);
            }

            $metaData = new DefaultTableMetaData($tableName, $columns);
            $table    = new DefaultTable($metaData);

            foreach ($rows as $row) {
                $table->addRow($row);
            }

            $this->tables[$tableName] = $table;
        }
    }

    public function getTable($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            throw new InvalidArgumentException("$tableName is not a table in the current database.");
        }

        return $this->tables[$tableName];
    }

    protected function createIterator($reverse = false)
    {
        return new DefaultTableIterator($this->tables, $reverse);
    }
}
