@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Import Materi & Soal (CSV)</h3>
    <p>Gunakan template <a href="{{ asset('resources/imports/materi_soal_template.csv') }}">materi_soal_template.csv</a>.
    Kolom `soal_json` berisi JSON array soal: [{"type":"mcq","pertanyaan":"...","choices":["a","b"],"jawaban_benar":"a"}, ...]</p>

    <form action="{{ route('teacher.materi.import.post') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">CSV File</label>
            <input type="file" name="file" class="form-control" accept=".csv" required />
        </div>
        <button class="btn btn-primary">Import</button>
    </form>

    @if(session('import_summary'))
        <div class="mt-3 alert alert-info">{{ session('import_summary') }}</div>
    @endif
</div>
@endsection
