@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Badili Nenosiri (Change Password)</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Sasisha Nenosiri Lako La Kuingilia Kwenye Mfumo</p>
    </div>
</div>

<div class="card" style="max-width:550px;">
    <form action="{{ route('password.change') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Nenosiri La Sasa (Current Password)</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Nenosiri Mpya (New Password)</label>
            <input type="password" name="new_password" class="form-control" minlength="6" required>
        </div>

        <div class="form-group">
            <label class="form-label">Thibitisha Nenosiri Mpya (Confirm New Password)</label>
            <input type="password" name="new_password_confirmation" class="form-control" minlength="6" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:1rem;">
            <i class="fa-solid fa-key"></i> Badili Nenosiri
        </button>
    </form>
</div>
@endsection
