@extends('layouts.app')

@section('content')
<h3>Riwayat Attempt</h3>
<div class="mt-3">
  @foreach($attempts as $a)
    <div class="card card-modern mb-2 p-3 d-flex justify-content-between align-items-center">
      <div>
        <div><strong>Nilai: {{ $a->score }}%</strong></div>
        <div class="small text-muted">Mulai: {{ $a->started_at }} — Selesai: {{ $a->finished_at }}</div>
      </div>
      <div>
        <a href="{{ route('student.attempt.result', $a->id) }}" class="btn btn-sm btn-outline-primary">Lihat</a>
      </div>
    </div>
  @endforeach
  <div class="mt-3">{{ $attempts->links() }}</div>
</div>

@endsection
