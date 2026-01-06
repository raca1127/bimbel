@extends('layouts.app')

@section('content')
<div class="card card-modern p-4 mb-4">
    <h3>Hasil Quiz</h3>
    <div class="mt-3 mb-3">
        <p><strong>Nilai:</strong> {{ $attempt->score ?? '-' }}</p>
        <p><strong>Durasi Tersisa:</strong> {{ gmdate('H:i:s', $attempt->duration_seconds ?? 0) }}</p>
        <a class="btn btn-outline-secondary" href="{{ route('student.index') }}">Kembali ke Dashboard</a>
    </div>

    <hr>

    <h5>Jawaban Soal</h5>
    <div class="mt-3">
        @forelse($attempt->answers as $answer)
            @php
                $soal = $answer->soal;
                // Decode JSON agar jadi array
                $choices = $soal->type === 'mcq' && !empty($soal->choices) 
                    ? json_decode($soal->choices, true) 
                    : [];
                $userAnswer = $answer->answer;
                $correctAnswer = $soal->jawaban_benar ?? null;
            @endphp

            <div class="mb-3 p-3 border rounded">
                <p><strong>{{ $loop->iteration }}. Soal:</strong> {!! nl2br(e($soal->pertanyaan)) !!}</p>

                {{-- MCQ --}}
                @if($soal->type === 'mcq' && is_array($choices))
                    <ul class="list-group">
                        @foreach($choices as $idx => $choice)
                            @php
                                $isUserAnswer = ($idx == $userAnswer);
                                $isCorrectAnswer = ($idx == $correctAnswer);
                                $class = $isUserAnswer
                                    ? ($isCorrectAnswer ? 'list-group-item-success' : 'list-group-item-danger')
                                    : ($isCorrectAnswer ? 'list-group-item-success' : '');
                            @endphp
                            <li class="list-group-item {{ $class }}">
                                {{ $choice }}
                                @if($isUserAnswer) <strong>(Jawaban Anda)</strong> @endif
                                @if($isCorrectAnswer && !$isUserAnswer) <strong>(Jawaban Benar)</strong> @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    {{-- Essay / jawaban bebas --}}
                    <p>
                        <strong>Jawaban Anda:</strong>
                        <textarea class="form-control" rows="2" readonly>{{ $answer->answer }}</textarea>
                    </p>
                @endif
            </div>
        @empty
            <p>Belum ada jawaban untuk attempt ini.</p>
        @endforelse
    </div>
</div>
@endsection
