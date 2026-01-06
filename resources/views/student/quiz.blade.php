@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Quiz Anda</h3>
  <div>
    <span id="timer" class="badge bg-warning">Waktu: --:--:--</span>
  </div>
</div>

<form method="POST" action="{{ route('student.quiz.submit', $attempt->id) }}">
  @csrf

  @foreach($attempt->answers as $idx => $ans)
    @php
        $q = $ans->soal;
        // Pastikan choices selalu array untuk MCQ
        $choices = is_array($q->choices) ? $q->choices : (json_decode($q->choices, true) ?? []);
    @endphp

    <div class="card card-modern mb-3 p-3">
      <h5>Q{{ $idx + 1 }}. {{ $q->pertanyaan }}</h5>

      @if($q->type === 'mcq')
        @foreach($choices as $choiceIndex => $choice)
          <div class="form-check">
            <input class="form-check-input" 
                   type="radio" 
                   name="answers[{{ $q->id }}]" 
                   id="q{{ $q->id }}_{{ $choiceIndex }}" 
                   value="{{ $choiceIndex }}"
                   {{ old("answers.$q->id") == $choiceIndex ? 'checked' : '' }}>
            <label class="form-check-label" for="q{{ $q->id }}_{{ $choiceIndex }}">
              {{ $choice }}
            </label>
          </div>
        @endforeach
      @else
        <textarea name="answers[{{ $q->id }}]" 
                  class="form-control" 
                  rows="4">{{ old("answers.$q->id") ?? '' }}</textarea>
      @endif
    </div>
  @endforeach

  <div class="d-flex justify-content-between">
    <a class="btn btn-outline-secondary" href="{{ route('student.index') }}">Batal</a>
    <button class="btn btn-success">Kirim Jawaban</button>
  </div>
</form>

@push('scripts')
<script>
  // Countdown timer
  let remaining = {{ $remaining }};
  const timerEl = document.getElementById('timer');

  function formatTime(s){
    const h = Math.floor(s/3600).toString().padStart(2,'0');
    const m = Math.floor((s%3600)/60).toString().padStart(2,'0');
    const sec = Math.floor(s%60).toString().padStart(2,'0');
    return `${h}:${m}:${sec}`;
  }

  function updateTimer() {
    timerEl.innerText = 'Waktu: ' + formatTime(remaining);
    if(remaining <= 0){
      clearInterval(interval);
      alert('Waktu habis. Form akan dikirim otomatis.');
      document.querySelector('form').submit();
    }
    remaining--;
  }

  timerEl.innerText = 'Waktu: ' + formatTime(remaining);
  const interval = setInterval(updateTimer, 1000);
</script>
@endpush

@endsection
