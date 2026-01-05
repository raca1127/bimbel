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
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input name="judul" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Konten</label>
            <textarea name="konten" class="form-control" rows="6" required></textarea>
          </div>
          <hr>
          <h6>Soal untuk materi ini (minimal 1)</h6>
          <div id="questions_container">
            <template id="question_template">
              <div class="question-block border rounded p-3 mb-3">
                <input type="hidden" name="questions[][id]" value="">
                <div class="mb-2">
                  <label class="form-label">Tipe Soal</label>
                  <select name="questions[][type]" class="form-select q-type">
                    <option value="mcq">Pilihan Berganda</option>
                    <option value="essay">Essay</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Pertanyaan</label>
                  <textarea name="questions[][pertanyaan]" class="form-control" rows="2" required></textarea>
                </div>
                <div class="mcq-area mb-2">
                  <label class="form-label">Pilihan</label>
                  <div class="mcq-options">
                    <input name="questions[][choices][]" class="form-control mb-2" placeholder="Pilihan 1">
                    <input name="questions[][choices][]" class="form-control mb-2" placeholder="Pilihan 2">
                    <input name="questions[][choices][]" class="form-control mb-2" placeholder="Pilihan 3">
                    <input name="questions[][choices][]" class="form-control mb-2" placeholder="Pilihan 4">
                    <input name="questions[][choices][]" class="form-control mb-2" placeholder="Pilihan 5">
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Jawaban Benar (index 0..4)</label>
                    <input name="questions[][jawaban_benar]" class="form-control">
                  </div>
                </div>
                <div class="essay-area mb-2" style="display:none">
                  <label class="form-label">Kunci Essai (opsional)</label>
                  <input name="questions[][jawaban_benar]" class="form-control">
                </div>
                <div class="text-end">
                  <button type="button" class="btn btn-danger btn-sm btn-remove-question">Hapus Soal</button>
                </div>
              </div>
            </template>
          </div>
          <div class="d-flex gap-2">
            <button type="button" id="btnAddQuestion" class="btn btn-outline-primary btn-sm">Tambah Soal</button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // dynamic questions
  const qTemplate = document.getElementById('question_template');
  const qContainer = document.getElementById('questions_container');
  function addQuestion(data = null) {
    const node = qTemplate.content.cloneNode(true);
    if (data) {
      // populate
      const block = node.querySelector('.question-block');
      block.querySelector('textarea[name="questions[][pertanyaan]"]').value = data.pertanyaan || '';
      block.querySelector('select.q-type').value = data.type || 'mcq';
      if (data.type === 'essay') { block.querySelector('.mcq-area').style.display='none'; block.querySelector('.essay-area').style.display='block'; }
      const choices = block.querySelectorAll('input[name="questions[][choices][]"]');
      (data.choices||[]).forEach((c,i)=>{ if(choices[i]) choices[i].value = c; });
      block.querySelector('input[name="questions[][jawaban_benar]"]').value = data.jawaban_benar ?? '';
    }
    qContainer.appendChild(node);
    attachRemoveHandlers();
    attachTypeHandlers();
  }
  function attachRemoveHandlers(){
    document.querySelectorAll('.btn-remove-question').forEach(btn=>{
      btn.onclick = function(){
        const el = this.closest('.question-block');
        window.confirmDelete('Hapus soal ini?', function(){ el.remove(); });
      };
    });
  }
  function attachTypeHandlers(){
    document.querySelectorAll('.q-type').forEach(sel=>{
      sel.onchange = function(){
        const block = this.closest('.question-block');
        if (this.value === 'mcq') { block.querySelector('.mcq-area').style.display='block'; block.querySelector('.essay-area').style.display='none'; }
        else { block.querySelector('.mcq-area').style.display='none'; block.querySelector('.essay-area').style.display='block'; }
      };
    });
  }
  document.getElementById('btnAddQuestion').addEventListener('click', ()=> addQuestion());
  // initialize with one
  addQuestion();
</script>
@endpush
