@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Dashboard Pelajar</h3>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex">
      <input name="q" class="form-control form-control-sm me-2" placeholder="Cari materi...">
      <button class="btn btn-sm btn-outline-primary">Cari</button>
    </form>
    <span class="badge bg-info align-self-center">Dibaca: {{ $readCount ?? 0 }} / {{ $total ?? 0 }}</span>
    {{-- Global start quiz removed: use per-materi 'Selesaikan Materi (Ujian Akhir)' buttons instead --}}
  </div>
</div>

<div class="row g-3">
  @foreach($materi as $m)
    <div class="col-md-6">
      <div class="card card-modern p-3">
        <h5>{{ $m->judul }}</h5>
        <p class="text-muted">{{ Str::limit(strip_tags($m->konten), 140) }}</p>
        <div class="d-flex gap-2 align-items-center">
          <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalView{{ $m->id }}">Baca</a>
          <form action="{{ route('student.exam.start', $m->id) }}" method="POST" class="ms-2">
            @csrf
            <button class="btn btn-sm btn-outline-danger">Selesaikan Materi (Ujian Akhir)</button>
          </form>
          <div class="ms-auto text-muted small">👁️ {{ $m->completions ?? 0 }}</div>
        </div>
      </div>
    </div>

    <!-- Modal baca -->
    <div class="modal fade" id="modalView{{ $m->id }}" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ $m->judul }}</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            {!! nl2br(e($m->konten)) !!}
          </div>
          <div class="modal-footer">
            <form action="{{ route('student.read', $m->id) }}" method="POST">
              @csrf
              <button class="btn btn-success">Tandai Sudah Dibaca</button>
            </form>
            <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

  @endforeach
</div>

<div class="mt-3">{{ $materi->links() }}</div>

@endsection
