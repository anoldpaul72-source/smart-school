@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usimamizi Wa Watumiaji Wa Mfumo</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Orodha Ya Watumiaji, Roles Na Shule Zao</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Sajili Mtumiaji Mpya</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex; gap:1rem; margin-bottom:1.25rem;">
        <select name="role" class="form-control" style="max-width:200px;">
            <option value="">-- Role Zote --</option>
            <option value="Admin" {{ request('role') == 'Admin' ? 'selected' : '' }}>Admin</option>
            <option value="Leader" {{ request('role') == 'Leader' ? 'selected' : '' }}>Leader</option>
            <option value="Teacher" {{ request('role') == 'Teacher' ? 'selected' : '' }}>Teacher</option>
            <option value="Parent" {{ request('role') == 'Parent' ? 'selected' : '' }}>Parent</option>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Shule</th>
                    <th>Kitendo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td><strong>{{ $u->username }}</strong></td>
                    <td>{{ $u->email ?? '-' }}</td>
                    <td><span class="role-badge">{{ $u->role }}</span></td>
                    <td>{{ $u->school_name ?? '-' }}</td>
                    <td style="display:flex; gap:0.4rem;">
                        <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.8rem;">
                            <i class="fa-solid fa-pen-to-square"></i> Hariri
                        </a>
                        <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Una uhakika unataka kumfuta mtumiaji huyu?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-logout" style="padding:0.35rem 0.6rem; font-size:0.8rem;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:var(--text-muted);">Hakuna watumiaji waliopatikana.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.25rem;">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection
