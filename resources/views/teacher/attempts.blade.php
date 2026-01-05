@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Daftar Attempt (Terkait Materi Anda)</h3>
  @foreach($attempts as $a)
    <div class="card mb-2 p-3">
      <div class="d-flex justify-content-between">
        <div>
          <strong>Attempt #{{ $a->id }}</strong> — Pelajar: {{ $a->pelajar->name ?? 'N/A' }}
          <div class="small text-muted">Score: {{ $a->score ?? '-' }} — {{ $a->created_at }}</div>
        </div>
        <div>
          <a href="{{ route('teacher.attempt.grade', $a->id) }}" class="btn btn-sm btn-primary">Grade</a>
        </div>
      </div>
    </div>
  @endforeach
  <div class="mt-3">{{ $attempts->links() }}</div>
</div>
@endsection
