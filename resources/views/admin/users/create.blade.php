@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Sajili Mtumiaji Mpya</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Tengeneza Akaunti Ya Admin, Leader, Teacher Au Parent</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Orodha</a>
</div>

<div class="card" style="max-width:650px;">
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Jina La Mtumiaji (Username)</label>
            <input type="text" name="username" class="form-control" placeholder="Weka username" required>
        </div>

        <div class="form-group">
            <label class="form-label">Barua Pepe (Email - Optional)</label>
            <input type="email" name="email" class="form-control" placeholder="user@example.com">
        </div>

        <div class="form-group">
            <label class="form-label">Nenosiri (Password)</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="6" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Nafasi (Role)</label>
                <select name="role" class="form-control" required>
                    <option value="Admin">Admin (Msimamizi Mkuu)</option>
                    <option value="Leader">Leader (Kiongozi)</option>
                    <option value="Teacher">Teacher (Mwalimu)</option>
                    <option value="Parent">Parent (Mzazi)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Shule</label>
                <select name="school_name" class="form-control">
                    <option value="">-- Hakuna / Zote --</option>
                    @foreach($schools as $s)
                    <option value="{{ $s->school_name }}">{{ $s->school_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:1rem;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Mtumiaji
        </button>
    </form>
</div>
@endsection
