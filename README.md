# BelajarOnline — Local Setup & Notes

This repository is a small Laravel application for online learning (guru & pelajar). This README contains setup, common issues, and seed data instructions.

## Requirements
- PHP 8.1+ (you have PHP 8.2.12)
- Composer
- A database (MySQL, SQLite, etc.)

## Install
BelajarOnline — README
======================

Ringkasan
--------
Aplikasi Laravel sederhana untuk pembelajaran online dengan peran `guru`, `pelajar`, dan `admin`.
Fitur inti:
- Materi (konten) dikelola oleh guru
- Soal (MCQ + Essai) terkait ke Materi (one-to-many)
- Pelajar harus membaca materi dan melakukan ujian akhir per-materi; nilai >= 70 menandai materi "completed"
- Admin dapat menyetujui permintaan guru, memblokir pengguna, dan menurunkan materi
- Attempt / AttemptAnswer menyimpan hasil ujian
- CKEditor terpasang via CDN untuk editor materi
- Konfirmasi hapus menggunakan modal global

Struktur folder & fungsi utama
-----------------------------
- app/
	- Http/
		- Controllers/
			- AuthController.php  : Login / register / logout flow
			- MateriController.php: CRUD materi + manage soal (guru-only)
			- StudentController.php: Dashboard pelajar, read materi, start exam, quiz flow, leaderboard
			- PublicController.php : Publik (materi list, show, become-guru request)
			- AdminController.php  : Admin actions (approve/reject guru, block/unblock, takedown materi, users/materi listing)
		- Middleware/
			- RoleMiddleware.php   : Route guard by role alias `role:`
			- CheckBlocked.php     : Prevent blocked users from using app
		- Kernel.php             : Global / route middleware registration
	- Models/
		- User.php              : role, guru_status, is_blocked
		- Materi.php            : hasMany `soal`, tracks `reads` & `completions`
		- Soal.php              : belongsTo `materi`, supports `type` (mcq|essay), `choices` (json)
		- MateriPelajar.php     : pivot per-pelajar status (`belum`,`read`,`completed`)
		- Attempt.php / AttemptAnswer.php : store quiz attempts and per-question answers

 - database/
	- migrations/            : schema changes (note: some migrations modify enums via raw SQL for MySQL/Postgres)
	- seeders/               : idempotent seeders: `UserSeeder`, `MateriSeeder`, `SoalSeeder`, `MateriPelajarSeeder`, `AttemptSeeder`

- resources/views/
	- layouts/app.blade.php  : base layout, toasts, global confirm modal, CKEditor init, stack scripts
	- public/                : public materi listing & show
	- teacher/               : guru dashboard, edit materi (multi-soal), attempts/grade views
	- student/               : student dashboard, quiz, result, leaderboard, history
	- admin/                 : admin listings (users/materi pages created)
	- partials/modal_materi.blade.php : modal to create materi + dynamic multi-question template

- routes/web.php           : route definitions (public, auth, teacher, student, admin)

Database design (summary)
-------------------------
- users: id, name, email (unique), password, role (guru|pelajar|admin), guru_status, is_blocked
- materi: id, guru_id, judul, konten (rich text), is_public, reads (uint), completions (uint)
- soal: id, materi_id, pertanyaan, type (mcq|essay), choices (json|null), jawaban_benar
- materi_pelajar: id, pelajar_id, materi_id, status (belum|read|completed)
- attempts: id, pelajar_id, materi_id (nullable — final exam), score, started_at, finished_at, duration_seconds
- attempt_answers: id, attempt_id, soal_id, answer (text), is_correct (bool)

Key application logic & flows
----------------------------
- Reading flow:
	- Student opens materi (public modal/browse). When "Tandai Sudah Dibaca" clicked, `MateriPelajar` record created/updated with `status = 'read'` and `materi.reads` increments.
	- Student can only start final exam for that materi if they have status 'read'.

- Final exam (per-materi):
	- `startExam($materiId)` creates an Attempt tied to `materi_id`, selects up to N soal for that materi and creates AttemptAnswer entries.
	- Student completes exam (50-minute timer enforced client-side + server-side check). On submit, MCQ are auto-graded; essay may be auto-graded if exact match or left for manual grading by guru.
	- If Attempt.score >= 70 and attempt has `materi_id`, the `materi_pelajar` record becomes `completed` and `materi.completions` increments.

- Teacher flow:
	- Teacher creates materi and associated soal (multiple) via modal or edit form. Soal types 'mcq' (with `choices`) or 'essay'.
	- Ownership checks: controllers verify that the current user is the guru for updating/deleting materi/soal.
	- Teachers can view attempts that include their soal and manually grade essay answers; grading recalculates attempt score.

- Admin flow:
	- Admin approves/rejects `become-guru` requests, blocks/unblocks users, and can takedown materi.

