@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Dashboard Guru</h3>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMateri">Tambah Materi + Soal</button>
</div>

<div class="row g-3">
  @forelse($materi as $m)
    <div class="col-md-6">
      <div class="card card-modern p-3">
        <div class="d-flex justify-content-between">
          <h5>{{ $m->judul }}</h5>
          <div>
            <form action="{{ route('teacher.materi.destroy', $m->id) }}" method="POST" class="d-inline confirm-delete" data-message="Hapus materi '{{ $m->judul }}'?">
              @csrf
              @method('DELETE')
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </form>
          </div>
        </div>
        <p class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($m->konten), 140) }}</p>
        <div class="small text-secondary">Soal: {{ $m->soal ? 'Ada' : 'Belum' }}</div>
      </div>
    </div>
  @empty
    <div class="col-12">
      <div class="card p-3 card-modern">Belum ada materi. Tambah menggunakan tombol di kanan atas.</div>
    </div>
  @endforelse
</div>

@include('partials.modal_materi')

@endsection
