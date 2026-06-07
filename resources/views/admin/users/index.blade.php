@extends('admin.layout')
@section('title','Users')

@section('admin')
<div class="adm-h">
  <h1>Users</h1>
  <form method="GET"><input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / email" style="width:240px"></form>
</div>

<table class="t">
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Trips</th><th>Joined</th><th>Actions</th></tr>
  @forelse($users as $u)
    <tr>
      <td>{{ $u->name }}</td>
      <td>{{ $u->email }}</td>
      <td>
        <form class="inline-form" method="POST" action="{{ route('admin.users.role',$u) }}">@csrf @method('PATCH')
          <select name="role" onchange="this.form.submit()">
            <option value="user" {{ $u->role==='user'?'selected':'' }}>User</option>
            <option value="admin" {{ $u->role==='admin'?'selected':'' }}>Admin</option>
          </select>
        </form>
      </td>
      <td>{{ $u->trips_count }}</td>
      <td>{{ $u->created_at->format('d M Y') }}</td>
      <td>
        @if($u->id !== auth()->id())
        <form class="inline-form" method="POST" action="{{ route('admin.users.destroy',$u) }}" onsubmit="return confirm('Delete {{ $u->name }}?')">@csrf @method('DELETE')
          <button style="border:none;background:none;color:var(--accent);cursor:pointer;font-size:13.5px">Delete</button>
        </form>
        @else<span class="badge mut">You</span>@endif
      </td>
    </tr>
  @empty
    <tr><td colspan="6" class="muted">No users found.</td></tr>
  @endforelse
</table>

@if($users->hasPages())
<div class="pager" style="display:flex;gap:8px;align-items:center">
  @if($users->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5">← Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $users->previousPageUrl() }}">← Prev</a>@endif
  <span class="muted" style="font-size:13px">Page {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
  @if($users->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $users->nextPageUrl() }}">Next →</a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next →</span>@endif
</div>
@endif
@endsection
