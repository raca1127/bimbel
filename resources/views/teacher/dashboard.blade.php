@extends('layouts.app')

@section('content')

{{-- Alerts Debug & Error --}}
@if(session('debug_soal'))
<div class="alert alert-warning">
    <strong>Debug Soal (input):</strong>
    <pre style="white-space:pre-wrap">{{ session('debug_soal') }}</pre>
</div>
@endif

@if(session('debug_soalData'))
<div class="alert alert-info">
    <strong>Debug Soal (disimpan):</strong>
    <pre style="white-space:pre-wrap">{{ session('debug_soalData') }}</pre>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <strong>Error:</strong>
    <ul>
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>

    @if(session('debug'))
        <pre style="white-space:pre-wrap">{{ print_r(session('debug'), true) }}</pre>
    @endif
</div>
@endif

{{-- Header Dashboard --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Dashboard Guru</h3>
    <div class="d-flex gap-2">
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">Masuk sebagai Pelajar</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMateri">
            Tambah Materi + Soal
        </button>
    </div>
</div>

<div class="tab-content" id="guruTabContent">
    {{-- Materi Guru --}}
    <div class="tab-pane fade show active" id="materi" role="tabpanel">
<div class="row g-3 mt-2">
    @forelse($materi as $m)
    <div class="col-md-6">
        <div class="card card-modern p-3">
            <div class="d-flex justify-content-between align-items-start">
                <h5>{{ $m->judul }}</h5>
                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('teacher.materi.edit', $m->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>

                    <form action="{{ route('teacher.materi.destroy', $m->id) }}" method="POST" class="d-inline confirm-delete" data-message="Hapus materi '{{ $m->judul }}'?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>

                    {{-- Tombol Lihat Attempt / Nilai --}}
<a href="{{ route('teacher.attempts') }}?materi_id={{ $m->id }}" class="btn btn-sm btn-success">
    Nilai Jawaban
</a>

                </div>
            </div>
            <p class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($m->konten), 140) }}</p>
            <div class="small text-secondary">
                Soal: {{ $m->soal && $m->soal->count() > 0 ? 'Ada' : 'Belum' }}
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card p-3 card-modern">Belum ada materi. Tambah menggunakan tombol di kanan atas.</div>
    </div>
    @endforelse
</div>

    </div>

@include('partials.modal_materi')

@endsection

