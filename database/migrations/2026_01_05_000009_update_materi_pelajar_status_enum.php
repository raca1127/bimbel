<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `materi_pelajar` MODIFY `status` ENUM('belum','read','completed') NOT NULL DEFAULT 'belum'");
        } elseif ($driver === 'pgsql') {
            // create new enum type and alter column
            DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'materi_pelajar_status') THEN CREATE TYPE materi_pelajar_status AS ENUM('belum','read','completed'); END IF; END$$;");
            DB::statement("ALTER TABLE materi_pelajar ALTER COLUMN status TYPE materi_pelajar_status USING status::text::materi_pelajar_status;");
        }
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `materi_pelajar` MODIFY `status` ENUM('belum','sudah') NOT NULL DEFAULT 'belum'");
        } elseif ($driver === 'pgsql') {
            DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'materi_pelajar_status') THEN CREATE TYPE old_materi_pelajar_status AS ENUM('belum','sudah'); ALTER TABLE materi_pelajar ALTER COLUMN status TYPE old_materi_pelajar_status USING status::text::old_materi_pelajar_status; DROP TYPE IF EXISTS materi_pelajar_status; END IF; END$$;");
        }
    }
};
