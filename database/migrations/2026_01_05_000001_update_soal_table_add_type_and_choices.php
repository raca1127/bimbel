<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('soal', function (Blueprint $table) {
            $table->string('type')->default('mcq')->after('materi_id'); // 'mcq' or 'essay'
            $table->json('choices')->nullable()->after('pertanyaan'); // for mcq: array of 5 choices
            $table->string('jawaban_benar')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('soal', function (Blueprint $table) {
            $table->dropColumn(['type', 'choices']);
        });
    }
};
