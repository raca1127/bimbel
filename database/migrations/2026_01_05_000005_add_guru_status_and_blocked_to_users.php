<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('guru_status')->default('none')->after('role'); // none, requested, approved, rejected
            $table->boolean('is_blocked')->default(false)->after('guru_status');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['guru_status', 'is_blocked']);
        });
    }
};
