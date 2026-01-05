@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card card-modern p-4">
      <h4 class="mb-3">Masuk</h4>
      <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input name="password" type="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <a href="{{ route('register') }}">Daftar akun</a>
          <button class="btn btn-primary">Masuk</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
