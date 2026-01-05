@extends('layouts.app')

@section('content')
<div class="card card-modern p-4">
  <h3>Hasil Quiz</h3>
  <div class="mt-3">
    <p>Nilai: <strong>{{ $attempt->score }}</strong></p>
    <p>Durasi: <strong>{{ gmdate('H:i:s', $attempt->duration_seconds ?? 0) }}</strong></p>
    <a class="btn btn-outline-secondary" href="{{ route('student.index') }}">Kembali ke Dashboard</a>
  </div>
</div>

@endsection
