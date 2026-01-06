@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Materi Umum</h3>
    <form class="d-flex" method="GET" role="search">
        <input 
            type="text" 
            name="q" 
            value="{{ request('q') }}" 
            class="form-control form-control-sm me-2" 
            placeholder="Cari materi..."
        >
        <button type="submit" class="btn btn-sm btn-outline-primary">Cari</button>
    </form>
</div>

@if($materi->isEmpty())
    <div class="alert alert-secondary text-center">
        Belum ada materi yang tersedia.
    </div>
@else
    <div class="row g-3">
        @foreach($materi as $m)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                    {{-- Judul --}}
                    <h5 class="card-title">{{ $m->judul }}</h5>

                    {{-- Konten --}}
                    <p class="card-text text-muted flex-grow-1">
                        {{ \Illuminate\Support\Str::limit(strip_tags($m->konten), 140, '...') }}
                    </p>

                    {{-- Aksi & Statistik --}}
                    <div class="d-flex align-items-center mt-3">
                        <a href="{{ route('public.materi.show', $m->id) }}" class="btn btn-sm btn-primary">
                            Baca
                        </a>
                        <div class="ms-auto text-muted small">
                            <i class="fas fa-eye"></i> {{ $m->completions ?? 0 }} dibaca
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $materi->links() }}
    </div>
@endif

@endsection
