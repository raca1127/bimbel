@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Edit Materi</h3>
  <form action="{{ route('teacher.materi.update', $materi->id) }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label">Judul</label>
      <input name="judul" class="form-control" value="{{ old('judul', $materi->judul) }}" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Konten</label>
      <textarea name="konten" class="form-control" rows="6" required>{{ old('konten', $materi->konten) }}</textarea>
    </div>

    <hr>
    <h5>Soal untuk materi ini</h5>
    <div id="questions_edit_container">
      @if($materi->soal && $materi->soal->count())
        @foreach($materi->soal as $s)
          <div class="question-block border rounded p-3 mb-3">
            <input type="hidden" name="questions[][id]" value="{{ $s->id }}">
            <div class="mb-2">
              <label class="form-label">Tipe Soal</label>
              <select name="questions[][type]" class="form-select q-type">
                <option value="mcq" {{ $s->type==='mcq' ? 'selected' : '' }}>Pilihan Ganda</option>
                <option value="essay" {{ $s->type==='essay' ? 'selected' : '' }}>Essai</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Pertanyaan</label>
              <textarea name="questions[][pertanyaan]" class="form-control" rows="2" required>{{ $s->pertanyaan }}</textarea>
            </div>

            {{-- MCQ area --}}
            <div class="mcq-area mb-2" style="display: {{ $s->type==='mcq' ? 'block' : 'none' }};">
              @php $choices = $s->choices ?? [] @endphp
              @for($i=0;$i<5;$i++)
                <input name="questions[][choices][]" class="form-control mb-2 choice-input" value="{{ $choices[$i] ?? '' }}" placeholder="Pilihan {{ $i+1 }}">
              @endfor
              <div class="mb-2">
                <label class="form-label">Jawaban Benar</label>
                <select name="questions[][jawaban_benar]" class="form-select answer-select">
                  <option value="">-- Pilih jawaban benar --</option>
                  @for($i=0;$i<5;$i++)
                    @if(!empty($choices[$i]))
                      <option value="{{ $i }}" {{ $s->jawaban_benar == $i ? 'selected' : '' }}>
                        {{ $choices[$i] }}
                      </option>
                    @endif
                  @endfor
                </select>
              </div>
            </div>

            {{-- Essay area --}}
            <div class="essay-area mb-2" style="display: {{ $s->type==='essay' ? 'block' : 'none' }};">
              <label class="form-label">Kunci Essai</label>
              <input name="questions[][jawaban_benar]" class="form-control" value="{{ $s->jawaban_benar }}">
            </div>

            <div class="text-end">
              <button type="button" class="btn btn-sm btn-danger btn-remove-question">Hapus Soal</button>
            </div>
          </div>
        @endforeach
      @endif
    </div>

    <div class="mb-3">
      <button type="button" id="btnAddQuestionEdit" class="btn btn-outline-primary btn-sm">Tambah Soal</button>
    </div>

    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('teacher.materi.index') }}" class="btn btn-secondary">Batal</a>
  </form>

  {{-- Template soal baru --}}
  <template id="question_template">
    <div class="question-block border rounded p-3 mb-3">
      <input type="hidden" name="questions[][id]" value="">
      <div class="mb-2">
        <label class="form-label">Tipe Soal</label>
        <select name="questions[][type]" class="form-select q-type">
          <option value="mcq">Pilihan Ganda</option>
          <option value="essay">Essai</option>
        </select>
      </div>
      <div class="mb-2">
        <label class="form-label">Pertanyaan</label>
        <textarea name="questions[][pertanyaan]" class="form-control" rows="2" required></textarea>
      </div>

      {{-- MCQ area --}}
      <div class="mcq-area mb-2">
        @for($i=0;$i<5;$i++)
          <input name="questions[][choices][]" class="form-control mb-2 choice-input" placeholder="Pilihan {{ $i+1 }}">
        @endfor
        <div class="mb-2">
          <label class="form-label">Jawaban Benar</label>
          <select name="questions[][jawaban_benar]" class="form-select answer-select">
            <option value="">-- Pilih jawaban benar --</option>
          </select>
        </div>
      </div>

      {{-- Essay area --}}
      <div class="essay-area mb-2" style="display:none;">
        <label class="form-label">Kunci Essai</label>
        <input name="questions[][jawaban_benar]" class="form-control">
      </div>

      <div class="text-end">
        <button type="button" class="btn btn-sm btn-danger btn-remove-question">Hapus Soal</button>
      </div>
    </div>
  </template>
</div>

@push('scripts')
<script>
function attachHandlers(root){
  // Hapus soal
  root.querySelectorAll('.btn-remove-question').forEach(btn=>{
    btn.onclick = function(){
      const blk = this.closest('.question-block');
      if(confirm('Hapus soal ini?')) blk.remove();
    };
  });

  // Ganti tipe soal
  root.querySelectorAll('.q-type').forEach(sel=>{
    sel.onchange = function(){
      const blk = this.closest('.question-block');
      if(this.value==='mcq'){
        blk.querySelector('.mcq-area').style.display='block';
        blk.querySelector('.essay-area').style.display='none';
      } else {
        blk.querySelector('.mcq-area').style.display='none';
        blk.querySelector('.essay-area').style.display='block';
      }
    };
  });

  // Update jawaban benar dropdown untuk MCQ
  root.querySelectorAll('.question-block').forEach(block=>{
    const choices = block.querySelectorAll('.choice-input');
    choices.forEach(inp=>{
      inp.addEventListener('input', ()=>{
        const select = block.querySelector('.answer-select');
        const currentVal = select.value;
        select.innerHTML = '<option value="">-- Pilih jawaban benar --</option>';
        choices.forEach((c,i)=>{
          if(c.value.trim()!==''){
            const opt = document.createElement('option');
            opt.value = i;
            opt.text = c.value;
            select.appendChild(opt);
          }
        });
        select.value = currentVal; // keep selected if still exists
      });
    });
  });
}

// Attach handler awal
attachHandlers(document);

// Tambah soal baru
document.getElementById('btnAddQuestionEdit').addEventListener('click', function(){
  const container = document.getElementById('questions_edit_container');
  const tpl = document.getElementById('question_template');
  const node = tpl.content.cloneNode(true);
  container.appendChild(node);
  attachHandlers(container); // attach ke elemen baru
});
</script>
@endpush
@endsection
