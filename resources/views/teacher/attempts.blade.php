@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Daftar Attempt (Terkait Materi Anda)</h3>

  {{-- Filter --}}
<form method="GET" class="d-flex gap-2 mb-3">
    <select name="materi_id" class="form-select w-auto">
        <option value="">Semua Materi</option>
        @foreach(\App\Models\Materi::where('guru_id', auth()->id())->get() as $m)
            <option value="{{ $m->id }}" {{ $materi_id == $m->id ? 'selected' : '' }}>
                {{ $m->judul }}
            </option>
        @endforeach
    </select>

    <select name="status" class="form-select w-auto">
        <option value="">Semua</option>
        <option value="ungraded" {{ $status=='ungraded'?'selected':'' }}>Belum Dinilai</option>
        <option value="graded" {{ $status=='graded'?'selected':'' }}>Sudah Dinilai</option>
    </select>

    <button class="btn btn-outline-primary">Filter</button>
</form>


  @forelse($attempts as $a)
    @php
        $graded = $a->score !== null;
    @endphp
    <div class="card mb-2 p-3 {{ $graded ? 'border-success' : 'border-warning' }}">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <strong>Attempt #{{ $a->id }}</strong> — Pelajar: {{ $a->pelajar->name ?? 'N/A' }}
          <div class="small text-muted">
            Score: {{ $graded ? $a->score : '-' }} — {{ $a->created_at->format('d M Y H:i') }}
            <span class="badge {{ $graded ? 'bg-success' : 'bg-warning' }}">
              {{ $graded ? 'Sudah Dinilai' : 'Belum Dinilai' }}
            </span>
          </div>
        </div>
        <div>
          <a href="{{ route('teacher.attempt.grade', $a->id) }}" class="btn btn-sm {{ $graded ? 'btn-secondary disabled' : 'btn-primary' }}">
            Grade
          </a>
        </div>
      </div>

      {{-- Tampilkan jawaban singkat --}}
<div class="mt-2">
@foreach($attempts as $a)
  @php $graded = $a->score !== null; @endphp
  <div class="card mb-2 p-3 {{ $graded ? 'border-success' : 'border-warning' }}">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <strong>Attempt #{{ $a->id }}</strong> — Pelajar: {{ $a->pelajar->name ?? 'N/A' }}
        <div class="small text-muted">
          Score: {{ $graded ? $a->score : '-' }} — {{ $a->created_at->format('d M Y H:i') }}
          <span class="badge {{ $graded ? 'bg-success' : 'bg-warning' }}">
            {{ $graded ? 'Sudah Dinilai' : 'Belum Dinilai' }}
          </span>
        </div>
      </div>
      <div>
        <a href="{{ route('teacher.attempt.grade', $a->id) }}" 
           class="btn btn-sm {{ $graded ? 'btn-secondary disabled' : 'btn-primary' }}">
          Grade
        </a>
      </div>
    </div>

    {{-- Jawaban singkat --}}
    <div class="mt-2">
      @foreach($a->answers as $ans)
        @php $q = $ans->soal; @endphp
        <div class="mb-1">
          <strong>{{ $loop->iteration }}. {{ $q->pertanyaan }}</strong>
          @if($q->type === 'mcq')
            <div class="small text-muted">
              Jawaban: {{ is_array($q->choices) ? ($q->choices[$ans->answer] ?? '-') : '-' }}
            </div>
          @else
            <textarea class="form-control" rows="2" readonly>{{ $ans->answer }}</textarea>
          @endif
        </div>
      @endforeach
    </div>
  </div>
@endforeach
</div>

<div class="mt-3">{{ $attempts->links() }}</div>


    </div>
  @empty
    <div class="alert alert-info">Belum ada attempt untuk materi ini.</div>
  @endforelse

  <div class="mt-3">{{ $attempts->links() }}</div>
</div>
@endsection
