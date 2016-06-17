<?php

use Illuminate\Database\Capsule\Manager;

Manager::schema()->create('nodes', function ($table)
{
    $table->increments('id');
    $table->string('value');
    $table->timestamps();
});

Manager::schema()->create('relations', function ($table)
{
    $table->integer('parent_id')->unsinged();
    $table->integer('child_id')->unsinged();

    $table->foreign('parent_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');
    $table->foreign('child_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');

    $table->primary(['parent_id', 'child_id']);
});

Manager::schema()->create('ancestry', function ($table)
{
    $table->integer('ancestor_id')->unsinged();
    $table->integer('descendant_id')->unsinged();

    $table->foreign('ancestor_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');
    $table->foreign('descendant_id')->references('id')->on('nodes')
        ->onUpdate('cascade')->onDelete('cascade');

    $table->primary(['ancestor_id', 'descendant_id']);
});
