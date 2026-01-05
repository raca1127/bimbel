@extends('layouts.app')

@section('content')
<h3>Leaderboard</h3>
<div class="list-group mt-3">
  @foreach($top as $row)
    <div class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <strong>{{ $row->pelajar->name ?? '—' }}</strong>
        <div class="small text-muted">Email: {{ $row->pelajar->email ?? '—' }}</div>
      </div>
      <div class="fw-bold">{{ $row->max_score }}%</div>
    </div>
  @endforeach
</div>

@endsection
