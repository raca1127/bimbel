<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attempts', function (Blueprint $table) {
            // ubah kolom score supaya bisa null dan default null
            $table->integer('score')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('attempts', function (Blueprint $table) {
            // rollback ke integer not null (misal default 0)
            $table->integer('score')->default(0)->nullable(false)->change();
        });
    }
};
