@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Admin Panel</h3>

  <h5 class="mt-4">Pending Guru Requests</h5>
  @foreach($pending as $p)
    <div class="card mb-2 p-3">
      <div class="d-flex justify-content-between">
        <div>
          <strong>{{ $p->name }}</strong> — {{ $p->email }}
          <div class="small text-muted">Status: {{ $p->guru_status }}</div>
        </div>
        <div class="d-flex gap-2">
          <form method="POST" action="{{ route('admin.approve_guru', $p->id) }}">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
          <form method="POST" action="{{ route('admin.reject_guru', $p->id) }}">@csrf<button class="btn btn-danger btn-sm">Reject</button></form>
        </div>
      </div>
    </div>
  @endforeach

  <h5 class="mt-4">All Users</h5>
  @foreach($users as $u)
    <div class="card mb-2 p-2">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <strong>{{ $u->name }}</strong> — {{ $u->email }}
          <div class="small text-muted">Role: {{ $u->role }} @if($u->guru_status) • GuruStatus: {{ $u->guru_status }} @endif</div>
        </div>
        <div class="d-flex gap-2">
          @if(!$u->is_blocked)
            <form method="POST" action="{{ route('admin.block_user', $u->id) }}">@csrf<button class="btn btn-warning btn-sm">Block</button></form>
          @else
            <form method="POST" action="{{ route('admin.unblock_user', $u->id) }}">@csrf<button class="btn btn-secondary btn-sm">Unblock</button></form>
          @endif
        </div>
      </div>
    </div>
  @endforeach

  <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
