@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Hariri Mtumiaji</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Badili Taarifa Za Akaunti Ya {{ $user->username }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Orodha</a>
</div>

<div class="card" style="max-width:650px;">
    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Nenosiri Mpya (Acha wazi kama hutaki kubadili)</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    <option value="Admin" {{ $user->role == 'Admin' ? 'selected' : '' }}>Admin</option>
                    <option value="Leader" {{ $user->role == 'Leader' ? 'selected' : '' }}>Leader</option>
                    <option value="Teacher" {{ $user->role == 'Teacher' ? 'selected' : '' }}>Teacher</option>
                    <option value="Parent" {{ $user->role == 'Parent' ? 'selected' : '' }}>Parent</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Shule</label>
                <select name="school_name" class="form-control">
                    <option value="">-- Hakuna / Zote --</option>
                    @foreach($schools as $s)
                    <option value="{{ $s->school_name }}" {{ $user->school_name == $s->school_name ? 'selected' : '' }}>{{ $s->school_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:1rem;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Mabadiliko
        </button>
    </form>
</div>
@endsection
