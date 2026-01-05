@extends('layouts.app')

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <h3>{{ $materi->judul }}</h3>
    <a href="{{ route('student.dashboard') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
</div>

<div class="card p-3">
    {!! $materi->konten !!}
</div>

<form action="{{ route('student.read', $materi->id) }}" method="POST" class="mt-3">
    @csrf
    <button class="btn btn-success">Tandai Sudah Dibaca</button>
</form>
@endsection
