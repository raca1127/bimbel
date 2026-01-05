@extends('layouts.app')

@section('content')
<div class="card card-modern p-4">
  <h3>{{ $materi->judul }}</h3>
  <div class="mt-3">{!! nl2br(e($materi->konten)) !!}</div>
  <div class="mt-3">
    <a href="{{ route('public.materi') }}" class="btn btn-outline-secondary">Kembali</a>
    <span class="ms-3 text-muted">👁️ {{ $materi->completions ?? 0 }} selesai</span>
  </div>
</div>

@endsection
