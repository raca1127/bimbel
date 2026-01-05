<div class="modal fade" id="modalMateri" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Tambah Materi & Soal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form action="{{ route('teacher.materi.store') }}" method="POST">
        @csrf

        <div class="modal-body">

          {{-- Judul --}}
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input name="judul" class="form-control" required>
          </div>

          {{-- Konten --}}
          <div class="mb-3">
            <label class="form-label">Konten</label>
            <textarea name="konten" class="form-control" rows="6"></textarea>
          </div>

          <hr>
          <h6>Soal</h6>

          <div id="questions_container"></div>

          <button type="button" id="btnAddQuestion" class="btn btn-outline-primary btn-sm mt-2">
            + Tambah Soal
          </button>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>

      </form>
    </div>
  </div>
</div>

{{-- TEMPLATE --}}
<template id="question_template">
  <div class="question-block border rounded p-3 mb-3" data-index="__INDEX__">

    <input type="hidden" name="questions[__INDEX__][id]">

    <div class="mb-2">
      <label class="form-label">Tipe Soal</label>
      <select name="questions[__INDEX__][type]" class="form-select q-type" required>
        <option value="mcq">Pilihan Berganda</option>
        <option value="essay">Essay</option>
      </select>
    </div>

    <div class="mb-2">
      <label class="form-label">Pertanyaan</label>
      <textarea
        name="questions[__INDEX__][pertanyaan]"
        class="form-control"
        rows="2"
        required></textarea>
    </div>

    {{-- MCQ --}}
    <div class="mcq-area">
      <label class="form-label">Pilihan Jawaban</label>
      @for ($i = 0; $i < 5; $i++)
        <input
          name="questions[__INDEX__][choices][]"
          class="form-control mb-1"
          placeholder="Pilihan {{ $i + 1 }}">
      @endfor

      <div class="mt-2">
        <label class="form-label">Jawaban Benar (index 0–4)</label>
        <input
          name="questions[__INDEX__][jawaban_benar]"
          class="form-control">
      </div>
    </div>

    {{-- ESSAY --}}
    <div class="essay-area" style="display:none">
      <label class="form-label">Kunci Jawaban Essay (opsional)</label>
      <input
        name="questions[__INDEX__][jawaban_benar]"
        class="form-control">
    </div>

    <div class="text-end mt-2">
      <button type="button" class="btn btn-danger btn-sm btn-remove-question">
        Hapus Soal
      </button>
    </div>

  </div>
</template>

@push('scripts')
<script>
  let questionIndex = 0;
  const container = document.getElementById('questions_container');
  const template = document.getElementById('question_template');

  function addQuestion() {
    let html = template.innerHTML.replaceAll('__INDEX__', questionIndex);
    const div = document.createElement('div');
    div.innerHTML = html;

    container.appendChild(div.firstElementChild);
    questionIndex++;
    bindEvents();
  }

  function bindEvents() {
    document.querySelectorAll('.q-type').forEach(sel => {
      sel.onchange = function () {
        const block = this.closest('.question-block');
        block.querySelector('.mcq-area').style.display =
          this.value === 'mcq' ? 'block' : 'none';
        block.querySelector('.essay-area').style.display =
          this.value === 'essay' ? 'block' : 'none';
      };
    });

    document.querySelectorAll('.btn-remove-question').forEach(btn => {
      btn.onclick = function () {
        this.closest('.question-block').remove();
      };
    });
  }

  document.getElementById('btnAddQuestion').onclick = addQuestion;

  // otomatis 1 soal pertama
  addQuestion();
</script>
@endpush
