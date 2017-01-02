<?php

/**
 * Create node, reltaions, & ancestry tables.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */

use Illuminate\Database\Capsule\Manager;

Manager::schema()->create('nodes', function ($table) {
    $table->increments('id');
    $table->string('value');
});

Manager::schema()->create('node_relations', function ($table) {
    $table->integer('parent_node_id')->unsinged();
    $table->integer('child_node_id')->unsinged();

    $table->foreign('parent_node_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');
    $table->foreign('child_node_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');

    $table->primary(['parent_node_id', 'child_node_id']);
});

Manager::schema()->create('node_ancestry', function ($table) {
    $table->integer('ancestor_node_id')->unsinged();
    $table->integer('descendant_node_id')->unsinged();

    $table->foreign('ancestor_node_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');
    $table->foreign('descendant_node_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');

    $table->primary(['ancestor_node_id', 'descendant_node_id']);
});
