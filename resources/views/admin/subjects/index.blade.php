@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usimamizi Wa Masomo (Subjects Catalog)</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Orodha Ya Masomo Yanayofundishwa</p>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 2fr;">
    <div class="card">
        <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-plus"></i> Ongeza Somo Mpya</h2>
        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Jina La Somo</label>
                <input type="text" name="subject_name" class="form-control" placeholder="Mfano: Kiswahili, Mathematics" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-floppy-disk"></i> Hifadhi Somo
            </button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-book"></i> Masomo Yaliyosajiliwa</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jina La Somo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $idx => $sub)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $sub->subject_name }}</strong></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align:center; color:var(--text-muted);">Hakuna masomo yaliyosajiliwa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