Migrations & DB notes
---------------------
- Some migrations alter enum columns using raw SQL (MySQL/Postgres). If you run migrations on MySQL, they will execute ALTER TABLE statements.
- If you need to rollback changes that shrink enums (e.g., remove 'admin' or change status values), the rollback code remaps unsafe values first to avoid truncation errors.
- If you run `php artisan migrate` and encounter enum/column-change errors, run:
```bash
composer require doctrine/dbal --dev
php artisan migrate
```
or apply SQL alterations manually for your DB engine.

Seeding
-------
- Use `php artisan db:seed` after migrating. The seeders are idempotent (`firstOrCreate` / `updateOrCreate` patterns) to avoid duplicate insert errors.
- Seeders create sample users (admin/guru/pelajar), several materi, multiple soal per materi, materi_pelajar statuses, and sample attempts.

Setup & run (development)
-------------------------
1. Install dependencies
```bash
composer install
npm install # if you will build assets
```
2. Configure `.env` (DB connection, APP_KEY). Generate app key:
```bash
php artisan key:generate
```
3. (Optional) If you will change existing columns, install DBAL:
```bash
composer require doctrine/dbal --dev
```
4. Run migrations & seeders:
```bash
php artisan migrate
php artisan db:seed
```
5. Serve app:
```bash
php artisan serve
```

Developer notes / gotchas
-------------------------
- The `soal` table originally had a unique index on `materi_id` which was removed to allow many soal per materi. If your DB has that index from an earlier run, drop it manually:
```sql
ALTER TABLE soal DROP INDEX soal_materi_id_unique;
```
- The `materi_pelajar.status` enum was expanded to include `read` and `completed`. If you get warnings when altering enums, migrations include remapping logic.
- CKEditor is initialized automatically for `textarea[name=konten]` via CDN in `layouts/app.blade.php`. If you prefer a different editor, replace the CDN include and initialization.
- Confirmation dialog: replaced browser `confirm()` calls with a global Bootstrap modal helper `window.confirmDelete(message, callback)` implemented in `layouts/app.blade.php` and used across views.

Testing & validation
--------------------
- Suggested manual checks after migrating and seeding:
	- Login as admin (`admin@example.com` / `adminpass`) and check admin pages.
	- Login as guru and create a materi with multiple soal using the modal or edit view.
	- Login as pelajar, read a materi, start final exam for that materi, submit answers, confirm `materi_pelajar` becomes `completed` if score >= 70.

Future improvements (optional)
-----------------------------
- Integrate richer media/file uploads for materi content.
- Improve essay grading workflow (teacher dashboard with pending essay answers to grade).
- Add automated tests (PHPUnit) for controllers and seeders.
- Add feature toggles and permissions via policies (Gate/Policy) instead of controller roll-your-own checks.

Importing materi & soal from CSV/Excel
------------------------------------
- A simple CSV import is available for teachers at the route `/guru/materi/import` (auth required).
- Template: `resources/imports/materi_soal_template.csv` (open in Excel and save as CSV if editing in Excel).
- CSV format expects columns: `judul`, `konten`, `is_public`, `soal_json`. The `soal_json` column contains a JSON array of soal objects:

	Example `soal_json` value:

	[{"type":"mcq","pertanyaan":"Apa warna langit?","choices":["Biru","Merah"],"jawaban_benar":"Biru"}, {"type":"essay","pertanyaan":"Jelaskan siklus air.","jawaban_benar":"Air menguap..."}]

- To import: login as a `guru`, go to `/guru/materi/import`, choose the CSV and upload. The importer will create each materi and its soal(s). Errors are shown after import.

Notes on Excel: Excel can open/edit CSV files. When saving from Excel, choose "CSV UTF-8 (Comma delimited)" to preserve UTF-8 characters.

Points system
-------------
- Users have a numeric `points` balance (stored in the `users.points` column). Each correct answer awards a configurable number of points (currently 10 points per correct answer).
- Teachers who grade and mark essay answers as correct (or who themselves take final exams and answer correctly) will also receive points for correct answers.


Files of interest (quick list)
-----------------------------
- `routes/web.php` — all routes and middleware usage
- `app/Http/Controllers/*` — main application logic
- `app/Models/*` — Eloquent models + relations
- `database/migrations/*` — schema and enum adjustments
- `database/seeders/*` — data population; idempotent
- `resources/views/layouts/app.blade.php` — base layout, CKEditor, confirm modal
- `resources/views/partials/modal_materi.blade.php` — dynamic multi-question creator

If you want, I can:
- Generate a developer `CONTRIBUTING.md` with coding conventions and testing steps.
- Scaffold the missing admin Blade views (`resources/views/admin/*`).
- Produce a short API / route reference listing `method | path | controller@method | middleware`.

---
Documentation created by the code assistant. For any section you want expanded (detailed route list, model properties with types, sequence diagrams), tell me which one and I'll add it to `README.md` or a separate doc file.
