<?php

namespace BeBat\PolyTree\Test\Database;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Test\TestIndirectRelation as Indirect;
use BeBat\PolyTree\Test\TestModel;
use Illuminate\Support\Collection;

/**
 * Database backed tests for attaching ancestry.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class IndirectAttachTest extends TestCase
{
    /**
     * Node models for testing
     *
     * @var array
     */
    protected $nodes;

    /**
     * Indirect relation to test.
     *
     * @var BeBat\PolyTree\Relations\Indirect
     */
    protected $relation;

    /**
     * Columns to sort results by.
     *
     * Setting this as a class property makes it easier to sort in both the DB and in memory values.
     *
     * @var array
     */
    protected $orderBy = ['ancestor_node_id', 'descendant_node_id'];

    /**
     * Set up our sample data and the SUT.
     */
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

    /**
     * If $parent & $child have no ancestors or descendants, the attachAncestry() should do nothing.
     */
    public function testNoRelatives()
    {
        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[2]);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[2]);

        verify('nothing was inserted', $this->getConnection()->getRowCount('node_ancestry'))->equals(0);
    }

    /**
     * If $parent has an ancestor, it should be attached to $child.
     */
    public function testParentSingleAncestor()
    {
        $this->appendDataSet($this->createYamlDataSet('SingleAncestry.yml'));

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [], [['1', '3']]);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $this->assertAncestryMatchesFixture('attachment/expected/ParentSingleAncestor.yml');
    }

    /**
     * If $child has a descendant, it should be attached to $parent.
     */
    public function testChildSingleDescendant()
    {
        $this->appendDataSet($this->createYamlDataSet('SingleAncestry.yml'));

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[1], [], [], [['3', '2']]);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[1]);

        $this->assertAncestryMatchesFixture('attachment/expected/ChildSingleDescendant.yml');
    }

    /**
     * If $parent has an ancestory and $child has a descendant, they should be merged together.
     */
    public function testSingleAncestorSingleDescendant()
    {
        $this->appendDataSet($this->createYamlDataSet('attachment/initial/SingleAncestorSingleDescendant.yml'));

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3], [['1', '4']], [['1', '3']], [['2', '4']]);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $this->assertAncestryMatchesFixture('attachment/expected/SingleAncestorSingleDescendant.yml');
    }

    /**
     * If $parent has multiple ancestors, they should be attached to $child.
     */
    public function testParentMultipleAncestors()
    {
        $this->appendDataSet($this->createYamlDataSet('attachment/initial/ParentMultipleAncestor.yml'));

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[4], [], [['1', '4'], ['2', '4']]);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[4]);

        $this->assertAncestryMatchesFixture('attachment/expected/ParentMultipleAncestor.yml');
    }

    /**
     * If $child has multiple descendants, they should be attached to $parent.
     */
    public function testChildMultipleDescendants()
    {
        $this->appendDataSet($this->createYamlDataSet('attachment/initial/ChildMultipleDescendant.yml'));

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[2], [], [], [['1', '3'], ['1', '4']]);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[2]);

        $this->assertAncestryMatchesFixture('attachment/expected/ChildMultipleDescendant.yml');
    }

    /**
     * If $parent and $child share an ancestor, nothing should be changed.
     */
    public function testSharedAncestor()
    {
        $this->appendDataSet($this->createYamlDataSet('SharedAncestor.yml'));

        $this->doAttachmentAssertions($this->nodes[2], $this->nodes[3]);

        $this->relation->attachAncestry($this->nodes[2], $this->nodes[3]);

        $this->assertAncestryMatchesFixture('SharedAncestor.yml');
    }

    /**
     * If $parent and $child share a descendant, nothing should be changed.
     */
    public function testSharedDescendant()
    {
        $this->appendDataSet($this->createYamlDataSet('SharedDescendant.yml'));

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[2]);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[2]);

        $this->assertAncestryMatchesFixture('SharedDescendant.yml');
    }

    /**
     * If $parent has an isolated descendant, nothing should be changed.
     */
    public function testParentDescendant()
    {
        $this->appendDataSet($this->createYamlDataSet('SingleAncestry.yml'));

        $this->doAttachmentAssertions($this->nodes[1], $this->nodes[3]);

        $this->relation->attachAncestry($this->nodes[1], $this->nodes[3]);

        $this->assertAncestryMatchesFixture('SingleAncestry.yml');
    }

    /**
     * If $child has an isolated ancestor, nothing should be changed.
     */
    public function testChildAncestor()
    {
        $this->appendDataSet($this->createYamlDataSet('SingleAncestry.yml'));

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[2]);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[2]);

        $this->assertAncestryMatchesFixture('SingleAncestry.yml');
    }

    /**
     * If $parent has mutliple ancestors and $child has multiple descendants, all relatives should be merged together.
     *
     * Graphically, this is what's happening:
     * <samp>
     * 1   2
     *  \ /
     *   3   4
     *  / \ /
     * 5   6
     *    / \
     *   7   8.
     * </samp>
     * (Adding 3 <- 6)
     */
    public function testMultipleAncestorsMultipleDescendants()
    {
        $this->appendDataSet($this->createYamlDataSet('attachment/initial/MultipleAncestorMultipleDescendant.yml'));

        $expectedJoined = [
            ['1', '7'],
            ['1', '8'],
            ['2', '7'],
            ['2', '8'],
        ];

        $expChildAncestors = [
            ['1', '6'],
            ['2', '6'],
        ];

        $expParentDescendants = [
            ['3', '7'],
            ['3', '8'],
        ];

        $this->doAttachmentAssertions($this->nodes[3], $this->nodes[6], $expectedJoined, $expChildAncestors, $expParentDescendants);

        $this->relation->attachAncestry($this->nodes[3], $this->nodes[6]);

        $this->assertAncestryMatchesFixture('attachment/expected/MultipleAncestorMultipleDescendant.yml');
    }

    /**
     * Recursively compare values in $a & $b looking at each column in order defined by ::$orderBy
     *
     * @param array $a
     * @param array $b
     * @param int $colNum
     *
     * @return int
     */
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

    /**
     * Add the $rows as an array dataset to the ancestry table.
     *
     * @param array $rows
     */
    protected function addRowsToAncestry(array $rows)
    {
        $existingAncestry = $this->createSimpleArrayDataSet('node_ancestry', $rows);

        $this->appendDataSet($existingAncestry);
    }

    /**
     * Assert that the data in ancestry table matches $rows
     *
     * @param array $rows
     */
    protected function assertAncestryMatchesArray(array $rows)
    {
        usort($rows, [$this, 'compareRows']);

        $expected = $this->createTableFromArray('node_ancestry', $rows);

        $actual = $this->getActualTableValues('node_ancestry', $this->orderBy);

        $this->assertTablesEqual($expected, $actual);
    }

    /**
     * Assert that the data in ancestry table matches a given YAML fixture.
     *
     * @param string $path
     */
    protected function assertAncestryMatchesFixture($path)
    {
        $expected = $this->createYamlDataSet($path)->getTable('node_ancestry');

        $actual = $this->getActualTableValues('node_ancestry', $this->orderBy);

        $this->assertTablesEqual($expected, $actual);
    }

    /**
     * Check various query building functions return the expected results for $parent and $child.
     *
     * @param BeBat\PolyTree\Contracts\Node $parent
     * @param BeBat\PolyTree\Contracts\Node $child
     * @param array                         $expJoined            Expected IDs cross joined between
     *                                                            $parent's ancestors and $child's descendants
     * @param array                         $expChildAncestors    Expected IDs with $parent's ancestors and $child
     * @param array                         $expParentDescendants Expected IDs with $parent and $child's descendants
     */
    protected function doAttachmentAssertions(
        Node $parent,
        Node $child,
        array $expJoined = [],
        array $expChildAncestors = [],
        array $expParentDescendants = []
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
