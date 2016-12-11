<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Test\TestIndirectRelation as Indirect;
use BeBat\PolyTree\Test\TestModel;
use Illuminate\Support\Collection;

class IndirectAttachTest extends TestCase
{
    protected $nodes;

    protected $relation;

    protected $orderBy = ['ancestor_node_id', 'descendant_node_id'];

    public function setUp()
    {
        parent::setUp();

        $this->nodes = [];

        for ($i = 1; $i <= 8; $i++) {
            $this->nodes[$i] = (new TestModel(['id' => $i]))->syncOriginal();
        }

        $this->relation = new Indirect(new TestModel(), 'foreign_key', 'other_key');
        $this->relation->unlock();
    }

    public function getDataSet()
    {
        $dataSet = parent::getDataSet();

        $dataSet->addDataSet($this->createYamlDataSet('EmptyAncestry.yml'));

        return $dataSet;
    }

    public function testNoRelatives()
    {
        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[2], [], [], []);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[2]);

        verify('nothing was inserted', $this->getConnection()->getRowCount('node_ancestry'))->equals(0);
    }

    public function testParentSingleAncestor()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [], [['1', '3']], []);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $rows[] = [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 3,
        ];

        $this->assertAncestryHasRows($rows);
    }

    public function testChildSingleDescendant()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[1], [], [], [['3', '2']]);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[1]);

        $rows[] = [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 2,
        ];

        $this->assertAncestryHasRows($rows);
    }

    public function testSingleAncestorSingleDescendant()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 4,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [['1', '4']], [['1', '3']], [['2', '4']]);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $rows = array_merge($rows, [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 4,
        ], [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 3,
        ], [
            'ancestor_node_id'   => 2,
            'descendant_node_id' => 4,
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    public function testParentMultipleAncestors()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 2,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[4], [], [['1', '4'], ['3', '4']], []);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[4]);

        $rows = array_merge($rows, [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 4,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 4,
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    public function testChildMultipleDescendants()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ], [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 3,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[4], $this->nodes[1], [], [], [['4', '2'], ['4', '3']]);

        $this->relation->attachAncestry($this->nodes[4], $this->nodes[1]);

        $rows = array_merge($rows, [[
            'ancestor_node_id'   => 4,
            'descendant_node_id' => 2,
        ], [
            'ancestor_node_id'   => 4,
            'descendant_node_id' => 3,
        ]]);

        $this->assertAncestryHasRows($rows);
    }

    public function testSharedAncestor()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ], [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 3,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [], [], []);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $this->assertAncestryHasRows($rows);
    }

    public function testSharedDescendant()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 2,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[3], [], [], []);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[3]);

        $this->assertAncestryHasRows($rows);
    }

    public function testParentDescendant()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
        ]];

        $this->addRowsToAncestry($rows);

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[3], [], [], []);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[3]);

        $this->assertAncestryHasRows($rows);
    }

    public function testChildAncestor()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 2,
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
     *   7   8.
     *
     * (Adding 3<-5)
     */
    public function testMultipleAncestorsMultipleDescendants()
    {
        $rows = [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 3,
        ], [
            'ancestor_node_id'   => 2,
            'descendant_node_id' => 3,
        ], [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 4,
        ], [
            'ancestor_node_id'   => 2,
            'descendant_node_id' => 4,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 4,
        ], [
            'ancestor_node_id'   => 6,
            'descendant_node_id' => 5,
        ], [
            'ancestor_node_id'   => 6,
            'descendant_node_id' => 7,
        ], [
            'ancestor_node_id'   => 6,
            'descendant_node_id' => 8,
        ], [
            'ancestor_node_id'   => 5,
            'descendant_node_id' => 7,
        ], [
            'ancestor_node_id'   => 5,
            'descendant_node_id' => 8,
        ]];

        $this->addRowsToAncestry($rows);

        $expectedJoined = [
            ['1', '7'],
            ['1', '8'],
            ['2', '7'],
            ['2', '8'],
        ];

        $expChildAncestors = [
            ['1', '5'],
            ['2', '5'],
        ];

        $expParentDescendants = [
            ['3', '7'],
            ['3', '8'],
        ];

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[5], $expectedJoined, $expChildAncestors, $expParentDescendants);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[5]);

        $rows = array_merge($rows, [[
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 7,
        ], [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 8,
        ], [
            'ancestor_node_id'   => 2,
            'descendant_node_id' => 7,
        ], [
            'ancestor_node_id'   => 2,
            'descendant_node_id' => 8,
        ], [
            'ancestor_node_id'   => 1,
            'descendant_node_id' => 5,
        ], [
            'ancestor_node_id'   => 2,
            'descendant_node_id' => 5,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 7,
        ], [
            'ancestor_node_id'   => 3,
            'descendant_node_id' => 8,
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
        }

        return $this->compareRows($a, $b, $colNum + 1);
    }

    protected function addRowsToAncestry(array $rows)
    {
        $existingAncestry = $this->createSimpleArrayDataSet('node_ancestry', $rows);

        $this->appendDataSet($existingAncestry);
    }

    protected function assertAncestryHasRows(array $rows)
    {
        usort($rows, [$this, 'compareRows']);

        $expected = $this->createTableFromArray('node_ancestry', $rows);

        $actual = $this->getActualTableValues('node_ancestry', $this->orderBy);

        $this->assertTablesEqual($expected, $actual);
    }

    protected function doAttachmentAssertions(
        TestModel $parent,
        TestModel $child,
        array $expJoined,
        array $expChildAncestors,
        array $expParentDescendants
    ) {
        $actualJoinedIds         = $this->relation->getQueryForJoinedNodes($parent, $child)->get();
        $actualChildAncestors    = $this->relation->getQueryForChildAncestors($parent, $child)->get();
        $actualParentDescendants = $this->relation->getQueryForParentDescendants($parent, $child)->get();

        if ($actualJoinedIds instanceof Collection) {
            $actualJoinedIds = $actualJoinedIds->all();
        }

        if ($actualChildAncestors instanceof Collection) {
            $actualChildAncestors = $actualChildAncestors->all();
        }

        if ($actualParentDescendants instanceof Collection) {
            $actualParentDescendants = $actualParentDescendants->all();
        }

        verify('joined IDs',                  $actualJoinedIds)->withoutOrder()->equals($expJoined);
        verify("IDs for parent's ancestors",  $actualChildAncestors)->withoutOrder()->equals($expChildAncestors);
        verify("IDs for child's descendants", $actualParentDescendants)->withoutOrder()->equals($expParentDescendants);
    }
}
