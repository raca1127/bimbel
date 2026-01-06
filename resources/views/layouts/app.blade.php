<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Belajar Online</title>
  <!-- Font Awesome Free CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8fafc; }
    .card-modern { border: 0; border-radius: 12px; box-shadow: 0 6px 18px rgba(24,39,75,0.08); }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="/">BelajarOnline</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMain">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="{{ route('public.materi') }}">Materi</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('student.leaderboard') }}">Leaderboard</a></li>
        </ul>

        <div class="d-flex gap-2">
          @auth
            <!-- Admin -->
            @if(auth()->user()->role === 'admin')
              <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.index') }}">Admin Panel</a>
            @endif

            <!-- Guru -->
            @if(auth()->user()->role === 'guru')
              <a class="btn btn-outline-secondary btn-sm" href="{{ route('teacher.materi.index') }}">Dashboard Guru</a>
              <a class="btn btn-outline-info btn-sm" href="{{ route('student.dashboard') }}">Dashboard Pelajar</a>
            @elseif(auth()->user()->role === 'pelajar')
              <a class="btn btn-outline-secondary btn-sm" href="{{ route('student.index') }}">Dashboard</a>
              <a class="btn btn-sm btn-outline-info" href="{{ route('user.become_guru') }}">Minta Jadi Guru</a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-danger">Logout</button>
            </form>
          @else
            <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">Masuk</a>
            <a class="btn btn-sm btn-primary" href="{{ route('register') }}">Daftar</a>
          @endauth
        </div>
      </div>
    </div>
  </nav>

  <!-- Main content -->
  <main class="container py-4">
    @yield('content')
  </main>

  <!-- Toast container -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    @if(session('success'))
      <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">{{ session('success') }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    @endif

    @if(session('error'))
      <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">{{ session('error') }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    @endif

    @if ($errors->any())
      <div class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <strong>Terdapat kesalahan:</strong>
            <ul class="mb-0">
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    @endif
  </div>

  <!-- Global confirm-delete modal -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Apakah Anda yakin ingin menghapus item ini?</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Hapus</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // CKEditor initialization
      document.querySelectorAll('textarea[name=konten]').forEach(function(tx, idx){
        if(!tx.id) tx.id = 'konten_'+idx;
        ClassicEditor.create(tx).catch(()=>{});
      });

      // Confirm delete modal
      var confirmModalEl = document.getElementById('confirmDeleteModal');
      var confirmModal = new bootstrap.Modal(confirmModalEl);
      var currentForm = null;
      var currentCallback = null;

      document.querySelectorAll('form.confirm-delete').forEach(function(f){
        f.addEventListener('submit', function(e){
          e.preventDefault();
          currentForm = this;
          currentCallback = null;
          var msg = this.dataset.message || 'Anda akan menghapus item ini.';
          confirmModalEl.querySelector('.modal-body').textContent = msg;
          confirmModal.show();
        });
      });

      window.confirmDelete = function(message, callback){
        currentForm = null;
        currentCallback = callback || null;
        confirmModalEl.querySelector('.modal-body').textContent = message || 'Anda yakin?';
        confirmModal.show();
      };

      document.getElementById('confirmDeleteBtn').addEventListener('click', function(){
        if(currentForm) { currentForm.submit(); currentForm = null; confirmModal.hide(); }
        else if(currentCallback) { try { currentCallback(); } catch(e){} currentCallback = null; confirmModal.hide(); }
        else { confirmModal.hide(); }
      });

      // Show toasts
      ['toast-success','toast-error','toast-errors'].forEach(function(id){
        var el = document.getElementById(id);
        if(el) new bootstrap.Toast(el, {delay: 5000 + (id==='toast-error'?2000:0) + (id==='toast-errors'?4000:0)}).show();
      });
    });
  </script>
  @stack('scripts')
</body>
</html>
