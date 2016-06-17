<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Test\TestModel;
use BeBat\PolyTree\Relations\Indirect as IndirectBase;
use BeBat\PolyTree\Test\ArrayDataSet;

class IndirectAttachTest extends TestCase
{
    protected $nodes;

    public function getDataSet()
    {
        $dataSet = parent::getDataSet();

        $dataSet->addDataSet($this->createYamlDataSet('EmptyAncestry.yml'));

        return $dataSet;
    }

    public function setUp()
    {
        parent::setUp();

        $this->nodes = array();

        for ($i = 1; $i <= 7; $i++) {
            $this->nodes[$i] = (new TestModel(['id' => $i]))->syncOriginal();
        }
    }

    /**
     * 1
     * |
     * 2 <-
     */
    public function testAttachSingleDescendant()
    {
        $this->nodes[1]->descendants()->unLock()->attach($this->nodes[2]);

        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1 <-
     * |
     * 2
     */
    public function testAttachSingleAncestor()
    {
        $this->nodes[2]->ancestors()->unLock()->attach($this->nodes[1]);

        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1
     * |
     * 2
     * |
     * 3 <-
     */
    public function testAttachLinearDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[2]->descendants()->unLock()->attach($this->nodes[3]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];

        $rows[] = [
            'ancestor_id' => 2,
            'descendant_id' => 3
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1 <-
     * |
     * 2
     * |
     * 3
     */
    public function testAttachLinearAncestor()
    {
        $rows = [[
            'ancestor_id' => 2,
            'descendant_id' => 3
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[2]->ancestors()->unLock()->attach($this->nodes[1]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 2
        ];

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     *   1
     *  / \
     * 2   3 <-
     */
    public function testAttachTreeDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[1]->descendants()->unLock()->attach($this->nodes[3]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];

        $this->assertAncestryHasRows($rows);
    }

    public function testAttachDescendantToTree()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[3]->ancestors()->unLock()->attach($this->nodes[1]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1   3 <-
     *  \ /
     *   2
     */
    public function testAttachTreeAncestor()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[2]->ancestors()->unLock()->attach($this->nodes[3]);

        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 2
        ];

        $this->assertAncestryHasRows($rows);
    }

    public function testAttachAncestorToTree()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[3]->descendants()->unLock()->attach($this->nodes[2]);

        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 2
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1  3
     * |  |
     * 2  4 <-
     */
    public function testAttachDisconnectedDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[3]->descendants()->unLock()->attach($this->nodes[4]);

        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 4
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1  3 <-
     * |  |
     * 2  4
     */
    public function testAttachDisconnectedAncestor()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[4]->ancestors()->unLock()->attach($this->nodes[3]);

        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 4
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1
     * |
     * 2
     * |
     * 3 <-
     * |
     * 4
     * |
     * 5
     */
    public function testAttachTreesWithAncestorsDescendants()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 4,
            'descendant_id' => 5
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[3]->ancestors()->unLock()->attach($this->nodes[2]);
        $this->nodes[3]->descendants()->unLock()->attach($this->nodes[4]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];
        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 4
        ];
        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 5
        ];
        $rows[] = [
            'ancestor_id' => 2,
            'descendant_id' => 3
        ];
        $rows[] = [
            'ancestor_id' => 2,
            'descendant_id' => 4
        ];
        $rows[] = [
            'ancestor_id' => 2,
            'descendant_id' => 5
        ];
        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 4
        ];
        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 5
        ];

        $this->assertAncestryHasRows($rows);
    }

    /**
     *   1
     *  / \
     * 2   3   4
     *      \ /
     *       5
     */
    public function testTreesWithoutAncestorsDescendants()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 4,
            'descendant_id' => 5
        ]];

        $this->addRowsToAncestry($rows);

        $this->nodes[3]->ancestors()->unLock()->attach($this->nodes[1]);
        $this->nodes[3]->descendants()->unLock()->attach($this->nodes[5]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];
        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 5
        ];
        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 5
        ];

        $this->assertAncestryHasRows($rows);
    }


    protected function compareRows(array $a, array $b)
    {
        if ($a['ancestor_id'] < $b['ancestor_id']) {
            return -1;
        } elseif ($a['ancestor_id'] > $b['ancestor_id']) {
            return 1;
        } else {
            if ($a['descendant_id'] < $b['descendant_id']) {
                return -1;
            } elseif ($a['descendant_id'] > $b['descendant_id']) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    protected function addRowsToAncestry(array $rows)
    {
        $existingAncestry = $this->createSimpleArrayDataSet('ancestry', $rows);

        $this->appendDataSet($existingAncestry);
    }

    protected function assertAncestryHasRows(array $rows)
    {
        usort($rows, [$this, 'compareRows']);

        $expected = $this->createTableFromArray('ancestry', $rows);

        $actual = $this->getActualTableValues(
            'ancestry', ['ancestor_id', 'descendant_id']
        );

        $this->assertTablesEqual($expected, $actual);
    }
}

class Indirect extends IndirectBase {}
