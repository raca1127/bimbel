@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="card card-modern p-4">
      <h4 class="mb-3">Daftar Akun</h4>
      <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" required>
        </div>
        
        <p class="text-muted">Pendaftaran default sebagai <strong>Pelajar</strong>. Jika ingin menjadi guru, setelah mendaftar buka halaman "Minta Menjadi Guru".</p>
      <p class="text-muted">Pendaftaran default sebagai <strong>Pelajar</strong>. Jika ingin menjadi guru, setelah mendaftar buka halaman "Minta Menjadi Guru".</p>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input name="password" type="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Konfirmasi Password</label>
          <input name="password_confirmation" type="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary">Daftar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
