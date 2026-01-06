@extends('layouts.app')

@section('content')
<div class="card card-modern p-4">
  <h3>{{ $materi->judul }}</h3>
  <div class="mt-3">{!! $materi->konten !!}</div>
  <div class="mt-3">
    <a href="{{ route('public.materi') }}" class="btn btn-outline-secondary">Kembali</a>
    <span class="ms-3 text-muted"> <i class="fas fa-eye"></i> {{ $materi->completions ?? 0 }} selesai</span>
  </div>
</div>
{{-- CSS tambahan agar gambar tidak meluber --}}
<style>
    .card img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0.5rem 0;
    }
</style>
@endsection
