@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Permintaan Menjadi Guru</h3>
  <p>Isi form berikut untuk meminta hak akses guru. Admin akan meninjau permintaan ini.</p>
  <form method="POST" action="{{ route('user.become_guru.submit') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Alasan singkat</label>
      <textarea name="reason" class="form-control" rows="4" required></textarea>
    </div>
    <button class="btn btn-primary">Kirim Permintaan</button>
  </form>
</div>
@endsection
