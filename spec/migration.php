<?php

namespace BeBat\PolyTree\Test;

require_once 'bootstrap.php';

use Illuminate\Database\Capsule\Manager;

Manager::schema()->create('test_models', function ($table)
{
    $table->increments('id');
    $table->string('value');
    $table->timestamps();
});

Manager::schema()->create('test_model_relations', function ($table)
{
    $table->integer('parent_test_model_id')->unsinged();
    $table->integer('child_test_model_id')->unsinged();

    $table->foreign('parent_test_model_id')->references('id')->on('test_models')
        ->onUpdate('cascade')->onDelete('cascade');
    $table->foreign('child_test_model_id')->references('id')->on('test_models')
        ->onUpdate('cascade')->onDelete('cascade');

    $table->primary(['parent_test_model_id', 'child_test_model_id']);
});

Manager::schema()->create('test_model_ancestry', function ($table)
{
    $table->integer('ancestor_test_model_id')->unsinged();
    $table->integer('descendant_test_model_id')->unsinged();

    $table->foreign('ancestor_test_model_id')->references('id')->on('test_models')
        ->onUpdate('cascade')->onDelete('cascade');
    $table->foreign('descendant_test_model_id')->references('id')->on('test_models')
        ->onUpdate('cascade')->onDelete('cascade');

    $table->primary(['ancestor_test_model_id', 'descendant_test_model_id']);
});
