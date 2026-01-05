@extends('layouts.app')

@section('content')
<div class="mb-3">
  <h3>Daftar Soal</h3>
  <p class="text-muted">Soal tersedia setelah membaca semua materi.</p>
</div>

@foreach($soal as $s)
  <div class="card card-modern mb-3 p-3">
    <h5>{{ $s->materi->judul ?? 'Soal' }}</h5>
    <p>{{ $s->pertanyaan }}</p>
    <div class="small text-secondary">Jawaban Benar: <strong>{{ $s->jawaban_benar }}</strong></div>
  </div>
@endforeach

@endsection
