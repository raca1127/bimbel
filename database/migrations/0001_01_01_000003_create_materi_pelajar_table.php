<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materi_pelajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelajar_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('materi_id')
                  ->constrained('materi')
                  ->cascadeOnDelete();
            // status: 'belum' (not read), 'read' (dibaca), 'completed' (selesai setelah lulus ujian)
            $table->enum('status', ['belum', 'read', 'completed'])->default('belum');
            $table->timestamps();

            $table->unique(['pelajar_id', 'materi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi_pelajar');
    }
};
