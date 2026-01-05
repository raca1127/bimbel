<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->foreignId('materi_id')->nullable()->constrained('materi')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('materi_id');
        });
    }
};
