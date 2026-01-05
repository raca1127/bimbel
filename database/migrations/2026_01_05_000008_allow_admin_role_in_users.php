<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Attempt to alter the enum to include 'admin'. This uses raw SQL and may be DB-specific (MySQL).
        // If your environment uses SQLite/Postgres, adjust accordingly or install doctrine/dbal and use change().
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('guru','pelajar','admin') NOT NULL");
        } else if ($driver === 'pgsql') {
            // Postgres: replace enum type by creating new type and altering column
            DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN CREATE TYPE user_role AS ENUM('guru','pelajar','admin'); END IF; END$$;");
            // Attempt to alter column using casting
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE user_role USING role::text::user_role;");
        } else {
            // For sqlite or others, fallback: no-op — seeders should avoid failing, but developer may need to adjust.
        }
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // remap any 'admin' roles to 'guru' to avoid enum truncation
            DB::statement("UPDATE users SET role = 'guru' WHERE role = 'admin'");
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('guru','pelajar') NOT NULL");
        } else if ($driver === 'pgsql') {
            // remap admin to guru first
            DB::statement("UPDATE users SET role = 'guru' WHERE role = 'admin'");
            DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN CREATE TYPE old_user_role AS ENUM('guru','pelajar'); ALTER TABLE users ALTER COLUMN role TYPE old_user_role USING role::text::old_user_role; DROP TYPE IF EXISTS user_role; END IF; END$$;");
        }
    }
};
