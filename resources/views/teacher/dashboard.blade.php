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
    <ul class="mb-0">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Header Dashboard --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Dashboard Guru</h3>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">
            Masuk sebagai Pelajar
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMateri">
            Tambah Materi + Soal
        </button>
    </div>
</div>

{{-- Materi Guru --}}
<div class="row g-3">
    @forelse($materi as $m)
    @php
        $totalSoal = optional($m->soal)->count() ?? 0;
        $mcqCount  = optional($m->soal)->where('type', 'mcq')->count() ?? 0;
        $essayCount = optional($m->soal)->where('type', 'essay')->count() ?? 0;

        // Total jawaban essay belum dinilai
        $ungradedCount = \App\Models\Attempt::whereHas('answers.soal', function($q) use ($m) {
            $q->where('materi_id', $m->id)->where('type', 'essay');
        })->whereHas('answers', function($q) {
            $q->whereNull('is_correct');
        })->count();
    @endphp

    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body d-flex flex-column">
                {{-- Judul dan Aksi --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0">{{ $m->judul }}</h5>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('teacher.materi.edit', $m->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>

                        <form action="{{ route('teacher.materi.destroy', $m->id) }}" method="POST" class="d-inline confirm-delete" data-message="Hapus materi '{{ $m->judul }}'?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>

                        <a href="{{ route('teacher.attempts') }}?materi_id={{ $m->id }}" class="btn btn-sm btn-success">
                            Nilai Jawaban
                        </a>
                    </div>
                </div>

                {{-- Konten --}}
                <p class="card-text text-muted flex-grow-1">
                    {{ \Illuminate\Support\Str::limit(strip_tags($m->konten), 140, '...') }}
                </p>

                {{-- Info Soal, Belum Dinilai & Tanggal --}}
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-1">
                    <div class="d-flex gap-1 flex-wrap">
                        @if($totalSoal > 0)
                            <span class="badge bg-info">Total: {{ $totalSoal }}</span>
                            <span class="badge bg-primary">MCQ: {{ $mcqCount }}</span>
                            <span class="badge bg-warning text-dark">Essay: {{ $essayCount }}</span>
                        @else
                            <span class="badge bg-secondary">Belum Ada Soal</span>
                        @endif

                        @if($ungradedCount > 0)
                            <span class="badge bg-danger">Belum Dinilai: {{ $ungradedCount }}</span>
                        @endif
                    </div>
                    <small class="text-muted">Dibuat: {{ $m->created_at->format('d M Y') }}</small>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card p-3 text-center shadow-sm border-0">
            Belum ada materi. Gunakan tombol <strong>Tambah Materi + Soal</strong> di atas.
        </div>
    </div>
    @endforelse
</div>

@include('partials.modal_materi')

@endsection
