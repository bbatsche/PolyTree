<?php

namespace spec\BeBat\PolyTree;

use BeBat\PolyTree\Model as BaseModel;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ModelSpec extends ObjectBehavior
{
    function let()
    {
        $this->beAnInstanceOf('spec\BeBat\PolyTree\TestModel');
    }

    function it_generates_table_names()
    {
        $this->getRelationsTable()->shouldEqual('test_model_relations');
        $this->getAncestryTable()->shouldEqual('test_model_ancestry');
    }

    function it_uses_overriden_table_names()
    {
        $this->setRelationsTable('overriden_relations');
        $this->setAncestryTable('overriden_ancestry');

        $this->getRelationsTable()->shouldEqual('overriden_relations');
        $this->getAncestryTable()->shouldEqual('overriden_ancestry');
    }

    function it_generates_column_names()
    {
        $this->getParentKeyName()->shouldEqual('parent_test_model_id');
        $this->getChildKeyName()->shouldEqual('child_test_model_id');
        $this->getAncestorKeyName()->shouldEqual('ancestor_test_model_id');
        $this->getDescendantKeyName()->shouldEqual('descendant_test_model_id');
    }

    function it_uses_overriden_column_names()
    {
        $this->setParentKeyName('overriden_parent_id');
        $this->setChildKeyName('overriden_child_id');
        $this->setAncestorKeyName('overriden_ancestor_id');
        $this->setDescendantKeyName('overriden_descendant_id');

        $this->getParentKeyName()->shouldEqual('overriden_parent_id');
        $this->getChildKeyName()->shouldEqual('overriden_child_id');
        $this->getAncestorKeyName()->shouldEqual('overriden_ancestor_id');
        $this->getDescendantKeyName()->shouldEqual('overriden_descendant_id');
    }

    function it_has_relationship_methods()
    {
        $this->hasParents()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasParents');
        $this->hasChildren()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasChildren');
        $this->hasAncestors()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasAncestors');
        $this->hasDescendants()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasDescendants');
    }

    function it_has_relationship_aliases()
    {
        $this->parents()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasParents');
        $this->children()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasChildren');
        $this->ancestors()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasAncestors');
        $this->descendants()->shouldBeAnInstanceOf('BeBat\PolyTree\Relations\HasDescendants');
    }
}

class TestModel extends BaseModel {}
