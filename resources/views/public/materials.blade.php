@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Materi Umum</h3>
  <form class="d-flex" method="GET">
    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm me-2" placeholder="Cari...">
    <button class="btn btn-sm btn-outline-primary">Cari</button>
  </form>
</div>

<div class="row g-3">
  @foreach($materi as $m)
    <div class="col-md-6">
      <div class="card card-modern p-3">
        <h5>{{ $m->judul }}</h5>
        <p class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($m->konten), 140) }}</p>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('public.materi.show', $m->id) }}" class="btn btn-sm btn-primary">Baca</a>
          <div class="ms-auto text-muted small">👁️ {{ $m->completions ?? 0 }}</div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="mt-3">{{ $materi->links() }}</div>

@endsection
