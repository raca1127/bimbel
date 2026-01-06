@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Dashboard Pelajar</h3>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex">
            <input 
                name="q" 
                class="form-control form-control-sm me-2" 
                placeholder="Cari materi..." 
                value="{{ request('q') }}"
            >
            <button class="btn btn-sm btn-outline-primary" type="submit">Cari</button>
        </form>
        <span class="badge bg-info fs-6">
            Dibaca: {{ $readCount ?? 0 }} / {{ $totalMateri ?? 0 }}
        </span>
        {{-- Tombol History global --}}
        <a href="{{ route('student.history') }}" class="btn btn-sm btn-warning ms-2">
            <i class="fas fa-history"></i> History
        </a>
    </div>
</div>

{{-- Materi Bookmark --}}
@if(!empty($bookmarkedIds))
<div class="mb-5">
    <h5 class="mb-3">Materi Bookmark</h5>
    <div class="row g-4">
        @php
            $bookmarkedMateri = \App\Models\Materi::whereIn('id', $bookmarkedIds)->get();
        @endphp
        @foreach($bookmarkedMateri as $bm)
        <div class="col-md-4">
            <div class="card shadow-sm h-100 p-3 border-primary border-1 card-hover d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 text-truncate" title="{{ $bm->judul }}">{{ $bm->judul }}</h6>
                    <span class="badge bg-primary"><i class="fas fa-bookmark"></i> Bookmark</span>
                </div>
                <p class="text-muted mb-3">{{ Str::limit(strip_tags($bm->konten), 100) }}</p>
                <div class="d-flex gap-2 mt-auto flex-wrap">
                    <a href="{{ route('student.materi.show', $bm->id) }}" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="fas fa-eye"></i> Baca
                    </a>
                    <form action="{{ route('student.bookmark.toggle', $bm->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-bookmark"></i> Hapus
                        </button>
                    </form>
                    <form action="{{ route('student.exam.start', $bm->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-danger">
                            <i class="fas fa-flag-checkered"></i> Selesaikan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Semua Materi --}}
@if($materi->isEmpty())
    <div class="alert alert-secondary text-center">
        Belum ada materi tersedia.
    </div>
@else
    <div class="row g-4">
        @foreach($materi as $m)
            @php
                $isRead = in_array($m->id, $readIds ?? []);
                $isBookmarked = in_array($m->id, $bookmarkedIds ?? []);
                $cardClasses = 'card shadow-sm h-100 p-3 card-hover d-flex flex-column';
                if($isRead) $cardClasses .= ' border-success border-2';
                elseif($isBookmarked) $cardClasses .= ' border-primary border-2';
            @endphp

            <div class="col-md-6">
                <div class="{{ $cardClasses }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0 text-truncate" title="{{ $m->judul }}">{{ $m->judul }}</h5>
                        @if($isRead)
                            <span class="badge bg-success"><i class="fas fa-check"></i> Completed</span>
                        @elseif($isBookmarked)
                            <span class="badge bg-primary"><i class="fas fa-bookmark"></i> Bookmark</span>
                        @endif
                    </div>
                    <p class="text-muted mt-2">{{ Str::limit(strip_tags($m->konten), 140) }}</p>
                    <div class="d-flex gap-2 align-items-center mt-auto flex-wrap">
                        <a href="{{ route('student.materi.show', $m->id) }}" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="fas fa-eye"></i> Baca
                        </a>

                        {{-- Bookmark toggle --}}
                        <form action="{{ route('student.bookmark.toggle', $m->id) }}" method="POST" class="d-inline">
                            @csrf
                            @if($isBookmarked)
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-bookmark"></i> Unbookmark
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="far fa-bookmark"></i> Bookmark
                                </button>
                            @endif
                        </form>

                        {{-- Start exam --}}
                        <form action="{{ route('student.exam.start', $m->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-danger">
                                <i class="fas fa-flag-checkered"></i> Selesaikan
                            </button>
                        </form>

                        {{-- Tombol Result (hanya jika sudah read) --}}
                        @if($isRead)
                            @php
                                $latestAttempt = \App\Models\Attempt::where('pelajar_id', Auth::id())
                                    ->whereHas('answers.soal', fn($q) => $q->where('materi_id', $m->id))
                                    ->latest()
                                    ->first();
                            @endphp
                            @if($latestAttempt)
                                <a href="{{ route('student.attempt.result', $latestAttempt->id) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-trophy"></i> Result
                                </a>
                            @endif
                        @endif

                        <div class="ms-auto text-muted small">
                            <i class="fas fa-eye"></i> {{ $m->completions ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-4">{{ $materi->links() }}</div>
@endif

{{-- Debug --}}
<script>
    const readIds = @json($readIds ?? []);
    const bookmarkedIds = @json($bookmarkedIds ?? []);
    console.log('readIds:', readIds);
    console.log('bookmarkedIds:', bookmarkedIds);
</script>

<style>
.card-hover {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.12);
}
</style>
@endsection
