@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usimamizi Wa Shule Zilizosajiliwa</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Orodha Ya Shule Kwenye Mfumo</p>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 2fr;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-plus"></i> Ongeza Shule Mpya</h2>
        <form action="{{ route('admin.schools.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Jina La Shule</label>
                <input type="text" name="school_name" class="form-control" placeholder="Mfano: St. Joseph Secondary" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-floppy-disk"></i> Hifadhi Shule
            </button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-school"></i> Orodha Ya Shule</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jina La Shule</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $idx => $s)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $s->school_name }}</strong></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align:center; color:var(--text-muted);">Hakuna shule zilizosajiliwa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
