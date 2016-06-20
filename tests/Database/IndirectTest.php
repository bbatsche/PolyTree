<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Test\TestModel;
use BeBat\PolyTree\Relations\Indirect as IndirectBase;
use BeBat\PolyTree\Test\ArrayDataSet;

class IndirectTest extends TestCase
{
    protected $nodes;

    protected $relation;

    protected $orderBy = ['ancestor_id', 'descendant_id'];

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

        for ($i = 1; $i <= 8; $i++) {
            $this->nodes[$i] = (new TestModel(['id' => $i]))->syncOriginal();
        }

        $this->relation = new Indirect(new TestModel, 'foreign_key', 'other_key');
        $this->relation->unlock();
    }

    public function testNoRelatives()
    {
        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[2], [], [], []);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[2]);

        verify('nothing was inserted', $this->getConnection()->getRowCount('ancestry'))->equals(0);
    }

    public function testParentSingleAncestor()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [], [['1', '3']], []);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $rows[] = [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ];

        $this->assertAncestryHasRows($rows);
    }

    public function testChildSingleDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[1], [], [], [['3', '2']]);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[1]);

        $rows[] = [
            'ancestor_id' => 3,
            'descendant_id' => 2
        ];

        $this->assertAncestryHasRows($rows);
    }

    public function testSingleAncestorSingleDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 4
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [['1', '4']], [['1', '3']], [['2', '4']]);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $rows = array_merge($rows, [[
            'ancestor_id' => 1,
            'descendant_id' => 4
        ], [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ], [
            'ancestor_id' => 2,
            'descendant_id' => 4
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    public function testParentMultipleAncestors()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[4], [], [['1', '4'], ['3', '4']], []);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[4]);

        $rows = array_merge($rows, [[
            'ancestor_id' => 1,
            'descendant_id' => 4
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 4
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    public function testChildMultipleDescendants()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[4], $this->nodes[1], [], [], [['4', '2'], ['4', '3']]);

        $this->relation->attachAncestry($this->nodes[4], $this->nodes[1]);

        $rows = array_merge($rows, [[
            'ancestor_id' => 4,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 4,
            'descendant_id' => 3
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    public function testSharedAncestor()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 1,
            'descendant_id' => 3
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [], [], []);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $this->assertAncestryHasRows($rows);
    }

    public function testSharedDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[3], [], [], []);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[3]);

        $this->assertAncestryHasRows($rows);
    }

    public function testParentDescendant()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[3], [], [], []);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[3]);

        $this->assertAncestryHasRows($rows);
    }

    public function testChildAncestor()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 2
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[2], [], [], []);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[2]);

        $this->assertAncestryHasRows($rows);
    }

    /**
     * 1   2
     *  \ /
     *   3   6
     *  / \ /
     * 4   5
     *    / \
     *   7   8
     *
     * (Adding 3<-5)
     */
    public function testMultipleAncestorsMultipleDescendants()
    {
        $rows = [[
            'ancestor_id' => 1,
            'descendant_id' => 3
        ], [
            'ancestor_id' => 2,
            'descendant_id' => 3
        ], [
            'ancestor_id' => 1,
            'descendant_id' => 4
        ], [
            'ancestor_id' => 2,
            'descendant_id' => 4
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 4
        ], [
            'ancestor_id' => 6,
            'descendant_id' => 5
        ], [
            'ancestor_id' => 6,
            'descendant_id' => 7
        ], [
            'ancestor_id' => 6,
            'descendant_id' => 8
        ], [
            'ancestor_id' => 5,
            'descendant_id' => 7
        ], [
            'ancestor_id' => 5,
            'descendant_id' => 8
        ]];

        $this->addRowsToAncestry($rows);

        $expectedJoined = [
            ['1', '7'],
            ['1', '8'],
            ['2', '7'],
            ['2', '8']
        ];

        $expChildAncestors = [
            ['1', '5'],
            ['2', '5']
        ];

        $expParentDescendants = [
            ['3', '7'],
            ['3', '8']
        ];

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[5], $expectedJoined, $expChildAncestors, $expParentDescendants);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[5]);

        $rows = array_merge($rows, [[
            'ancestor_id' => 1,
            'descendant_id' => 7
        ], [
            'ancestor_id' => 1,
            'descendant_id' => 8
        ], [
            'ancestor_id' => 2,
            'descendant_id' => 7
        ], [
            'ancestor_id' => 2,
            'descendant_id' => 8
        ], [
            'ancestor_id' => 1,
            'descendant_id' => 5
        ], [
            'ancestor_id' => 2,
            'descendant_id' => 5
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 7
        ], [
            'ancestor_id' => 3,
            'descendant_id' => 8
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    protected function compareRows(array $a, array $b, $colNum = 0)
    {
        if (!isset($this->orderBy[$colNum])) {
            return 0;
        }

        $column = $this->orderBy[$colNum];

        if ($a[$column] < $b[$column]) {
            return -1;
        } elseif ($a[$column] > $b[$column]) {
            return 1;
        } else {
            return $this->compareRows($a, $b, $colNum + 1);
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

        $actual = $this->getActualTableValues('ancestry', $this->orderBy);

        $this->assertTablesEqual($expected, $actual);
    }

    protected function doAttachmentAssertions(
        TestModel $parent,
        TestModel $child,
        array $expJoined,
        array $expChildAncestors,
        array $expParentDescendants
    ) {
        $actualJoinedIds        = $this->relation->getQueryForJoinedNodes($parent, $child)->get();
        $actualParentAncestors  = $this->relation->getQueryForParentAncestors($parent, $child)->get();
        $actualChildDescendants = $this->relation->getQueryForChildDescendants($parent, $child)->get();

        verify('joined IDs',                  $actualJoinedIds)->withoutOrder()->equals($expJoined);
        verify("IDs for parent's ancestors",  $actualParentAncestors)->withoutOrder()->equals($expChildAncestors);
        verify("IDs for child's descendants", $actualChildDescendants)->withoutOrder()->equals($expParentDescendants);
    }
}

class Indirect extends IndirectBase {}
