@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Penilaian Essai — Attempt #{{ $attempt->id }}</h3>
  <form method="POST" action="{{ route('teacher.attempt.grade', $attempt->id) }}">
    @csrf
    @foreach($attempt->answers as $ans)
      @if($ans->soal->type === 'essay')
      <div class="card mb-3 p-3">
        <h5>{{ $ans->soal->pertanyaan }}</h5>
        <p><strong>Jawaban Pelajar:</strong> {{ $ans->answer ?? '-' }}</p>
        <div class="mb-2">
          <label class="form-label">Tandai sebagai benar?</label>
          <select name="is_correct[{{ $ans->id }}]" class="form-select">
            <option value="0" {{ $ans->is_correct ? '' : 'selected' }}>Tidak</option>
            <option value="1" {{ $ans->is_correct ? 'selected' : '' }}>Ya</option>
          </select>
        </div>
      </div>
      @endif
    @endforeach
    <button class="btn btn-primary">Simpan Penilaian</button>
  </form>
</div>
@endsection
