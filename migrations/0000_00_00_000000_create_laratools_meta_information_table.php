<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLaratoolsMetaInformationTable extends Migration
{
    public function up()
    {
        Schema::create('meta_information', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->morphs('owner');
            $table->string('key')->index();
            $table->string('value');
            $table->boolean('is_encrypted')->default(false);
        });
    }

    public function down()
    {
        Schema::drop('meta_information');
    }
}
