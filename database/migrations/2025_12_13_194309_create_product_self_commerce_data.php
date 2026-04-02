<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $connection = 'mongodb';
    protected $collection = 'product_self_commerce_data';

    public function up()
    {
        Schema::connection($this->connection)
                ->create($this->collection, function ($collection) {
            $collection->index('identify');
        });


    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists($this->collection);
    }

};
