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
    @php $q = $ans->soal; @endphp
    <div class="card card-modern mb-3 p-3">
      <h5>Q{{ $idx+1 }}. {{ $q->pertanyaan }}</h5>
      @if($q->type === 'mcq')
        @foreach($q->choices as $choiceIndex => $choice)
          <div class="form-check">
            <input class="form-check-input" type="radio" name="answers[{{ $q->id }}]" id="q{{ $q->id }}_{{ $choiceIndex }}" value="{{ $choiceIndex }}">
            <label class="form-check-label" for="q{{ $q->id }}_{{ $choiceIndex }}">{{ $choice }}</label>
          </div>
        @endforeach
      @else
        <textarea name="answers[{{ $q->id }}]" class="form-control" rows="4"></textarea>
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
  // simple countdown display using remaining seconds from server
  let remaining = {{ $remaining }};
  function formatTime(s){
    let h = Math.floor(s/3600).toString().padStart(2,'0');
    let m = Math.floor((s%3600)/60).toString().padStart(2,'0');
    let sec = Math.floor(s%60).toString().padStart(2,'0');
    return h+':'+m+':'+sec;
  }
  const timerEl = document.getElementById('timer');
  timerEl.innerText = 'Waktu: ' + formatTime(remaining);
  const interval = setInterval(()=>{
    remaining--;
    if(remaining<=0){
      clearInterval(interval);
      alert('Waktu habis. Form akan dikirim otomatis.');
      // submit form automatically
      document.querySelector('form').submit();
    }
    timerEl.innerText = 'Waktu: ' + formatTime(remaining);
  },1000);
</script>
@endpush

@endsection
